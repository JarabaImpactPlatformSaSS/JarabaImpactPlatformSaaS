<?php

declare(strict_types=1);

namespace Drupal\jaraba_onboarding\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Progreso de Onboarding de Tenant.
 *
 * Rastrea el avance de un tenant a traves del wizard de
 * 7 pasos de configuracion inicial, incluyendo datos
 * capturados por paso y pasos omitidos.
 *
 * Fase 5 — Doc 179.
 *
 * @ContentEntityType(
 *   id = "tenant_onboarding_progress",
 *   label = @Translation("Progreso de Onboarding de Tenant"),
 *   label_collection = @Translation("Progresos de Onboarding de Tenant"),
 *   label_singular = @Translation("progreso de onboarding de tenant"),
 *   label_plural = @Translation("progresos de onboarding de tenant"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_onboarding\TenantOnboardingProgressListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_onboarding\Access\TenantOnboardingProgressAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "tenant_onboarding_progress",
 *   admin_permission = "administer onboarding",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/tenant-onboarding-progress/{tenant_onboarding_progress}",
 *     "delete-form" = "/admin/content/tenant-onboarding-progress/{tenant_onboarding_progress}/delete",
 *     "collection" = "/admin/content/tenant-onboarding-progress",
 *   },
 *   field_ui_base_route = "entity.tenant_onboarding_progress.settings",
 * )
 */
class TenantOnboardingProgress extends ContentEntityBase implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * Constantes de pasos del wizard.
   */
  public const STEP_WELCOME = 1;
  public const STEP_IDENTITY = 2;
  public const STEP_FISCAL = 3;
  public const STEP_PAYMENTS = 4;
  public const STEP_TEAM = 5;
  public const STEP_CONTENT = 6;
  public const STEP_LAUNCH = 7;
  public const TOTAL_STEPS = 7;

  /**
   * Obtiene los pasos completados como array.
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
   * Establece los pasos completados.
   */
  public function setCompletedSteps(array $steps): static {
    $this->set('completed_steps', json_encode(array_values(array_unique($steps))));
    return $this;
  }

  /**
   * Obtiene los datos capturados por paso.
   */
  public function getStepData(): array {
    $value = $this->get('step_data')->value;
    if (!$value) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Establece datos para un paso especifico.
   */
  public function setStepData(int $step, array $data): static {
    $allData = $this->getStepData();
    $allData[$step] = $data;
    $this->set('step_data', json_encode($allData));
    return $this;
  }

  /**
   * Obtiene los pasos omitidos.
   */
  public function getSkippedSteps(): array {
    $value = $this->get('skipped_steps')->value;
    if (!$value) {
      return [];
    }
    $decoded = json_decode($value, TRUE);
    return is_array($decoded) ? $decoded : [];
  }

  /**
   * Marca un paso como omitido.
   */
  public function addSkippedStep(int $step): static {
    $skipped = $this->getSkippedSteps();
    if (!in_array($step, $skipped, TRUE)) {
      $skipped[] = $step;
    }
    $this->set('skipped_steps', json_encode($skipped));
    return $this;
  }

  /**
   * Comprueba si el wizard esta completado.
   */
  public function isComplete(): bool {
    return (int) $this->get('current_step')->value >= self::TOTAL_STEPS
      && $this->get('completed_at')->value !== NULL;
  }

  /**
   * Calcula el porcentaje de progreso.
   */
  public function getProgressPercentage(): int {
    $completed = count($this->getCompletedSteps());
    $skipped = count($this->getSkippedSteps());
    $total = self::TOTAL_STEPS;
    return (int) round((($completed + $skipped) / $total) * 100);
  }

  /**
   * {@inheritdoc}
   */
  public function label(): ?string {
    $tenantId = $this->get('tenant_id')->target_id;
    return sprintf('Wizard Onboarding #%d (Tenant: %d, Paso: %d/%d)',
      $this->id(),
      $tenantId ?? 0,
      (int) $this->get('current_step')->value,
      self::TOTAL_STEPS
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Tenant'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'group')
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Usuario'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['vertical'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Vertical'))
      ->setRequired(TRUE)
      ->setSetting('allowed_values', [
        'agroconecta' => 'AgroConecta',
        'comercioconecta' => 'ComercioConecta',
        'serviciosconecta' => 'ServiciosConecta',
        'empleabilidad' => 'Empleabilidad',
        'emprendimiento' => 'Emprendimiento',
      ])
      ->setDisplayOptions('form', ['weight' => 2])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['current_step'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Paso Actual'))
      ->setDefaultValue(1)
      ->setSetting('min', 1)
      ->setSetting('max', self::TOTAL_STEPS)
      ->setDisplayOptions('form', ['weight' => 3])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['completed_steps'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Pasos Completados'))
      ->setDescription(t('JSON con los numeros de pasos completados.'))
      ->setDefaultValue('[]')
      ->setDisplayConfigurable('view', TRUE);

    $fields['step_data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Datos por Paso'))
      ->setDescription(t('JSON con datos capturados en cada paso.'))
      ->setDefaultValue('{}')
      ->setDisplayConfigurable('view', TRUE);

    $fields['skipped_steps'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Pasos Omitidos'))
      ->setDescription(t('JSON con los numeros de pasos omitidos.'))
      ->setDefaultValue('[]')
      ->setDisplayConfigurable('view', TRUE);

    $fields['time_spent_seconds'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Tiempo Invertido (segundos)'))
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['started_at'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Fecha de Inicio'));

    $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Fecha de Completacion'))
      ->setDisplayOptions('form', ['weight' => 5])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Fecha de Modificacion'));

    return $fields;
  }

}
