<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuracion WaTemplate.
 *
 * Registro de templates de WhatsApp pre-aprobados por Meta.
 * ConfigEntity porque son datos de configuracion editables desde admin.
 *
 * @ConfigEntityType(
 *   id = "wa_template",
 *   label = @Translation("WhatsApp Template"),
 *   label_collection = @Translation("Templates WhatsApp"),
 *   label_singular = @Translation("template WhatsApp"),
 *   label_plural = @Translation("templates WhatsApp"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_whatsapp\WaTemplateListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_whatsapp\Form\WaTemplateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "wa_template",
 *   admin_permission = "administer whatsapp",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "langcode",
 *     "category",
 *     "status_meta",
 *     "header_type",
 *     "body_text",
 *     "footer_text",
 *     "buttons",
 *     "variables_schema",
 *     "meta_template_id",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/wa-templates",
 *     "add-form" = "/admin/structure/wa-templates/add",
 *     "edit-form" = "/admin/structure/wa-templates/{wa_template}/edit",
 *     "delete-form" = "/admin/structure/wa-templates/{wa_template}/delete",
 *   },
 * )
 */
class WaTemplate extends ConfigEntityBase implements WaTemplateInterface {

  /**
   * @var string
   */
  protected $id;

  /**
   * @var string
   */
  protected $label;

  /**
   * @var string
   */
  protected $langcode = 'es';

  /**
   * @var string
   */
  protected $category = 'utility';

  /**
   * @var string
   */
  protected $status_meta = 'pending';

  /**
   * @var string
   */
  protected $header_type = 'none';

  /**
   * @var string
   */
  protected $body_text = '';

  /**
   * @var string
   */
  protected $footer_text = '';

  /**
   * @var array
   */
  protected $buttons = [];

  /**
   * @var array
   */
  protected $variables_schema = [];

  /**
   * @var string|null
   */
  protected $meta_template_id = NULL;

  /**
   * {@inheritdoc}
   */
  public function getCategory(): string {
    return $this->category ?? 'utility';
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusMeta(): string {
    return $this->status_meta ?? 'pending';
  }

  /**
   * {@inheritdoc}
   */
  public function getBodyText(): string {
    return $this->body_text ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getMetaTemplateId(): ?string {
    return $this->meta_template_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getVariablesSchema(): array {
    return $this->variables_schema ?? [];
  }

}
