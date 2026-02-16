<?php

declare(strict_types=1);

namespace Drupal\jaraba_dr\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Psr\Log\LoggerInterface;

/**
 * Servicio de comunicacion multi-canal de incidentes.
 *
 * ESTRUCTURA:
 * Gestiona las notificaciones de incidentes DR a traves de multiples
 * canales: email, Slack, SMS y webhooks.
 *
 * LOGICA:
 * - Envia notificaciones por los canales configurados.
 * - Encola mensajes para envio asincrono via QueueFactory.
 * - Registra cada comunicacion en el communication_log del incidente.
 * - Gestiona escalados cuando no hay respuesta en el timeout configurado.
 *
 * RELACIONES:
 * - DrIncident (entidad de incidentes)
 * - jaraba_dr.settings (canales y timeout de escalado)
 *
 * Spec: Doc 185 s4.3. Plan: FASE 9, Stack Compliance Legal N1.
 */
class IncidentCommunicatorService {

  /**
   * Construye el servicio de comunicacion de incidentes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   Gestor de envio de mail.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   Factoria de colas.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logging.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly MailManagerInterface $mailManager,
    protected readonly QueueFactory $queueFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Envia notificacion de un incidente por todos los canales configurados.
   *
   * @param int $incidentId
   *   ID de la entidad DrIncident.
   * @param string $message
   *   Mensaje a enviar.
   *
   * @return int
   *   Numero de notificaciones enviadas.
   */
  public function notifyIncident(int $incidentId, string $message): int {
    // Stub: implementacion completa en fases posteriores.
    $this->logger->info('Notificacion de incidente @id: @message', [
      '@id' => $incidentId,
      '@message' => $message,
    ]);
    return 0;
  }

  /**
   * Verifica si hay incidentes que requieren escalado.
   *
   * @return int
   *   Numero de incidentes escalados.
   */
  public function checkEscalations(): int {
    // Stub: implementacion completa en fases posteriores.
    return 0;
  }

}
