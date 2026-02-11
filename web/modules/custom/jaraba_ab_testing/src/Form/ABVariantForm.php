<?php

namespace Drupal\jaraba_ab_testing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar variantes A/B.
 *
 * Estructura: Extiende ContentEntityForm con auto-generación de
 *   variant_key desde el label.
 *
 * Lógica: Al guardar, si el campo variant_key está vacío se genera
 *   automáticamente a partir del label (transliteración + lowercase
 *   + guiones bajos). Redirige al listado tras guardar.
 *
 * Sintaxis: Drupal 11 — return types estrictos, SAVED_NEW/SAVED_UPDATED.
 */
class ABVariantForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;

    // Auto-generar variant_key desde el label si está vacío.
    if (empty($entity->get('variant_key')->value) && !empty($entity->label())) {
      $variant_key = $this->generateVariantKey($entity->label());
      $entity->set('variant_key', $variant_key);
    }

    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Variante A/B %label creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Variante A/B %label actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

  /**
   * Genera un variant_key a partir de un label.
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
   *   El nombre de la variante.
   *
   * @return string
   *   El variant_key generado.
   */
  protected function generateVariantKey(string $label): string {
    $key = mb_strtolower($label);
    $key = preg_replace('/[áàäâ]/u', 'a', $key);
    $key = preg_replace('/[éèëê]/u', 'e', $key);
    $key = preg_replace('/[íìïî]/u', 'i', $key);
    $key = preg_replace('/[óòöô]/u', 'o', $key);
    $key = preg_replace('/[úùüû]/u', 'u', $key);
    $key = preg_replace('/ñ/u', 'n', $key);
    $key = preg_replace('/[^a-z0-9\s_-]/', '', $key);
    $key = preg_replace('/[\s-]+/', '_', $key);
    return trim($key, '_');
  }

}
