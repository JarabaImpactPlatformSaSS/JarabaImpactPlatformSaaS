<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_legal_knowledge\Service\LegalAlertService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Procesa items de notificacion de alertas de cambios normativos.
 *
 * Los items son encolados cuando se detectan cambios en normas
 * (nuevas, modificadas, derogadas) y deben ser notificados a los
 * tenants afectados. Este worker crea las alertas y envia los emails.
 *
 * @QueueWorker(
 *   id = "jaraba_legal_knowledge_alert_notification",
 *   title = @Translation("Legal Alert Notification Worker"),
 *   cron = {"time" = 30}
 * )
 */
class LegalAlertNotificationWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuracion del plugin.
   * @param string $plugin_id
   *   ID del plugin.
   * @param mixed $plugin_definition
   *   Definicion del plugin.
   * @param \Drupal\jaraba_legal_knowledge\Service\LegalAlertService $alertService
   *   Servicio de alertas legales.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected LegalAlertService $alertService,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('jaraba_legal_knowledge.alert'),
      $container->get('logger.channel.jaraba_legal_knowledge'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * Procesa un item de notificacion de alerta normativa.
   *
   * El $data puede contener dos tipos de acciones:
   *
   * 1. Crear alerta: Requiere 'action' => 'create_alert' y:
   *    - tenant_id: (int) ID del tenant.
   *    - norm_id: (int) ID de la entidad LegalNorm.
   *    - change_type: (string) 'nueva', 'modificacion', 'derogacion'.
   *    - summary: (string) Resumen del cambio.
   *    - areas: (array) Areas tematicas afectadas.
   *    - severity: (string) 'info', 'warning', 'critical'.
   *
   * 2. Enviar pendientes: 'action' => 'send_pending'.
   *    Envia todas las alertas pendientes por email.
   */
  public function processItem($data): void {
    $action = $data['action'] ?? 'create_alert';

    try {
      if ($action === 'send_pending') {
        $sentCount = $this->alertService->sendPendingAlerts();
        $this->logger->info('Alertas pendientes procesadas via cola: @count enviadas.', [
          '@count' => $sentCount,
        ]);
      }
      else {
        // Crear alerta.
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $normId = (int) ($data['norm_id'] ?? 0);
        $changeType = $data['change_type'] ?? 'modificacion';
        $summary = $data['summary'] ?? '';
        $areas = $data['areas'] ?? [];
        $severity = $data['severity'] ?? 'info';

        if ($tenantId <= 0 || $normId <= 0) {
          $this->logger->warning('Item de alerta invalido: tenant_id=@tenant, norm_id=@norm.', [
            '@tenant' => $tenantId,
            '@norm' => $normId,
          ]);
          return;
        }

        $alert = $this->alertService->createAlert(
          $tenantId,
          $normId,
          $changeType,
          $summary,
          $areas,
          $severity
        );

        $this->logger->info('Alerta creada via cola: @id para tenant @tenant.', [
          '@id' => $alert->id(),
          '@tenant' => $tenantId,
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando item de alerta: @error', [
        '@error' => $e->getMessage(),
      ]);
      // No relanzar la excepcion para evitar reencolar indefinidamente.
    }
  }

}
