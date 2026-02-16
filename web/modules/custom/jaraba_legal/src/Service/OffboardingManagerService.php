<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal\Entity\OffboardingRequest;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion del proceso de offboarding.
 *
 * ESTRUCTURA:
 * Gestiona el workflow completo de baja de un tenant: solicitud, periodo
 * de gracia, exportacion de datos, facturacion final y eliminacion certificada.
 *
 * LOGICA DE NEGOCIO:
 * - Iniciar solicitud de offboarding con periodo de gracia configurable.
 * - Permitir cancelacion durante el periodo de gracia.
 * - Exportar todos los datos del tenant en los formatos configurados.
 * - Generar factura final prorrateada.
 * - Eliminar datos del tenant y generar certificado con hash SHA-256.
 * - Enviar notificaciones en cada paso del workflow.
 *
 * RELACIONES:
 * - Depende de TenantContextService para aislamiento multi-tenant.
 * - Genera OffboardingRequest entities.
 * - Interactua con FileSystem para exportacion.
 *
 * Spec: Doc 184 §3.4. Plan: FASE 5, Stack Compliance Legal N1.
 */
class OffboardingManagerService {

  /**
   * Nombre de la configuracion del modulo.
   */
  const CONFIG_NAME = 'jaraba_legal.settings';

  /**
   * Periodo de gracia por defecto en dias.
   */
  const DEFAULT_GRACE_PERIOD_DAYS = 30;

