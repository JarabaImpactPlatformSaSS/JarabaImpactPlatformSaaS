<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * SERVICIO DE NOTIFICACIÓN DE BRECHAS — BreachNotificationService.
 *
 * ESTRUCTURA:
 * Servicio que gestiona el ciclo de vida de las brechas de seguridad
 * según RGPD Art. 33-34. Implementa registro, evaluación de impacto,
 * notificación a AEPD (72h), notificación a afectados y cierre.
 *
 * LÓGICA DE NEGOCIO:
 * - Toda brecha se registra con severidad, datos afectados y timestamp.
 * - Se evalúa si requiere notificación a la AEPD (riesgo alto/muy alto).
 * - El plazo de notificación a AEPD es de 72 horas desde detección.
 * - Si hay riesgo alto para los derechos de los interesados, se les notifica.
 * - El envío masivo de emails usa QueueWorker para no bloquear.
 * - Se mantiene un timeline completo del incidente para auditoría.
 *
 * RELACIONES:
 * - BreachNotificationService → QueueFactory (envío masivo asíncrono)
 * - BreachNotificationService → MailManagerInterface (notificaciones)
 * - BreachNotificationService → TenantContextService (tenants afectados)
 * - BreachNotificationService ← PrivacyApiController (API REST)
 * - BreachNotificationService ← hook_cron() (verificación plazos 72h)
 *
 * Spec: Doc 183 §7. Plan: FASE 3, Stack Compliance Legal N1.
 *
 * @package Drupal\jaraba_privacy\Service
 */
class BreachNotificationService {

  /**
   * Niveles de severidad de brecha.
   */
  const SEVERITY_LOW = 'low';
  const SEVERITY_MEDIUM = 'medium';
  const SEVERITY_HIGH = 'high';
  const SEVERITY_CRITICAL = 'critical';

  /**
   * Estados del ciclo de vida de la brecha.
   */
  const STATUS_DETECTED = 'detected';
  const STATUS_ASSESSING = 'assessing';
  const STATUS_NOTIFIED_AEPD = 'notified_aepd';
  const STATUS_NOTIFIED_USERS = 'notified_users';
  const STATUS_REMEDIATING = 'remediating';
  const STATUS_CLOSED = 'closed';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected ConfigFactoryInterface $configFactory,
    protected MailManagerInterface $mailManager,
    protected QueueFactory $queueFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra una nueva brecha de seguridad.
   *
   * @param string $title
   *   Título descriptivo de la brecha.
   * @param string $severity
   *   Severidad: low, medium, high, critical.
   * @param string $description
   *   Descripción detallada del incidente.
   * @param array $affected_data
   *   Categorías de datos afectados.
   * @param array $affected_tenants
   *   IDs de tenants afectados (vacío si es plataforma general).
   * @param int $detected_by
   *   ID del usuario que detectó la brecha.
   *
   * @return array
   *   Datos del incidente registrado con timeline inicial.
   */
  public function reportBreach(
    string $title,
    string $severity,
    string $description,
    array $affected_data,
    array $affected_tenants,
    int $detected_by,
  ): array {
    $config = $this->configFactory->get('jaraba_privacy.settings');
    $notification_hours = $config->get('breach_notification_hours') ?? 72;

    $now = time();
    $aepd_deadline = $now + ($notification_hours * 3600);

    $breach = [
      'id' => uniqid('breach_', TRUE),
      'title' => $title,
      'severity' => $severity,
      'description' => $description,
      'affected_data' => $affected_data,
      'affected_tenants' => $affected_tenants,
      'detected_at' => $now,
      'detected_by' => $detected_by,
      'aepd_deadline' => $aepd_deadline,
      'status' => self::STATUS_DETECTED,
      'timeline' => [
        [
          'timestamp' => $now,
          'action' => 'detected',
          'description' => (string) new TranslatableMarkup('Brecha detectada: @title', ['@title' => $title]),
          'actor' => $detected_by,
        ],
      ],
    ];

    // Almacenar en state para seguimiento temporal.
    // En producción, esto sería una Content Entity dedicada.
    $breaches = \Drupal::state()->get('jaraba_privacy.active_breaches', []);
    $breaches[$breach['id']] = $breach;
    \Drupal::state()->set('jaraba_privacy.active_breaches', $breaches);

    $this->logger->critical('BRECHA DE SEGURIDAD DETECTADA: @title (severidad: @severity). Plazo AEPD: @deadline.', [
      '@title' => $title,
      '@severity' => $severity,
      '@deadline' => date('d/m/Y H:i', $aepd_deadline),
    ]);

    // Notificación inmediata al DPO.
    $this->notifyDpoBreach($breach);

    return $breach;
  }

