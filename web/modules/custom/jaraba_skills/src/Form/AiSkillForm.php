<?php

declare(strict_types=1);

namespace Drupal\jaraba_skills\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing AI skills.
 */
class AiSkillForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identification'),
        'icon' => ['category' => 'ai', 'name' => 'brain'],
        'description' => $this->t('Skill name and type.'),
        'fields' => ['name', 'skill_type', 'vertical_id', 'agent_type'],
      ],
      'config' => [
        'label' => $this->t('Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('Prompt content and priority.'),
        'fields' => ['content', 'priority'],
      ],
      'experiment' => [
        'label' => $this->t('Experiment'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('A/B testing configuration.'),
        'fields' => ['experiment_id', 'experiment_variant'],
      ],
      'status' => [
        'label' => $this->t('Status'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activation and tenant.'),
        'fields' => ['is_active', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ai', 'name' => 'brain'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Attach Monaco Editor for the content field.
    $form['#attached']['library'][] = 'jaraba_skills/skill.prompt-editor';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl(Url::fromRoute('jaraba_skills.dashboard'));
    return $result;
  }

}
