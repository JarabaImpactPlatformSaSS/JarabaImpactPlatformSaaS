<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Configuracion de comisiones del marketplace por tenant/vertical.
 *
 * GAP-M02: Reemplaza el hardcoded commission_rate = 10.0 en CheckoutService.
 * Permite configurar comisiones diferenciadas por tenant, con valores por
 * defecto definidos a nivel de plataforma.
 *
 * @ConfigEntityType(
 *   id = "marketplace_commission_config",
 *   label = @Translation("Configuración de Comisión Marketplace"),
 *   label_collection = @Translation("Configuraciones de Comisiones"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_comercio_conecta\MarketplaceCommissionConfigListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_comercio_conecta\Form\MarketplaceCommissionConfigForm",
 *       "add" = "Drupal\jaraba_comercio_conecta\Form\MarketplaceCommissionConfigForm",
 *       "edit" = "Drupal\jaraba_comercio_conecta\Form\MarketplaceCommissionConfigForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "commission",
 *   admin_permission = "administer comercioconecta settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "tenant_id",
 *     "default_rate",
 *     "category_rates",
 *     "min_rate",
 *     "max_rate",
 *     "platform_fee",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/marketplace-commission",
 *     "add-form" = "/admin/structure/marketplace-commission/add",
 *     "edit-form" = "/admin/structure/marketplace-commission/{marketplace_commission_config}/edit",
 *     "delete-form" = "/admin/structure/marketplace-commission/{marketplace_commission_config}/delete",
 *   },
 * )
 */
class MarketplaceCommissionConfig extends ConfigEntityBase {

  /**
   * Machine name ID.
   */
  protected string $id = '';

  /**
   * Display label.
   */
  protected string $label = '';

  /**
   * Tenant ID (0 = platform default).
   */
  protected int $tenant_id = 0;

  /**
   * Default commission rate (percentage).
   */
  protected float $default_rate = 10.0;

  /**
   * Category-specific rates (JSON-decoded array keyed by category TID).
   *
   * @var array<string, float>
   */
  protected array $category_rates = [];

  /**
   * Minimum commission rate (floor).
   */
  protected float $min_rate = 5.0;

  /**
   * Maximum commission rate (cap).
   */
  protected float $max_rate = 25.0;

  /**
   * Platform fixed fee per transaction (EUR).
   */
  protected float $platform_fee = 0.0;

  /**
   * Resolves the effective commission rate for a given category.
   *
   * @param string|null $categoryId
   *   Optional product category TID.
   *
   * @return float
   *   Commission rate as percentage (e.g. 10.0 = 10%).
   */
  public function getEffectiveRate(?string $categoryId = NULL): float {
    if ($categoryId && isset($this->category_rates[$categoryId])) {
      $rate = (float) $this->category_rates[$categoryId];
    }
    else {
      $rate = $this->default_rate;
    }

    return max($this->min_rate, min($this->max_rate, $rate));
  }

  /**
   * Gets the platform fixed fee per transaction.
   */
  public function getPlatformFee(): float {
    return $this->platform_fee;
  }

}
