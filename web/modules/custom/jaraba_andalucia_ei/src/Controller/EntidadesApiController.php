<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_andalucia_ei\Service\IncentiveReceiptService;
use Drupal\jaraba_andalucia_ei\Service\IncentiveWaiverService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * API controller para CRUD frontend de entidades Andalucia +ei.
 *
 * Endpoints JSON para actuaciones STO, indicadores FSE+,
 * inserciones laborales y prospecciones empresariales.
 *
 * Seguridad:
 * - CSRF-API-001: Rutas con _csrf_request_header_token: 'TRUE'
 * - TENANT-001: Todas las queries filtran por tenant
 * - API-WHITELIST-001: ALLOWED_FIELDS por entidad
 * - ACCESS-STRICT-001: Comparaciones con (int)===(int)
 */
class EntidadesApiController extends ControllerBase {

  /**
   * Campos permitidos para actuaciones STO (API-WHITELIST-001).
   */
  private const ACTUACION_FIELDS = [
    'id', 'participante_id', 'tipo_actuacion', 'fecha', 'hora_inicio',
    'hora_fin', 'duracion_minutos', 'contenido', 'resultado', 'lugar',
    'orientador_id', 'fase_participante', 'firmado_participante',
    'firmado_orientador', 'vobo_sae_status', 'sto_exportado',
    'created', 'changed',
  ];

  /**
   * Campos permitidos para indicadores FSE+ (API-WHITELIST-001).
   */
  private const INDICADOR_FSE_FIELDS = [
    'id', 'participante_id', 'momento_recogida', 'fecha_recogida',
    'situacion_laboral', 'nivel_educativo_isced', 'discapacidad',
    'discapacidad_tipo', 'discapacidad_grado', 'pais_origen',
    'nacionalidad', 'hogar_unipersonal', 'hijos_a_cargo',
    'zona_residencia', 'situacion_sin_hogar', 'comunidad_marginada',
    'situacion_laboral_resultado', 'tipo_contrato_resultado',
    'cualificacion_obtenida', 'tipo_cualificacion', 'mejora_situacion',
    'inclusion_social', 'completado', 'notas', 'created', 'changed',
  ];

  /**
   * Campos permitidos para inserciones laborales (API-WHITELIST-001).
   */
  private const INSERCION_FIELDS = [
    'id', 'participante_id', 'tipo_insercion', 'fecha_alta', 'verificado',
    'empresa_nombre', 'empresa_cif', 'tipo_contrato', 'jornada',
    'horas_semanales', 'sector_actividad', 'fecha_alta_reta',
    'cnae_actividad', 'sector_emprendimiento', 'modelo_fiscal',
    'empresa_agraria', 'tipo_cultivo', 'fecha_inicio_campana',
    'fecha_fin_campana', 'notas', 'created', 'changed',
  ];

  /**
   * Campos permitidos para prospecciones empresariales (API-WHITELIST-001).
   */
  private const PROSPECCION_FIELDS = [
    'id', 'empresa_nombre', 'cif', 'sector', 'tamano_empresa',
    'provincia', 'contacto_nombre', 'contacto_cargo', 'contacto_email',
    'contacto_telefono', 'estado', 'tipo_colaboracion',
    'puestos_disponibles', 'perfiles_demandados',
    'fecha_primer_contacto', 'fecha_ultimo_seguimiento', 'notas',
    'participantes_derivados', 'participantes_insertados',
    'status', 'created', 'changed',
  ];

  /**
   * Tipos de actuacion validos (API-WHITELIST-001).
   */
  private const ALLOWED_TIPOS_ACTUACION = [
    'orientacion_individual', 'orientacion_grupal', 'formacion',
    'tutoria', 'prospeccion', 'intermediacion',
  ];

  /**
   * Momentos de recogida validos (API-WHITELIST-001).
   */
  private const ALLOWED_MOMENTOS = ['entrada', 'salida', 'seguimiento_6m'];

  /**
   * Tipos de insercion validos (API-WHITELIST-001).
   */
  private const ALLOWED_TIPOS_INSERCION = ['cuenta_ajena', 'cuenta_propia', 'agrario'];

