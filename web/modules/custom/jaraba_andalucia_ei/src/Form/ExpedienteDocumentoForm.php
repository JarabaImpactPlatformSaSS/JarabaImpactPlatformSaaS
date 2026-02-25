<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for ExpedienteDocumento add/edit forms.
 *
 * Adds a managed_file widget for document upload with validators
 * (pdf/doc/docx/jpg/png, 10MB max, private://expediente_documentos/).
 * On save, extracts file metadata and inherits tenant_id from participante.
 */
class ExpedienteDocumentoForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['archivo_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Archivo del documento'),
      '#description' => $this->t('Formatos permitidos: PDF, DOC, DOCX, JPG, PNG. Máximo 10 MB.'),
      '#upload_location' => 'private://expediente_documentos/',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf doc docx jpg png'],
        'file_validate_size' => [10 * 1024 * 1024],
      ],
      '#weight' => -6,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();

    // Extract metadata from uploaded file.
    $fids = $form_state->getValue('archivo_upload');
    if (!empty($fids)) {
      $fid = is_array($fids) ? reset($fids) : $fids;
      if ($fid) {
        /** @var \Drupal\file\FileInterface|null $file */
        $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
        if ($file) {
          $entity->set('archivo_nombre', $file->getFilename());
          $entity->set('archivo_mime', $file->getMimeType());
          $entity->set('archivo_tamano', (int) $file->getSize());
          // Mark file as permanent.
          $file->setPermanent();
          $file->save();
        }
      }
    }

    // Inherit tenant_id from participante.
    if ($entity->get('tenant_id')->isEmpty() && !$entity->get('participante_id')->isEmpty()) {
      $participanteId = $entity->get('participante_id')->target_id;
      if ($participanteId) {
        $participante = \Drupal::entityTypeManager()
          ->getStorage('programa_participante_ei')
          ->load($participanteId);
        if ($participante && $participante->hasField('tenant_id') && !$participante->get('tenant_id')->isEmpty()) {
          $entity->set('tenant_id', $participante->get('tenant_id')->target_id);
        }
      }
    }

    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('El documento %title ha sido guardado.', [
      '%title' => $entity->getTitulo(),
    ]));

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
