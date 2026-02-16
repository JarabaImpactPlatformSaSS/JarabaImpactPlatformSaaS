<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal\Entity\WhistleblowerReport;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion del canal de denuncias.
 *
 * ESTRUCTURA:
 * Gestiona el canal de denuncias conforme a la Directiva EU 2019/1937:
 * recepcion de reportes (anonimos o identificados), cifrado de datos
 * sensibles, asignacion de investigadores y seguimiento anonimo.
 *
 * LOGICA DE NEGOCIO:
 * - Recibir reportes con cifrado de descripcion y datos de contacto.
 * - Generar codigo de seguimiento unico (tracking_code).
 * - Permitir seguimiento anonimo por codigo sin identificar al denunciante.
 * - Asignar investigador y gestionar el workflow de investigacion.
 * - Registrar resolucion y notificar al denunciante (si tiene contacto).
 * - Los reportes son inmutables una vez creados (excepto estado/resolucion).
 *
 * RELACIONES:
 * - Depende de TenantContextService para aislamiento multi-tenant.
 * - Genera WhistleblowerReport entities.
 *
 * Spec: Doc 184 §3.5. Plan: FASE 5, Stack Compliance Legal N1.
 */
class WhistleblowerChannelService {

  /**
   * Nombre de la configuracion del modulo.
   */
  const CONFIG_NAME = 'jaraba_legal.settings';

  /**
   * Constructor del servicio.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected ConfigFactoryInterface $configFactory,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Recibe y registra un nuevo reporte del canal de denuncias.
   *
   * Genera un tracking_code unico, cifra la descripcion y los
   * datos de contacto del denunciante (si los proporciona).
   *
   * @param array $data
   *   Datos del reporte:
   *   - 'category': (string) Categoria de la denuncia.
   *   - 'description': (string) Descripcion detallada.
   *   - 'severity': (string) Severidad (low, medium, high, critical).
   *   - 'reporter_contact': (string|null) Datos de contacto opcionales.
   *   - 'is_anonymous': (bool) Si la denuncia es anonima.
   *   - 'ip_address': (string|null) IP del denunciante.
   *   - 'tenant_id': (int|null) Tenant relacionado.
   *
   * @return array
   *   Datos del reporte creado con tracking_code para seguimiento.
   */
  public function submitReport(array $data): array {
    // Generar codigo de seguimiento unico.
    $trackingCode = $this->generateTrackingCode();

    // Cifrar la descripcion.
    $encryptedDescription = $this->encryptData($data['description'] ?? '');

    // Cifrar datos de contacto si los hay.
    $reporterContact = NULL;
    $isAnonymous = $data['is_anonymous'] ?? TRUE;
    if (!$isAnonymous && !empty($data['reporter_contact'])) {
      $reporterContact = $this->encryptData($data['reporter_contact']);
    }

    $tenantId = $data['tenant_id'] ?? NULL;

    $storage = $this->entityTypeManager->getStorage('whistleblower_report');

    /** @var \Drupal\jaraba_legal\Entity\WhistleblowerReport $report */
    $report = $storage->create([
      'tracking_code' => $trackingCode,
      'category' => $data['category'] ?? 'other',
      'description_encrypted' => [
        'value' => $encryptedDescription,
        'format' => 'plain_text',
      ],
      'severity' => $data['severity'] ?? 'medium',
      'status' => 'received',
      'reporter_contact_encrypted' => $reporterContact,
      'is_anonymous' => $isAnonymous,
      'ip_address' => $data['ip_address'] ?? NULL,
      'tenant_id' => $tenantId,
    ]);

    $report->save();

    $this->logger->info('Reporte de denuncia recibido: @code (categoria: @category, severidad: @severity, anonimo: @anon).', [
      '@code' => $trackingCode,
      '@category' => $data['category'] ?? 'other',
      '@severity' => $data['severity'] ?? 'medium',
      '@anon' => $isAnonymous ? 'si' : 'no',
    ]);

    // Notificar al responsable del canal si esta configurado.
    $this->notifyChannelHandler($tenantId, $trackingCode);

    return [
      'tracking_code' => $trackingCode,
      'id' => (int) $report->id(),
      'status' => 'received',
      'category' => $data['category'] ?? 'other',
      'severity' => $data['severity'] ?? 'medium',
      'is_anonymous' => $isAnonymous,
      'created_at' => (int) $report->get('created')->value,
      'message' => (string) new TranslatableMarkup(
        'Su reporte ha sido recibido. Use el codigo @code para consultar el estado.',
        ['@code' => $trackingCode]
      ),
    ];
  }

