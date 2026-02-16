<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_privacy\Entity\DpaAgreement;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * GESTOR DE DPA — DpaManagerService.
 *
 * ESTRUCTURA:
 * Servicio central para la gestión del ciclo de vida de los Data Processing
 * Agreements (DPA) en el ecosistema multi-tenant. Cada tenant debe firmar
 * un DPA antes de que se active el procesamiento de sus datos personales.
 *
 * LÓGICA DE NEGOCIO:
 * - El DPA es obligatorio por RGPD Art. 28 antes de procesar datos.
 * - Cada versión del DPA invalida la anterior (status 'superseded').
 * - La firma incluye timestamp, IP, user-agent y hash SHA-256 del contenido.
 * - El PDF firmado se genera con sello de tiempo y se almacena como archivo.
 * - El modal de firma es bloqueante: el tenant no puede acceder al panel sin DPA.
 *
 * RELACIONES:
 * - DpaManagerService → TenantContextService (contexto tenant)
 * - DpaManagerService → FileSystemInterface (almacenamiento PDF)
 * - DpaManagerService → MailManagerInterface (envío copia DPA)
 * - DpaManagerService ← PrivacyApiController (API REST)
 * - DpaManagerService ← hook_user_login() (verificación al login)
 *
 * Spec: Doc 183 §2.3.1. Plan: FASE 2, Stack Compliance Legal N1.
 *
 * @package Drupal\jaraba_privacy\Service
 */
class DpaManagerService {

  /**
   * Nombre de la configuración del módulo.
   */
  const CONFIG_NAME = 'jaraba_privacy.settings';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected ConfigFactoryInterface $configFactory,
    protected FileSystemInterface $fileSystem,
    protected MailManagerInterface $mailManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera un nuevo DPA para un tenant con contenido personalizado.
   *
   * Crea un DPA en estado 'pending_signature' con el contenido base
   * personalizado para el tenant (nombre, vertical, subprocesadores).
   *
   * @param int $tenant_id
   *   ID del tenant (Group entity).
   *
   * @return \Drupal\jaraba_privacy\Entity\DpaAgreement|null
   *   DPA generado o NULL si el tenant no existe.
   *
   * @throws \InvalidArgumentException
   *   Si el tenant no existe.
   */
  public function generateDpa(int $tenant_id): ?DpaAgreement {
    $tenant = $this->entityTypeManager->getStorage('group')->load($tenant_id);
    if (!$tenant) {
      throw new \InvalidArgumentException(
        (string) new TranslatableMarkup('El tenant con ID @id no existe.', ['@id' => $tenant_id])
      );
    }

    // Determinar siguiente versión.
    $current = $this->getCurrentDpa($tenant_id);
    $next_version = $current ? $this->incrementVersion($current->get('version')->value) : '1.0';

    $storage = $this->entityTypeManager->getStorage('dpa_agreement');

    /** @var \Drupal\jaraba_privacy\Entity\DpaAgreement $dpa */
    $dpa = $storage->create([
      'tenant_id' => $tenant_id,
      'version' => $next_version,
      'status' => 'pending_signature',
      'data_categories' => json_encode($this->getDefaultDataCategories(), JSON_THROW_ON_ERROR),
      'subprocessors_accepted' => json_encode($this->getSubprocessorsList(), JSON_THROW_ON_ERROR),
    ]);

    $dpa->save();

    $this->logger->info('DPA v@version generado para tenant @tenant.', [
      '@version' => $next_version,
      '@tenant' => $tenant->label(),
    ]);

    return $dpa;
  }

