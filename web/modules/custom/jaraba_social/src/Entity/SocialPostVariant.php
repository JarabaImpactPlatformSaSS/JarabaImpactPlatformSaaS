<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad SocialPostVariant para variantes A/B de posts sociales.
 *
 * PROPOSITO:
 * Almacena variantes de contenido para un mismo post social,
 * permitiendo testing A/B de diferentes copies, hashtags y CTAs.
 * Cada variante registra metricas de rendimiento individuales.
 *
 * MULTI-TENANT:
 * Cada variante esta asociada a un tenant_id especifico.
 *
 * @ContentEntityType(
 *   id = "social_post_variant",
 *   label = @Translation("Variante de Post Social"),
 *   label_collection = @Translation("Variantes de Posts Sociales"),
 *   label_singular = @Translation("variante de post social"),
 *   label_plural = @Translation("variantes de posts sociales"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_social\ListBuilder\SocialPostVariantListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_social\Form\SocialPostVariantForm",
 *       "add" = "Drupal\jaraba_social\Form\SocialPostVariantForm",
 *       "edit" = "Drupal\jaraba_social\Form\SocialPostVariantForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_social\Access\SocialPostVariantAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "social_post_variant",
 *   fieldable = TRUE,
 *   admin_permission = "administer social media",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "variant_name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/social-post-variants/{social_post_variant}",
 *     "add-form" = "/admin/content/social-post-variants/add",
 *     "edit-form" = "/admin/content/social-post-variants/{social_post_variant}/edit",
 *     "delete-form" = "/admin/content/social-post-variants/{social_post_variant}/delete",
 *     "collection" = "/admin/content/social-post-variants",
 *   },
 *   field_ui_base_route = "entity.social_post_variant.settings",
 * )
 */
class SocialPostVariant extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant propietario de la variante.'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['post_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Post Social'))
      ->setDescription(t('Post social al que pertenece esta variante.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'social_post')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['variant_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nombre de Variante'))
      ->setDescription(t('Nombre identificativo de la variante, por ejemplo "Variante A".'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 100)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Contenido'))
      ->setDescription(t('Texto del post para esta variante.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['media_urls'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('URLs de Medios'))
      ->setDescription(t('Array JSON con URLs de imagenes/videos asociados.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['hashtags'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Hashtags'))
      ->setDescription(t('Hashtags asociados a esta variante.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['call_to_action'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Call to Action'))
      ->setDescription(t('Llamada a la accion de esta variante.'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['is_winner'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Ganadora'))
      ->setDescription(t('Indica si esta variante fue seleccionada como ganadora del test A/B.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['impressions'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Impresiones'))
      ->setDescription(t('Numero total de impresiones de esta variante.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['engagements'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Interacciones'))
      ->setDescription(t('Numero total de interacciones (likes, comentarios, etc.).'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['clicks'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Clicks'))
      ->setDescription(t('Numero total de clicks en la variante.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['shares'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Compartidos'))
      ->setDescription(t('Numero total de veces compartida.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['engagement_rate'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Tasa de Engagement'))
      ->setDescription(t('Porcentaje de engagement calculado (engagements/impresiones).'))
      ->setSettings([
        'precision' => 8,
        'scale' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'number',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Modificado'));

    return $fields;
  }

  /**
   * Obtiene el nombre de la variante.
   */
  public function getVariantName(): string {
    return $this->get('variant_name')->value ?? '';
  }

  /**
   * Indica si esta variante es la ganadora.
   */
  public function isWinner(): bool {
    return (bool) $this->get('is_winner')->value;
  }

  /**
   * Obtiene la tasa de engagement.
   */
  public function getEngagementRate(): float {
    return (float) ($this->get('engagement_rate')->value ?? 0);
  }

  /**
   * Obtiene el numero de impresiones.
   */
  public function getImpressions(): int {
    return (int) ($this->get('impressions')->value ?? 0);
  }

  /**
   * Obtiene el numero de interacciones.
   */
  public function getEngagements(): int {
    return (int) ($this->get('engagements')->value ?? 0);
  }

}
