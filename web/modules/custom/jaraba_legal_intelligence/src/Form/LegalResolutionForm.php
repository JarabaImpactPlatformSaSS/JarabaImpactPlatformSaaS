<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar resoluciones legales.
 *
 * ESTRUCTURA: Extiende ContentEntityForm organizando campos
 *   en grupos colapsables (details) para facilitar la edicion
 *   de entidades con gran cantidad de campos.
 *
 * LOGICA: Agrupa los campos en secciones tematicas: identificacion,
 *   metadatos, contenido, datos generados por IA, campos UE,
 *   datos Qdrant y SEO. Los grupos de contenido, IA, UE, Qdrant
 *   y SEO se muestran colapsados por defecto.
 *
 * RELACIONES:
 * - LegalResolutionForm -> LegalResolution entity (edita)
 * - LegalResolutionForm <- Drupal routing (invocado por add/edit)
 */
class LegalResolutionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // Grupo de identificacion.
    $form['identification'] = [
      '#type' => 'details',
      '#title' => $this->t('Identificacion'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    $identification_fields = ['source_id', 'external_ref', 'content_hash'];
    foreach ($identification_fields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'identification';
      }
    }

    // Grupo de metadatos.
    $form['metadata'] = [
      '#type' => 'details',
      '#title' => $this->t('Metadatos'),
      '#open' => FALSE,
      '#weight' => 10,
    ];

    $metadata_fields = [
      'title', 'resolution_type', 'issuing_body', 'jurisdiction',
      'date_issued', 'date_published', 'status_legal',
    ];
    foreach ($metadata_fields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'metadata';
      }
    }

    // Grupo de contenido.
    $form['content'] = [
      '#type' => 'details',
      '#title' => $this->t('Contenido'),
      '#open' => FALSE,
      '#weight' => 20,
    ];

    $content_fields = ['full_text', 'original_url'];
    foreach ($content_fields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'content';
      }
    }

    // Grupo de datos generados por IA.
    $form['ai_generated'] = [
      '#type' => 'details',
      '#title' => $this->t('Datos Generados por IA'),
      '#open' => FALSE,
      '#weight' => 30,
    ];

    $ai_fields = ['abstract_ai', 'key_holdings', 'topics', 'cited_legislation'];
    foreach ($ai_fields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'ai_generated';
      }
    }

    // Grupo de campos UE.
    $form['eu_fields'] = [
      '#type' => 'details',
      '#title' => $this->t('Campos Union Europea'),
      '#open' => FALSE,
      '#weight' => 40,
    ];

    $eu_field_list = [
      'celex_number', 'ecli', 'case_number', 'procedure_type',
      'respondent_state', 'cedh_articles', 'eu_legal_basis',
      'advocate_general', 'importance_level', 'language_original',
      'impact_spain',
    ];
    foreach ($eu_field_list as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'eu_fields';
      }
    }

    // Grupo de Qdrant / NLP.
    $form['qdrant'] = [
      '#type' => 'details',
      '#title' => $this->t('Qdrant / NLP'),
      '#open' => FALSE,
      '#weight' => 50,
    ];

    $qdrant_fields = ['vector_ids', 'qdrant_collection', 'last_nlp_processed'];
    foreach ($qdrant_fields as $field) {
      if (isset($form[$field])) {
        $form[$field]['#group'] = 'qdrant';
      }
    }

    // Grupo de SEO.
    $form['seo'] = [
      '#type' => 'details',
      '#title' => $this->t('SEO'),
      '#open' => FALSE,
      '#weight' => 60,
    ];

    if (isset($form['seo_slug'])) {
      $form['seo_slug']['#group'] = 'seo';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();
    $message_args = ['%label' => $entity->toLink()->toString()];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Resolucion legal %label creada correctamente.', $message_args));
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('Resolucion legal %label actualizada correctamente.', $message_args));
        break;
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
