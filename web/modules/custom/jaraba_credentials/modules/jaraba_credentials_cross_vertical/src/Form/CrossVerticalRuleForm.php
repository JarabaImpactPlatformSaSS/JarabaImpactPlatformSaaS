<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar CrossVerticalRule.
 *
 * Migrado a PremiumEntityFormBase (PREMIUM-FORMS-PATTERN-001).
 */
class CrossVerticalRuleForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identidad'),
        'fields' => ['name', 'machine_name', 'description', 'rarity', 'status'],
      ],
      'rules' => [
        'label' => $this->t('Reglas Cross-Vertical'),
        'fields' => ['verticals_required', 'conditions', 'result_template_id'],
      ],
      'rewards' => [
        'label' => $this->t('Recompensas'),
        'fields' => ['bonus_credits', 'bonus_xp'],
      ],
      'metadata' => [
        'label' => $this->t('Metadata'),
        'fields' => ['tenant_id', 'uid', 'created', 'changed'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layout-template', 'variant' => 'duotone', 'color' => 'azul-corporativo'];
  }

}
