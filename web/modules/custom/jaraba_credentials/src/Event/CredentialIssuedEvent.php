<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\jaraba_credentials\Entity\IssuedCredential;

/**
 * Evento despachado tras la emision exitosa de una credencial.
 *
 * P1-04: Transporta la credencial emitida y el contexto de emision
 * para que los suscriptores puedan actuar de forma reactiva.
 *
 * Casos de uso:
 * - Notificacion por email al destinatario.
 * - Webhook dispatch a plataformas externas (LinkedIn, etc.).
 * - Evaluacion de stacks/diplomas.
 * - Registro de metricas de impacto.
 */
class CredentialIssuedEvent extends Event {

  /**
   * La credencial recien emitida.
   */
  protected IssuedCredential $credential;

  /**
   * Contexto adicional de la emision.
   *
   * Puede contener:
   * - 'trigger': string — Origen del trigger (certification, lms, exam, manual).
   * - 'certification_id': int — ID de la certificacion origen.
   * - 'exam_score': int — Puntuacion del examen.
   * - 'course_id': int — ID del curso LMS completado.
   *
   * @var array
   */
  protected array $context;

  /**
   * Constructor del evento.
   *
   * @param \Drupal\jaraba_credentials\Entity\IssuedCredential $credential
   *   La credencial emitida.
   * @param array $context
   *   Contexto de la emision.
   */
  public function __construct(IssuedCredential $credential, array $context = []) {
    $this->credential = $credential;
    $this->context = $context;
  }

  /**
   * Obtiene la credencial emitida.
   *
   * @return \Drupal\jaraba_credentials\Entity\IssuedCredential
   *   La credencial.
   */
  public function getCredential(): IssuedCredential {
    return $this->credential;
  }

  /**
   * Obtiene el contexto de la emision.
   *
   * @return array
   *   Contexto con informacion del trigger y datos adicionales.
   */
  public function getContext(): array {
    return $this->context;
  }

  /**
   * Obtiene el origen del trigger.
   *
   * @return string
   *   Identificador del trigger (certification, lms, exam, manual, stack).
   */
  public function getTrigger(): string {
    return $this->context['trigger'] ?? 'unknown';
  }

  /**
   * Obtiene el ID del usuario destinatario.
   *
   * @return int
   *   ID del usuario.
   */
  public function getRecipientId(): int {
    return (int) ($this->credential->get('recipient_id')->target_id ?? 0);
  }

  /**
   * Obtiene el ID del template utilizado.
   *
   * @return int
   *   ID del template.
   */
  public function getTemplateId(): int {
    return (int) ($this->credential->get('template_id')->target_id ?? 0);
  }

}
