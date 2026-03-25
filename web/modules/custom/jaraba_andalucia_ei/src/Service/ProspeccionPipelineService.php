<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\NegocioProspectadoEi;
use Psr\Log\LoggerInterface;

/**
 * Orquesta la vista Kanban del pipeline de prospección comercial.
 *
 * Agrupa entidades NegocioProspectadoEi por estado_embudo (6 fases)
 * y proporciona estadísticas agregadas para el dashboard coordinador.
 *
 * TENANT-001: Todas las queries filtran por tenant_id.
 */
class ProspeccionPipelineService {

  /**
   * Fases válidas del embudo comercial.
   *
   * @var string[]
   */
  private const FASES_EMBUDO = [
    'identificado',
    'contactado',
    'interesado',
    'propuesta',
    'acuerdo',
    'conversion',
  ];

  /**
   * Constructs a ProspeccionPipelineService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log para andalucia_ei.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el pipeline agrupado por estado del embudo.
   *
   * Devuelve un array asociativo donde cada clave es una fase del embudo
   * y el valor es un array de datos de negocios en esa fase.
   *
   * @param int $tenantId
   *   ID del tenant (grupo) para filtrar.
   *
   * @return array<string, array<int, array{id: int, nombre_negocio: string, sector: string, provincia: string, clasificacion_urgencia: string, persona_contacto: string, pack_compatible: string, participante_asignado: string}>>
   *   Array indexado por fase del embudo con datos de cada negocio.
   */
  public function getPipelineByEstado(int $tenantId): array {
    // Inicializar todas las fases vacías para garantizar estructura completa.
    $pipeline = [];
    foreach (self::FASES_EMBUDO as $fase) {
      $pipeline[$fase] = [];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('negocio_prospectado_ei');

      // TENANT-001: Filtrar siempre por tenant_id.
      $ids = $storage->getQuery()
        ->accessCheck(TRUE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->execute();

      if (count($ids) === 0) {
        return $pipeline;
      }

      /** @var \Drupal\jaraba_andalucia_ei\Entity\NegocioProspectadoEi[] $negocios */
      $negocios = $storage->loadMultiple($ids);

      foreach ($negocios as $negocio) {
        $estado = $negocio->getEstadoEmbudo();

        // Solo incluir fases válidas.
        if (!in_array($estado, self::FASES_EMBUDO, TRUE)) {
          $this->logger->warning('NegocioProspectadoEi @id tiene estado_embudo inválido: @estado', [
            '@id' => $negocio->id(),
            '@estado' => $estado,
          ]);
          continue;
        }

        $pipeline[$estado][] = $this->buildNegocioData($negocio);
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo pipeline de prospección para tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $pipeline;
  }

  /**
   * Obtiene estadísticas agregadas del pipeline.
   *
   * @param int $tenantId
   *   ID del tenant (grupo) para filtrar.
   *
   * @return array{por_fase: array<string, int>, total: int, tasa_conversion: float}
   *   Conteo por fase, total y tasa de conversión.
   */
  public function getEstadisticas(int $tenantId): array {
    $estadisticas = [
      'por_fase' => [],
      'total' => 0,
      'tasa_conversion' => 0.0,
    ];

    // Inicializar conteo en cero para todas las fases.
    foreach (self::FASES_EMBUDO as $fase) {
      $estadisticas['por_fase'][$fase] = 0;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('negocio_prospectado_ei');

      foreach (self::FASES_EMBUDO as $fase) {
        // TENANT-001: Filtrar siempre por tenant_id.
        $count = $storage->getQuery()
          ->accessCheck(TRUE)
          ->condition('tenant_id', $tenantId)
          ->condition('estado_embudo', $fase)
          ->count()
          ->execute();

        $estadisticas['por_fase'][$fase] = $count;
        $estadisticas['total'] += $count;
      }

      // Calcular tasa de conversión (conversiones / total).
      if ($estadisticas['total'] > 0) {
        $estadisticas['tasa_conversion'] = round(
          ($estadisticas['por_fase']['conversion'] / $estadisticas['total']) * 100,
          1
        );
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo estadísticas de prospección para tenant @tenant: @message', [
        '@tenant' => $tenantId,
        '@message' => $e->getMessage(),
      ]);
    }

    return $estadisticas;
  }

  /**
   * Mueve un negocio a una nueva fase del embudo.
   *
   * Diseñado para soportar drag-and-drop en la vista Kanban.
   *
   * @param int $negocioId
   *   ID del NegocioProspectadoEi a mover.
   * @param string $nuevoEstado
   *   Nueva fase del embudo (debe ser una de las 6 fases válidas).
   *
   * @return bool
   *   TRUE si el cambio se realizó correctamente, FALSE en caso contrario.
   */
  public function moverEstado(int $negocioId, string $nuevoEstado): bool {
    // Validar que el nuevo estado es válido.
    if (!in_array($nuevoEstado, self::FASES_EMBUDO, TRUE)) {
      $this->logger->warning('Intento de mover negocio @id a estado inválido: @estado', [
        '@id' => $negocioId,
        '@estado' => $nuevoEstado,
      ]);
      return FALSE;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('negocio_prospectado_ei');

      /** @var \Drupal\jaraba_andalucia_ei\Entity\NegocioProspectadoEi|null $negocio */
      $negocio = $storage->load($negocioId);

      if ($negocio === NULL) {
        $this->logger->warning('NegocioProspectadoEi @id no encontrado para mover estado.', [
          '@id' => $negocioId,
        ]);
        return FALSE;
      }

      $estadoAnterior = $negocio->getEstadoEmbudo();
      $negocio->setEstadoEmbudo($nuevoEstado);
      $negocio->save();

      // LABEL-NULLSAFE-001: label() puede devolver NULL.
      $nombre = $negocio->label() ?? (string) $negocioId;

      $this->logger->info('Negocio "@nombre" (ID: @id) movido de @anterior a @nuevo.', [
        '@nombre' => $nombre,
        '@id' => $negocioId,
        '@anterior' => $estadoAnterior,
        '@nuevo' => $nuevoEstado,
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error moviendo negocio @id a estado @estado: @message', [
        '@id' => $negocioId,
        '@estado' => $nuevoEstado,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Construye el array de datos de un negocio para el pipeline.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\NegocioProspectadoEi $negocio
   *   La entidad negocio prospectado.
   *
   * @return array{id: int, nombre_negocio: string, sector: string, provincia: string, clasificacion_urgencia: string, persona_contacto: string, pack_compatible: string, participante_asignado: string}
   *   Datos del negocio formateados para la vista Kanban.
   */
  private function buildNegocioData(NegocioProspectadoEi $negocio): array {
    // LABEL-NULLSAFE-001: label() puede devolver NULL.
    $participanteNombre = '';
    if (!$negocio->get('participante_asignado')->isEmpty()) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $participante */
      $participante = $negocio->get('participante_asignado')->entity;
      if ($participante !== NULL) {
        $participanteNombre = $participante->label() ?? '';
      }
    }

    return [
      'id' => (int) $negocio->id(),
      'nombre_negocio' => $negocio->getNombreNegocio(),
      'sector' => $negocio->getSector(),
      'provincia' => $negocio->get('provincia')->value ?? '',
      'clasificacion_urgencia' => $negocio->getClasificacionUrgencia(),
      'persona_contacto' => $negocio->get('persona_contacto')->value ?? '',
      'pack_compatible' => $negocio->get('pack_compatible')->value ?? '',
      'participante_asignado' => $participanteNombre,
    ];
  }

}
