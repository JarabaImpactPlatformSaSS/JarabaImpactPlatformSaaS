<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuracion PromotionConfig.
 *
 * Almacena promociones, programas destacados y ofertas activas que deben
 * ser conocidas por el copilot IA, templates Twig, y emails.
 *
 * Es la fuente de verdad centralizada para "que esta activo ahora" en el SaaS.
 * Resuelve el vacio arquitectonico donde el copilot publico no conocia
 * las promociones activas (popup Andalucia +ei, promo banner, etc.).
 *
 * Convencion de ID: {vertical}_{programa}_{anno}
 * Ejemplo: andalucia_ei_piil_2025, global_kit_digital_2026
 *
 * @ConfigEntityType(
 *   id = "promotion_config",
 *   label = @Translation("Promotion Config"),
 *   label_collection = @Translation("Promociones Activas"),
 *   label_singular = @Translation("promoción"),
 *   label_plural = @Translation("promociones"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\PromotionConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\PromotionConfigForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\PromotionConfigForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "promotion_config",
 *   admin_permission = "administer promotions",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "vertical",
 *     "type",
 *     "highlight_values",
 *     "cta_url",
 *     "cta_label",
 *     "secondary_cta_url",
 *     "secondary_cta_label",
 *     "date_start",
 *     "date_end",
 *     "priority",
 *     "copilot_instruction",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/promotion-config",
 *     "add-form" = "/admin/structure/promotion-config/add",
 *     "edit-form" = "/admin/structure/promotion-config/{promotion_config}/edit",
 *     "delete-form" = "/admin/structure/promotion-config/{promotion_config}/delete",
 *   },
 * )
 */
class PromotionConfig extends ConfigEntityBase implements PromotionConfigInterface {

  /**
   * El ID de la promocion (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * Titulo publico de la promocion.
   *
   * @var string
   */
  protected $label;

  /**
   * Descripcion extendida para el copilot y UI.
   *
   * @var string
   */
  protected $description = '';

  /**
   * Clave canonica del vertical (VERTICAL-CANONICAL-001) o 'global'.
   *
   * @var string
   */
  protected $vertical = 'global';

  /**
   * Tipo de promocion.
   *
   * Valores: program, discount, subsidy, event, announcement.
   *
   * @var string
   */
  protected $type = 'announcement';

  /**
   * Datos destacados como key-value (ej: plazas: "45", incentivo: "528€").
   *
   * Almacenado como JSON string, decodificado en getter.
   *
   * @var string
   */
  protected $highlight_values = '{}';

  /**
   * Ruta interna del CTA principal.
   *
   * @var string
   */
  protected $cta_url = '';

  /**
   * Texto del boton CTA principal.
   *
   * @var string
   */
  protected $cta_label = '';

  /**
   * Ruta del CTA secundario.
   *
   * @var string
   */
  protected $secondary_cta_url = '';

  /**
   * Texto del CTA secundario.
   *
   * @var string
   */
  protected $secondary_cta_label = '';

  /**
   * Fecha inicio (Y-m-d) o vacio para sin limite.
   *
   * @var string
   */
  protected $date_start = '';

  /**
   * Fecha fin (Y-m-d) o vacio para sin limite.
   *
   * @var string
   */
  protected $date_end = '';

  /**
   * Prioridad (mayor = mas importante, se muestra primero).
   *
   * @var int
   */
  protected $priority = 0;

  /**
   * Instruccion especial para el copilot sobre esta promocion.
   *
   * @var string
   */
  protected $copilot_instruction = '';

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription(string $description): static {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getVertical(): string {
    return $this->vertical;
  }

  /**
   * {@inheritdoc}
   */
  public function setVertical(string $vertical): static {
    $this->vertical = $vertical;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setType(string $type): static {
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * @return array<string, string>
   */
  public function getHighlightValues(): array {
    $raw = $this->highlight_values;
    $decoded = json_decode($raw, TRUE);
    return \is_array($decoded) ? $decoded : [];
  }

  /**
   * {@inheritdoc}
   */

  /**
   * @param array<string, string> $values
   */
  public function setHighlightValues(array $values): static {
    $this->highlight_values = json_encode($values, JSON_UNESCAPED_UNICODE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCtaUrl(): string {
    return $this->cta_url;
  }

  /**
   * {@inheritdoc}
   */
  public function setCtaUrl(string $url): static {
    $this->cta_url = $url;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCtaLabel(): string {
    return $this->cta_label;
  }

  /**
   * {@inheritdoc}
   */
  public function setCtaLabel(string $label): static {
    $this->cta_label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondaryCtaUrl(): string {
    return $this->secondary_cta_url;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecondaryCtaLabel(): string {
    return $this->secondary_cta_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateStart(): string {
    return $this->date_start;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateEnd(): string {
    return $this->date_end;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority(): int {
    return $this->priority;
  }

  /**
   * {@inheritdoc}
   */
  public function getCopilotInstruction(): string {
    return $this->copilot_instruction;
  }

  /**
   * {@inheritdoc}
   */
  public function setCopilotInstruction(string $instruction): static {
    $this->copilot_instruction = $instruction;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isCurrentlyActive(): bool {
    if (!$this->status()) {
      return FALSE;
    }
    $now = date('Y-m-d');
    if ($this->date_start !== '' && $this->date_start > $now) {
      return FALSE;
    }
    if ($this->date_end !== '' && $this->date_end < $now) {
      return FALSE;
    }
    return TRUE;
  }

}
