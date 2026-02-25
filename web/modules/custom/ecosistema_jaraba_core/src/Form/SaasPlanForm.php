<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Premium form for creating/editing SaasPlan entities.
 */
class SaasPlanForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'basic' => [
        'label' => $this->t('Información del Plan'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Nombre, vertical, estado y orden.'),
        'fields' => ['name', 'vertical', 'status', 'weight'],
      ],
      'pricing' => [
        'label' => $this->t('Precios'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Precios mensuales, anuales y Stripe.'),
        'fields' => ['price_monthly', 'price_yearly', 'stripe_price_id'],
      ],
      'features' => [
        'label' => $this->t('Features Incluidas'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'description' => $this->t('Funcionalidades incluidas en el plan.'),
        'fields' => ['features'],
      ],
      'limits' => [
        'label' => $this->t('Límites del Plan'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Define los límites en formato JSON. Usa -1 para ilimitado.'),
        'fields' => ['limits'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'fiscal', 'name' => 'coins'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Add description and help for limits JSON field.
    $section = 'premium_section_limits';
    if (isset($form[$section]['limits']['widget'][0]['value'])) {
      $form[$section]['limits']['widget'][0]['value']['#description'] = $this->t('Ejemplo: {"productores": 50, "storage_gb": 25, "ai_queries": 100}');
    }

    $form[$section]['help'] = [
      '#type' => 'markup',
      '#markup' => '<div class="description">' .
        '<strong>' . $this->t('Claves disponibles:') . '</strong><br>' .
        '<code>productores</code> - ' . $this->t('Máximo de productores') . '<br>' .
        '<code>storage_gb</code> - ' . $this->t('Almacenamiento en GB') . '<br>' .
        '<code>ai_queries</code> - ' . $this->t('Queries de IA por mes (0 = no incluido)') . '<br>' .
        '<code>webhooks</code> - ' . $this->t('Número de webhooks') . '<br>' .
        '</div>',
      '#weight' => 100,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    // Validate JSON limits.
    $limits = $form_state->getValue(['limits', 0, 'value']);
    if ($limits) {
      $decoded = json_decode($limits, TRUE);
      if ($decoded === NULL) {
        $form_state->setErrorByName('limits', $this->t('Los límites deben ser un JSON válido.'));
      }
    }

    // Validate yearly price <= monthly * 12.
    $monthly = (float) $form_state->getValue(['price_monthly', 0, 'value']);
    $yearly = (float) $form_state->getValue(['price_yearly', 0, 'value']);

    if ($yearly > 0 && $yearly > ($monthly * 12)) {
      $form_state->setErrorByName('price_yearly', $this->t('El precio anual debería ser menor o igual que el mensual × 12 (actualmente @expected €).', [
        '@expected' => number_format($monthly * 12, 2),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
    return $result;
  }

}
