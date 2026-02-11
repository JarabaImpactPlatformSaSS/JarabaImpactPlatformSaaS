<?php

namespace Drupal\jaraba_ab_testing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar experimentos A/B.
 *
 * Estructura: Extiende ContentEntityForm con auto-generación de
 *   machine_name desde el label.
 *
 * Lógica: Al guardar, si el campo machine_name está vacío se genera
 *   automáticamente a partir del label (transliteración + lowercase
 *   + guiones bajos). Redirige al listado tras guardar.
 *
 * Sintaxis: Drupal 11 — return types estrictos, SAVED_NEW/SAVED_UPDATED.
 */
class ABExperimentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;

    // Auto-generar machine_name desde el label si está vacío.
    if (empty($entity->get('machine_name')->value) && !empty($entity->label())) {
      $machine_name = $this->generateMachineName($entity->label());
      $entity->set('machine_name', $machine_name);
    }

    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Experimento A/B %label creado.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Experimento A/B %label actualizado.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

  /**
   * Genera un machine_name a partir de un label.
   *
   * Estructura: Método protegido auxiliar del formulario.
   *
   * Lógica: Transliteración manual de caracteres acentuados del español,
   *   conversión a minúsculas, eliminación de caracteres especiales
   *   y reemplazo de espacios por guiones bajos.
   *
   * Sintaxis: Expresiones regulares con soporte Unicode (flag /u).
   *
   * @param string $label
   *   El nombre del experimento.
   *
   * @return string
   *   El machine_name generado.
   */
  protected function generateMachineName(string $label): string {
    $name = mb_strtolower($label);
    $name = preg_replace('/[áàäâ]/u', 'a', $name);
    $name = preg_replace('/[éèëê]/u', 'e', $name);
    $name = preg_replace('/[íìïî]/u', 'i', $name);
    $name = preg_replace('/[óòöô]/u', 'o', $name);
    $name = preg_replace('/[úùüû]/u', 'u', $name);
    $name = preg_replace('/ñ/u', 'n', $name);
    $name = preg_replace('/[^a-z0-9\s_-]/', '', $name);
    $name = preg_replace('/[\s-]+/', '_', $name);
    return trim($name, '_');
  }

}
