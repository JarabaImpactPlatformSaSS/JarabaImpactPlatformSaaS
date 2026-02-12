<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar menús del sitio.
 */
class SiteMenuForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['basic'] = [
      '#type' => 'details',
      '#title' => $this->t('Información del Menú'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['basic']['label'] = $form['label'];
    $form['basic']['machine_name'] = $form['machine_name'];
    $form['basic']['description'] = $form['description'];
    unset($form['label'], $form['machine_name'], $form['description']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);

    $machineName = $form_state->getValue(['machine_name', 0, 'value']) ?? '';
    if (!empty($machineName) && !preg_match('/^[a-z0-9_]+$/', $machineName)) {
      $form_state->setErrorByName('machine_name', $this->t('El nombre máquina solo puede contener letras minúsculas, números y guiones bajos.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Menú "%label" guardado.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirect('entity.site_menu.collection');
    return $result;
  }

}
