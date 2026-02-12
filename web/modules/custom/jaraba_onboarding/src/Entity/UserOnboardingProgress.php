<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Progreso de Onboarding de Usuario.
 *
 * Rastrea el avance de cada usuario a traves de su template
 * de onboarding asignado, incluyendo pasos completados,
 * porcentaje de progreso y timestamps de inicio/fin.
 *
 * @ContentEntityType(
 *   id = "user_onboarding_progress",
 *   label = @Translation("Progreso de Onboarding"),
 *   label_collection = @Translation("Progresos de Onboarding"),
 *   label_singular = @Translation("progreso de onboarding"),
 *   label_plural = @Translation("progresos de onboarding"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_onboarding\UserOnboardingProgressListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_onboarding\Access\UserOnboardingProgressAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "user_onboarding_progress",
 *   admin_permission = "administer onboarding",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/onboarding-progress/{user_onboarding_progress}",
 *     "delete-form" = "/admin/content/onboarding-progress/{user_onboarding_progress}/delete",
 *     "collection" = "/admin/content/onboarding-progress",
 *   },
 * )
 */
class UserOnboardingProgress extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Obtiene los pasos completados como array.
   *
   * @return array
   *   Los pasos completados decodificados de JSON.
   */
  public function getCompletedSteps(): array {
    $value = $this->get('completed_steps')->value;
    if (!$value) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Establece los pasos completados desde un array.
   *
   * @param array $steps
   *   Array de step IDs completados.
   *
   * @return $this
   */
  public function setCompletedSteps(array $steps): static {
    $this->set('completed_steps', json_encode($steps));
    return $this;
  }

  /**
   * Comprueba si el onboarding esta completado.
   */
  public function isComplete(): bool {
    return (int) $this->get('progress_percentage')->value === 100;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): ?string {
    $userId = $this->get('user_id')->target_id;
    $templateId = $this->get('template_id')->target_id;
    return sprintf('Progreso #%d (User: %d, Template: %d)', $this->id(), $userId ?? 0, $templateId ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['template_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Template de Onboarding'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'onboarding_template')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['current_step'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Paso Actual'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_steps'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Pasos Completados'))
      ->setDescription(t('JSON con los IDs de pasos completados.'))
      ->setDisplayConfigurable('view', TRUE);

    $fields['progress_percentage'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Porcentaje de Progreso'))
      ->setDefaultValue(0)
      ->setSetting('min', 0)
      ->setSetting('max', 100)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['started_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Inicio'))
      ->setDisplayOptions('form', ['weight' => 4])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Completacion'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 6])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Creacion'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