  /**
   * Estados de prospeccion validos (API-WHITELIST-001).
   */
  private const ALLOWED_ESTADOS_PROSPECCION = [
    'lead', 'contactado', 'interesado', 'colaborador', 'descartado',
  ];

  /**
   * Constructor.
   *
   * CONTROLLER-READONLY-001: $entityTypeManager assigned in body.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null $tenantContext
   *   Servicio de contexto de tenant (opcional cross-modulo).
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected readonly ?TenantContextService $tenantContext,
    protected readonly LoggerInterface $logger,
    protected readonly ?IncentiveReceiptService $incentiveReceiptService = NULL,
    protected readonly ?IncentiveWaiverService $incentiveWaiverService = NULL,
  ) {
    // CONTROLLER-READONLY-001: ControllerBase::$entityTypeManager no tiene
    // declaracion de tipo. Asignar manualmente en body.
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ecosistema_jaraba_core.tenant_context', ContainerInterface::NULL_ON_INVALID_REFERENCE),
      $container->get('logger.channel.jaraba_andalucia_ei'),
      $container->has('jaraba_andalucia_ei.incentive_receipt')
        ? $container->get('jaraba_andalucia_ei.incentive_receipt') : NULL,
      $container->has('jaraba_andalucia_ei.incentive_waiver')
        ? $container->get('jaraba_andalucia_ei.incentive_waiver') : NULL,
    );
  }

  // =========================================================================
  // ACTUACIONES STO
  // =========================================================================

  /**
   * Lista actuaciones STO filtradas por tenant.
   *
   * GET /api/v1/andalucia-ei/actuaciones
   * Query params: ?participante_id, ?tipo_actuacion, ?page, ?limit
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con lista paginada de actuaciones.
   */
  public function listActuaciones(Request $request): JsonResponse {
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = ($page - 1) * $limit;
    $participanteId = $request->query->get('participante_id');
    $tipoActuacion = $request->query->get('tipo_actuacion');

    // API-WHITELIST-001: validar tipo_actuacion.
    if ($tipoActuacion !== NULL && !in_array($tipoActuacion, self::ALLOWED_TIPOS_ACTUACION, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INVALID_PARAM', 'message' => 'Tipo de actuacion no valido.'],
      ], 400);
    }

    $tenantGroupId = $this->resolveTenantGroupId();
    $storage = $this->entityTypeManager->getStorage('actuacion_sto');

    // Query con filtros.
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('fecha', 'DESC')
      ->sort('created', 'DESC')
      ->range($offset, $limit);

    $this->addTenantCondition($query, $tenantGroupId);

    if ($participanteId !== NULL) {
      $query->condition('participante_id', (int) $participanteId);
    }
    if ($tipoActuacion !== NULL) {
      $query->condition('tipo_actuacion', $tipoActuacion);
    }

    $ids = $query->execute();

    // Count query con mismos filtros.
    $countQuery = $storage->getQuery()
      ->accessCheck(TRUE)
      ->count();

    $this->addTenantCondition($countQuery, $tenantGroupId);

    if ($participanteId !== NULL) {
      $countQuery->condition('participante_id', (int) $participanteId);
    }
    if ($tipoActuacion !== NULL) {
      $countQuery->condition('tipo_actuacion', $tipoActuacion);
    }

    $total = (int) $countQuery->execute();

