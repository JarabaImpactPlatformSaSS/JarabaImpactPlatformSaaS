<?php

declare(strict_types=1);

namespace Drupal\jaraba_ab_testing\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar variantes A/B.
 */
class ABVariantForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identity' => [
        'label' => $this->t('Identity'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Variant name and technical key.'),
        'fields' => ['label', 'variant_key'],
      ],
      'experiment' => [
        'label' => $this->t('Experiment'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Parent experiment reference.'),
        'fields' => ['experiment_id'],
      ],
      'configuration' => [
        'label' => $this->t('Configuration'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'description' => $this->t('Control flag, traffic weight and variant data.'),
        'fields' => ['is_control', 'traffic_weight', 'variant_data'],
      ],
      'metrics' => [
        'label' => $this->t('Metrics'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Visitor, conversion and revenue counters.'),
        'fields' => ['visitors', 'conversions', 'revenue'],
      ],
      'tenant' => [
        'label' => $this->t('Tenant'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Multi-tenant assignment.'),
        'fields' => ['tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'chart'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;

    // Auto-generar variant_key desde el label si esta vacio.
    if (empty($entity->get('variant_key')->value) && !empty($entity->label())) {
      $variant_key = $this->generateVariantKey($entity->label());
      $entity->set('variant_key', $variant_key);
    }

    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

  /**
   * Genera un variant_key a partir de un label.
   *
   * @param string $label
   *   El nombre de la variante.
   *
   * @return string
   *   El variant_key generado.
   */
  protected function generateVariantKey(string $label): string {
    $key = mb_strtolower($label);
    $key = preg_replace('/[áàäâ]/u', 'a', $key);
    $key = preg_replace('/[éèëê]/u', 'e', $key);
    $key = preg_replace('/[íìïî]/u', 'i', $key);
    $key = preg_replace('/[óòöô]/u', 'o', $key);
    $key = preg_replace('/[úùüû]/u', 'u', $key);
    $key = preg_replace('/ñ/u', 'n', $key);
    $key = preg_replace('/[^a-z0-9\s_-]/', '', $key);
    $key = preg_replace('/[\s-]+/', '_', $key);
    return trim($key, '_');
  }

}
