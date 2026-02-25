<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for search synonyms.
 */
class SearchSynonymForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'sinonimos' => [
        'label' => $this->t('Sinonimos de Busqueda'),
        'icon' => ['category' => 'analytics', 'name' => 'search'],
        'description' => $this->t('Define el termino original y sus sinonimos para expandir busquedas.'),
        'fields' => ['term', 'synonyms', 'is_active'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'analytics', 'name' => 'search'];
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