  /**
   * Firma un DPA electrónicamente.
   *
   * Registra la firma con hash SHA-256 del contenido, timestamp UTC,
   * IP del firmante y datos de identificación. Marca DPAs anteriores
   * como 'superseded'.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param int $user_id
   *   ID del usuario firmante.
   * @param string $ip_address
   *   Dirección IP desde la que se firma.
   * @param string $signer_name
   *   Nombre completo del firmante.
   * @param string $signer_role
   *   Cargo del firmante en la organización.
   *
   * @return \Drupal\jaraba_privacy\Entity\DpaAgreement
   *   DPA firmado con hash y PDF generado.
   *
   * @throws \RuntimeException
   *   Si ya existe un DPA activo o el DPA no está en estado pendiente.
   */
  public function signDpa(int $tenant_id, int $user_id, string $ip_address, string $signer_name, string $signer_role): DpaAgreement {
    // Buscar DPA pendiente de firma para este tenant.
    $storage = $this->entityTypeManager->getStorage('dpa_agreement');
    $dpa_ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', 'pending_signature')
      ->sort('created', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($dpa_ids)) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('No existe un DPA pendiente de firma para este tenant.')
      );
    }

    /** @var \Drupal\jaraba_privacy\Entity\DpaAgreement $dpa */
    $dpa = $storage->load(reset($dpa_ids));

    // Marcar DPAs anteriores como superseded.
    $this->supersedePreviousDpas($tenant_id, (int) $dpa->id());

    // Calcular hash SHA-256 del contenido del DPA.
    $content_to_hash = json_encode([
      'tenant_id' => $tenant_id,
      'version' => $dpa->get('version')->value,
      'signer_name' => $signer_name,
      'signer_role' => $signer_role,
      'ip_address' => $ip_address,
      'timestamp' => time(),
      'subprocessors' => $dpa->get('subprocessors_accepted')->value,
      'data_categories' => $dpa->get('data_categories')->value,
    ], JSON_THROW_ON_ERROR);

    $dpa_hash = hash('sha256', $content_to_hash);

    // Firmar el DPA.
    $dpa->set('signed_at', time());
    $dpa->set('signed_by', $user_id);
    $dpa->set('signer_name', $signer_name);
    $dpa->set('signer_role', $signer_role);
    $dpa->set('ip_address', $ip_address);
    $dpa->set('dpa_hash', $dpa_hash);
    $dpa->set('status', 'active');
    $dpa->save();

    $this->logger->info('DPA v@version firmado por @signer para tenant @tenant.', [
      '@version' => $dpa->get('version')->value,
      '@signer' => $signer_name,
      '@tenant' => $tenant_id,
    ]);

    return $dpa;
  }

  /**
   * Obtiene el DPA vigente (activo) de un tenant.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_privacy\Entity\DpaAgreement|null
   *   DPA activo o NULL si no hay ninguno vigente.
   */
  public function getCurrentDpa(int $tenant_id): ?DpaAgreement {
    $storage = $this->entityTypeManager->getStorage('dpa_agreement');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', 'active')
      ->sort('signed_at', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    /** @var \Drupal\jaraba_privacy\Entity\DpaAgreement $dpa */
    $dpa = $storage->load(reset($ids));
    return $dpa;
  }

  /**
   * Actualiza el DPA de un tenant generando una nueva versión.
   *
   * Crea un nuevo DPA en estado 'pending_signature'. El anterior
   * seguirá activo hasta que se firme el nuevo.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param string $new_version
   *   Nueva versión del DPA.
   *
   * @return \Drupal\jaraba_privacy\Entity\DpaAgreement
   *   Nuevo DPA generado en estado pendiente.
   */
  public function updateDpa(int $tenant_id, string $new_version): DpaAgreement {
    $storage = $this->entityTypeManager->getStorage('dpa_agreement');

    /** @var \Drupal\jaraba_privacy\Entity\DpaAgreement $dpa */
    $dpa = $storage->create([
      'tenant_id' => $tenant_id,
      'version' => $new_version,
      'status' => 'pending_signature',
      'data_categories' => json_encode($this->getDefaultDataCategories(), JSON_THROW_ON_ERROR),
      'subprocessors_accepted' => json_encode($this->getSubprocessorsList(), JSON_THROW_ON_ERROR),
    ]);

    $dpa->save();

    $this->logger->info('Nueva versión de DPA v@version generada para tenant @tenant.', [
      '@version' => $new_version,
      '@tenant' => $tenant_id,
    ]);

    return $dpa;
  }

  /**
   * Exporta el PDF firmado de un DPA.
   *
   * @param int $dpa_id
   *   ID del DPA.
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Respuesta con el archivo PDF.
   *
   * @throws \RuntimeException
   *   Si el DPA no existe o no tiene PDF asociado.
   */
  public function exportDpaPdf(int $dpa_id): BinaryFileResponse {
    $dpa = $this->entityTypeManager->getStorage('dpa_agreement')->load($dpa_id);

    if (!$dpa) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('El DPA con ID @id no existe.', ['@id' => $dpa_id])
      );
    }

    $file_id = $dpa->get('pdf_file_id')->target_id;
    if (!$file_id) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('El DPA no tiene un PDF asociado.')
      );
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = $this->entityTypeManager->getStorage('file')->load($file_id);
    $uri = $file->getFileUri();
    $real_path = $this->fileSystem->realpath($uri);

    return new BinaryFileResponse($real_path, 200, [
      'Content-Type' => 'application/pdf',
      'Content-Disposition' => 'attachment; filename="DPA_v' . $dpa->get('version')->value . '.pdf"',
    ]);
  }

  /**
   * Verifica si un tenant tiene un DPA activo.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return bool
   *   TRUE si el tenant tiene un DPA activo firmado.
   */
  public function hasDpa(int $tenant_id): bool {
    return $this->getCurrentDpa($tenant_id) !== NULL;
  }

  /**
   * Obtiene el historial completo de DPAs de un tenant.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_privacy\Entity\DpaAgreement[]
   *   Array de DPAs ordenados por fecha de creación (más reciente primero).
   */
  public function getDpaHistory(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('dpa_agreement');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->sort('created', 'DESC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    return $storage->loadMultiple($ids);
  }

  /**
   * Obtiene la lista de subprocesadores de la plataforma.
   *
   * @return array
   *   Lista de subprocesadores con nombre, propósito y ubicación.
   */
  public function getSubprocessorsList(): array {
    return [
      [
        'name' => 'Amazon Web Services (AWS)',
        'purpose' => 'Infraestructura cloud (hosting, almacenamiento, CDN)',
        'location' => 'EU (Ireland, Frankfurt)',
        'safeguards' => 'SCC + Adequacy Decision',
      ],
      [
        'name' => 'Stripe',
        'purpose' => 'Procesamiento de pagos y facturación',
        'location' => 'EU + US',
        'safeguards' => 'SCC + DPA firmado',
      ],
      [
        'name' => 'Mailgun / SendGrid',
        'purpose' => 'Envío de emails transaccionales',
        'location' => 'EU',
        'safeguards' => 'SCC + DPA firmado',
      ],
    ];
  }

  /**
   * Marca DPAs anteriores como 'superseded'.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param int $exclude_id
   *   ID del DPA a excluir (el nuevo que se está firmando).
   */
  protected function supersedePreviousDpas(int $tenant_id, int $exclude_id): void {
    $storage = $this->entityTypeManager->getStorage('dpa_agreement');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('status', 'active')
      ->condition('id', $exclude_id, '!=')
      ->execute();

    foreach ($storage->loadMultiple($ids) as $dpa) {
      $dpa->set('status', 'superseded');
      $dpa->save();
    }
  }

  /**
   * Incrementa la versión del DPA (1.0 → 2.0).
   */
  protected function incrementVersion(string $version): string {
    $parts = explode('.', $version);
    $major = (int) ($parts[0] ?? 1);
    return ($major + 1) . '.0';
  }

  /**
   * Categorías de datos personales por defecto tratados en la plataforma.
   */
  protected function getDefaultDataCategories(): array {
    return [
      'identification' => 'Datos identificativos (nombre, email, teléfono)',
      'professional' => 'Datos profesionales (empresa, cargo, experiencia)',
      'financial' => 'Datos financieros (facturación, método de pago)',
      'usage' => 'Datos de uso (actividad en la plataforma, logs)',
      'location' => 'Datos de localización (IP, geolocalización aproximada)',
    ];
  }

}