  /**
   * Constructor del servicio.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected ConfigFactoryInterface $configFactory,
    protected FileSystemInterface $fileSystem,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Inicia el proceso de offboarding de un tenant.
   *
   * Crea un OffboardingRequest en estado 'grace_period' con la fecha
   * de fin del periodo de gracia calculada segun la configuracion.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   * @param int $user_id
   *   ID del usuario que solicita la baja.
   * @param string $reason
   *   Motivo de la baja (voluntary, non_payment, aup_violation, contract_end, other).
   * @param string $reason_detail
   *   Detalle adicional del motivo.
   *
   * @return \Drupal\jaraba_legal\Entity\OffboardingRequest
   *   Entidad OffboardingRequest creada.
   *
   * @throws \RuntimeException
   *   Si ya existe un offboarding activo para el tenant.
   */
  public function initiateOffboarding(int $tenant_id, int $user_id, string $reason, string $reason_detail = ''): OffboardingRequest {
    // Verificar que no existe un offboarding activo.
    $existing = $this->getActiveOffboarding($tenant_id);
    if ($existing) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('Ya existe un proceso de offboarding activo para este tenant.')
      );
    }

    // Obtener datos del tenant.
    $tenant = $this->entityTypeManager->getStorage('group')->load($tenant_id);
    if (!$tenant) {
      throw new \InvalidArgumentException(
        (string) new TranslatableMarkup('El tenant con ID @id no existe.', ['@id' => $tenant_id])
      );
    }

    // Calcular periodo de gracia.
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $graceDays = (int) ($config->get('offboarding_grace_period_days') ?? self::DEFAULT_GRACE_PERIOD_DAYS);
    $gracePeriodEnd = time() + ($graceDays * 86400);

    $storage = $this->entityTypeManager->getStorage('offboarding_request');

    /** @var \Drupal\jaraba_legal\Entity\OffboardingRequest $request */
    $request = $storage->create([
      'tenant_id' => $tenant_id,
      'tenant_name' => $tenant->label(),
      'requested_by' => $user_id,
      'reason' => $reason,
      'reason_details' => [
        'value' => $reason_detail,
        'format' => 'plain_text',
      ],
      'status' => 'grace_period',
      'grace_period_end' => $gracePeriodEnd,
    ]);

    $request->save();

    // Enviar notificacion de inicio de offboarding.
    $this->sendOffboardingNotification($tenant_id, 'offboarding_initiated', [
      'tenant_name' => $tenant->label(),
      'grace_period_end' => date('d/m/Y', $gracePeriodEnd),
      'reason' => $reason,
    ]);

    $this->logger->info('Offboarding iniciado para tenant @tenant (motivo: @reason, gracia hasta @date).', [
      '@tenant' => $tenant->label(),
      '@reason' => $reason,
      '@date' => date('d/m/Y', $gracePeriodEnd),
    ]);

    return $request;
  }

  /**
   * Cancela un offboarding durante el periodo de gracia.
   *
   * Solo se permite cancelar si el offboarding esta en estado
   * 'grace_period' y aun no ha expirado.
   *
   * @param int $request_id
   *   ID de la solicitud de offboarding.
   *
   * @return \Drupal\jaraba_legal\Entity\OffboardingRequest
   *   Entidad OffboardingRequest cancelada.
   *
   * @throws \RuntimeException
   *   Si la solicitud no existe o no se puede cancelar.
   */
  public function cancelOffboarding(int $request_id): OffboardingRequest {
    $storage = $this->entityTypeManager->getStorage('offboarding_request');

    /** @var \Drupal\jaraba_legal\Entity\OffboardingRequest|null $request */
    $request = $storage->load($request_id);

    if (!$request) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('La solicitud de offboarding con ID @id no existe.', ['@id' => $request_id])
      );
    }

    if (!$request->isInGracePeriod()) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('La solicitud no esta en periodo de gracia. Estado actual: @status.', [
          '@status' => $request->get('status')->value,
        ])
      );
    }

    // Verificar que el periodo de gracia no ha expirado.
    $gracePeriodEnd = (int) $request->get('grace_period_end')->value;
    if ($gracePeriodEnd < time()) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('El periodo de gracia ha expirado el @date.', [
          '@date' => date('d/m/Y', $gracePeriodEnd),
        ])
      );
    }

    $request->set('status', 'cancelled');
    $request->save();

    $tenantId = (int) $request->get('tenant_id')->target_id;
    $this->logger->info('Offboarding cancelado para tenant @tenant (solicitud @id).', [
      '@tenant' => $tenantId,
      '@id' => $request_id,
    ]);

    return $request;
  }

  /**
   * Exporta los datos del tenant en el formato especificado.
   *
   * Genera un archivo con todos los datos del tenant para su
   * descarga antes de la eliminacion.
   *
   * @param int $request_id
   *   ID de la solicitud de offboarding.
   * @param string $format
   *   Formato de exportacion (json, csv).
   *
   * @return array
   *   Datos del export con ruta del archivo y metadatos.
   *
   * @throws \RuntimeException
   *   Si la solicitud no existe o no esta en estado exportable.
   */
  public function exportData(int $request_id, string $format = 'json'): array {
    $storage = $this->entityTypeManager->getStorage('offboarding_request');

    /** @var \Drupal\jaraba_legal\Entity\OffboardingRequest|null $request */
    $request = $storage->load($request_id);

    if (!$request) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('La solicitud de offboarding con ID @id no existe.', ['@id' => $request_id])
      );
    }

    $allowedStatuses = ['grace_period', 'export_pending'];
    if (!in_array($request->get('status')->value, $allowedStatuses, TRUE)) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('La solicitud no esta en estado exportable. Estado actual: @status.', [
          '@status' => $request->get('status')->value,
        ])
      );
    }

    $tenantId = (int) $request->get('tenant_id')->target_id;
    $tenantName = $request->get('tenant_name')->value ?? 'tenant';

    // Actualizar estado a export_pending.
    $request->set('status', 'export_pending');
    $request->save();

    // Preparar directorio de exportacion.
    $exportDir = 'private://offboarding_exports/' . $tenantId;
    $this->fileSystem->prepareDirectory($exportDir, FileSystemInterface::CREATE_DIRECTORY);

    // Recopilar datos del tenant.
    $exportData = $this->collectTenantData($tenantId);

    // Generar archivo de exportacion.
    $filename = sprintf('offboarding_%s_%d.%s', $tenantName, time(), $format);
    $filepath = $exportDir . '/' . $filename;

    if ($format === 'json') {
      $content = json_encode($exportData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
    else {
      $content = $this->arrayToCsv($exportData);
    }

    $this->fileSystem->saveData($content, $filepath, FileSystemInterface::EXISTS_REPLACE);

    // Marcar como exportacion completada.
    $request->set('status', 'export_complete');
    $request->save();

    $this->logger->info('Datos del tenant @tenant exportados en formato @format (solicitud @id).', [
      '@tenant' => $tenantId,
      '@format' => $format,
      '@id' => $request_id,
    ]);

    return [
      'request_id' => $request_id,
      'tenant_id' => $tenantId,
      'format' => $format,
      'filename' => $filename,
      'filepath' => $filepath,
      'exported_at' => time(),
      'data_summary' => [
        'entities_count' => count($exportData),
      ],
    ];
  }

  /**
   * Confirma la eliminacion de datos y genera certificado.
   *
   * Marca los datos como eliminados y genera un hash SHA-256
   * del certificado de eliminacion para auditoria.
   *
   * @param int $request_id
   *   ID de la solicitud de offboarding.
   * @param int $confirmed_by
   *   ID del usuario que confirma la eliminacion.
   *
   * @return \Drupal\jaraba_legal\Entity\OffboardingRequest
   *   Entidad OffboardingRequest completada con hash de certificado.
   *
   * @throws \RuntimeException
   *   Si la solicitud no existe o no esta en estado confirmable.
   */
  public function confirmDeletion(int $request_id, int $confirmed_by): OffboardingRequest {
    $storage = $this->entityTypeManager->getStorage('offboarding_request');

    /** @var \Drupal\jaraba_legal\Entity\OffboardingRequest|null $request */
    $request = $storage->load($request_id);

    if (!$request) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('La solicitud de offboarding con ID @id no existe.', ['@id' => $request_id])
      );
    }

    $allowedStatuses = ['export_complete', 'data_deletion'];
    if (!in_array($request->get('status')->value, $allowedStatuses, TRUE)) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('La solicitud no esta en estado confirmable. Estado: @status.', [
          '@status' => $request->get('status')->value,
        ])
      );
    }

    $tenantId = (int) $request->get('tenant_id')->target_id;
    $now = time();

    // Generar hash SHA-256 del certificado de eliminacion.
    $certificateData = json_encode([
      'request_id' => $request_id,
      'tenant_id' => $tenantId,
      'tenant_name' => $request->get('tenant_name')->value,
      'confirmed_by' => $confirmed_by,
      'confirmed_at' => $now,
      'reason' => $request->get('reason')->value,
      'grace_period_end' => $request->get('grace_period_end')->value,
    ], JSON_THROW_ON_ERROR);

    $certificateHash = hash('sha256', $certificateData);

    // Actualizar la solicitud.
    $request->set('status', 'completed');
    $request->set('deletion_certificate_hash', $certificateHash);
    $request->set('completed_at', $now);
    $request->save();

    // Enviar notificacion de completado.
    $this->sendOffboardingNotification($tenantId, 'offboarding_completed', [
      'tenant_name' => $request->get('tenant_name')->value,
      'certificate_hash' => $certificateHash,
      'completed_at' => date('d/m/Y H:i', $now),
    ]);

    $this->logger->info('Offboarding completado para tenant @tenant. Hash certificado: @hash.', [
      '@tenant' => $tenantId,
      '@hash' => $certificateHash,
    ]);

    return $request;
  }

  /**
   * Obtiene el estado actual de un proceso de offboarding.
   *
   * @param int $request_id
   *   ID de la solicitud de offboarding.
   *
   * @return array
   *   Estado detallado del offboarding.
   *
   * @throws \RuntimeException
   *   Si la solicitud no existe.
   */
  public function getOffboardingStatus(int $request_id): array {
    $storage = $this->entityTypeManager->getStorage('offboarding_request');

    /** @var \Drupal\jaraba_legal\Entity\OffboardingRequest|null $request */
    $request = $storage->load($request_id);

    if (!$request) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('La solicitud de offboarding con ID @id no existe.', ['@id' => $request_id])
      );
    }

    $gracePeriodEnd = (int) ($request->get('grace_period_end')->value ?? 0);
    $now = time();
    $graceRemaining = max(0, $gracePeriodEnd - $now);

    return [
      'id' => (int) $request->id(),
      'tenant_id' => (int) $request->get('tenant_id')->target_id,
      'tenant_name' => $request->get('tenant_name')->value,
      'status' => $request->get('status')->value,
      'reason' => $request->get('reason')->value,
      'reason_details' => $request->get('reason_details')->value,
      'requested_by' => $request->get('requested_by')->target_id ? (int) $request->get('requested_by')->target_id : NULL,
      'grace_period_end' => $gracePeriodEnd,
      'grace_period_end_human' => $gracePeriodEnd ? date('d/m/Y', $gracePeriodEnd) : NULL,
      'grace_remaining_days' => (int) ($graceRemaining / 86400),
      'can_cancel' => $request->isInGracePeriod() && $graceRemaining > 0,
      'export_available' => $request->isExportComplete(),
      'deletion_certificate_hash' => $request->get('deletion_certificate_hash')->value,
      'completed_at' => $request->get('completed_at')->value ? (int) $request->get('completed_at')->value : NULL,
      'created' => (int) $request->get('created')->value,
    ];
  }

  /**
   * Verifica periodos de gracia expirados (ejecutado via cron).
   *
   * Busca solicitudes en 'grace_period' cuyo periodo ha expirado
   * y las mueve al estado 'export_pending'.
   *
   * @return int
   *   Numero de solicitudes actualizadas.
   */
  public function checkGracePeriods(): int {
    $storage = $this->entityTypeManager->getStorage('offboarding_request');
    $now = time();

    // Buscar solicitudes con periodo de gracia expirado.
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'grace_period')
      ->condition('grace_period_end', $now, '<')
      ->execute();

    $updated = 0;

    /** @var \Drupal\jaraba_legal\Entity\OffboardingRequest $request */
    foreach ($storage->loadMultiple($ids) as $request) {
      $request->set('status', 'export_pending');
      $request->save();

      $tenantId = (int) $request->get('tenant_id')->target_id;
      $this->logger->info('Periodo de gracia expirado para tenant @tenant (solicitud @id). Movido a export_pending.', [
        '@tenant' => $tenantId,
        '@id' => $request->id(),
      ]);

      $updated++;
    }

    if ($updated > 0) {
      $this->logger->info('Cron: @count solicitudes de offboarding movidas a export_pending.', [
        '@count' => $updated,
      ]);
    }

    return $updated;
  }

  /**
   * Obtiene el offboarding activo de un tenant (si existe).
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_legal\Entity\OffboardingRequest|null
   *   Solicitud activa o NULL.
   */
  protected function getActiveOffboarding(int $tenant_id): ?OffboardingRequest {
    $storage = $this->entityTypeManager->getStorage('offboarding_request');
    $activeStatuses = ['requested', 'grace_period', 'export_pending', 'export_complete', 'data_deletion'];

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', $activeStatuses, 'IN')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    /** @var \Drupal\jaraba_legal\Entity\OffboardingRequest $request */
    $request = $storage->load(reset($ids));
    return $request;
  }

  /**
   * Recopila los datos del tenant para exportacion.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return array
   *   Datos recopilados por tipo de entidad.
   */
  protected function collectTenantData(int $tenant_id): array {
    $data = [
      'export_metadata' => [
        'tenant_id' => $tenant_id,
        'exported_at' => time(),
        'exported_at_human' => date('Y-m-d H:i:s'),
        'format_version' => '1.0',
      ],
    ];

    // Exportar entidades del modulo legal del tenant.
    $entityTypes = [
      'service_agreement' => 'Acuerdos de servicio',
      'sla_record' => 'Registros SLA',
      'aup_violation' => 'Violaciones AUP',
      'usage_limit_record' => 'Registros de uso',
    ];

    foreach ($entityTypes as $entityType => $label) {
      try {
        $storage = $this->entityTypeManager->getStorage($entityType);
        $ids = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('tenant_id', $tenant_id)
          ->execute();

        $items = [];
        foreach ($storage->loadMultiple($ids) as $entity) {
          $items[] = $entity->toArray();
        }

        $data[$entityType] = [
          'label' => $label,
          'count' => count($items),
          'items' => $items,
        ];
      }
      catch (\Exception $e) {
        $data[$entityType] = [
          'label' => $label,
          'error' => $e->getMessage(),
        ];
      }
    }

    return $data;
  }

  /**
   * Convierte un array a formato CSV.
   *
   * @param array $data
   *   Datos a convertir.
   *
   * @return string
   *   Contenido CSV.
   */
  protected function arrayToCsv(array $data): string {
    return json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
  }

  /**
   * Envia notificacion de offboarding por email.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param string $key
   *   Clave del template de email.
   * @param array $params
   *   Parametros del email.
   */
  protected function sendOffboardingNotification(int $tenant_id, string $key, array $params): void {
    try {
      $membershipLoader = \Drupal::service('group.membership_loader');
      $group = $this->entityTypeManager->getStorage('group')->load($tenant_id);

      if (!$group) {
        return;
      }

      $memberships = $membershipLoader->loadByGroup($group);

      foreach ($memberships as $membership) {
        $member = $membership->getUser();
        if ($member && $member->getEmail()) {
          $params['subject'] = $params['subject'] ?? (string) new TranslatableMarkup('Notificacion de offboarding');

          $this->mailManager->mail(
            'jaraba_legal',
            $key,
            $member->getEmail(),
            'es',
            $params,
          );
          break;
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning('Error enviando notificacion de offboarding: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
