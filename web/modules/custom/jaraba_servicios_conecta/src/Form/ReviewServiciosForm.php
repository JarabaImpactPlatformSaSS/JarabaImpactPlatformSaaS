<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Formulario para crear/editar resenas de servicio.
 *
 * Estructura: Extiende PremiumEntityFormBase con secciones premium.
 *
 * Logica: Agrupa campos por: datos de la resena (valoracion, titulo,
 *   comentario), referencias, moderacion y respuesta del profesional.
 */
class ReviewServiciosForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'review' => [
        'label' => $this->t('Review Data'),
        'icon' => ['category' => 'ui', 'name' => 'star'],
        'description' => $this->t('Rating, title, and comment.'),
        'fields' => ['rating', 'title', 'comment'],
      ],
      'references' => [
        'label' => $this->t('References'),
        'icon' => ['category' => 'business', 'name' => 'briefcase'],
        'description' => $this->t('Provider, offering, booking, and reviewer references.'),
        'fields' => ['provider_id', 'offering_id', 'booking_id', 'reviewer_uid'],
      ],
      'moderation' => [
        'label' => $this->t('Moderation'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Review moderation status.'),
        'fields' => ['status'],
      ],
      'response' => [
        'label' => $this->t('Provider Response'),
        'icon' => ['category' => 'social', 'name' => 'social'],
        'description' => $this->t('Response from the provider and response date.'),
        'fields' => ['provider_response', 'response_date'],
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

    // Replace the rating field with a select 1-5.
    if (isset($form['rating'])) {
      $form['rating']['widget'][0]['value']['#type'] = 'select';
      $form['rating']['widget'][0]['value']['#options'] = [
        1 => $this->t('1 - Muy malo'),
        2 => $this->t('2 - Malo'),
        3 => $this->t('3 - Regular'),
        4 => $this->t('4 - Bueno'),
        5 => $this->t('5 - Excelente'),
      ];
      unset($form['rating']['widget'][0]['value']['#min']);
      unset($form['rating']['widget'][0]['value']['#max']);
    }

    // The rating field may have been moved to a section already.
    // Check inside premium sections too.
    foreach ($form as $key => $element) {
      if (str_starts_with($key, 'premium_section_') && isset($element['rating'])) {
        $form[$key]['rating']['widget'][0]['value']['#type'] = 'select';
        $form[$key]['rating']['widget'][0]['value']['#options'] = [
          1 => $this->t('1 - Muy malo'),
          2 => $this->t('2 - Malo'),
          3 => $this->t('3 - Regular'),
          4 => $this->t('4 - Bueno'),
          5 => $this->t('5 - Excelente'),
        ];
        unset($form[$key]['rating']['widget'][0]['value']['#min']);
        unset($form[$key]['rating']['widget'][0]['value']['#max']);
      }
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
