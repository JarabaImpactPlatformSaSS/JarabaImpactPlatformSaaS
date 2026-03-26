<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestiona la expiración del acceso al programa tras 12 meses.
 *
 * Los participantes tienen acceso al programa durante MESES_PROGRAMA meses
 * desde su fecha de alta (campo 'created'). Este servicio:
 * - Calcula la fecha de expiración programada.
 * - Evalúa si corresponde enviar avisos previos.
 * - Ejecuta expiraciones automáticas vía cron.
 *
 * AVISOS_DIAS define los días de antelación para notificaciones:
 * 60, 30, 15, 7, 3, 1 días antes de la expiración.
 */
class BonoProgramaExpiryService {

  /**
   * Duración del programa en meses.
   */
  public const MESES_PROGRAMA = 12;

  /**
   * Días de antelación para enviar avisos de expiración.
   *
   * @var int[]
   */
  public const AVISOS_DIAS = [60, 30, 15, 7, 3, 1];

  /**
   * State key prefix for tracking sent notifications.
   */
  private const STATE_PREFIX = 'jaraba_andalucia_ei.bono_expiry_aviso';

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly StateInterface $state,
    protected readonly ?EiMultichannelNotificationService $notificationService = NULL,
  ) {}

  /**
   * Calcula la fecha de expiración programada para un participante.
   *
   * @param object $participante
   *   La entidad ProgramaParticipanteEi.
   *
   * @return \DateTimeImmutable|null
   *   La fecha de expiración, o NULL si no se puede calcular.
   */
  public function getExpiracionProgramada(object $participante): ?\DateTimeImmutable {
    $createdTimestamp = $participante->get('created')->value;
    if ($createdTimestamp === NULL) {
      return NULL;
    }

    try {
      $created = (new \DateTimeImmutable())->setTimestamp((int) $createdTimestamp);

      return $created->modify('+' . self::MESES_PROGRAMA . ' months');
    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculating expiry for participante @id: @msg', [
        '@id' => $participante->id(),
        '@msg' => $e->getMessage(),
      ]);

      return NULL;
    }
  }

  /**
   * Calcula los días restantes hasta la expiración.
   *
   * @param object $participante
   *   La entidad ProgramaParticipanteEi.
   *
   * @return int|null
   *   Días restantes (negativo si ya expiró), o NULL si no se puede calcular.
   */
  public function getDiasRestantes(object $participante): ?int {
    $expiracion = $this->getExpiracionProgramada($participante);
    if ($expiracion === NULL) {
      return NULL;
    }

    $now = new \DateTimeImmutable();
    $diff = $now->diff($expiracion);

    return $diff->invert === 1 ? -$diff->days : $diff->days;
  }

  /**
   * Evalúa y envía avisos de expiración próxima para un participante.
   *
   * Compara los días restantes contra AVISOS_DIAS y envía notificación
   * si corresponde y no se ha enviado ya para ese umbral.
   *
   * @param object $participante
   *   La entidad ProgramaParticipanteEi.
   *
   * @return string|null
   *   El tipo de aviso enviado ('aviso_60d', etc.), o NULL si no corresponde.
   */
  public function evaluarAvisos(object $participante): ?string {
    $diasRestantes = $this->getDiasRestantes($participante);
    if ($diasRestantes === NULL || $diasRestantes < 0) {
      return NULL;
    }

    foreach (self::AVISOS_DIAS as $umbral) {
      if ($diasRestantes <= $umbral) {
        $stateKey = self::STATE_PREFIX . '.' . $participante->id() . '.' . $umbral;
        $alreadySent = $this->state->get($stateKey, FALSE);

        if ($alreadySent) {
          continue;
        }

        // Mark as sent before attempting notification.
        $this->state->set($stateKey, TRUE);

        $avisoType = 'aviso_' . $umbral . 'd';

        if ($this->notificationService !== NULL) {
          try {
            $this->notificationService->send(
              $participante,
              'bono_expiry',
              [
                'dias_restantes' => $diasRestantes,
                'umbral' => $umbral,
                'tipo_aviso' => $avisoType,
              ]
            );
          }
          catch (\Throwable $e) {
            $this->logger->warning('Failed to send expiry notification for participante @id: @msg', [
              '@id' => $participante->id(),
              '@msg' => $e->getMessage(),
            ]);
          }
        }

        $this->logger->info('Expiry notice @type sent for participante @id (@days days remaining)', [
          '@type' => $avisoType,
          '@id' => $participante->id(),
          '@days' => $diasRestantes,
        ]);

        return $avisoType;
      }
    }

    return NULL;
  }

  /**
   * Ejecuta expiraciones para participantes cuyo plazo ha vencido.
   *
   * Busca participantes activos cuya fecha de alta + MESES_PROGRAMA
   * ya ha pasado y actualiza su fase a 'expirado'.
   *
   * @return int
   *   Número de participantes expirados.
   */
  public function ejecutarExpiraciones(): int {
    $count = 0;

    try {
      if (!$this->entityTypeManager->hasDefinition('programa_participante_ei')) {
        return 0;
      }

      $cutoff = (new \DateTimeImmutable())
        ->modify('-' . self::MESES_PROGRAMA . ' months')
        ->getTimestamp();

      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $cutoff, '<')
        ->condition('fase_actual', 'expirado', '<>')
        ->execute();

      if ($ids === []) {
        return 0;
      }

      $participantes = $storage->loadMultiple($ids);
      foreach ($participantes as $participante) {
        $diasRestantes = $this->getDiasRestantes($participante);
        if ($diasRestantes !== NULL && $diasRestantes < 0) {
          $participante->set('fase_actual', 'expirado');
          $participante->save();
          $count++;

          $this->logger->info('Participante @id expired (created @date)', [
            '@id' => $participante->id(),
            '@date' => date('Y-m-d', (int) $participante->get('created')->value),
          ]);
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error during expiry execution: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $count;
  }

  /**
   * Obtiene participantes próximos a expirar dentro de N días.
   *
   * @param int $diasMargen
   *   Número de días máximo hasta la expiración.
   *
   * @return array<int, array{participante_id: int, dias_restantes: int}>
   *   Lista de participantes con sus días restantes.
   */
  public function getParticipantesProximosAExpirar(int $diasMargen = 60): array {
    $result = [];

    try {
      if (!$this->entityTypeManager->hasDefinition('programa_participante_ei')) {
        return [];
      }

      // Cutoff: participants created between (now - MESES_PROGRAMA + diasMargen) and (now - MESES_PROGRAMA).
      $now = new \DateTimeImmutable();
      $expiryBase = $now->modify('-' . self::MESES_PROGRAMA . ' months');
      $cutoffStart = $expiryBase->modify('-' . $diasMargen . ' days');

      $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('created', $cutoffStart->getTimestamp(), '>=')
        ->condition('fase_actual', 'expirado', '<>')
        ->execute();

      if ($ids === []) {
        return [];
      }

      $participantes = $storage->loadMultiple($ids);
      foreach ($participantes as $participante) {
        $diasRestantes = $this->getDiasRestantes($participante);
        if ($diasRestantes !== NULL && $diasRestantes >= 0 && $diasRestantes <= $diasMargen) {
          $result[] = [
            'participante_id' => (int) $participante->id(),
            'dias_restantes' => $diasRestantes,
          ];
        }
      }

      // Sort by days remaining ascending.
      usort($result, static function (array $a, array $b): int {
        return $a['dias_restantes'] <=> $b['dias_restantes'];
      });
    }
    catch (\Throwable $e) {
      $this->logger->error('Error listing upcoming expirations: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $result;
  }

}
