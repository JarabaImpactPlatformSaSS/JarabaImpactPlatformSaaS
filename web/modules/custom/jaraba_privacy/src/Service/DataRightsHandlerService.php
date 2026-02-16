<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_privacy\Entity\DataRightsRequest;
use Psr\Log\LoggerInterface;

/**
 * GESTOR DE DERECHOS ARCO-POL — DataRightsHandlerService.
 *
 * ESTRUCTURA:
 * Servicio que gestiona el ciclo de vida de las solicitudes de ejercicio
 * de derechos del interesado según RGPD Art. 15-22. Implementa el flujo
 * completo: recepción, verificación, procesamiento y respuesta.
 *
 * LÓGICA DE NEGOCIO:
 * - Las solicitudes tienen un plazo máximo de 30 días naturales.
 * - La identidad del solicitante debe verificarse antes de procesar.
 * - El DPO recibe notificación por email al crear cada solicitud.
 * - Se generan alertas automáticas a T-5d y T-2d del vencimiento.
 * - Cada solicitud mantiene un audit trail completo.
 *
 * RELACIONES:
 * - DataRightsHandlerService → TenantContextService (contexto tenant)
 * - DataRightsHandlerService → MailManagerInterface (notificaciones DPO)
 * - DataRightsHandlerService → ConfigFactoryInterface (plazos)
 * - DataRightsHandlerService ← PrivacyApiController (API REST)
 * - DataRightsHandlerService ← hook_cron() (verificación plazos)
 *
 * Spec: Doc 183 §6. Plan: FASE 3, Stack Compliance Legal N1.
 *
 * @package Drupal\jaraba_privacy\Service
 */
