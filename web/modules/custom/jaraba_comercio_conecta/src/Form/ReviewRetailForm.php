<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for creating/editing retail reviews.
 */
class ReviewRetailForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'resena' => [
        'label' => $this->t('Resena'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'description' => $this->t('Contenido de la resena.'),
        'fields' => ['title', 'body', 'rating', 'entity_type_ref', 'entity_id_ref', 'photos'],
      ],
      'moderacion' => [
        'label' => $this->t('Moderacion'),
        'icon' => ['category' => 'ui', 'name' => 'check'],
        'description' => $this->t('Estado de moderacion y respuesta del comercio.'),
        'fields' => ['status', 'merchant_response'],
      ],
      'estadisticas' => [
        'label' => $this->t('Estadisticas'),
        'icon' => ['category' => 'analytics', 'name' => 'chart'],
        'description' => $this->t('Metricas calculadas automaticamente.'),
        'fields' => ['helpful_count'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'star'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Computed field: helpful_count is read-only.
    if (isset($form['premium_section_estadisticas']['helpful_count'])) {
      $form['premium_section_estadisticas']['helpful_count']['#disabled'] = TRUE;
    }
    elseif (isset($form['helpful_count'])) {
      $form['helpful_count']['#disabled'] = TRUE;
    }

    return $form;
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
