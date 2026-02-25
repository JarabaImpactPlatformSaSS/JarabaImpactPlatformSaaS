<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar colecciones AgroConecta.
 */
class AgroCollectionForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'collection' => [
        'label' => $this->t('Datos de la colección'),
        'icon' => ['category' => 'verticals', 'name' => 'agro'],
        'fields' => ['name', 'slug', 'description', 'image', 'type'],
      ],
      'content' => [
        'label' => $this->t('Contenido'),
        'icon' => ['category' => 'ui', 'name' => 'edit'],
        'fields' => ['product_ids', 'rules'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'fields' => ['position', 'is_featured', 'is_active', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'verticals', 'name' => 'agro'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $type = $form_state->getValue(['type', 0, 'value']);

    // Validar JSON de product_ids si es colección manual.
    if ($type === 'manual') {
      $product_ids = $form_state->getValue(['product_ids', 0, 'value']);
      if ($product_ids) {
        $decoded = json_decode($product_ids, TRUE);
        if (!is_array($decoded)) {
          $form_state->setErrorByName('product_ids', $this->t('Los IDs de productos deben ser un JSON array válido. Ej: [1, 5, 12]'));
        }
      }
    }

    // Validar JSON de rules si es colección smart.
    if ($type === 'smart') {
      $rules = $form_state->getValue(['rules', 0, 'value']);
      if ($rules) {
        $decoded = json_decode($rules, TRUE);
        if (!is_array($decoded)) {
          $form_state->setErrorByName('rules', $this->t('Las reglas deben ser un JSON object válido. Ej: {"category_id": 5, "min_rating": 4}'));
        }
      }
    }

    // Validar tipo válido.
    $valid_types = ['manual', 'smart'];
    if ($type && !in_array($type, $valid_types)) {
      $form_state->setErrorByName('type', $this->t('Tipo de colección no válido. Tipos permitidos: @types.', [
        '@types' => implode(', ', $valid_types),
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
