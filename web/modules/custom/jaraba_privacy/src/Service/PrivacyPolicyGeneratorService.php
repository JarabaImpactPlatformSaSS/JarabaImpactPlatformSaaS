<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_privacy\Entity\PrivacyPolicy;
use Psr\Log\LoggerInterface;

/**
 * GENERADOR DE POLÍTICAS DE PRIVACIDAD — PrivacyPolicyGeneratorService.
 *
 * ESTRUCTURA:
 * Servicio que genera políticas de privacidad parametrizadas por vertical
 * y tenant. Las políticas se crean como Content Entities PrivacyPolicy
 * con contenido HTML personalizado.
 *
 * LÓGICA DE NEGOCIO:
 * - Cada vertical tiene un template de política con secciones específicas.
 * - El contenido se personaliza con datos del tenant (nombre, DPO, vertical).
 * - Las políticas se versionan: cada nueva versión desactiva la anterior.
 * - El hash SHA-256 del contenido permite verificar integridad.
 *
 * RELACIONES:
 * - PrivacyPolicyGeneratorService → TenantContextService (contexto tenant)
 * - PrivacyPolicyGeneratorService → ConfigFactoryInterface (settings DPO)
 * - PrivacyPolicyGeneratorService ← PrivacyApiController (API REST)
 *
 * Spec: Doc 183 §8.1.1. Plan: FASE 2, Stack Compliance Legal N1.
 *
 * @package Drupal\jaraba_privacy\Service
 */
