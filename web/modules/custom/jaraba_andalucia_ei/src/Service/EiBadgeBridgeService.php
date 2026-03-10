<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Puente entre el itinerario +ei y el sistema de badges/gamificación.
 *
 * Sprint 11 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * Gamificación significativa: evalúa hitos del itinerario PIIL y emite
 * badges automáticamente cuando se alcanzan umbrales definidos.
 */
class EiBadgeBridgeService {

  /**
   * Hitos de badge con umbrales y metadatos.
   *
   * Cada badge define el campo y valor umbral que el participante
   * debe alcanzar para obtenerlo.
   */
  public const BADGE_MILESTONES = [
    'ei_primera_semana' => [
      'label' => 'Primera semana completada',
      'campo' => 'dias_en_programa',
      'umbral' => 7,
      'tipo' => 'count_gte',
      'icono' => 'estrella',
      'orden' => 1,
    ],
    'ei_diagnostico_completado' => [
      'label' => 'DIME completado',
      'campo' => 'diagnostico_completado',
      'umbral' => TRUE,
      'tipo' => 'boolean',
      'icono' => 'lupa',
      'orden' => 2,
    ],
    'ei_orientacion_10h' => [
      'label' => '10h de orientación',
      'campo' => 'horas_orientacion',
      'umbral' => 10,
      'tipo' => 'count_gte',
      'icono' => 'brujula',
      'orden' => 3,
    ],
    'ei_formacion_25h' => [
      'label' => '25h de formación',
      'campo' => 'horas_formacion',
      'umbral' => 25,
      'tipo' => 'count_gte',
      'icono' => 'libro',
      'orden' => 4,
    ],
    'ei_formacion_completa' => [
      'label' => '50h de formación',
      'campo' => 'horas_formacion',
      'umbral' => 50,
      'tipo' => 'count_gte',
      'icono' => 'graduacion',
      'orden' => 5,
    ],
    'ei_insercion' => [
      'label' => 'Inserción lograda',
      'campo' => 'tipo_insercion',
      'umbral' => TRUE,
      'tipo' => 'not_empty',
      'icono' => 'cohete',
      'orden' => 6,
    ],
    'ei_emprendimiento' => [
      'label' => 'Emprendimiento lanzado',
      'campo' => 'emprendimiento_lanzado',
      'umbral' => TRUE,
      'tipo' => 'boolean',
      'icono' => 'bombilla',
      'orden' => 7,
    ],
    'ei_alumni' => [
      'label' => 'Alumni activo',
      'campo' => 'fase_actual',
      'umbral' => 'seguimiento',
      'tipo' => 'equals',
      'icono' => 'comunidad',
      'orden' => 8,
    ],
    'ei_mentor_peer' => [
      'label' => 'Mentor peer activo',
      'campo' => 'es_mentor_peer',
      'umbral' => TRUE,
      'tipo' => 'boolean',
      'icono' => 'manos',
      'orden' => 9,
    ],
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $badgeAwardService = NULL,
  ) {}

  /**
   * Evalúa todos los hitos y emite badges pendientes.
   *
   * Comprueba cada umbral de BADGE_MILESTONES contra los datos
   * del participante y emite los badges que aún no tenga.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array
   *   Lista de badge_ids emitidos en esta ejecución.
   */
  public function evaluarYEmitirBadges(int $participanteId): array {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        $this->logger->warning('Participante @id no encontrado para evaluación de badges.', [
          '@id' => $participanteId,
        ]);
        return [];
      }

      // Obtener badges ya obtenidos.
      $badgesExistentes = $this->getBadgesParticipante($participanteId);
      $badgeIdsExistentes = array_column($badgesExistentes, 'badge_id');

      $emitidos = [];

      foreach (self::BADGE_MILESTONES as $badgeId => $milestone) {
        // Saltar si ya tiene este badge.
        if (in_array($badgeId, $badgeIdsExistentes, TRUE)) {
          continue;
        }

        // Evaluar si cumple el umbral.
        if ($this->cumpleUmbral($participante, $milestone)) {
          $emitido = $this->emitirBadge($participanteId, $badgeId, $participante);
          if ($emitido) {
            $emitidos[] = $badgeId;
          }
        }
      }

      if (!empty($emitidos)) {
        $this->logger->info('Badges emitidos para participante @id: @badges.', [
          '@id' => $participanteId,
          '@badges' => implode(', ', $emitidos),
        ]);
      }

