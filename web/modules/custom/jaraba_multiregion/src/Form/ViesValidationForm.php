<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de visualizacion/edicion de validaciones VIES.
 *
 * Estructura: Extiende ContentEntityForm para heredar el manejo
 *   automatico de campos base de la entidad ViesValidation. Agrupa
 *   los campos en 3 fieldsets: resultado, datos empresa y metadatos.
 *
 * Logica: Este formulario se usa principalmente para visualizar
 *   resultados de validaciones VIES ya realizadas. Los registros
 *   normalmente se crean via API, pero el formulario permite
 *   tambien la edicion manual si es necesario. El fieldset de
 *   metadatos se muestra cerrado por defecto.
 *
 * Sintaxis: Todos los strings usan TranslatableMarkup. Solo se
 *   muestra un mensaje generico al guardar, sin distinguir
 *   creacion/actualizacion ya que el uso habitual es solo lectura.
 */
class ViesValidationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario con 3 fieldsets.
   * Logica: El fieldset Resultado muestra los campos principales
   *   de la validacion. Datos empresa contiene la informacion
   *   retornada por VIES. Metadatos muestra el identificador de
   *   la peticion y la fecha de validacion, cerrado por defecto.
   * Sintaxis: Usa isset() para mover campos condicionalmente.
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset: Resultado ---
    $form['result'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Resultado'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['vat_number'])) {
      $form['result']['vat_number'] = $form['vat_number'];
      unset($form['vat_number']);
    }
    if (isset($form['country_code'])) {
      $form['result']['country_code'] = $form['country_code'];
      unset($form['country_code']);
    }
    if (isset($form['is_valid'])) {
      $form['result']['is_valid'] = $form['is_valid'];
      unset($form['is_valid']);
    }

    // --- Fieldset: Datos empresa ---
    $form['company_data'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Datos empresa'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    if (isset($form['company_name'])) {
      $form['company_data']['company_name'] = $form['company_name'];
      unset($form['company_name']);
    }
    if (isset($form['company_address'])) {
      $form['company_data']['company_address'] = $form['company_address'];
      unset($form['company_address']);
    }

    // --- Fieldset: Metadatos (cerrado por defecto) ---
    $form['metadata'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Metadatos'),
      '#open' => FALSE,
      '#weight' => 2,
    ];

    if (isset($form['request_identifier'])) {
      $form['metadata']['request_identifier'] = $form['request_identifier'];
      unset($form['request_identifier']);
    }
    if (isset($form['validated_at'])) {
      $form['metadata']['validated_at'] = $form['validated_at'];
      unset($form['validated_at']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Override del metodo save para mensaje personalizado.
   * Logica: Muestra un unico mensaje generico ya que las validaciones
   *   VIES normalmente se registran via API y este formulario se
   *   usa principalmente para consulta o correccion manual.
   * Sintaxis: Usa TranslatableMarkup sin placeholders.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();

    $this->messenger()->addStatus(new TranslatableMarkup('Validacion VIES registrada.'));

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
