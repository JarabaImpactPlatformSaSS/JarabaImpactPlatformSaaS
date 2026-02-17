<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de creacion/edicion de reglas fiscales.
 *
 * Estructura: Extiende ContentEntityForm para heredar el manejo
 *   automatico de campos base de la entidad TaxRule. Agrupa los
 *   campos en 4 fieldsets: identificacion, tipos impositivos,
 *   configuracion y vigencia.
 *
 * Logica: Al guardar, distingue entre creacion y actualizacion para
 *   mostrar el mensaje apropiado con el nombre de la regla. Redirige
 *   a la coleccion de reglas fiscales para vista de listado.
 *
 * Sintaxis: Todos los strings usan TranslatableMarkup. Los campos
 *   se reubican desde el form base a fieldsets tematicos #type=>'details'.
 */
class TaxRuleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario con 4 fieldsets abiertos.
   * Logica: Cada fieldset agrupa campos semanticamente relacionados.
   *   Identificacion contiene pais y nombre del impuesto. Tipos
   *   impositivos agrupa todas las tasas. Configuracion incluye
   *   umbrales y flags. Vigencia define el periodo de aplicacion.
   * Sintaxis: Usa isset() para mover campos condicionalmente.
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset: Identificacion ---
    $form['identification'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Identificacion'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['country_code'])) {
      $form['identification']['country_code'] = $form['country_code'];
      unset($form['country_code']);
    }
    if (isset($form['tax_name'])) {
      $form['identification']['tax_name'] = $form['tax_name'];
      unset($form['tax_name']);
    }
    if (isset($form['eu_member'])) {
      $form['identification']['eu_member'] = $form['eu_member'];
      unset($form['eu_member']);
    }

    // --- Fieldset: Tipos impositivos ---
    $form['tax_rates'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Tipos impositivos'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    if (isset($form['standard_rate'])) {
      $form['tax_rates']['standard_rate'] = $form['standard_rate'];
      unset($form['standard_rate']);
    }
    if (isset($form['reduced_rate'])) {
      $form['tax_rates']['reduced_rate'] = $form['reduced_rate'];
      unset($form['reduced_rate']);
    }
    if (isset($form['super_reduced_rate'])) {
      $form['tax_rates']['super_reduced_rate'] = $form['super_reduced_rate'];
      unset($form['super_reduced_rate']);
    }
    if (isset($form['digital_services_rate'])) {
      $form['tax_rates']['digital_services_rate'] = $form['digital_services_rate'];
      unset($form['digital_services_rate']);
    }

    // --- Fieldset: Configuracion ---
    $form['configuration'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Configuracion'),
      '#open' => TRUE,
      '#weight' => 2,
    ];

    if (isset($form['oss_threshold'])) {
      $form['configuration']['oss_threshold'] = $form['oss_threshold'];
      unset($form['oss_threshold']);
    }
    if (isset($form['reverse_charge_enabled'])) {
      $form['configuration']['reverse_charge_enabled'] = $form['reverse_charge_enabled'];
      unset($form['reverse_charge_enabled']);
    }

    // --- Fieldset: Vigencia ---
    $form['validity'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Vigencia'),
      '#open' => TRUE,
      '#weight' => 3,
    ];

    if (isset($form['effective_from'])) {
      $form['validity']['effective_from'] = $form['effective_from'];
      unset($form['effective_from']);
    }
    if (isset($form['effective_to'])) {
      $form['validity']['effective_to'] = $form['effective_to'];
      unset($form['effective_to']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Override del metodo save para mensajes personalizados.
   * Logica: Distingue SAVED_NEW vs SAVED_UPDATED para informar al
   *   usuario si la regla fue creada o actualizada. Usa el campo
   *   tax_name como identificador visual en el mensaje.
   * Sintaxis: Usa TranslatableMarkup con placeholder %name.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $name = $entity->get('tax_name')->value ?? $entity->id();

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus(new TranslatableMarkup('Regla fiscal "%name" creada.', [
        '%name' => $name,
      ]));
    }
    else {
      $this->messenger()->addStatus(new TranslatableMarkup('Regla fiscal "%name" actualizada.', [
        '%name' => $name,
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
