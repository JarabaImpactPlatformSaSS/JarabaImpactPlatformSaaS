<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Resultado del Quiz de Recomendación de Vertical.
 *
 * Persiste las respuestas del usuario, los scores calculados por vertical,
 * la recomendación final y la explicación generada por IA. Se vincula al
 * usuario y tenant post-registro via linkResultToUser().
 *
 * @ContentEntityType(
 *   id = "quiz_result",
 *   label = @Translation("Resultado de Quiz"),
 *   label_collection = @Translation("Resultados de Quiz"),
 *   label_singular = @Translation("resultado de quiz"),
 *   label_plural = @Translation("resultados de quiz"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\ecosistema_jaraba_core\Access\DefaultEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "quiz_result",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/quiz-results",
 *     "canonical" = "/admin/content/quiz-results/{quiz_result}",
 *     "delete-form" = "/admin/content/quiz-results/{quiz_result}/delete",
 *   },
 * )
 */
class QuizResult extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setDescription(t('Usuario vinculado (NULL si anónimo, se vincula al registrarse).'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ]);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setDescription(t('Tenant vinculado (NULL si anónimo, se vincula al registrarse).'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ]);

    $fields['answers'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Respuestas'))
      ->setDescription(t('JSON con las 4 respuestas: perfil, sector, necesidad, urgencia.'));

    $fields['scores'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Scores'))
      ->setDescription(t('JSON con scores calculados por vertical.'));

    $fields['recommended_vertical'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Vertical Recomendado'))
      ->setDescription(t('El vertical ganador del scoring.'))
      ->setSetting('max_length', 64)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => 2,
      ]);

    $fields['alternative_verticals'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Verticales Alternativos'))
      ->setDescription(t('JSON con top 2 y 3 alternativas.'));

    $fields['ai_explanation'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Explicación IA'))
      ->setDescription(t('Texto personalizado generado por IA.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 3,
      ]);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('Email capturado (NULL si no lo dio).'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'email_mailto',
        'weight' => 4,
      ]);

    $fields['source_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL de origen'))
      ->setDescription(t('URL de referencia desde donde llegó al quiz.'))
      ->setSetting('max_length', 512);

    $fields['utm_source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UTM Source'))
      ->setSetting('max_length', 128);

    $fields['utm_medium'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UTM Medium'))
      ->setSetting('max_length', 128);

    $fields['utm_campaign'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UTM Campaign'))
      ->setSetting('max_length', 128);

    $fields['ip_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IP Hash'))
      ->setDescription(t('SHA256(IP + date + salt) para GDPR.'))
      ->setSetting('max_length', 64);

    $fields['converted'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Convertido'))
      ->setDescription(t('TRUE si el usuario se registró después.'))
      ->setDefaultValue(FALSE);

    // Cross-módulo opcional (integer, no entity_reference — ENTITY-FK-001).
    $fields['crm_contact_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('CRM Contact ID'))
      ->setDescription(t('FK al Contact del CRM (cross-módulo opcional).'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creado'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Actualizado'));

    return $fields;
  }

  /**
   * Obtener las respuestas del quiz.
   */
  public function getAnswers(): array {
    return $this->get('answers')->first()?->getValue() ?? [];
  }

  /**
   * Obtener los scores calculados.
   */
  public function getScores(): array {
    return $this->get('scores')->first()?->getValue() ?? [];
  }

  /**
   * Obtener el vertical recomendado.
   */
  public function getRecommendedVertical(): string {
    return (string) $this->get('recommended_vertical')->value;
  }

  /**
   * Verificar si se ha convertido (registrado).
   */
  public function isConverted(): bool {
    return (bool) $this->get('converted')->value;
  }

}
