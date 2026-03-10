<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Puente entre participantes +ei egresados y la red Alumni.
 *
 * Sprint 10 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * Gestiona el directorio alumni, estadísticas de impacto,
 * registro como mentor peer y recopilación de historias de éxito.
 */
class EiAlumniBridgeService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $mentoringService = NULL,
    protected ?object $tenantContext = NULL,
  ) {}

  /**
   * Obtiene el directorio de alumni con datos públicos (sin PII sensible).
   *
   * Devuelve nombre, sector, tipo de inserción y fecha de egreso.
   * No incluye datos personales más allá del nombre.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param array $filters
   *   Filtros opcionales: sector, tipo_insercion, year, municipio.
   *
   * @return array
   *   Lista de alumni con datos públicos.
   */
  public function getAlumniDirectory(int $tenantId, array $filters = []): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('fase_actual', 'seguimiento')
        ->condition('status', 1);

      // Aplicar filtros opcionales.
      if (!empty($filters['sector'])) {
        $query->condition('sector_insercion', $filters['sector']);
      }
      if (!empty($filters['tipo_insercion'])) {
        $query->condition('tipo_insercion', $filters['tipo_insercion']);
      }
      if (!empty($filters['year'])) {
        $inicio = strtotime($filters['year'] . '-01-01');
        $fin = strtotime($filters['year'] . '-12-31');
        if ($inicio && $fin) {
          $query->condition('fecha_insercion', $inicio, '>=');
          $query->condition('fecha_insercion', $fin, '<=');
        }
      }
      if (!empty($filters['municipio'])) {
        $query->condition('municipio', $filters['municipio']);
      }

      $query->sort('changed', 'DESC');
      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $participantes = $storage->loadMultiple($ids);
      $directorio = [];

      foreach ($participantes as $participante) {
        $directorio[] = [
          'id' => (int) $participante->id(),
          'nombre' => $participante->label() ?? '',
          'sector' => $participante->hasField('sector_insercion')
            ? ($participante->get('sector_insercion')->value ?? '')
            : '',
          'tipo_insercion' => $participante->hasField('tipo_insercion')
            ? ($participante->get('tipo_insercion')->value ?? '')
            : '',
          'municipio' => $participante->hasField('municipio')
            ? ($participante->get('municipio')->value ?? '')
            : '',
          'fecha_insercion' => $participante->hasField('fecha_insercion')
            ? ($participante->get('fecha_insercion')->value ?? '')
            : '',
          'es_mentor_peer' => $participante->hasField('es_mentor_peer')
            ? (bool) ($participante->get('es_mentor_peer')->value ?? FALSE)
            : FALSE,
        ];
      }

      return $directorio;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo directorio alumni tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene estadísticas agregadas de alumni por tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Estadísticas: total, por sector, por tipo de inserción, tasa retención.
   */
  public function getAlumniStats(int $tenantId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // Total alumni (fase seguimiento).
      $total = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('fase_actual', 'seguimiento')
        ->condition('status', 1)
        ->count()
        ->execute();

      if ($total === 0) {
        return [
          'total' => 0,
          'por_sector' => [],
          'por_tipo_insercion' => [],
          'tasa_retencion' => 0.0,
        ];
      }

      // Cargar todos para agregar.
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('fase_actual', 'seguimiento')
        ->condition('status', 1)
        ->execute();

      $participantes = $storage->loadMultiple($ids);
      $porSector = [];
      $porTipoInsercion = [];
      $retenidos = 0;

      foreach ($participantes as $participante) {
        // Agrupar por sector.
        $sector = $participante->hasField('sector_insercion')
          ? ($participante->get('sector_insercion')->value ?? 'sin_definir')
          : 'sin_definir';
        $porSector[$sector] = ($porSector[$sector] ?? 0) + 1;

        // Agrupar por tipo de inserción.
        $tipo = $participante->hasField('tipo_insercion')
          ? ($participante->get('tipo_insercion')->value ?? 'sin_definir')
          : 'sin_definir';
        $porTipoInsercion[$tipo] = ($porTipoInsercion[$tipo] ?? 0) + 1;

        // Tasa de retención: los que mantienen empleo > 6 meses.
        if ($participante->hasField('fecha_insercion') && !$participante->get('fecha_insercion')->isEmpty()) {
          $fechaInsercion = $participante->get('fecha_insercion')->value;
          $seisMesesAtras = strtotime('-6 months');
          if (is_string($fechaInsercion) && strtotime($fechaInsercion) !== FALSE) {
            if (strtotime($fechaInsercion) <= $seisMesesAtras) {
              $retenidos++;
            }
          }
        }
      }

      // Ordenar por cantidad descendente.
      arsort($porSector);
      arsort($porTipoInsercion);

      // Tasa de retención sobre quienes llevan > 6 meses.
      $elegiblesRetencion = 0;
      foreach ($participantes as $participante) {
        if ($participante->hasField('fecha_insercion') && !$participante->get('fecha_insercion')->isEmpty()) {
          $fechaInsercion = $participante->get('fecha_insercion')->value;
          $seisMesesAtras = strtotime('-6 months');
          if (is_string($fechaInsercion) && strtotime($fechaInsercion) !== FALSE && strtotime($fechaInsercion) <= $seisMesesAtras) {
            $elegiblesRetencion++;
          }
        }
      }

      $tasaRetencion = $elegiblesRetencion > 0
        ? round(($retenidos / $elegiblesRetencion) * 100, 1)
        : 0.0;

      return [
        'total' => $total,
        'por_sector' => $porSector,
        'por_tipo_insercion' => $porTipoInsercion,
        'tasa_retencion' => $tasaRetencion,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando estadísticas alumni tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'total' => 0,
        'por_sector' => [],
        'por_tipo_insercion' => [],
        'tasa_retencion' => 0.0,
      ];
    }
  }

  /**
   * Registra a un alumni como mentor peer en jaraba_mentoring.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi en fase seguimiento.
   *
   * @return bool
   *   TRUE si se registró correctamente.
   */
  public function registrarComoMentorPeer(int $participanteId): bool {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        $this->logger->warning('Participante @id no encontrado para registro mentor peer.', [
          '@id' => $participanteId,
        ]);
        return FALSE;
      }

      // Verificar que está en fase seguimiento (alumni).
      $fase = $participante->hasField('fase_actual')
        ? ($participante->get('fase_actual')->value ?? '')
        : '';
      if ($fase !== 'seguimiento') {
        $this->logger->info('Participante @id no es alumni (fase: @fase); registro mentor denegado.', [
          '@id' => $participanteId,
          '@fase' => $fase,
        ]);
        return FALSE;
      }

      // Marcar como mentor peer en la entidad participante.
      if ($participante->hasField('es_mentor_peer')) {
        $participante->set('es_mentor_peer', TRUE);
        $participante->save();
      }

      // Registrar en jaraba_mentoring si el servicio está disponible.
      if ($this->mentoringService) {
        try {
          $this->mentoringService->registerPeerMentor([
            'uid' => $participante->getOwnerId(),
            'source_entity_type' => 'programa_participante_ei',
            'source_entity_id' => $participanteId,
            'tipo' => 'peer_alumni',
            'sector' => $participante->hasField('sector_insercion')
              ? ($participante->get('sector_insercion')->value ?? '')
              : '',
          ]);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error registrando mentor peer @id en jaraba_mentoring: @msg', [
            '@id' => $participanteId,
            '@msg' => $e->getMessage(),
          ]);
          // No bloquear: el flag local ya se guardó.
        }
      }

      $this->logger->info('Participante @id registrado como mentor peer.', [
        '@id' => $participanteId,
      ]);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error registrando mentor peer @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene historias de éxito de alumni con datos reales de impacto.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Número máximo de historias.
   *
   * @return array
   *   Lista de historias con nombre, sector, tipo inserción y datos de impacto.
   */
  public function getHistoriasExito(int $tenantId, int $limit = 6): array {
    try {
      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

      // Buscar alumni con datos de impacto relevantes.
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('fase_actual', 'seguimiento')
        ->condition('status', 1)
        ->condition('historia_exito_publicable', TRUE)
        ->sort('fecha_insercion', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $participantes = $storage->loadMultiple($ids);
      $historias = [];

      foreach ($participantes as $participante) {
        $historias[] = [
          'id' => (int) $participante->id(),
          'nombre' => $participante->label() ?? '',
          'sector' => $participante->hasField('sector_insercion')
            ? ($participante->get('sector_insercion')->value ?? '')
            : '',
          'tipo_insercion' => $participante->hasField('tipo_insercion')
            ? ($participante->get('tipo_insercion')->value ?? '')
            : '',
          'testimonio' => $participante->hasField('testimonio_alumni')
            ? ($participante->get('testimonio_alumni')->value ?? '')
            : '',
          'fecha_insercion' => $participante->hasField('fecha_insercion')
            ? ($participante->get('fecha_insercion')->value ?? '')
            : '',
          'meses_retencion' => $this->calcularMesesRetencion($participante),
          'municipio' => $participante->hasField('municipio')
            ? ($participante->get('municipio')->value ?? '')
            : '',
        ];
      }

      return $historias;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo historias de éxito tenant @tid: @msg', [
        '@tid' => $tenantId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Calcula meses de retención desde la fecha de inserción.
   *
   * @param object $participante
   *   Entidad ProgramaParticipanteEi.
   *
   * @return int
   *   Meses de retención o 0 si no aplica.
   */
  protected function calcularMesesRetencion(object $participante): int {
    if (!$participante->hasField('fecha_insercion') || $participante->get('fecha_insercion')->isEmpty()) {
      return 0;
    }

    $fechaInsercion = $participante->get('fecha_insercion')->value;
    if (!is_string($fechaInsercion)) {
      return 0;
    }

    $timestamp = strtotime($fechaInsercion);
    if ($timestamp === FALSE) {
      return 0;
    }

    $meses = (int) round((time() - $timestamp) / (30 * 86400));
    return max(0, $meses);
  }

}
