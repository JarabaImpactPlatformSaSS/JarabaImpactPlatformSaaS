<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Facturacion" — visible si billing dashboard accesible.
 *
 * Proporciona acceso rapido a:
 * - Dashboard financiero (facturas, metodos de pago)
 * - Pagina de precios (comparar planes)
 * - Kit Digital (si aplica)
 *
 * ROUTE-LANGPREFIX-001: Todas las URLs via resolveRoute().
 * OPTIONAL-CROSSMODULE-001: Rutas de jaraba_billing opcional.
 */
class BillingProfileSection extends AbstractUserProfileSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'billing';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Facturacion');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    return (string) $this->t('Facturas, pagos y plan de suscripcion');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'finance', 'name' => 'credit-card'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'impulse';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 40;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(int $uid): bool {
    // Visible si existe la ruta de plan settings (tenant-facing).
    return $this->resolveRoute('ecosistema_jaraba_core.tenant_self_service.plan') !== NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Rutas tenant-facing: /my-settings/plan, pricing page, /kit-digital.
   * NUNCA /billing/dashboard (requiere permiso view own billing → 403).
   */
  public function getLinks(int $uid): array {
    return array_values(array_filter([
      $this->makeLink(
        $this->t('Plan y facturacion'),
        'ecosistema_jaraba_core.tenant_self_service.plan',
        'finance', 'wallet-cards', 'impulse',
        ['description' => $this->t('Tu plan actual y datos de facturacion')],
      ),
      $this->makeLink(
        $this->t('Planes y precios'),
        'ecosistema_jaraba_core.pricing.page',
        'finance', 'plan-upgrade', 'impulse',
        ['description' => $this->t('Compara planes y funcionalidades')],
      ),
      $this->makeLink(
        $this->t('Kit Digital'),
        'jaraba_billing.kit_digital.landing',
        'compliance', 'kit-digital', 'impulse',
        ['description' => $this->t('Soluciones de digitalizacion subvencionadas')],
      ),
    ]));
  }

}