      return $emitidos;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error evaluando badges participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene todos los badges obtenidos por un participante.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array
   *   Lista de badges con badge_id, label, fecha_obtencion, icono.
   */
  public function getBadgesParticipante(int $participanteId): array {
    try {
      if (!$this->badgeAwardService) {
        // Sin servicio de badges, devolver desde campo local si existe.
        return $this->getBadgesDesdeEntidad($participanteId);
      }

      return $this->badgeAwardService->getBadgesForEntity(
        'programa_participante_ei',
        $participanteId,
      );
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo badges participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene el próximo hito alcanzable para un participante.
   *
   * Devuelve el badge de menor orden que aún no haya obtenido,
   * con indicación de progreso hacia el umbral.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array|null
   *   Datos del próximo hito o NULL si todos completados.
   */
  public function getProximoHito(int $participanteId): ?array {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return NULL;
      }

      $badgesExistentes = $this->getBadgesParticipante($participanteId);
      $badgeIdsExistentes = array_column($badgesExistentes, 'badge_id');

      // Ordenar milestones por orden.
      $milestones = self::BADGE_MILESTONES;
      uasort($milestones, static fn(array $a, array $b): int => $a['orden'] <=> $b['orden']);

      foreach ($milestones as $badgeId => $milestone) {
        if (in_array($badgeId, $badgeIdsExistentes, TRUE)) {
          continue;
        }

        // Calcular progreso actual.
        $progreso = $this->calcularProgreso($participante, $milestone);

        return [
          'badge_id' => $badgeId,
          'label' => $milestone['label'],
          'icono' => $milestone['icono'],
          'tipo' => $milestone['tipo'],
          'umbral' => $milestone['umbral'],
          'valor_actual' => $progreso['valor'],
          'porcentaje' => $progreso['porcentaje'],
        ];
      }

      // Todos los badges completados.
      return NULL;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo próximo hito participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Evalúa si un participante cumple el umbral de un milestone.
   *
   * @param object $participante
   *   Entidad ProgramaParticipanteEi.
   * @param array $milestone
   *   Definición del milestone de BADGE_MILESTONES.
   *
   * @return bool
   *   TRUE si cumple el umbral.
   */
  protected function cumpleUmbral(object $participante, array $milestone): bool {
    $campo = $milestone['campo'];

    if (!$participante->hasField($campo)) {
      return FALSE;
    }

    $valor = $participante->get($campo)->value;

    return match ($milestone['tipo']) {
      'count_gte' => is_numeric($valor) && (float) $valor >= (float) $milestone['umbral'],
      'boolean' => !empty($valor),
      'not_empty' => $valor !== NULL && $valor !== '',
      'equals' => $valor === $milestone['umbral'],
      default => FALSE,
    };
  }

  /**
   * Calcula el progreso actual hacia un milestone.
   *
   * @param object $participante
   *   Entidad ProgramaParticipanteEi.
   * @param array $milestone
   *   Definición del milestone.
   *
   * @return array{valor: mixed, porcentaje: float}
   *   Valor actual y porcentaje de progreso.
   */
  protected function calcularProgreso(object $participante, array $milestone): array {
    $campo = $milestone['campo'];

    if (!$participante->hasField($campo)) {
      return ['valor' => NULL, 'porcentaje' => 0.0];
    }

    $valor = $participante->get($campo)->value;

    if ($milestone['tipo'] === 'count_gte' && is_numeric($milestone['umbral'])) {
      $valorNum = is_numeric($valor) ? (float) $valor : 0.0;
      $umbral = (float) $milestone['umbral'];
      $porcentaje = $umbral > 0 ? min(100.0, round(($valorNum / $umbral) * 100, 1)) : 0.0;
      return ['valor' => $valorNum, 'porcentaje' => $porcentaje];
    }

    // Para tipos boolean/not_empty/equals: 0% o 100%.
    $cumple = $this->cumpleUmbral($participante, $milestone);
    return ['valor' => $valor, 'porcentaje' => $cumple ? 100.0 : 0.0];
  }

  /**
   * Emite un badge para un participante.
   *
   * @param int $participanteId
   *   ID del participante.
   * @param string $badgeId
   *   Identificador del badge.
   * @param object $participante
   *   Entidad participante.
   *
   * @return bool
   *   TRUE si se emitió correctamente.
   */
  protected function emitirBadge(int $participanteId, string $badgeId, object $participante): bool {
    if (!$this->badgeAwardService) {
      $this->logger->info('Servicio de badges no disponible; badge @badge pendiente para @id.', [
        '@badge' => $badgeId,
        '@id' => $participanteId,
      ]);
      return FALSE;
    }

    try {
      $milestone = self::BADGE_MILESTONES[$badgeId] ?? NULL;
      if (!$milestone) {
        return FALSE;
      }

      $this->badgeAwardService->awardBadge([
        'badge_id' => $badgeId,
        'label' => $milestone['label'],
        'entity_type' => 'programa_participante_ei',
        'entity_id' => $participanteId,
        'uid' => $participante->getOwnerId(),
        'icono' => $milestone['icono'],
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error emitiendo badge @badge para @id: @msg', [
        '@badge' => $badgeId,
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene badges almacenados localmente en la entidad participante.
   *
   * Fallback cuando badgeAwardService no está disponible.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return array
   *   Lista de badges desde campo local.
   */
  protected function getBadgesDesdeEntidad(int $participanteId): array {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante || !$participante->hasField('badges_obtenidos')) {
        return [];
      }

      $badgesRaw = $participante->get('badges_obtenidos')->value ?? '';
      if (empty($badgesRaw)) {
        return [];
      }

      $badgeIds = array_filter(array_map('trim', explode(',', $badgesRaw)));
      $badges = [];

      foreach ($badgeIds as $badgeId) {
        $milestone = self::BADGE_MILESTONES[$badgeId] ?? NULL;
        if ($milestone) {
          $badges[] = [
            'badge_id' => $badgeId,
            'label' => $milestone['label'],
            'icono' => $milestone['icono'],
            'fecha_obtencion' => '',
          ];
        }
      }

      return $badges;
    }
    catch (\Throwable) {
      return [];
    }
  }

}
