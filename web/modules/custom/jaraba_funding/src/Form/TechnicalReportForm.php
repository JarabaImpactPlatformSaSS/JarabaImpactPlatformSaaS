<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de creacion/edicion de memorias tecnicas.
 *
 * Estructura: ContentEntityForm con campos agrupados por seccion.
 *
 * Logica: Las memorias tecnicas pueden crearse manualmente o
 *   generarse via IA desde la API. Este formulario permite la
 *   edicion manual de las secciones de contenido.
 */
class TechnicalReportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['identification'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Identificacion'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['title'])) {
      $form['identification']['title'] = $form['title'];
      unset($form['title']);
    }
    if (isset($form['application_id'])) {
      $form['identification']['application_id'] = $form['application_id'];
      unset($form['application_id']);
    }
    if (isset($form['report_type'])) {
      $form['identification']['report_type'] = $form['report_type'];
      unset($form['report_type']);
    }

    $form['content_group'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Contenido'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    if (isset($form['content_sections'])) {
      $form['content_group']['content_sections'] = $form['content_sections'];
      unset($form['content_sections']);
    }

    $form['ai_info'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Informacion de IA'),
      '#open' => FALSE,
      '#weight' => 2,
    ];

    if (isset($form['ai_generated'])) {
      $form['ai_info']['ai_generated'] = $form['ai_generated'];
      unset($form['ai_generated']);
    }
    if (isset($form['ai_model_used'])) {
      $form['ai_info']['ai_model_used'] = $form['ai_model_used'];
      unset($form['ai_model_used']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus(new TranslatableMarkup('Memoria tecnica "%title" creada correctamente.', [
        '%title' => $entity->label(),
      ]));
    }
    else {
      $this->messenger()->addStatus(new TranslatableMarkup('Memoria tecnica "%title" actualizada.', [
        '%title' => $entity->label(),
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