class PrivacyPolicyGeneratorService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera una nueva política de privacidad para un tenant y vertical.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param string $vertical
   *   Vertical del ecosistema (empleo, emprendimiento, comercio, etc.).
   *
   * @return \Drupal\jaraba_privacy\Entity\PrivacyPolicy
   *   Política generada en estado borrador.
   */
  public function generatePolicy(int $tenant_id, string $vertical): PrivacyPolicy {
    $tenant = $this->entityTypeManager->getStorage('group')->load($tenant_id);
    $tenant_name = $tenant ? $tenant->label() : 'Tenant';

    $config = $this->configFactory->get('jaraba_privacy.settings');
    $dpo_name = $config->get('dpo_name') ?: 'DPO';
    $dpo_email = $config->get('dpo_email') ?: 'dpo@jarabaimpact.com';

    // Generar contenido HTML personalizado.
    $content_html = $this->buildPolicyContent($tenant_name, $vertical, $dpo_name, $dpo_email);
    $content_hash = hash('sha256', $content_html);

    // Determinar versión.
    $current = $this->getActivePolicy($tenant_id, $vertical);
    $version = $current ? $this->incrementVersion($current->get('version')->value) : '1.0';

    $storage = $this->entityTypeManager->getStorage('privacy_policy');

    /** @var \Drupal\jaraba_privacy\Entity\PrivacyPolicy $policy */
    $policy = $storage->create([
      'tenant_id' => $tenant_id,
      'vertical' => $vertical,
      'version' => $version,
      'content_html' => ['value' => $content_html, 'format' => 'full_html'],
      'content_hash' => $content_hash,
      'is_active' => FALSE,
      'dpo_contact' => $dpo_email,
    ]);

    $policy->save();

    $this->logger->info('Política de privacidad v@version generada para tenant @tenant, vertical @vertical.', [
      '@version' => $version,
      '@tenant' => $tenant_name,
      '@vertical' => $vertical,
    ]);

    return $policy;
  }

  /**
   * Publica una política, desactivando la versión anterior.
   *
   * @param int $policy_id
   *   ID de la política a publicar.
   *
   * @return \Drupal\jaraba_privacy\Entity\PrivacyPolicy
   *   Política publicada.
   */
  public function publishPolicy(int $policy_id): PrivacyPolicy {
    /** @var \Drupal\jaraba_privacy\Entity\PrivacyPolicy $policy */
    $policy = $this->entityTypeManager->getStorage('privacy_policy')->load($policy_id);

    if (!$policy) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('La política con ID @id no existe.', ['@id' => $policy_id])
      );
    }

    $tenant_id = (int) $policy->get('tenant_id')->target_id;
    $vertical = $policy->get('vertical')->value;

    // Desactivar política anterior.
    $this->deactivatePolicies($tenant_id, $vertical, (int) $policy->id());

    // Activar y publicar.
    $policy->set('is_active', TRUE);
    $policy->set('published_at', time());
    $policy->save();

    return $policy;
  }

  /**
   * Obtiene la política activa de un tenant para un vertical.
   *
   * @param int $tenant_id
   *   ID del tenant.
   * @param string $vertical
   *   Vertical del ecosistema.
   *
   * @return \Drupal\jaraba_privacy\Entity\PrivacyPolicy|null
   *   Política activa o NULL.
   */
  public function getActivePolicy(int $tenant_id, string $vertical): ?PrivacyPolicy {
    $storage = $this->entityTypeManager->getStorage('privacy_policy');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('vertical', $vertical)
      ->condition('is_active', TRUE)
      ->sort('published_at', 'DESC')
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Obtiene todas las políticas activas de un tenant.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return \Drupal\jaraba_privacy\Entity\PrivacyPolicy[]
   *   Array de políticas activas indexadas por vertical.
   */
  public function getAllActivePolicies(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('privacy_policy');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('is_active', TRUE)
      ->execute();

    $policies = [];
    foreach ($storage->loadMultiple($ids) as $policy) {
      $policies[$policy->get('vertical')->value] = $policy;
    }

    return $policies;
  }

  /**
   * Construye el contenido HTML de la política por vertical.
   *
   * @param string $tenant_name
   *   Nombre del tenant.
   * @param string $vertical
   *   Vertical del ecosistema.
   * @param string $dpo_name
   *   Nombre del DPO.
   * @param string $dpo_email
   *   Email del DPO.
   *
   * @return string
   *   Contenido HTML de la política.
   */
  protected function buildPolicyContent(string $tenant_name, string $vertical, string $dpo_name, string $dpo_email): string {
    $vertical_data = $this->getVerticalSpecificData($vertical);

    return '<h2>' . (string) new TranslatableMarkup('Política de Privacidad') . '</h2>'
      . '<p>' . (string) new TranslatableMarkup(
          'Esta política de privacidad describe cómo @tenant_name ("nosotros", "nuestro") recopila, utiliza y protege la información personal de los usuarios en el vertical de @vertical_label.',
          ['@tenant_name' => $tenant_name, '@vertical_label' => $vertical_data['label']]
        ) . '</p>'
      . '<h3>' . (string) new TranslatableMarkup('1. Responsable del tratamiento') . '</h3>'
      . '<p>' . (string) new TranslatableMarkup('Delegado de Protección de Datos: @dpo_name — @dpo_email', ['@dpo_name' => $dpo_name, '@dpo_email' => $dpo_email]) . '</p>'
      . '<h3>' . (string) new TranslatableMarkup('2. Datos que recopilamos') . '</h3>'
      . '<ul>' . $vertical_data['data_collected'] . '</ul>'
      . '<h3>' . (string) new TranslatableMarkup('3. Base legal del tratamiento') . '</h3>'
      . '<p>' . $vertical_data['legal_basis'] . '</p>'
      . '<h3>' . (string) new TranslatableMarkup('4. Derechos del interesado') . '</h3>'
      . '<p>' . (string) new TranslatableMarkup('Puede ejercer sus derechos de Acceso, Rectificación, Supresión, Oposición, Portabilidad y Limitación (ARCO-POL) contactando al DPO en @dpo_email o a través del formulario disponible en la plataforma.', ['@dpo_email' => $dpo_email]) . '</p>'
      . '<h3>' . (string) new TranslatableMarkup('5. Plazo de conservación') . '</h3>'
      . '<p>' . (string) new TranslatableMarkup('Los datos se conservarán mientras dure la relación contractual y durante los plazos de prescripción legal aplicables.') . '</p>';
  }

  /**
   * Datos específicos por vertical para la política.
   */
  protected function getVerticalSpecificData(string $vertical): array {
    $defaults = [
      'label' => (string) new TranslatableMarkup('General'),
      'data_collected' => '<li>' . (string) new TranslatableMarkup('Datos identificativos') . '</li>'
        . '<li>' . (string) new TranslatableMarkup('Datos de contacto') . '</li>'
        . '<li>' . (string) new TranslatableMarkup('Datos de uso de la plataforma') . '</li>',
      'legal_basis' => (string) new TranslatableMarkup('Ejecución de contrato (RGPD Art. 6.1.b) y consentimiento (Art. 6.1.a).'),
    ];

    $verticals = [
      'empleo' => [
        'label' => (string) new TranslatableMarkup('Empleo y Empleabilidad'),
        'data_collected' => $defaults['data_collected']
          . '<li>' . (string) new TranslatableMarkup('CV y experiencia profesional') . '</li>'
          . '<li>' . (string) new TranslatableMarkup('Competencias y habilidades') . '</li>'
          . '<li>' . (string) new TranslatableMarkup('Resultados de diagnóstico de empleabilidad') . '</li>',
        'legal_basis' => (string) new TranslatableMarkup('Ejecución de contrato (intermediación laboral) y consentimiento.'),
      ],
      'emprendimiento' => [
        'label' => (string) new TranslatableMarkup('Emprendimiento'),
        'data_collected' => $defaults['data_collected']
          . '<li>' . (string) new TranslatableMarkup('Datos de plan de negocio') . '</li>'
          . '<li>' . (string) new TranslatableMarkup('Métricas de experimentos') . '</li>',
        'legal_basis' => $defaults['legal_basis'],
      ],
      'comercio' => [
        'label' => (string) new TranslatableMarkup('Comercio'),
        'data_collected' => $defaults['data_collected']
          . '<li>' . (string) new TranslatableMarkup('Datos de comercio (productos, inventario)') . '</li>'
          . '<li>' . (string) new TranslatableMarkup('Datos de transacciones comerciales') . '</li>',
        'legal_basis' => $defaults['legal_basis'],
      ],
    ];

    return $verticals[$vertical] ?? $defaults;
  }

  /**
   * Desactiva todas las políticas de un vertical excepto la indicada.
   */
  protected function deactivatePolicies(int $tenant_id, string $vertical, int $exclude_id): void {
    $storage = $this->entityTypeManager->getStorage('privacy_policy');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $tenant_id)
      ->condition('vertical', $vertical)
      ->condition('is_active', TRUE)
      ->condition('id', $exclude_id, '!=')
      ->execute();

    foreach ($storage->loadMultiple($ids) as $policy) {
      $policy->set('is_active', FALSE);
      $policy->save();
    }
  }

  /**
   * Incrementa versión (1.0 → 2.0).
   */
  protected function incrementVersion(string $version): string {
    $parts = explode('.', $version);
    return ((int) ($parts[0] ?? 1) + 1) . '.0';
  }

}
