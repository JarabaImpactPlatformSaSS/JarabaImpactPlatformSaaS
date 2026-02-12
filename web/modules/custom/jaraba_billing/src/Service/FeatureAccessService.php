<?php

declare(strict_types=1);

namespace Drupal\jaraba_billing\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Verificación de acceso a features basada en plan + add-ons.
 *
 * Spec 158 §6.1: Verifica en tiempo real si un tenant tiene acceso
 * a una feature, considerando tanto el plan base como add-ons activos.
 */
class FeatureAccessService {

  /**
   * Mapeo de features a códigos de add-on.
   */
  protected const FEATURE_ADDON_MAP = [
    // CRM.
    'crm_pipeline' => 'jaraba_crm',
    'crm_contacts' => 'jaraba_crm',
    'lead_scoring' => 'jaraba_crm',
    // Email Marketing.
    'email_campaigns' => 'jaraba_email',
    'email_sequences' => 'jaraba_email',
    'email_templates' => 'jaraba_email',
    // Social Media.
    'social_calendar' => 'jaraba_social',
    'social_posts' => 'jaraba_social',
    // Paid Ads.
    'ads_sync' => 'paid_ads_sync',
    'roas_tracking' => 'paid_ads_sync',
    // Retargeting.
    'pixels_manager' => 'retargeting_pixels',
    'server_tracking' => 'retargeting_pixels',
    // Events.
    'events_create' => 'events_webinars',
    'webinar_integration' => 'events_webinars',
    // A/B Testing.
    'experiments' => 'ab_testing',
    'ab_variants' => 'ab_testing',
    // Referral.
    'referral_codes' => 'referral_program',
    'rewards' => 'referral_program',
    // Page Builder — P1-01.
    'premium_blocks' => 'page_builder_premium',
    'page_builder_seo' => 'page_builder_seo',
    'page_builder_analytics' => 'page_builder_analytics',
    'page_builder_schema_org' => 'page_builder_seo',
    // Credentials — P1-02.
    'credential_stacks' => 'credentials_advanced',
    'credential_portability' => 'credentials_advanced',
  ];

  public function __construct(
    protected PlanValidator $planValidator,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Verifica si un tenant puede acceder a una feature.
   *
   * Comprueba primero el plan base vía PlanValidator, luego add-ons activos.
   */
  public function canAccess(int $tenantId, string $feature): bool {
    try {
      $tenant = $this->entityTypeManager->getStorage('group')->load($tenantId);
      if (!$tenant) {
        return FALSE;
      }

      // Check plan base first.
      if ($this->planValidator->hasFeature($tenant, $feature)) {
        return TRUE;
      }

      // Check add-ons.
      $addonCode = $this->getAddonForFeature($feature);
      if ($addonCode && $this->hasActiveAddon($tenantId, $addonCode)) {
        return TRUE;
      }

      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error checking feature access for tenant @id, feature @feature: @error', [
        '@id' => $tenantId,
        '@feature' => $feature,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Lista los códigos de add-on activos de un tenant.
   *
   * @return string[]
   *   Array de addon_code strings.
   */
  public function getActiveAddons(int $tenantId): array {
    $storage = $this->entityTypeManager->getStorage('tenant_addon');
    $addons = $storage->loadByProperties([
      'tenant_id' => $tenantId,
      'status' => 'active',
    ]);

    $codes = [];
    foreach ($addons as $addon) {
      $codes[] = $addon->get('addon_code')->value;
    }

    return $codes;
  }

  /**
   * Comprueba si un tenant tiene un add-on específico activo.
   */
  public function hasActiveAddon(int $tenantId, string $addonCode): bool {
    $storage = $this->entityTypeManager->getStorage('tenant_addon');
    $addons = $storage->loadByProperties([
      'tenant_id' => $tenantId,
      'addon_code' => $addonCode,
      'status' => 'active',
    ]);

    return !empty($addons);
  }

  /**
   * Obtiene el código de add-on necesario para una feature.
   *
   * @return string|null
   *   Código del add-on o NULL si la feature no requiere add-on.
   */
  public function getAddonForFeature(string $feature): ?string {
    return self::FEATURE_ADDON_MAP[$feature] ?? NULL;
  }

  /**
   * Lista add-ons disponibles (no activos) para un tenant.
   *
   * @return array
   *   Array de add-ons disponibles con código y etiqueta.
   */
  public function getAvailableAddons(int $tenantId): array {
    $allAddons = [
      'jaraba_crm' => 'CRM',
      'jaraba_email' => 'Email Marketing',
      'jaraba_email_plus' => 'Email Marketing Plus',
      'jaraba_social' => 'Social Media',
      'paid_ads_sync' => 'Paid Ads Sync',
      'retargeting_pixels' => 'Retargeting Pixels',
      'events_webinars' => 'Events & Webinars',
      'ab_testing' => 'A/B Testing',
      'referral_program' => 'Referral Program',
      // P1-01: Page Builder add-ons.
      'page_builder_premium' => 'Page Builder Premium Blocks',
      'page_builder_seo' => 'Page Builder SEO Avanzado',
      'page_builder_analytics' => 'Page Builder Analytics',
      // P1-02: Credentials add-ons.
      'credentials_advanced' => 'Credentials Avanzado (Stacks + Portabilidad)',
    ];

    $activeAddons = $this->getActiveAddons($tenantId);
    $available = [];

    foreach ($allAddons as $code => $label) {
      if (!in_array($code, $activeAddons, TRUE)) {
        $available[] = [
          'code' => $code,
          'label' => $label,
        ];
      }
    }

    return $available;
  }

}
