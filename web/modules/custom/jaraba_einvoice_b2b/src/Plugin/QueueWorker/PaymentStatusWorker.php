<?php

declare(strict_types=1);

namespace Drupal\jaraba_einvoice_b2b\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\jaraba_einvoice_b2b\Service\EInvoicePaymentStatusService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Processes queued payment status SPFE communications.
 *
 * Handles batch communication of payment events to the SPFE as
 * required by Ley 18/2022 (Crea y Crece).
 *
 * @QueueWorker(
 *   id = "einvoice_payment_status",
 *   title = @Translation("E-Invoice Payment Status Communication"),
 *   cron = {"time" = 60}
 * )
 *
 * Spec: Doc 181, Section 3.4.
 * Plan: FASE 10 (ECA-EI-005).
 */
class PaymentStatusWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EInvoicePaymentStatusService $paymentService,
    protected LoggerInterface $logger,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('jaraba_einvoice_b2b.payment_status_service'),
      $container->get('logger.factory')->get('jaraba_einvoice_b2b'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    $eventId = $data['event_id'] ?? NULL;

    if (!$eventId) {
      $this->logger->error('PaymentStatusWorker: Missing event_id.');
      return;
    }

    $success = $this->paymentService->communicateToSPFE($eventId);

    $this->logger->info('PaymentStatusWorker: Event @id communication @status.', [
      '@id' => $eventId,
      '@status' => $success ? 'succeeded' : 'failed',
    ]);
  }

}
