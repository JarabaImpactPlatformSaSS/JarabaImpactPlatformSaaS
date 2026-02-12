<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Event;

/**
 * Define los nombres de eventos del sistema de credenciales.
 *
 * P1-04: Constantes centralizadas para desacoplar emisores de suscriptores.
 * Permite que otros modulos (emprendimiento, LMS, notificaciones) reaccionen
 * a eventos del ciclo de vida de credenciales sin dependencia directa.
 */
final class CredentialEvents {

  /**
   * Se despacha tras emitir exitosamente una credencial.
   *
   * Permite a suscriptores:
   * - Enviar notificaciones en tiempo real al destinatario.
   * - Despachar webhooks a sistemas externos.
   * - Registrar metricas de emision.
   * - Evaluar automaticamente stacks/diplomas.
   *
   * @Event
   *
   * @var string
   */
  const CREDENTIAL_ISSUED = 'jaraba_credentials.credential_issued';

  /**
   * Se despacha cuando una credencial es revocada.
   *
   * Permite notificar al usuario y actualizar sistemas externos.
   *
   * @Event
   *
   * @var string
   */
  const CREDENTIAL_REVOKED = 'jaraba_credentials.credential_revoked';

  /**
   * Se despacha cuando una credencial esta proxima a expirar.
   *
   * Permite enviar recordatorios al usuario con tiempo suficiente
   * para renovar o completar requisitos pendientes.
   *
   * @Event
   *
   * @var string
   */
  const CREDENTIAL_EXPIRING = 'jaraba_credentials.credential_expiring';

  /**
   * Se despacha cuando un usuario completa un stack de credenciales.
   *
   * Permite auto-emitir la credencial de stack (diploma) y
   * notificar logros al usuario.
   *
   * @Event
   *
   * @var string
   */
  const STACK_COMPLETED = 'jaraba_credentials.stack_completed';

}