  /**
   * Evalúa el impacto de una brecha para determinar si requiere notificación AEPD.
   *
   * @param string $breach_id
   *   ID de la brecha.
   *
   * @return array
   *   Resultado de la evaluación: requires_aepd_notification, requires_user_notification, risk_level.
   */
  public function assessImpact(string $breach_id): array {
    $breach = $this->getBreachById($breach_id);
    if (!$breach) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('Brecha @id no encontrada.', ['@id' => $breach_id])
      );
    }

    $severity = $breach['severity'];
    $affected_count = count($breach['affected_tenants']);

    // Evaluación según RGPD Art. 33-34.
    $requires_aepd = in_array($severity, [self::SEVERITY_HIGH, self::SEVERITY_CRITICAL], TRUE);
    $requires_users = $severity === self::SEVERITY_CRITICAL;

    // Datos sensibles siempre requieren notificación.
    $sensitive_categories = ['health', 'biometric', 'criminal', 'political', 'religious'];
    $has_sensitive = !empty(array_intersect($breach['affected_data'], $sensitive_categories));
    if ($has_sensitive) {
      $requires_aepd = TRUE;
      $requires_users = TRUE;
    }

    $risk_level = match (TRUE) {
      $severity === self::SEVERITY_CRITICAL && ($affected_count > 100 || $has_sensitive) => 'very_high',
      $severity === self::SEVERITY_CRITICAL || $severity === self::SEVERITY_HIGH => 'high',
      $severity === self::SEVERITY_MEDIUM => 'medium',
      default => 'low',
    };

    // Actualizar breach.
    $breach['assessment'] = [
      'requires_aepd_notification' => $requires_aepd,
      'requires_user_notification' => $requires_users,
      'risk_level' => $risk_level,
      'assessed_at' => time(),
    ];
    $breach['status'] = self::STATUS_ASSESSING;
    $breach['timeline'][] = [
      'timestamp' => time(),
      'action' => 'assessed',
      'description' => (string) new TranslatableMarkup('Evaluación de impacto completada. Riesgo: @risk.', ['@risk' => $risk_level]),
    ];

    $this->updateBreach($breach);

    return $breach['assessment'];
  }

  /**
   * Genera la notificación formal a la AEPD.
   *
   * @param string $breach_id
   *   ID de la brecha.
   *
   * @return array
   *   Datos de la notificación AEPD generada.
   */
  public function notifyAepd(string $breach_id): array {
    $breach = $this->getBreachById($breach_id);
    if (!$breach) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('Brecha @id no encontrada.', ['@id' => $breach_id])
      );
    }

    $config = $this->configFactory->get('jaraba_privacy.settings');

    $notification = [
      'breach_id' => $breach_id,
      'notified_at' => time(),
      'within_deadline' => time() <= $breach['aepd_deadline'],
      'dpo_name' => $config->get('dpo_name') ?: 'DPO',
      'dpo_email' => $config->get('dpo_email') ?: '',
      'description' => $breach['description'],
      'severity' => $breach['severity'],
      'affected_data' => $breach['affected_data'],
      'measures_taken' => (string) new TranslatableMarkup('Investigación en curso. Medidas de contención aplicadas.'),
    ];

    // Actualizar estado.
    $breach['status'] = self::STATUS_NOTIFIED_AEPD;
    $breach['aepd_notification'] = $notification;
    $breach['timeline'][] = [
      'timestamp' => time(),
      'action' => 'aepd_notified',
      'description' => (string) new TranslatableMarkup('Notificación enviada a AEPD. Dentro de plazo: @within.', [
        '@within' => $notification['within_deadline'] ? 'Sí' : 'No',
      ]),
    ];

    $this->updateBreach($breach);

    $this->logger->info('Notificación AEPD enviada para brecha @id. Dentro de plazo: @within.', [
      '@id' => $breach_id,
      '@within' => $notification['within_deadline'] ? 'Sí' : 'No',
    ]);

    return $notification;
  }

  /**
   * Notifica a los usuarios afectados vía queue asíncrona.
   *
   * @param string $breach_id
   *   ID de la brecha.
   * @param string $message
   *   Mensaje de notificación.
   */
  public function notifyAffectedUsers(string $breach_id, string $message): void {
    $breach = $this->getBreachById($breach_id);
    if (!$breach) {
      return;
    }

    // Encolar notificaciones para envío masivo.
    $queue = $this->queueFactory->get('jaraba_privacy_breach_notification');

    foreach ($breach['affected_tenants'] as $tenant_id) {
      $queue->createItem([
        'breach_id' => $breach_id,
        'tenant_id' => $tenant_id,
        'message' => $message,
      ]);
    }

    $breach['status'] = self::STATUS_NOTIFIED_USERS;
    $breach['timeline'][] = [
      'timestamp' => time(),
      'action' => 'users_notified',
      'description' => (string) new TranslatableMarkup('@count tenants en cola de notificación.', [
        '@count' => count($breach['affected_tenants']),
      ]),
    ];

    $this->updateBreach($breach);
  }

  /**
   * Cierra una brecha con causa raíz y plan de remediación.
   *
   * @param string $breach_id
   *   ID de la brecha.
   * @param string $root_cause
   *   Causa raíz identificada.
   * @param string $remediation_plan
   *   Plan de remediación y medidas preventivas.
   * @param int $closed_by
   *   ID del usuario que cierra.
   *
   * @return array
   *   Brecha cerrada.
   */
  public function closeIncident(string $breach_id, string $root_cause, string $remediation_plan, int $closed_by): array {
    $breach = $this->getBreachById($breach_id);
    if (!$breach) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('Brecha @id no encontrada.', ['@id' => $breach_id])
      );
    }

    $breach['status'] = self::STATUS_CLOSED;
    $breach['root_cause'] = $root_cause;
    $breach['remediation_plan'] = $remediation_plan;
    $breach['closed_at'] = time();
    $breach['closed_by'] = $closed_by;
    $breach['timeline'][] = [
      'timestamp' => time(),
      'action' => 'closed',
      'description' => (string) new TranslatableMarkup('Brecha cerrada. Causa raíz identificada y plan de remediación documentado.'),
      'actor' => $closed_by,
    ];

    $this->updateBreach($breach);

    $this->logger->info('Brecha @id cerrada. Duración: @hours horas.', [
      '@id' => $breach_id,
      '@hours' => round((time() - $breach['detected_at']) / 3600, 1),
    ]);

    return $breach;
  }

  /**
   * Obtiene el timeline completo de una brecha.
   *
   * @param string $breach_id
   *   ID de la brecha.
   *
   * @return array
   *   Timeline de la brecha.
   */
  public function getBreachTimeline(string $breach_id): array {
    $breach = $this->getBreachById($breach_id);
    return $breach['timeline'] ?? [];
  }

  /**
   * Obtiene una brecha por ID desde state.
   */
  protected function getBreachById(string $breach_id): ?array {
    $breaches = \Drupal::state()->get('jaraba_privacy.active_breaches', []);
    return $breaches[$breach_id] ?? NULL;
  }

  /**
   * Actualiza una brecha en state.
   */
  protected function updateBreach(array $breach): void {
    $breaches = \Drupal::state()->get('jaraba_privacy.active_breaches', []);
    $breaches[$breach['id']] = $breach;
    \Drupal::state()->set('jaraba_privacy.active_breaches', $breaches);
  }

  /**
   * Notifica al DPO sobre una brecha detectada.
   */
  protected function notifyDpoBreach(array $breach): void {
    $config = $this->configFactory->get('jaraba_privacy.settings');
    $dpo_email = $config->get('dpo_email');

    if (!$dpo_email) {
      $this->logger->error('BRECHA: No se pudo notificar al DPO — email no configurado.');
      return;
    }

    $this->mailManager->mail(
      'jaraba_privacy',
      'breach_detected',
      $dpo_email,
      'es',
      [
        'breach' => $breach,
        'subject' => (string) new TranslatableMarkup('[URGENTE] Brecha de seguridad detectada: @title', [
          '@title' => $breach['title'],
        ]),
      ]
    );
  }

}
