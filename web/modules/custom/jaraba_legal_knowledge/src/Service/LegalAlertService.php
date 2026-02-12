<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de alertas de cambios normativos.
 *
 * Detecta cambios en normas (nuevas, modificadas, derogadas),
 * crea alertas por tenant y envia notificaciones por email.
 *
 * ARQUITECTURA:
 * - Deteccion de cambios comparando con normas existentes.
 * - Entidades NormChangeAlert con ciclo: pending -> sent / dismissed.
 * - Notificacion por email via MailManagerInterface.
 * - Multi-tenant: alertas filtradas por tenant_id.
 * - Severidad: info, warning, critical.
 */
class LegalAlertService {

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
   *   Gestor de envio de emails.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto del tenant actual.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected TenantContextService $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Detecta cambios entre datos nuevos de normas y las existentes.
   *
   * Compara una lista de normas obtenidas del BOE con las almacenadas
   * en el sistema para determinar si son nuevas, modificadas o derogadas.
   *
   * @param array $newNormData
   *   Array de datos de normas obtenidos del BOE. Cada elemento contiene:
   *   - boe_id: (string) Identificador BOE.
   *   - title: (string) Titulo de la norma.
   *   - status: (string) Estado actual (vigente, derogada, modificada).
   *   - subject_areas: (array) Areas tematicas.
   *
   * @return array
   *   Array de cambios detectados. Cada elemento contiene:
   *   - boe_id: (string) Identificador BOE.
   *   - title: (string) Titulo de la norma.
   *   - change_type: (string) 'nueva', 'modificacion' o 'derogacion'.
   *   - subject_areas: (array) Areas tematicas afectadas.
   */
  public function detectChanges(array $newNormData): array {
    $changes = [];

    try {
      $normStorage = $this->entityTypeManager->getStorage('legal_norm');

      foreach ($newNormData as $normData) {
        $boeId = $normData['boe_id'] ?? '';
        if (empty($boeId)) {
          continue;
        }

        // Buscar norma existente.
        $existingIds = $normStorage->getQuery()
          ->accessCheck(FALSE)
          ->condition('boe_id', $boeId)
          ->range(0, 1)
          ->execute();

        if (empty($existingIds)) {
          // Norma nueva.
          $changes[] = [
            'boe_id' => $boeId,
            'title' => $normData['title'] ?? $boeId,
            'change_type' => 'nueva',
            'subject_areas' => $normData['subject_areas'] ?? [],
          ];
        }
        else {
          // Norma existente: verificar cambios.
          $existing = $normStorage->load(reset($existingIds));
          $existingStatus = $existing->get('status')->value ?? 'vigente';
          $newStatus = $normData['status'] ?? 'vigente';

          if ($newStatus === 'derogada' && $existingStatus !== 'derogada') {
            $changes[] = [
              'boe_id' => $boeId,
              'title' => $normData['title'] ?? $existing->label(),
              'change_type' => 'derogacion',
              'subject_areas' => $normData['subject_areas'] ?? [],
            ];
          }
          elseif ($newStatus !== $existingStatus) {
            $changes[] = [
              'boe_id' => $boeId,
              'title' => $normData['title'] ?? $existing->label(),
              'change_type' => 'modificacion',
              'subject_areas' => $normData['subject_areas'] ?? [],
            ];
          }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error detectando cambios normativos: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $changes;
  }

  /**
   * Crea una alerta de cambio normativo para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant afectado.
   * @param int $normId
   *   ID de la entidad LegalNorm.
   * @param string $changeType
   *   Tipo de cambio: 'nueva', 'modificacion', 'derogacion'.
   * @param string $summary
   *   Resumen del cambio.
   * @param array $areas
   *   Areas tematicas afectadas.
   * @param string $severity
   *   Severidad: 'info', 'warning', 'critical'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   La entidad NormChangeAlert creada.
   */
  public function createAlert(int $tenantId, int $normId, string $changeType, string $summary, array $areas, string $severity) {
    $storage = $this->entityTypeManager->getStorage('norm_change_alert');

    $alert = $storage->create([
      'tenant_id' => $tenantId,
      'norm_id' => $normId,
      'change_type' => $changeType,
      'summary' => $summary,
      'subject_areas' => $areas,
      'severity' => $severity,
      'status' => 'pending',
      'created' => \Drupal::time()->getRequestTime(),
    ]);
    $alert->save();

    $this->logger->info('Alerta creada para tenant @tenant: @type en norma @norm (@severity).', [
      '@tenant' => $tenantId,
      '@type' => $changeType,
      '@norm' => $normId,
      '@severity' => $severity,
    ]);

    return $alert;
  }

  /**
   * Envia todas las alertas pendientes por email.
   *
   * Carga alertas con status 'pending', agrupa por tenant,
   * envia un email consolidado por tenant y actualiza status a 'sent'.
   *
   * @return int
   *   Numero de alertas enviadas.
   */
  public function sendPendingAlerts(): int {
    $sentCount = 0;

    try {
      $alertStorage = $this->entityTypeManager->getStorage('norm_change_alert');
      $pendingIds = $alertStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'pending')
        ->sort('created', 'ASC')
        ->execute();

      if (empty($pendingIds)) {
        return 0;
      }

      $alerts = $alertStorage->loadMultiple($pendingIds);

      // Agrupar alertas por tenant.
      $alertsByTenant = [];
      foreach ($alerts as $alert) {
        $tenantId = (int) $alert->get('tenant_id')->value;
        $alertsByTenant[$tenantId][] = $alert;
      }

      // Enviar emails agrupados por tenant.
      foreach ($alertsByTenant as $tenantId => $tenantAlerts) {
        try {
          $this->sendAlertEmail($tenantId, $tenantAlerts);

          // Marcar como enviadas.
          foreach ($tenantAlerts as $alert) {
            $alert->set('status', 'sent');
            $alert->set('sent_at', \Drupal::time()->getRequestTime());
            $alert->save();
            $sentCount++;
          }
        }
        catch (\Exception $e) {
          $this->logger->error('Error enviando alertas para tenant @tenant: @error', [
            '@tenant' => $tenantId,
            '@error' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('Alertas pendientes enviadas: @count de @total.', [
        '@count' => $sentCount,
        '@total' => count($pendingIds),
      ]);
    }
    catch (\Exception $e) {
      $this->logger->error('Error procesando alertas pendientes: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $sentCount;
  }

  /**
   * Obtiene alertas recientes para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param int $limit
   *   Numero maximo de alertas a retornar.
   *
   * @return array
   *   Array de alertas con: id, change_type, summary, severity,
   *   status, created, subject_areas.
   */
  public function getAlertsForTenant(int $tenantId, int $limit = 20): array {
    try {
      $storage = $this->entityTypeManager->getStorage('norm_change_alert');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $alerts = $storage->loadMultiple($ids);
      $results = [];

      foreach ($alerts as $alert) {
        $results[] = [
          'id' => (int) $alert->id(),
          'norm_id' => (int) $alert->get('norm_id')->value,
          'change_type' => $alert->get('change_type')->value,
          'summary' => $alert->get('summary')->value,
          'severity' => $alert->get('severity')->value,
          'status' => $alert->get('status')->value,
          'subject_areas' => $alert->get('subject_areas')->value ?? [],
          'created' => (int) $alert->get('created')->value,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo alertas para tenant @tenant: @error', [
        '@tenant' => $tenantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Envia un email consolidado de alertas para un tenant.
   *
   * @param int $tenantId
   *   ID del tenant.
   * @param array $alerts
   *   Array de entidades NormChangeAlert.
   */
  protected function sendAlertEmail(int $tenantId, array $alerts): void {
    // Obtener email del administrador del tenant.
    $tenantEntity = $this->entityTypeManager->getStorage('group')->load($tenantId);
    if (!$tenantEntity) {
      $this->logger->warning('Tenant @id no encontrado para envio de alertas.', [
        '@id' => $tenantId,
      ]);
      return;
    }

    // Buscar el propietario/admin del tenant.
    $ownerId = $tenantEntity->getOwnerId();
    $owner = $this->entityTypeManager->getStorage('user')->load($ownerId);
    if (!$owner || !$owner->getEmail()) {
      $this->logger->warning('No se encontro email de contacto para tenant @id.', [
        '@id' => $tenantId,
      ]);
      return;
    }

    // Construir contenido del email.
    $body = "Se han detectado los siguientes cambios normativos relevantes:\n\n";

    foreach ($alerts as $index => $alert) {
      $num = $index + 1;
      $changeType = $alert->get('change_type')->value;
      $summary = $alert->get('summary')->value;
      $severity = $alert->get('severity')->value;

      $typeLabel = match ($changeType) {
        'nueva' => 'Nueva norma',
        'modificacion' => 'Modificacion',
        'derogacion' => 'Derogacion',
        default => 'Cambio',
      };

      $body .= "{$num}. [{$typeLabel}] ({$severity}) {$summary}\n";
    }

    $body .= "\nPuede consultar los detalles en su panel de administracion.";

    $params = [
      'subject' => 'Alertas normativas: ' . count($alerts) . ' cambios detectados',
      'body' => $body,
    ];

    $this->mailManager->mail(
      'jaraba_legal_knowledge',
      'norm_change_alert',
      $owner->getEmail(),
      'es',
      $params,
      NULL,
      TRUE
    );
  }

}
