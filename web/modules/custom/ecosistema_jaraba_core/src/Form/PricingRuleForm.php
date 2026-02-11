<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de creación/edición de PricingRule.
 *
 * PROPÓSITO:
 * Permite a administradores crear y editar reglas de precios
 * personalizadas por plan y métrica.
 *
 * LÓGICA:
 * - Organiza campos en 3 grupos: identificación, configuración de precio, estado
 * - Valida que tiers JSON sea válido cuando el modelo no es flat
 * - Valida unicidad de combinación plan+métrica
 */
class PricingRuleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo 1: Identificación.
    $form['identification'] = [
      '#type' => 'details',
      '#title' => $this->t('Identificación'),
      '#open' => TRUE,
      '#weight' => -20,
    ];
    $form['name']['#group'] = 'identification';
    $form['plan_id']['#group'] = 'identification';
    $form['metric_type']['#group'] = 'identification';

    // Grupo 2: Configuración de precio.
    $form['pricing_config'] = [
      '#type' => 'details',
      '#title' => $this->t('Configuración de Precio'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['pricing_model']['#group'] = 'pricing_config';
    $form['included_quantity']['#group'] = 'pricing_config';
    $form['unit_price']['#group'] = 'pricing_config';
    $form['tiers']['#group'] = 'pricing_config';
    $form['currency']['#group'] = 'pricing_config';

    // Grupo 3: Estado.
    $form['status_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Estado'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['is_active']['#group'] = 'status_group';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $model = $form_state->getValue('pricing_model')[0]['value'] ?? 'flat';

    // Validar JSON de tiers si el modelo no es flat.
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

    $entity = $this->entity;
    $message = $result === SAVED_NEW
      ? $this->t('Regla de precios %label creada.', ['%label' => $entity->label()])
      : $this->t('Regla de precios %label actualizada.', ['%label' => $entity->label()]);

    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
