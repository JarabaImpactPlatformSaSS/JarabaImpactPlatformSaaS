<?php

declare(strict_types=1);

namespace Drupal\jaraba_events\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar landing pages de eventos.
 *
 * Estructura: Extiende ContentEntityForm con auto-generación de slug.
 *
 * Lógica: Al guardar, si el campo slug está vacío se genera
 *   automáticamente a partir del título (transliteración + lowercase).
 *   Redirige al listado tras guardar.
 *
 * Sintaxis: Drupal 11 — return types estrictos, SAVED_NEW/SAVED_UPDATED.
 */
class EventLandingPageForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->entity;

    // Auto-generar slug desde el título si está vacío.
    if (empty($entity->get('slug')->value) && !empty($entity->get('title')->value)) {
      $slug = $this->generateSlug($entity->get('title')->value);
      $entity->set('slug', $slug);
    }

    $result = parent::save($form, $form_state);
    $message_args = ['%title' => $entity->label()];

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus($this->t('Landing page %title creada.', $message_args));
    }
    else {
      $this->messenger()->addStatus($this->t('Landing page %title actualizada.', $message_args));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));
    return $result;
  }

  /**
   * Genera un slug URL-friendly a partir de un título.
   *
   * Estructura: Método protegido auxiliar del formulario.
   *
   * Lógica: Transliteración manual de caracteres acentuados del español,
   *   conversión a minúsculas, eliminación de caracteres especiales
   *   y reemplazo de espacios por guiones.
   *
   * Sintaxis: Expresiones regulares con soporte Unicode (flag /u).
   *
   * @param string $title
   *   El título de la landing page.
   *
   * @return string
   *   El slug generado.
   */
  protected function generateSlug(string $title): string {
    $slug = mb_strtolower($title);
    $slug = preg_replace('/[áàäâ]/u', 'a', $slug);
    $slug = preg_replace('/[éèëê]/u', 'e', $slug);
    $slug = preg_replace('/[íìïî]/u', 'i', $slug);
    $slug = preg_replace('/[óòöô]/u', 'o', $slug);
    $slug = preg_replace('/[úùüû]/u', 'u', $slug);
    $slug = preg_replace('/ñ/u', 'n', $slug);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
  }

}
