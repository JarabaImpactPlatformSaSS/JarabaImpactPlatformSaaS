<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_export\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

/**
 * Recopila datos de un tenant por secciones para exportación.
 *
 * Patrón basado en GdprCommands.php: try/catch por entity type,
 * graceful degradation si un módulo no está instalado.
 */
class TenantDataCollectorService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected Connection $database,
    protected FileSystemInterface $fileSystem,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Recopila todos los datos de las secciones solicitadas.
   *
   * @param int $groupId
   *   ID del grupo (tenant).
   * @param int $tenantEntityId
   *   ID de la entidad tenant.
   * @param array $sections
   *   Secciones a exportar.
   * @param callable|null $progressCallback
   *   Callback para actualizar progreso: fn(int $percent, string $phase).
   *
   * @return array
   *   Datos recopilados indexados por sección.
   */
  public function collectAll(int $groupId, int $tenantEntityId, array $sections, ?callable $progressCallback = NULL): array {
    $data = [];
    $sectionMethods = [
      'core' => 'collectCoreData',
      'analytics' => 'collectAnalyticsData',
      'knowledge' => 'collectKnowledgeData',
      'operational' => 'collectOperationalData',
      'vertical' => 'collectVerticalData',
      'files' => 'collectFiles',
    ];

    $totalSections = count($sections);
    $completed = 0;

    foreach ($sections as $section) {
      if (!isset($sectionMethods[$section])) {
        continue;
      }

      $method = $sectionMethods[$section];
      if ($progressCallback) {
        $percent = $totalSections > 0 ? (int) (($completed / $totalSections) * 100) : 0;
        $progressCallback($percent, $section);
      }

      try {
        $data[$section] = $this->{$method}($groupId, $tenantEntityId);
      }
      catch (\Exception $e) {
        $this->logger->warning('Error collecting @section for tenant @tid: @msg', [
          '@section' => $section,
          '@tid' => $groupId,
          '@msg' => $e->getMessage(),
        ]);
        $data[$section] = ['_error' => $e->getMessage()];
      }

      $completed++;
    }

    if ($progressCallback) {
      $progressCallback(100, 'complete');
    }

    return $data;
  }

  /**
   * Grupo A — Datos principales del tenant.
   */
  public function collectCoreData(int $groupId, int $tenantEntityId): array {
    $core = [];

    // Tenant entity.
    try {
      $tenant = $this->entityTypeManager->getStorage('tenant')->load($tenantEntityId);
      if ($tenant) {
        $core['tenant'] = $this->entityToArray($tenant);
      }
    }
    catch (\Exception $e) {
      $this->logger->info('Tenant export: tenant entity not available: @msg', ['@msg' => $e->getMessage()]);
    }

    // Billing customer.
    $core['billing_customers'] = $this->collectByCondition('billing_customer', 'tenant_id', $groupId);

    // Billing invoices.
    $core['billing_invoices'] = $this->collectByCondition('billing_invoice', 'tenant_id', $groupId);

    // Billing payment methods.
    $core['billing_payment_methods'] = $this->collectByCondition('billing_payment_method', 'tenant_id', $groupId);

    // WhiteLabel config.
    $core['whitelabel_config'] = $this->collectByCondition('whitelabel_config', 'tenant_id', $groupId);

    // VeriFactu tenant config.
    $core['verifactu_tenant_config'] = $this->collectByCondition('verifactu_tenant_config', 'tenant_id', $groupId);

    // VeriFactu invoice records.
    $core['verifactu_invoice_records'] = $this->collectByCondition('verifactu_invoice_record', 'tenant_id', $groupId);

    return $core;
  }

  /**
   * Grupo B — Datos de analytics.
   */
  public function collectAnalyticsData(int $groupId, int $tenantEntityId): array {
    $analytics = [];
    $config = \Drupal::config('jaraba_tenant_export.settings');
    $rowLimit = (int) ($config->get('analytics_row_limit') ?? 50000);

    // Analytics events (generator, limited).
    try {
      $query = $this->database->select('analytics_event', 'ae')
        ->fields('ae')
        ->condition('ae.tenant_id', $tenantEntityId)
        ->orderBy('ae.created', 'DESC')
        ->range(0, $rowLimit);

      $result = $query->execute();
      $events = [];
      foreach ($result as $row) {
        $events[] = (array) $row;
      }
      $analytics['events'] = $events;
      $analytics['events_truncated'] = count($events) >= $rowLimit;
    }
    catch (\Exception $e) {
      $this->logger->info('Tenant export: analytics_event not available: @msg', ['@msg' => $e->getMessage()]);
    }

    // Analytics daily.
    $analytics['analytics_daily'] = $this->collectByCondition('analytics_daily', 'tenant_id', $tenantEntityId);

    // Funnel definitions.
    $analytics['funnel_definitions'] = $this->collectByCondition('funnel_definition', 'tenant_id', $tenantEntityId);

    // Cohort definitions.
    $analytics['cohort_definitions'] = $this->collectByCondition('cohort_definition', 'tenant_id', $tenantEntityId);

    // Custom reports.
    $analytics['custom_reports'] = $this->collectByCondition('custom_report', 'tenant_id', $tenantEntityId);

    return $analytics;
  }

  /**
   * Grupo C — Base de conocimiento.
   */
  public function collectKnowledgeData(int $groupId, int $tenantEntityId): array {
    $knowledge = [];

    $knowledge['tenant_documents'] = $this->collectByCondition('tenant_document', 'tenant_id', $tenantEntityId);
    $knowledge['kb_articles'] = $this->collectByCondition('kb_article', 'tenant_id', $tenantEntityId);
    $knowledge['kb_categories'] = $this->collectByCondition('kb_category', 'tenant_id', $tenantEntityId);
    $knowledge['tenant_faqs'] = $this->collectByCondition('tenant_faq', 'tenant_id', $tenantEntityId);
    $knowledge['tenant_policies'] = $this->collectByCondition('tenant_policy', 'tenant_id', $tenantEntityId);
    $knowledge['tenant_knowledge_config'] = $this->collectByCondition('tenant_knowledge_config', 'tenant_id', $tenantEntityId);

    return $knowledge;
  }

  /**
   * Grupo D — Datos operacionales.
   */
  public function collectOperationalData(int $groupId, int $tenantEntityId): array {
    $operational = [];

    $operational['audit_log'] = $this->collectByCondition('audit_log', 'tenant_id', $groupId, 1000);
    $operational['email_campaigns'] = $this->collectByCondition('email_campaign', 'tenant_id', $tenantEntityId);
    $operational['email_lists'] = $this->collectByCondition('email_list', 'tenant_id', $tenantEntityId);
    $operational['email_subscribers'] = $this->collectByCondition('email_subscriber', 'tenant_id', $tenantEntityId);
    $operational['crm_contacts'] = $this->collectByCondition('crm_contact', 'tenant_id', $tenantEntityId);
    $operational['crm_companies'] = $this->collectByCondition('crm_company', 'tenant_id', $tenantEntityId);
    $operational['site_configs'] = $this->collectByCondition('site_config', 'tenant_id', $tenantEntityId);

    return $operational;
  }

  /**
   * Grupo E — Datos verticales (si módulos instalados).
   */
  public function collectVerticalData(int $groupId, int $tenantEntityId): array {
    $vertical = [];

    $vertical['product_agro'] = $this->collectByCondition('product_agro', 'tenant_id', $groupId);
    $vertical['producer_profiles'] = $this->collectByCondition('producer_profile', 'tenant_id', $groupId);

    return $vertical;
  }

  /**
   * Grupo F — Archivos del tenant.
   *
   * Patrón TenantContextService::calculateStorageMetrics: resuelve UIDs
   * de miembros del grupo y recopila sus archivos.
   */
  public function collectFiles(int $groupId, int $tenantEntityId): array {
    $files = [];

    try {
      // Obtener UIDs de miembros del grupo.
      $membershipStorage = $this->entityTypeManager->getStorage('group_content');
      $membershipIds = $membershipStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('gid', $groupId)
        ->condition('type', '%group_membership', 'LIKE')
        ->execute();

      $uids = [];
      if (!empty($membershipIds)) {
        $memberships = $membershipStorage->loadMultiple($membershipIds);
        foreach ($memberships as $membership) {
          $entityId = $membership->get('entity_id')->target_id;
          if ($entityId) {
            $uids[] = (int) $entityId;
          }
        }
      }

      if (empty($uids)) {
        return ['index' => [], 'total_size' => 0];
      }

      // Recopilar archivos propiedad de los miembros.
      $fileStorage = $this->entityTypeManager->getStorage('file');
      $fileIds = $fileStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uids, 'IN')
        ->execute();

      if (!empty($fileIds)) {
        $fileEntities = $fileStorage->loadMultiple($fileIds);
        $totalSize = 0;
        foreach ($fileEntities as $file) {
          $uri = $file->getFileUri();
          $size = (int) $file->getSize();
          $totalSize += $size;
          $files['index'][] = [
            'fid' => (int) $file->id(),
            'filename' => $file->getFilename(),
            'uri' => $uri,
            'size' => $size,
            'mime' => $file->getMimeType(),
            'created' => date('c', (int) $file->getCreatedTime()),
          ];
        }
        $files['total_size'] = $totalSize;
      }
      else {
        $files['index'] = [];
        $files['total_size'] = 0;
      }
    }
    catch (\Exception $e) {
      $this->logger->info('Tenant export: file collection failed: @msg', ['@msg' => $e->getMessage()]);
      $files['index'] = [];
      $files['total_size'] = 0;
    }

    return $files;
  }

  /**
   * Descubre dinámicamente qué secciones están disponibles.
   *
   * @return array
   *   Array de secciones con label, description, available.
   */
  public function getAvailableSections(): array {
    $sections = [
      'core' => [
        'label' => (string) t('Datos Principales'),
        'description' => (string) t('Tenant, facturación, métodos de pago, configuración WhiteLabel, VeriFactu.'),
        'available' => TRUE,
      ],
      'analytics' => [
        'label' => (string) t('Analytics'),
        'description' => (string) t('Eventos, métricas diarias, funnels, cohortes, informes personalizados.'),
        'available' => $this->entityTypeExists('analytics_event') || $this->tableExists('analytics_event'),
      ],
      'knowledge' => [
        'label' => (string) t('Base de Conocimiento'),
        'description' => (string) t('Documentos, artículos KB, categorías, FAQs, políticas.'),
        'available' => $this->entityTypeExists('tenant_document'),
      ],
      'operational' => [
        'label' => (string) t('Operacional'),
        'description' => (string) t('Registro de auditoría, campañas email, CRM.'),
        'available' => TRUE,
      ],
      'vertical' => [
        'label' => (string) t('Vertical (Agro)'),
        'description' => (string) t('Productos agrícolas, perfiles de productores.'),
        'available' => $this->entityTypeExists('product_agro'),
      ],
      'files' => [
        'label' => (string) t('Archivos'),
        'description' => (string) t('Todos los archivos subidos por miembros del tenant.'),
        'available' => TRUE,
      ],
    ];

    return $sections;
  }

  /**
   * Recopila entidades por una condición simple.
   */
  protected function collectByCondition(string $entityTypeId, string $field, int|string $value, int $limit = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage($entityTypeId);
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition($field, $value);

      if ($limit > 0) {
        $query->range(0, $limit);
      }

      $ids = $query->execute();

      if (empty($ids)) {
        return [];
      }

      $entities = $storage->loadMultiple($ids);
      $result = [];
      foreach ($entities as $entity) {
        $result[] = $this->entityToArray($entity);
      }
      return $result;
    }
    catch (\Exception $e) {
      // Entity type may not be installed — graceful degradation.
      return [];
    }
  }

  /**
   * Convierte una entidad a array serializable.
   */
  protected function entityToArray(object $entity): array {
    $data = [
      'id' => $entity->id(),
      'uuid' => method_exists($entity, 'uuid') ? $entity->uuid() : NULL,
    ];

    if (method_exists($entity, 'getFields')) {
      foreach ($entity->getFields() as $fieldName => $field) {
        if (in_array($fieldName, ['id', 'uuid'], TRUE)) {
          continue;
        }
        $values = $field->getValue();
        $data[$fieldName] = count($values) === 1 ? reset($values) : $values;
      }
    }

    return $data;
  }

  /**
   * Comprueba si un entity type existe.
   */
  protected function entityTypeExists(string $entityTypeId): bool {
    try {
      return $this->entityTypeManager->hasDefinition($entityTypeId);
    }
    catch (\Exception) {
      return FALSE;
    }
  }

  /**
   * Comprueba si una tabla existe en la base de datos.
   */
  protected function tableExists(string $table): bool {
    return $this->database->schema()->tableExists($table);
  }

}
