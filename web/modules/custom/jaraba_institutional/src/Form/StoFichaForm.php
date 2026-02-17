<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de creacion de fichas tecnicas STO (append-only).
 *
 * ESTRUCTURA: ContentEntityForm con 3 fieldsets organizando los campos:
 *   identificacion, contenido y generacion. Solo soporta creacion
 *   — las fichas STO son entidades append-only (no editables).
 *
 * LOGICA: Una vez generada, la ficha STO queda inmutable como
 *   documento de registro del itinerario de insercion del
 *   participante. Solo se gestiona el caso SAVED_NEW.
 */
class StoFichaForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset 1: Identificacion ---
    $form['identification_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Identificacion'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $identificationFields = ['participant_id', 'ficha_type'];
    foreach ($identificationFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'identification_group';
      }
    }

    // --- Fieldset 2: Contenido ---
    $form['content_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Contenido de la ficha'),
      '#open' => TRUE,
      '#weight' => 10,
    ];

    $contentFields = [
      'diagnostico_empleabilidad',
      'itinerario_insercion',
      'acciones_orientacion',
      'resultados',
    ];
    foreach ($contentFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'content_group';
      }
    }

    // --- Fieldset 3: Generacion ---
    $form['generation_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Generacion'),
      '#open' => FALSE,
      '#weight' => 20,
    ];

    $generationFields = ['ai_generated', 'ai_model_used'];
    foreach ($generationFields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'generation_group';
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $fichaNumber = $entity->get('ficha_number')->value ?? '';

    // Append-only: solo gestionamos SAVED_NEW.
    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus(
        $this->t('Ficha STO «@number» generada correctamente.', [
          '@number' => $fichaNumber,
        ]),
      );
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
