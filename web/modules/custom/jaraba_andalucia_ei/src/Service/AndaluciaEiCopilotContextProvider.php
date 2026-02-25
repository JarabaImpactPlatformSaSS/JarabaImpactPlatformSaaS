<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides Andalucía +ei context to Copilot v2.
 *
 * Enriches the copilot conversation with participant-specific data:
 * phase, hours, documents, milestones, and cross-vertical bridges.
 *
 * AI-IDENTITY-001: Uses Jaraba identity in all prompts.
 */
class AndaluciaEiCopilotContextProvider {

  /**
   * Constructs an AndaluciaEiCopilotContextProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\jaraba_andalucia_ei\Service\ExpedienteService $expedienteService
   *   The document folder service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected ExpedienteService $expedienteService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Gets context data for the copilot.
   *
   * @return array|null
   *   Context data or NULL if user is not a participant.
   */
  public function getContext(): ?array {
    $participante = $this->getParticipante();
    if (!$participante) {
      return NULL;
    }

    $horasOrientacion = $participante->getTotalHorasOrientacion();
    $horasFormacion = (float) ($participante->get('horas_formacion')->value ?? 0);
    $completitud = $this->expedienteService->getCompletuDocumental((int) $participante->id());

    $faseLabels = [
      'atencion' => 'Atención',
      'insercion' => 'Inserción',
    ];

    return [
      'vertical' => 'andalucia_ei',
      'participante_id' => (int) $participante->id(),
      'fase_actual' => $participante->getFaseActual(),
      'fase_label' => $faseLabels[$participante->getFaseActual()] ?? $participante->getFaseActual(),
      'horas' => [
        'orientacion_total' => $horasOrientacion,
        'orientacion_meta' => 10.0,
        'orientacion_pct' => min(100, round(($horasOrientacion / 10) * 100)),
        'formacion' => $horasFormacion,
        'formacion_meta' => 50.0,
        'formacion_pct' => min(100, round(($horasFormacion / 50) * 100)),
        'mentoria_ia' => $participante->getHorasMentoriaIa(),
        'mentoria_humana' => $participante->getHorasMentoriaHumana(),
      ],
      'documentos' => [
        'sto_completados' => $completitud['completados'],
        'sto_total' => $completitud['total_requeridos'],
        'sto_porcentaje' => $completitud['porcentaje'],
        'pendientes_revision' => $this->countPendingDocs((int) $participante->id()),
      ],
      'milestones' => [
        'puede_transitar' => $participante->canTransitToInsercion(),
        'incentivo_recibido' => $participante->hasReceivedIncentivo(),
        'tipo_insercion' => $participante->get('tipo_insercion')->value ?? NULL,
      ],
      'system_prompt_addition' => $this->buildSystemPrompt($participante),
    ];
  }

  /**
   * Builds a system prompt addition for the copilot.
   *
   * @param mixed $participante
   *   The participant entity.
   *
   * @return string
   *   System prompt text.
   */
  protected function buildSystemPrompt($participante): string {
    $fase = $participante->getFaseActual();
    $horas = $participante->getTotalHorasOrientacion();
    $formacion = (float) ($participante->get('horas_formacion')->value ?? 0);

    $prompt = sprintf(
      'El usuario es participante del programa Andalucía +ei de la Fundación Jaraba. ' .
      'Está en fase "%s". Lleva %.1fh de orientación (meta: 10h) y %.1fh de formación (meta: 50h). ',
      $fase,
      $horas,
      $formacion,
    );

    if ($fase === 'atencion') {
      if ($horas < 5) {
        $prompt .= 'Prioridad: aumentar horas de orientación. Sugiere sesiones y actividades. ';
      }
      elseif ($formacion < 25) {
        $prompt .= 'Prioridad: avanzar en formación. Recomienda módulos del LMS. ';
      }
      elseif ($participante->canTransitToInsercion()) {
        $prompt .= 'El participante cumple requisitos para transitar a inserción. Orienta sobre el proceso. ';
      }
    }
    elseif ($fase === 'insercion') {
      $tipo = $participante->get('tipo_insercion')->value ?? '';
      if (empty($tipo)) {
        $prompt .= 'Prioridad: definir vía de inserción (empleo, autoempleo, formación complementaria). ';
      }
      else {
        $prompt .= sprintf('Vía de inserción: %s. Apoya en la búsqueda activa y preparación. ', $tipo);
      }
    }

    return $prompt;
  }

  /**
   * Gets the current user's participant entity.
   */
  protected function getParticipante() {
    $uid = $this->currentUser->id();
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $uid)
      ->condition('fase_actual', 'baja', '<>')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Counts documents pending review for a participant.
   */
  protected function countPendingDocs(int $participanteId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('expediente_documento');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('estado_revision', ['pendiente', 'en_revision'], 'IN')
        ->condition('status', 1)
        ->execute();
      return count($ids);
    }
    catch (\Exception $e) {
      return 0;
    }
  }

}