  /**
   * Consulta un reporte por su codigo de seguimiento.
   *
   * Permite el seguimiento anonimo sin revelar datos sensibles.
   * Solo devuelve informacion de estado, no el contenido del reporte.
   *
   * @param string $tracking_code
   *   Codigo de seguimiento del reporte.
   *
   * @return array|null
   *   Datos publicos del reporte o NULL si no existe.
   */
  public function getReportByTrackingCode(string $tracking_code): ?array {
    $storage = $this->entityTypeManager->getStorage('whistleblower_report');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tracking_code', $tracking_code)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    /** @var \Drupal\jaraba_legal\Entity\WhistleblowerReport $report */
    $report = $storage->load(reset($ids));

    // Solo devolver informacion de estado, sin datos sensibles.
    return [
      'tracking_code' => $tracking_code,
      'status' => $report->get('status')->value,
      'category' => $report->get('category')->value,
      'severity' => $report->get('severity')->value,
      'is_anonymous' => (bool) $report->get('is_anonymous')->value,
      'has_investigator' => $report->hasAssignee(),
      'resolution' => $report->isResolved() || $report->isDismissed()
        ? $report->get('resolution')->value
        : NULL,
      'resolved_at' => $report->get('resolved_at')->value
        ? (int) $report->get('resolved_at')->value
        : NULL,
      'created_at' => (int) $report->get('created')->value,
    ];
  }

