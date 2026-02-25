<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Form\PremiumEntityFormBase;

/**
 * Premium form for ExpedienteDocumento add/edit.
 *
 * Adds a managed_file widget for document upload with validators
 * (pdf/doc/docx/jpg/png, 10MB max, private://expediente_documentos/).
 * On save, extracts file metadata and inherits tenant_id from participante.
 */
class ExpedienteDocumentoForm extends PremiumEntityFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getSectionDefinitions(): array {
    return [
      'document' => [
        'label' => $this->t('Documento'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Título, categoría y archivo.'),
        'fields' => ['titulo', 'categoria', 'participante_id'],
      ],
      'file_metadata' => [
        'label' => $this->t('Metadatos del Archivo'),
        'icon' => ['category' => 'ui', 'name' => 'document'],
        'description' => $this->t('Información del archivo subido.'),
        'fields' => ['archivo_vault_id', 'archivo_nombre', 'archivo_mime', 'archivo_tamano'],
      ],
      'review' => [
        'label' => $this->t('Revisión'),
        'icon' => ['category' => 'ui', 'name' => 'shield'],
        'description' => $this->t('Estado de revisión humana e IA.'),
        'fields' => ['estado_revision', 'revision_ia_score', 'revision_ia_feedback', 'revision_humana_notas', 'revisor_id'],
      ],
      'signature' => [
        'label' => $this->t('Firma Digital'),
        'icon' => ['category' => 'ui', 'name' => 'lock'],
        'description' => $this->t('Firma digital y certificado.'),
        'fields' => ['firmado', 'firma_fecha', 'firma_certificado_info'],
      ],
      'config' => [
        'label' => $this->t('Configuración'),
        'icon' => ['category' => 'ui', 'name' => 'settings'],
        'description' => $this->t('STO, vencimiento y estado.'),
        'fields' => ['requerido_sto', 'sto_sincronizado', 'fecha_vencimiento', 'status', 'tenant_id'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getFormIcon(): array {
    return ['category' => 'ui', 'name' => 'document'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    // Add managed_file upload widget in the document section.
    $form['premium_section_document']['archivo_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Archivo del documento'),
      '#description' => $this->t('Formatos permitidos: PDF, DOC, DOCX, JPG, PNG. Máximo 10 MB.'),
      '#upload_location' => 'private://expediente_documentos/',
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf doc docx jpg png'],
        'file_validate_size' => [10 * 1024 * 1024],
      ],
      '#weight' => 100,
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
    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

}