    $items = [];
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $entity) {
        $items[] = $this->buildEntityResponse($entity, self::ACTUACION_FIELDS);
      }
    }

    return new JsonResponse([
      'items' => $items,
      'total' => $total,
      'page' => $page,
      'limit' => $limit,
    ]);
  }

  /**
   * Obtiene detalle de una actuacion STO.
   *
   * GET /api/v1/andalucia-ei/actuaciones/{id}
   *
   * @param int $id
   *   El ID de la actuacion.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con datos de la actuacion o 404.
   */
  public function getActuacion(int $id): JsonResponse {
    $entity = $this->entityTypeManager->getStorage('actuacion_sto')->load($id);

    if (!$entity) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Actuacion no encontrada.'],
      ], 404);
    }

    // TENANT-001: verificar tenant match.
    $tenantGroupId = $this->resolveTenantGroupId();
    if ($tenantGroupId !== NULL) {
      $entityTenantId = $entity->get('tenant_id')->target_id;
      if ($entityTenantId !== NULL && (int) $entityTenantId !== $tenantGroupId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'NOT_FOUND', 'message' => 'Actuacion no encontrada.'],
        ], 404);
      }
    }

    return new JsonResponse($this->buildEntityResponse($entity, self::ACTUACION_FIELDS));
  }

  // =========================================================================
  // INDICADORES FSE+
  // =========================================================================

  /**
   * Lista indicadores FSE+ filtrados por tenant.
   *
   * GET /api/v1/andalucia-ei/indicadores-fse
   * Query params: ?participante_id, ?momento
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con lista paginada de indicadores.
   */
  public function listIndicadoresFse(Request $request): JsonResponse {
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = ($page - 1) * $limit;
    $participanteId = $request->query->get('participante_id');
    $momento = $request->query->get('momento');

    // API-WHITELIST-001: validar momento.
    if ($momento !== NULL && !in_array($momento, self::ALLOWED_MOMENTOS, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INVALID_PARAM', 'message' => 'Momento de recogida no valido.'],
      ], 400);
    }

    $tenantGroupId = $this->resolveTenantGroupId();
    $storage = $this->entityTypeManager->getStorage('indicador_fse_plus');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('fecha_recogida', 'DESC')
      ->sort('created', 'DESC')
      ->range($offset, $limit);

    $this->addTenantCondition($query, $tenantGroupId);

    if ($participanteId !== NULL) {
      $query->condition('participante_id', (int) $participanteId);
    }
    if ($momento !== NULL) {
      $query->condition('momento_recogida', $momento);
    }

    $ids = $query->execute();

    $countQuery = $storage->getQuery()
      ->accessCheck(TRUE)
      ->count();

    $this->addTenantCondition($countQuery, $tenantGroupId);

    if ($participanteId !== NULL) {
      $countQuery->condition('participante_id', (int) $participanteId);
    }
    if ($momento !== NULL) {
      $countQuery->condition('momento_recogida', $momento);
    }

    $total = (int) $countQuery->execute();

    $items = [];
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $entity) {
        $items[] = $this->buildEntityResponse($entity, self::INDICADOR_FSE_FIELDS);
      }
    }

    return new JsonResponse([
      'items' => $items,
      'total' => $total,
      'page' => $page,
      'limit' => $limit,
    ]);
  }

  /**
   * Obtiene detalle de un indicador FSE+.
   *
   * GET /api/v1/andalucia-ei/indicadores-fse/{id}
   *
   * @param int $id
   *   El ID del indicador.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con datos del indicador o 404.
   */
  public function getIndicadorFse(int $id): JsonResponse {
    $entity = $this->entityTypeManager->getStorage('indicador_fse_plus')->load($id);

    if (!$entity) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Indicador FSE+ no encontrado.'],
      ], 404);
    }

    // TENANT-001: verificar tenant match.
    $tenantGroupId = $this->resolveTenantGroupId();
    if ($tenantGroupId !== NULL) {
      $entityTenantId = $entity->get('tenant_id')->target_id;
      if ($entityTenantId !== NULL && (int) $entityTenantId !== $tenantGroupId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'NOT_FOUND', 'message' => 'Indicador FSE+ no encontrado.'],
        ], 404);
      }
    }

    return new JsonResponse($this->buildEntityResponse($entity, self::INDICADOR_FSE_FIELDS));
  }

  // =========================================================================
  // INSERCIONES LABORALES
  // =========================================================================

  /**
   * Lista inserciones laborales filtradas por tenant.
   *
   * GET /api/v1/andalucia-ei/inserciones
   * Query params: ?participante_id, ?tipo, ?page, ?limit
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con lista paginada de inserciones.
   */
  public function listInserciones(Request $request): JsonResponse {
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = ($page - 1) * $limit;
    $participanteId = $request->query->get('participante_id');
    $tipo = $request->query->get('tipo');

    // API-WHITELIST-001: validar tipo.
    if ($tipo !== NULL && !in_array($tipo, self::ALLOWED_TIPOS_INSERCION, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INVALID_PARAM', 'message' => 'Tipo de insercion no valido.'],
      ], 400);
    }

    $tenantGroupId = $this->resolveTenantGroupId();
    $storage = $this->entityTypeManager->getStorage('insercion_laboral');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('fecha_alta', 'DESC')
      ->sort('created', 'DESC')
      ->range($offset, $limit);

    $this->addTenantCondition($query, $tenantGroupId);

    if ($participanteId !== NULL) {
      $query->condition('participante_id', (int) $participanteId);
    }
    if ($tipo !== NULL) {
      $query->condition('tipo_insercion', $tipo);
    }

    $ids = $query->execute();

    $countQuery = $storage->getQuery()
      ->accessCheck(TRUE)
      ->count();

    $this->addTenantCondition($countQuery, $tenantGroupId);

    if ($participanteId !== NULL) {
      $countQuery->condition('participante_id', (int) $participanteId);
    }
    if ($tipo !== NULL) {
      $countQuery->condition('tipo_insercion', $tipo);
    }

    $total = (int) $countQuery->execute();

    $items = [];
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $entity) {
        $items[] = $this->buildEntityResponse($entity, self::INSERCION_FIELDS);
      }
    }

    return new JsonResponse([
      'items' => $items,
      'total' => $total,
      'page' => $page,
      'limit' => $limit,
    ]);
  }

  /**
   * Obtiene detalle de una insercion laboral.
   *
   * GET /api/v1/andalucia-ei/inserciones/{id}
   *
   * @param int $id
   *   El ID de la insercion.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con datos de la insercion o 404.
   */
  public function getInsercion(int $id): JsonResponse {
    $entity = $this->entityTypeManager->getStorage('insercion_laboral')->load($id);

    if (!$entity) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Insercion laboral no encontrada.'],
      ], 404);
    }

    // TENANT-001: verificar tenant match.
    $tenantGroupId = $this->resolveTenantGroupId();
    if ($tenantGroupId !== NULL) {
      $entityTenantId = $entity->get('tenant_id')->target_id;
      if ($entityTenantId !== NULL && (int) $entityTenantId !== $tenantGroupId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'NOT_FOUND', 'message' => 'Insercion laboral no encontrada.'],
        ], 404);
      }
    }

    return new JsonResponse($this->buildEntityResponse($entity, self::INSERCION_FIELDS));
  }

  // =========================================================================
  // PROSPECCIONES EMPRESARIALES
  // =========================================================================

  /**
   * Lista prospecciones empresariales filtradas por tenant.
   *
   * GET /api/v1/andalucia-ei/prospecciones
   * Query params: ?estado, ?page, ?limit
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con lista paginada de prospecciones.
   */
  public function listProspecciones(Request $request): JsonResponse {
    $page = max(1, (int) $request->query->get('page', 1));
    $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
    $offset = ($page - 1) * $limit;
    $estado = $request->query->get('estado');

    // API-WHITELIST-001: validar estado.
    if ($estado !== NULL && !in_array($estado, self::ALLOWED_ESTADOS_PROSPECCION, TRUE)) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'INVALID_PARAM', 'message' => 'Estado de prospeccion no valido.'],
      ], 400);
    }

    $tenantGroupId = $this->resolveTenantGroupId();
    $storage = $this->entityTypeManager->getStorage('prospeccion_empresarial');

    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->sort('changed', 'DESC')
      ->range($offset, $limit);

    $this->addTenantCondition($query, $tenantGroupId);

    if ($estado !== NULL) {
      $query->condition('estado', $estado);
    }

    $ids = $query->execute();

    $countQuery = $storage->getQuery()
      ->accessCheck(TRUE)
      ->count();

    $this->addTenantCondition($countQuery, $tenantGroupId);

    if ($estado !== NULL) {
      $countQuery->condition('estado', $estado);
    }

    $total = (int) $countQuery->execute();

    $items = [];
    if (!empty($ids)) {
      foreach ($storage->loadMultiple($ids) as $entity) {
        $items[] = $this->buildEntityResponse($entity, self::PROSPECCION_FIELDS);
      }
    }

    return new JsonResponse([
      'items' => $items,
      'total' => $total,
      'page' => $page,
      'limit' => $limit,
    ]);
  }

  /**
   * Obtiene detalle de una prospeccion empresarial.
   *
   * GET /api/v1/andalucia-ei/prospecciones/{id}
   *
   * @param int $id
   *   El ID de la prospeccion.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con datos de la prospeccion o 404.
   */
  public function getProspeccion(int $id): JsonResponse {
    $entity = $this->entityTypeManager->getStorage('prospeccion_empresarial')->load($id);

    if (!$entity) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => ['code' => 'NOT_FOUND', 'message' => 'Prospeccion empresarial no encontrada.'],
      ], 404);
    }

    // TENANT-001: verificar tenant match.
    $tenantGroupId = $this->resolveTenantGroupId();
    if ($tenantGroupId !== NULL) {
      $entityTenantId = $entity->get('tenant_id')->target_id;
      if ($entityTenantId !== NULL && (int) $entityTenantId !== $tenantGroupId) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => ['code' => 'NOT_FOUND', 'message' => 'Prospeccion empresarial no encontrada.'],
        ], 404);
      }
    }

    return new JsonResponse($this->buildEntityResponse($entity, self::PROSPECCION_FIELDS));
  }

  // =========================================================================
  // HELPERS PRIVADOS
  // =========================================================================

  /**
   * Resuelve el Group ID del tenant actual.
   *
   * TENANT-001: Usa TenantContextService para resolver el tenant.
   *
   * @return int|null
   *   El ID del grupo tenant, o NULL si no hay contexto.
   */
  private function resolveTenantGroupId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }
    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Anade condicion de tenant a una entity query.
   *
   * TENANT-001: Toda query DEBE filtrar por tenant.
   *
   * @param mixed $query
   *   La entity query.
   * @param int|null $tenantId
   *   El ID del tenant group.
   */
  private function addTenantCondition(mixed $query, ?int $tenantId): void {
    if ($tenantId !== NULL) {
      $query->condition('tenant_id', $tenantId);
    }
  }

  /**
   * Extrae campos permitidos de una entidad para la respuesta JSON.
   *
   * API-WHITELIST-001: Solo devuelve campos definidos en ALLOWED_FIELDS.
   *
   * @param object $entity
   *   La entidad a serializar.
   * @param array $fields
   *   Lista de nombres de campo permitidos.
   *
   * @return array
   *   Array asociativo con los valores de los campos permitidos.
   */
  private function buildEntityResponse(object $entity, array $fields): array {
    $data = [];

    foreach ($fields as $fieldName) {
      if (!$entity->hasField($fieldName)) {
        continue;
      }

      $field = $entity->get($fieldName);
      $fieldDefinition = $field->getFieldDefinition();
      $fieldType = $fieldDefinition->getType();

      // Extraer valor segun tipo de campo.
      switch ($fieldType) {
        case 'entity_reference':
          $data[$fieldName] = $field->target_id !== NULL ? (int) $field->target_id : NULL;
          break;

        case 'boolean':
          $data[$fieldName] = (bool) ($field->value ?? FALSE);
          break;

        case 'integer':
        case 'created':
        case 'changed':
          $data[$fieldName] = $field->value !== NULL ? (int) $field->value : NULL;
          break;

        case 'float':
        case 'decimal':
          $data[$fieldName] = $field->value !== NULL ? (float) $field->value : NULL;
          break;

        case 'text_long':
          $data[$fieldName] = $field->value ?? '';
          break;

        default:
          // string, list_string, datetime, email, etc.
          $data[$fieldName] = $field->value ?? '';
          break;
      }
    }

    // Siempre incluir id.
    if (!isset($data['id'])) {
      $data['id'] = (int) $entity->id();
    }

    return $data;
  }

}