  /**
   * Asigna un investigador a un reporte.
   *
   * @param int $report_id
   *   ID del reporte.
   * @param int $investigator_id
   *   ID del usuario investigador.
   *
   * @return \Drupal\jaraba_legal\Entity\WhistleblowerReport
   *   Reporte actualizado.
   *
   * @throws \RuntimeException
   *   Si el reporte no existe o ya esta cerrado.
   */
  public function assignInvestigator(int $report_id, int $investigator_id): WhistleblowerReport {
    $storage = $this->entityTypeManager->getStorage('whistleblower_report');

    /** @var \Drupal\jaraba_legal\Entity\WhistleblowerReport|null $report */
    $report = $storage->load($report_id);

    if (!$report) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('El reporte con ID @id no existe.', ['@id' => $report_id])
      );
    }

    if ($report->isResolved() || $report->isDismissed()) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('El reporte ya esta cerrado (estado: @status).', [
          '@status' => $report->get('status')->value,
        ])
      );
    }

    // Verificar que el investigador existe.
    $investigator = $this->entityTypeManager->getStorage('user')->load($investigator_id);
    if (!$investigator) {
      throw new \InvalidArgumentException(
        (string) new TranslatableMarkup('El usuario investigador con ID @id no existe.', ['@id' => $investigator_id])
      );
    }

    $report->set('assigned_to', $investigator_id);
    $report->set('status', 'investigating');
    $report->save();

    $this->logger->info('Investigador @investigator asignado al reporte @code.', [
      '@investigator' => $investigator->getDisplayName(),
      '@code' => $report->get('tracking_code')->value,
    ]);

    return $report;
  }

  /**
   * Actualiza el estado de un reporte y opcionalmente registra la resolucion.
   *
   * @param int $report_id
   *   ID del reporte.
   * @param string $status
   *   Nuevo estado (received, investigating, resolved, dismissed).
   * @param string|null $resolution
   *   Texto de la resolucion (obligatorio si status es resolved/dismissed).
   *
   * @return \Drupal\jaraba_legal\Entity\WhistleblowerReport
   *   Reporte actualizado.
   *
   * @throws \RuntimeException
   *   Si el reporte no existe.
   */
  public function updateStatus(int $report_id, string $status, ?string $resolution = NULL): WhistleblowerReport {
    $storage = $this->entityTypeManager->getStorage('whistleblower_report');

    /** @var \Drupal\jaraba_legal\Entity\WhistleblowerReport|null $report */
    $report = $storage->load($report_id);

    if (!$report) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('El reporte con ID @id no existe.', ['@id' => $report_id])
      );
    }

    $validStatuses = ['received', 'investigating', 'resolved', 'dismissed'];
    if (!in_array($status, $validStatuses, TRUE)) {
      throw new \InvalidArgumentException(
        (string) new TranslatableMarkup('Estado invalido: @status', ['@status' => $status])
      );
    }

    $report->set('status', $status);

    // Si se cierra el reporte, registrar resolucion y timestamp.
    if (in_array($status, ['resolved', 'dismissed'], TRUE)) {
      $report->set('resolved_at', time());
      if ($resolution) {
        $report->set('resolution', [
          'value' => $resolution,
          'format' => 'plain_text',
        ]);
      }
    }

    $report->save();

    $this->logger->info('Reporte @code actualizado a estado @status.', [
      '@code' => $report->get('tracking_code')->value,
      '@status' => $status,
    ]);

    return $report;
  }

  /**
   * Obtiene estadisticas del canal de denuncias para un tenant.
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   *
   * @return array
   *   Estadisticas: total, por estado, por severidad, tiempo medio de resolucion.
   */
  public function getReportStats(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('whistleblower_report');

    // Total de reportes.
    $totalQuery = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->count();
    $total = (int) $totalQuery->execute();

    // Conteo por estado.
    $byStatus = [];
    foreach (['received', 'investigating', 'resolved', 'dismissed'] as $status) {
      $count = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->condition('status', $status)
        ->count()
        ->execute();
      $byStatus[$status] = $count;
    }

    // Conteo por severidad.
    $bySeverity = [];
    foreach (['low', 'medium', 'high', 'critical'] as $severity) {
      $count = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->condition('severity', $severity)
        ->count()
        ->execute();
      $bySeverity[$severity] = $count;
    }

    // Reportes anonimos vs identificados.
    $anonymousCount = (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('is_anonymous', TRUE)
      ->count()
      ->execute();

    return [
      'total' => $total,
      'by_status' => $byStatus,
      'by_severity' => $bySeverity,
      'anonymous_count' => $anonymousCount,
      'identified_count' => $total - $anonymousCount,
      'open_count' => $byStatus['received'] + $byStatus['investigating'],
      'closed_count' => $byStatus['resolved'] + $byStatus['dismissed'],
    ];
  }

  /**
   * Genera un codigo de seguimiento unico de 16 caracteres.
   *
   * Formato: WB-XXXXXXXX-XXXX (alfanumerico en mayusculas).
   *
   * @return string
   *   Codigo de seguimiento.
   */
  protected function generateTrackingCode(): string {
    $bytes = random_bytes(8);
    $code = strtoupper(substr(bin2hex($bytes), 0, 12));

    return 'WB-' . substr($code, 0, 8) . '-' . substr($code, 8, 4);
  }

  /**
   * Cifra datos sensibles usando AES-256-CBC.
   *
   * Utiliza la clave de cifrado configurada o una derivada del
   * hash key del sitio. Los datos se almacenan en base64.
   *
   * @param string $data
   *   Datos a cifrar.
   *
   * @return string
   *   Datos cifrados en base64.
   */
  protected function encryptData(string $data): string {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $key = $config->get('whistleblower_encryption_key');

    if (!$key) {
      // Usar hash key del sitio como fallback.
      $key = \Drupal::service('settings')->getHashSalt();
    }

    $method = 'aes-256-cbc';
    $ivLength = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);

    // Almacenar IV + datos cifrados en base64.
    return base64_encode($iv . '::' . $encrypted);
  }

  /**
   * Descifra datos sensibles.
   *
   * @param string $encryptedData
   *   Datos cifrados en base64.
   *
   * @return string|null
   *   Datos descifrados o NULL si no se puede descifrar.
   */
  protected function decryptData(string $encryptedData): ?string {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $key = $config->get('whistleblower_encryption_key');

    if (!$key) {
      $key = \Drupal::service('settings')->getHashSalt();
    }

    $decoded = base64_decode($encryptedData, TRUE);
    if ($decoded === FALSE) {
      return NULL;
    }

    $parts = explode('::', $decoded, 2);
    if (count($parts) !== 2) {
      return NULL;
    }

    [$iv, $encrypted] = $parts;
    $method = 'aes-256-cbc';

    $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);
    return $decrypted !== FALSE ? $decrypted : NULL;
  }

  /**
   * Notifica al responsable del canal de denuncias.
   *
   * @param int|null $tenant_id
   *   ID del tenant (puede ser NULL para reportes globales).
   * @param string $tracking_code
   *   Codigo de seguimiento del nuevo reporte.
   */
  protected function notifyChannelHandler(?int $tenant_id, string $tracking_code): void {
    $config = $this->configFactory->get(self::CONFIG_NAME);
    $handlerEmail = $config->get('whistleblower_handler_email');

    if (!$handlerEmail) {
      return;
    }

    try {
      $params = [
        'subject' => (string) new TranslatableMarkup('Nuevo reporte en el canal de denuncias'),
        'tracking_code' => $tracking_code,
        'tenant_id' => $tenant_id,
      ];

      $this->mailManager->mail(
        'jaraba_legal',
        'whistleblower_new_report',
        $handlerEmail,
        'es',
        $params,
      );
    }
    catch (\Exception $e) {
      $this->logger->warning('Error notificando al responsable del canal: @error', [
        '@error' => $e->getMessage(),
      ]);
    }
  }

}