class DataRightsHandlerService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected ConfigFactoryInterface $configFactory,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Crea una nueva solicitud de ejercicio de derechos.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param string $requester_name
   *   Nombre del solicitante.
   * @param string $requester_email
   *   Email del solicitante.
   * @param string $right_type
   *   Tipo de derecho: access, rectification, erasure, restriction, portability, objection.
   * @param string $description
   *   Descripción de la solicitud.
   * @param string $verification_method
   *   Método de verificación de identidad: session, otp, document.
   *
   * @return \Drupal\jaraba_privacy\Entity\DataRightsRequest
   *   Solicitud creada.
   */
  public function createRequest(
    int $tenant_id,
    string $requester_name,
    string $requester_email,
    string $right_type,
    string $description,
    string $verification_method = 'session',
  ): DataRightsRequest {
    $config = $this->configFactory->get('jaraba_privacy.settings');
    $deadline_days = $config->get('arco_pol_deadline_days') ?? 30;

    $now = time();
    $deadline = $now + ($deadline_days * 86400);

    $storage = $this->entityTypeManager->getStorage('data_rights_request');

    /** @var \Drupal\jaraba_privacy\Entity\DataRightsRequest $request */
    $request = $storage->create([
      'tenant_id' => $tenant_id,
      'requester_name' => $requester_name,
      'requester_email' => $requester_email,
      'right_type' => $right_type,
      'description' => ['value' => $description, 'format' => 'plain_text'],
      'verification_method' => $verification_method,
      'identity_verified' => $verification_method === 'session',
      'received_at' => $now,
      'deadline' => $deadline,
      'status' => $verification_method === 'session' ? 'in_progress' : 'pending_verification',
    ]);

    $request->save();

    // Notificar al DPO.
    $this->notifyDpo($request);

    $this->logger->info('Solicitud ARCO-POL (@type) creada por @name para tenant @tenant. Plazo: @deadline.', [
      '@type' => $right_type,
      '@name' => $requester_name,
      '@tenant' => $tenant_id,
      '@deadline' => date('d/m/Y', $deadline),
    ]);

    return $request;
  }

  /**
   * Procesa una solicitud aprobada.
   *
   * @param int $request_id
   *   ID de la solicitud.
   * @param string $response_text
   *   Texto de respuesta al interesado.
   * @param int $handler_id
   *   ID del usuario que procesa (DPO).
   *
   * @return \Drupal\jaraba_privacy\Entity\DataRightsRequest
   *   Solicitud procesada.
   */
  public function processRequest(int $request_id, string $response_text, int $handler_id): DataRightsRequest {
    /** @var \Drupal\jaraba_privacy\Entity\DataRightsRequest $request */
    $request = $this->entityTypeManager->getStorage('data_rights_request')->load($request_id);

    if (!$request) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('La solicitud con ID @id no existe.', ['@id' => $request_id])
      );
    }

    $request->set('status', 'completed');
    $request->set('response', ['value' => $response_text, 'format' => 'plain_text']);
    $request->set('completed_at', time());
    $request->set('handler_id', $handler_id);
    $request->save();

    // Notificar al solicitante.
    $this->notifyRequester($request);

    $this->logger->info('Solicitud ARCO-POL #@id completada por usuario @handler.', [
      '@id' => $request_id,
      '@handler' => $handler_id,
    ]);

    return $request;
  }

  /**
   * Consulta el estado de una solicitud.
   *
   * @param int $request_id
   *   ID de la solicitud.
   *
   * @return array
   *   Datos de estado: status, days_remaining, handler, etc.
   */
  public function getRequestStatus(int $request_id): array {
    /** @var \Drupal\jaraba_privacy\Entity\DataRightsRequest $request */
    $request = $this->entityTypeManager->getStorage('data_rights_request')->load($request_id);

    if (!$request) {
      return ['error' => 'not_found'];
    }

    return [
      'id' => (int) $request->id(),
      'status' => $request->get('status')->value,
      'right_type' => $request->get('right_type')->value,
      'days_remaining' => $request->getDaysRemaining(),
      'identity_verified' => (bool) $request->get('identity_verified')->value,
      'received_at' => (int) $request->get('received_at')->value,
      'deadline' => (int) $request->get('deadline')->value,
      'completed_at' => $request->get('completed_at')->value,
    ];
  }

  /**
   * Verifica plazos próximos a vencer y genera alertas.
   *
   * Se ejecuta desde hook_cron(). Detecta solicitudes abiertas
   * a T-5d y T-2d del vencimiento y notifica al DPO.
   *
   * @return int
   *   Número de alertas generadas.
   */
  public function checkDeadlines(): int {
    $storage = $this->entityTypeManager->getStorage('data_rights_request');
    $now = time();
    $alerts = 0;

    // Solicitudes abiertas (no completadas, no rechazadas, no expiradas).
    $open_statuses = ['received', 'pending_verification', 'in_progress'];
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', $open_statuses, 'IN')
      ->execute();

    foreach ($storage->loadMultiple($ids) as $request) {
      $deadline = (int) $request->get('deadline')->value;
      $days_remaining = max(0, (int) ceil(($deadline - $now) / 86400));

      // Alerta a T-5d.
      if ($days_remaining === 5) {
        $this->notifyDpoDeadlineWarning($request, $days_remaining);
        $alerts++;
      }

      // Alerta urgente a T-2d.
      if ($days_remaining === 2) {
        $this->notifyDpoDeadlineWarning($request, $days_remaining);
        $alerts++;
      }

      // Marcar como expirada si plazo superado.
      if ($days_remaining === 0 && $request->get('status')->value !== 'completed') {
        $request->set('status', 'expired');
        $request->save();
        $this->notifyDpoDeadlineWarning($request, 0);
        $alerts++;
      }
    }

    if ($alerts > 0) {
      $this->logger->warning('@count alertas de plazo ARCO-POL generadas.', ['@count' => $alerts]);
    }

    return $alerts;
  }

  /**
   * Genera informe de solicitudes para auditoría.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param int|null $from_timestamp
   *   Desde (timestamp). NULL para todos.
   * @param int|null $to_timestamp
   *   Hasta (timestamp). NULL para ahora.
   *
   * @return array
   *   Informe con estadísticas y listado de solicitudes.
   */
  public function generateReport(int $tenant_id, ?int $from_timestamp = NULL, ?int $to_timestamp = NULL): array {
    $storage = $this->entityTypeManager->getStorage('data_rights_request');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id);

    if ($from_timestamp) {
      $query->condition('created', $from_timestamp, '>=');
    }
    if ($to_timestamp) {
      $query->condition('created', $to_timestamp, '<=');
    }

    $ids = $query->sort('created', 'DESC')->execute();
    $requests = $storage->loadMultiple($ids);

    // Estadísticas.
    $stats = [
      'total' => count($requests),
      'by_type' => [],
      'by_status' => [],
      'avg_resolution_days' => 0,
      'within_deadline' => 0,
      'expired' => 0,
    ];

    $total_resolution_days = 0;
    $resolved_count = 0;

    foreach ($requests as $request) {
      $type = $request->get('right_type')->value;
      $status = $request->get('status')->value;

      $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;
      $stats['by_status'][$status] = ($stats['by_status'][$status] ?? 0) + 1;

      if ($status === 'completed') {
        $received = (int) $request->get('received_at')->value;
        $completed = (int) $request->get('completed_at')->value;
        $resolution_days = ($completed - $received) / 86400;
        $total_resolution_days += $resolution_days;
        $resolved_count++;

        $deadline = (int) $request->get('deadline')->value;
        if ($completed <= $deadline) {
          $stats['within_deadline']++;
        }
      }
      elseif ($status === 'expired') {
        $stats['expired']++;
      }
    }

    if ($resolved_count > 0) {
      $stats['avg_resolution_days'] = round($total_resolution_days / $resolved_count, 1);
    }

    return $stats;
  }

  /**
   * Notifica al DPO sobre una nueva solicitud.
   */
  protected function notifyDpo(DataRightsRequest $request): void {
    $config = $this->configFactory->get('jaraba_privacy.settings');
    $dpo_email = $config->get('dpo_email');

    if (!$dpo_email) {
      $this->logger->warning('No se pudo notificar al DPO: email no configurado.');
      return;
    }

    $this->mailManager->mail(
      'jaraba_privacy',
      'arco_pol_new_request',
      $dpo_email,
      'es',
      [
        'request' => $request,
        'subject' => (string) new TranslatableMarkup('Nueva solicitud ARCO-POL: @type de @name', [
          '@type' => $request->get('right_type')->value,
          '@name' => $request->get('requester_name')->value,
        ]),
      ]
    );
  }

  /**
   * Notifica al solicitante sobre la resolución.
   */
  protected function notifyRequester(DataRightsRequest $request): void {
    $email = $request->get('requester_email')->value;
    if (!$email) {
      return;
    }

    $this->mailManager->mail(
      'jaraba_privacy',
      'arco_pol_resolved',
      $email,
      'es',
      [
        'request' => $request,
        'subject' => (string) new TranslatableMarkup('Su solicitud ARCO-POL ha sido procesada'),
      ]
    );
  }

  /**
   * Notifica al DPO sobre plazos próximos a vencer.
   */
  protected function notifyDpoDeadlineWarning(DataRightsRequest $request, int $days_remaining): void {
    $config = $this->configFactory->get('jaraba_privacy.settings');
    $dpo_email = $config->get('dpo_email');

    if (!$dpo_email) {
      return;
    }

    $urgency = $days_remaining <= 2 ? 'URGENTE' : 'Aviso';

    $this->mailManager->mail(
      'jaraba_privacy',
      'arco_pol_deadline_warning',
      $dpo_email,
      'es',
      [
        'request' => $request,
        'days_remaining' => $days_remaining,
        'subject' => (string) new TranslatableMarkup('[@urgency] Solicitud ARCO-POL #@id — @days días restantes', [
          '@urgency' => $urgency,
          '@id' => $request->id(),
          '@days' => $days_remaining,
        ]),
      ]
    );
  }

}
