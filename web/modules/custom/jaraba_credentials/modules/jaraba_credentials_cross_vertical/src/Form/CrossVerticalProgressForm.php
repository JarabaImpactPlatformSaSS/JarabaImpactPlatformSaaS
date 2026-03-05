<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_cross_vertical\Form;

use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar CrossVerticalProgress.
 *
 * Migrado a PremiumEntityFormBase (PREMIUM-FORMS-PATTERN-001).
 */
class CrossVerticalProgressForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'progress' => [
        'label' => $this->t('Progreso'),
        'fields' => ['rule_id', 'uid', 'progress_by_vertical', 'total_progress', 'status'],
      ],
      'completion' => [
        'label' => $this->t('Completado'),
        'fields' => ['completed_at', 'awarded_credential_id'],
      ],
      'metadata' => [
        'label' => $this->t('Metadata'),
        'fields' => ['tenant_id', 'created', 'changed'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'layout-template', 'variant' => 'duotone', 'color' => 'verde-innovacion'];
  }

}
