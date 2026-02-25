<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Premium form for creating/editing PricingRule entities.
 */
class PricingRuleForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'identification' => [
        'label' => $this->t('Identificación'),
        'icon' => ['category' => 'fiscal', 'name' => 'coins'],
        'description' => $this->t('Nombre, plan y métrica.'),
        'fields' => ['name', 'plan_id', 'metric_type'],
      ],
      'pricing' => [
        'label' => $this->t('Configuración de Precio'),
        'icon' => ['category' => 'fiscal', 'name' => 'receipt'],
        'description' => $this->t('Modelo, cantidades y escalones.'),
        'fields' => ['pricing_model', 'included_quantity', 'unit_price', 'tiers', 'currency'],
      ],
      'status' => [
        'label' => $this->t('Estado'),
        'icon' => ['category' => 'ui', 'name' => 'toggle'],
        'description' => $this->t('Activación de la regla.'),
        'fields' => ['is_active'],
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
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $model = $form_state->getValue('pricing_model')[0]['value'] ?? 'flat';

    // Validate tiers JSON when model is not flat.
    if ($model !== 'flat') {
      $tiersRaw = $form_state->getValue('tiers')[0]['value'] ?? '';
      if (!empty($tiersRaw)) {
        $decoded = json_decode($tiersRaw, TRUE);
        if (!is_array($decoded)) {
          $form_state->setErrorByName('tiers', $this->t('Los escalones de precios deben ser un JSON válido.'));
        }
        else {
          foreach ($decoded as $i => $tier) {
            if (!isset($tier['from']) || !isset($tier['to']) || !isset($tier['price'])) {
              $form_state->setErrorByName('tiers', $this->t('Cada escalón debe tener las claves "from", "to" y "price". Error en el escalón @n.', ['@n' => $i + 1]));
              break;
            }
          }
        }
      }
      elseif (in_array($model, ['tiered', 'volume', 'package'])) {
        $form_state->setErrorByName('tiers', $this->t('Los modelos escalonado, volumen y paquete requieren definición de escalones.'));
      }
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
