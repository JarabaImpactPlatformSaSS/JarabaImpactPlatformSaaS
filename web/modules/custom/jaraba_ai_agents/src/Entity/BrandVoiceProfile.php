<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Brand Voice Profile entity (S5-06: HAL-AI-30).
 *
 * Persists per-tenant brand voice configuration with personality traits,
 * forbidden/preferred terms, and example phrases. Replaces config-only
 * approach in TenantBrandVoiceService with entity-backed persistence.
 *
 * @ContentEntityType(
 *   id = "brand_voice_profile",
 *   label = @Translation("Brand Voice Profile"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler",
 *   },
 *   base_table = "brand_voice_profile",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *   },
 * )
 */
class BrandVoiceProfile extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Profile Name'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setRequired(TRUE);

    $fields['archetype'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Brand Archetype'))
      ->setSetting('allowed_values', [
        'professional' => 'Profesional',
        'artisan' => 'Artesanal',
        'innovative' => 'Innovador',
        'friendly' => 'Cercano',
        'expert' => 'Experto',
        'playful' => 'Divertido',
        'luxury' => 'Premium/Lujo',
        'eco' => 'Eco/Sostenible',
      ])
      ->setDefaultValue('professional');

    $fields['formality'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Formality (1-10)'))
      ->setSetting('min', 1)
      ->setSetting('max', 10)
      ->setDefaultValue(5);

    $fields['warmth'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Warmth (1-10)'))
      ->setSetting('min', 1)
      ->setSetting('max', 10)
      ->setDefaultValue(5);

    $fields['confidence'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Confidence (1-10)'))
      ->setSetting('min', 1)
      ->setSetting('max', 10)
      ->setDefaultValue(7);

    $fields['humor'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Humor (1-10)'))
      ->setSetting('min', 1)
      ->setSetting('max', 10)
      ->setDefaultValue(3);

    $fields['technical'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Technical Level (1-10)'))
      ->setSetting('min', 1)
      ->setSetting('max', 10)
      ->setDefaultValue(5);

    $fields['forbidden_terms'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Forbidden Terms (JSON array)'))
      ->setDescription(t('JSON array of terms to avoid.'));

    $fields['preferred_terms'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Preferred Terms (JSON array)'))
      ->setDescription(t('JSON array of preferred terminology.'));

    $fields['example_phrases'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Example Phrases (JSON array)'))
      ->setDescription(t('JSON array of example on-brand phrases.'));

    $fields['is_active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Active'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'));

    return $fields;
  }

  /**
   * Gets forbidden terms as array.
   */
  public function getForbiddenTerms(): array {
    $raw = $this->get('forbidden_terms')->value ?? '[]';
    return json_decode($raw, TRUE) ?: [];
  }

  /**
   * Gets preferred terms as array.
   */
  public function getPreferredTerms(): array {
    $raw = $this->get('preferred_terms')->value ?? '[]';
    return json_decode($raw, TRUE) ?: [];
  }

  /**
   * Gets example phrases as array.
   */
  public function getExamplePhrases(): array {
    $raw = $this->get('example_phrases')->value ?? '[]';
    return json_decode($raw, TRUE) ?: [];
  }

}
