<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing the portfolio of 29 deliverables per participant.
 *
 * Seeds, tracks progress, and manages validation workflow for
 * EntregableFormativoEi entities in the PIIL program.
 *
 * TENANT-001: All queries filter by participant (already tenant-scoped).
 */
class PortfolioEntregablesService {

  /**
   * The 29 canonical deliverables of the PIIL program.
   *
   * Each entry: ['titulo' => string, 'sesion' => string, 'modulo' => string].
   */
  public const ENTREGABLES = [
    1 => ['titulo' => 'Perfil profesional + evaluación digital', 'sesion' => 'OI-1.1', 'modulo' => 'orientacion'],
    2 => ['titulo' => 'Fichas autoconocimiento 1-8', 'sesion' => 'OI-1.2', 'modulo' => 'orientacion'],
    3 => ['titulo' => 'Ruta personalizada + 3 objetivos SMART', 'sesion' => 'OI-2.2', 'modulo' => 'orientacion'],
    4 => ['titulo' => '10 interacciones IA documentadas', 'sesion' => 'M0-1', 'modulo' => 'modulo_0'],
    5 => ['titulo' => 'Dashboard personalizado + web básica', 'sesion' => 'M0-2', 'modulo' => 'modulo_0'],
    6 => ['titulo' => '3 tareas productivas del pack', 'sesion' => 'M0-3', 'modulo' => 'modulo_0'],
    7 => ['titulo' => 'Propuesta de valor (3 versiones)', 'sesion' => 'M1-1', 'modulo' => 'modulo_1'],
    8 => ['titulo' => 'Lean Canvas v2 validado', 'sesion' => 'M1-3', 'modulo' => 'modulo_1'],
    9 => ['titulo' => 'Portfolio servicios con fichas y precios', 'sesion' => 'M2-1', 'modulo' => 'modulo_2'],
    10 => ['titulo' => 'Punto de equilibrio calculado', 'sesion' => 'M2-2', 'modulo' => 'modulo_2'],
    11 => ['titulo' => 'Previsión financiera 12 meses', 'sesion' => 'M2-2', 'modulo' => 'modulo_2'],
    12 => ['titulo' => 'Mapa de ayudas + línea temporal', 'sesion' => 'M2-3', 'modulo' => 'modulo_2'],
    13 => ['titulo' => 'Plan Financiero Básico consolidado', 'sesion' => 'M2-4', 'modulo' => 'modulo_2'],
    14 => ['titulo' => 'Secuencia de alta + IAE/CNAE', 'sesion' => 'M3-1', 'modulo' => 'modulo_3'],
    15 => ['titulo' => 'Factura modelo del pack', 'sesion' => 'M3-2', 'modulo' => 'modulo_3'],
    16 => ['titulo' => 'Calendario fiscal personalizado', 'sesion' => 'M3-2', 'modulo' => 'modulo_3'],
    17 => ['titulo' => 'Solicitudes L1 y L2 simuladas', 'sesion' => 'M3-3', 'modulo' => 'modulo_3'],
    18 => ['titulo' => 'Web profesional publicada', 'sesion' => 'M4-1', 'modulo' => 'modulo_4'],
    19 => ['titulo' => 'Perfil red social configurado', 'sesion' => 'M4-1', 'modulo' => 'modulo_4'],
    20 => ['titulo' => '5 piezas de contenido publicadas', 'sesion' => 'M4-2', 'modulo' => 'modulo_4'],
    21 => ['titulo' => 'Calendario editorial 4 semanas', 'sesion' => 'M4-4', 'modulo' => 'modulo_4'],
    22 => ['titulo' => 'Embudo de captación diseñado', 'sesion' => 'M4-3', 'modulo' => 'modulo_4'],
    23 => ['titulo' => 'CRM con 5+ contactos reales', 'sesion' => 'M4-3', 'modulo' => 'modulo_4'],
    24 => ['titulo' => 'Packs publicados en catálogo digital', 'sesion' => 'M5-1', 'modulo' => 'modulo_5'],
    25 => ['titulo' => 'Programa semanal de trabajo', 'sesion' => 'M5-1', 'modulo' => 'modulo_5'],
    26 => ['titulo' => 'Proyecto piloto documentado', 'sesion' => 'M5-2', 'modulo' => 'modulo_5'],
    27 => ['titulo' => 'Pitch de venta ensayado (3 versiones)', 'sesion' => 'M5-3', 'modulo' => 'modulo_5'],
    28 => ['titulo' => 'CV actualizado o CMI básico', 'sesion' => 'M5-3', 'modulo' => 'modulo_5'],
    29 => ['titulo' => 'Plan de 30 días post-formación', 'sesion' => 'M5-3', 'modulo' => 'modulo_5'],
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Seeds the 29 deliverables for a participant. Idempotent.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   * @param int $tenantId
   *   The group (tenant) ID.
   */
  public function seedEntregables(int $participanteId, int $tenantId): void {
    try {
      $storage = $this->entityTypeManager->getStorage('entregable_formativo_ei');

      // Check which entregables already exist for this participant.
      $existingIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->execute();

      /** @var \Drupal\Core\Entity\ContentEntityInterface[] $existingEntities */
      $existingEntities = count($existingIds) > 0 ? $storage->loadMultiple($existingIds) : [];

      // Build set of existing numero values.
      $existingNumbers = [];
      foreach ($existingEntities as $entity) {
        if ($entity->hasField('numero') && !$entity->get('numero')->isEmpty()) {
          $existingNumbers[(int) $entity->get('numero')->value] = TRUE;
        }
      }

      foreach (self::ENTREGABLES as $numero => $data) {
        if (isset($existingNumbers[$numero])) {
          continue;
        }

        /** @var \Drupal\Core\Entity\ContentEntityInterface $entregable */
        $entregable = $storage->create([
          'participante_id' => $participanteId,
          'numero' => $numero,
          'titulo' => $data['titulo'],
          'sesion_origen' => $data['sesion'],
          'modulo' => $data['modulo'],
          'estado' => 'pendiente',
          'tenant_id' => $tenantId,
        ]);
        $entregable->save();
      }

      $this->logger->info('Seeded entregables for participante @id in tenant @tenant.', [
        '@id' => $participanteId,
        '@tenant' => $tenantId,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error seeding entregables for participante @id: @message', [
        '@id' => $participanteId,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Returns all deliverables for a participant, grouped by module.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   *
   * @return array<string, array<int, array<string, mixed>>>
   *   Keyed by modulo, each containing entregable data arrays.
   */
  public function getPortfolio(int $participanteId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('entregable_formativo_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->sort('numero', 'ASC')
        ->execute();

      if (count($ids) === 0) {
        return [];
      }

      /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
      $entities = $storage->loadMultiple($ids);
      $grouped = [];

      foreach ($entities as $entity) {
        $modulo = $entity->hasField('modulo') && !$entity->get('modulo')->isEmpty()
          ? (string) $entity->get('modulo')->value
          : 'sin_modulo';

        $numero = $entity->hasField('numero') && !$entity->get('numero')->isEmpty()
          ? (int) $entity->get('numero')->value
          : (int) $entity->id();

        $grouped[$modulo][$numero] = [
          'id' => (int) $entity->id(),
          'numero' => $numero,
          'titulo' => $entity->hasField('titulo') && !$entity->get('titulo')->isEmpty()
            ? (string) $entity->get('titulo')->value
            : '',
          'sesion' => $entity->hasField('sesion_origen') && !$entity->get('sesion_origen')->isEmpty()
            ? (string) $entity->get('sesion_origen')->value
            : '',
          'estado' => $entity->hasField('estado') && !$entity->get('estado')->isEmpty()
            ? (string) $entity->get('estado')->value
            : 'pendiente',
        ];
      }

      return $grouped;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading portfolio for participante @id: @message', [
        '@id' => $participanteId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Returns progress summary for a participant.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   *
   * @return array{total: int, completados: int, validados: int, porcentaje: float}
   *   Progress data.
   */
  public function getProgreso(int $participanteId): array {
    $result = [
      'total' => 29,
      'completados' => 0,
      'validados' => 0,
      'porcentaje' => 0.0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('entregable_formativo_ei');

      $completadosCount = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('estado', ['completado', 'validado'], 'IN')
        ->count()
        ->execute();

      $validadosCount = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('estado', 'validado')
        ->count()
        ->execute();

      $result['completados'] = $completadosCount;
      $result['validados'] = $validadosCount;
      $result['porcentaje'] = $result['total'] > 0
        ? round(($completadosCount / $result['total']) * 100, 2)
        : 0.0;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error computing progress for participante @id: @message', [
        '@id' => $participanteId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Marks a deliverable as completed.
   *
   * @param int $entregableId
   *   The entregable_formativo_ei entity ID.
   *
   * @return bool
   *   TRUE if successfully marked, FALSE otherwise.
   */
  public function marcarCompletado(int $entregableId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('entregable_formativo_ei');
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
      $entity = $storage->load($entregableId);

      if ($entity === NULL) {
        $this->logger->warning('Entregable @id not found for marcarCompletado.', [
          '@id' => $entregableId,
        ]);
        return FALSE;
      }

      if (!$entity->hasField('estado')) {
        return FALSE;
      }

      $entity->set('estado', 'completado');
      $entity->save();

      $this->logger->info('Entregable @id marked as completado.', [
        '@id' => $entregableId,
      ]);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error marking entregable @id as completado: @message', [
        '@id' => $entregableId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Validates a deliverable by a formador.
   *
   * @param int $entregableId
   *   The entregable_formativo_ei entity ID.
   * @param int $formadorUid
   *   The user ID of the formador validating.
   * @param string $notas
   *   Validation notes.
   *
   * @return bool
   *   TRUE if successfully validated, FALSE otherwise.
   */
  public function validarEntregable(int $entregableId, int $formadorUid, string $notas): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('entregable_formativo_ei');
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
      $entity = $storage->load($entregableId);

      if ($entity === NULL) {
        $this->logger->warning('Entregable @id not found for validarEntregable.', [
          '@id' => $entregableId,
        ]);
        return FALSE;
      }

      if (!$entity->hasField('estado')) {
        return FALSE;
      }

      $entity->set('estado', 'validado');

      if ($entity->hasField('validado_por')) {
        $entity->set('validado_por', $formadorUid);
      }

      if ($entity->hasField('validado_fecha')) {
        $entity->set('validado_fecha', date('Y-m-d\TH:i:s'));
      }

      if ($entity->hasField('notas_validacion')) {
        $entity->set('notas_validacion', $notas);
      }

      $entity->save();

      $this->logger->info('Entregable @id validated by formador @uid.', [
        '@id' => $entregableId,
        '@uid' => $formadorUid,
      ]);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error validating entregable @id: @message', [
        '@id' => $entregableId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Returns deliverables pending formador validation for a tenant.
   *
   * @param int $tenantId
   *   The group (tenant) ID.
   *
   * @return array<int, array<string, mixed>>
   *   Array of entregable data awaiting validation.
   */
  public function getPendientesValidacion(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('entregable_formativo_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('estado', 'completado')
        ->sort('changed', 'ASC')
        ->execute();

      if (count($ids) === 0) {
        return [];
      }

      /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
      $entities = $storage->loadMultiple($ids);
      $pendientes = [];

      foreach ($entities as $entity) {
        $participanteRef = $entity->hasField('participante_id') && !$entity->get('participante_id')->isEmpty()
          ? $entity->get('participante_id')->entity
          : NULL;
        $participanteLabel = $participanteRef !== NULL
          ? ($participanteRef->label() ?? (string) $participanteRef->id())
          : '';

        $pendientes[(int) $entity->id()] = [
          'id' => (int) $entity->id(),
          'titulo' => $entity->hasField('titulo') && !$entity->get('titulo')->isEmpty()
            ? (string) $entity->get('titulo')->value
            : '',
          'participante' => $participanteLabel,
          'participante_id' => $entity->hasField('participante_id') && !$entity->get('participante_id')->isEmpty()
            ? (int) $entity->get('participante_id')->target_id
            : 0,
          'sesion' => $entity->hasField('sesion_origen') && !$entity->get('sesion_origen')->isEmpty()
            ? (string) $entity->get('sesion_origen')->value
            : '',
          'modulo' => $entity->hasField('modulo') && !$entity->get('modulo')->isEmpty()
            ? (string) $entity->get('modulo')->value
            : '',
        ];
      }

      return $pendientes;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading pendientes validacion for tenant @id: @message', [
        '@id' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
