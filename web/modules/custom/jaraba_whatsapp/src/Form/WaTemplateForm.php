<?php

declare(strict_types=1);

namespace Drupal\jaraba_whatsapp\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for WaTemplate config entities.
 */
class WaTemplateForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\jaraba_whatsapp\Entity\WaTemplateInterface $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre del Template'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\jaraba_whatsapp\Entity\WaTemplate::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Categoria'),
      '#options' => [
        'utility' => $this->t('Utility (transaccional)'),
        'marketing' => $this->t('Marketing'),
        'authentication' => $this->t('Authentication'),
      ],
      '#default_value' => $entity->getCategory(),
    ];

    $form['status_meta'] = [
      '#type' => 'select',
      '#title' => $this->t('Estado en Meta'),
      '#options' => [
        'pending' => $this->t('Pendiente'),
        'approved' => $this->t('Aprobado'),
        'rejected' => $this->t('Rechazado'),
      ],
      '#default_value' => $entity->getStatusMeta(),
    ];

    $form['header_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Tipo de cabecera'),
      '#options' => [
        'none' => $this->t('Ninguna'),
        'text' => $this->t('Texto'),
        'image' => $this->t('Imagen'),
        'document' => $this->t('Documento'),
      ],
      '#default_value' => $entity->get('header_type') ?? 'none',
    ];

    $form['body_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Cuerpo del template'),
      '#description' => $this->t('Usa {{1}}, {{2}}... para variables. Max 1024 caracteres.'),
      '#default_value' => $entity->getBodyText(),
      '#rows' => 5,
      '#maxlength' => 1024,
    ];

    $form['footer_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pie del template'),
      '#maxlength' => 60,
      '#default_value' => $entity->get('footer_text') ?? '',
    ];

    $form['meta_template_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Meta Template ID'),
      '#description' => $this->t('ID asignado por Meta tras la aprobacion.'),
      '#default_value' => $entity->getMetaTemplateId(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Template WhatsApp %label guardado.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

}
