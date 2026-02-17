<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de creacion/edicion de configuracion regional por tenant.
 *
 * Estructura: Extiende ContentEntityForm para heredar el manejo
 *   automatico de campos base de la entidad TenantRegion. Agrupa
 *   los campos en 4 fieldsets: regional, moneda, fiscal y GDPR.
 *
 * Logica: Al guardar, distingue entre creacion y actualizacion para
 *   mostrar el mensaje adecuado. Redirige a la coleccion de regiones.
 *   El fieldset GDPR se muestra cerrado por defecto ya que su uso
 *   es menos frecuente.
 *
 * Sintaxis: Todos los strings usan TranslatableMarkup. Los campos
 *   se reubican desde el form base a los fieldsets correspondientes.
 */
class TenantRegionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario agrupando campos en fieldsets.
   * Logica: Reubica cada campo de la entidad desde su posicion por
   *   defecto al fieldset tematico correspondiente. Los fieldsets
   *   de configuracion principal se abren por defecto; GDPR cerrado.
   * Sintaxis: Usa #type => 'details' con #open para cada grupo.
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset: Configuracion regional ---
    $form['regional'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Configuracion regional'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['legal_jurisdiction'])) {
      $form['regional']['legal_jurisdiction'] = $form['legal_jurisdiction'];
      unset($form['legal_jurisdiction']);
    }
    if (isset($form['data_region'])) {
      $form['regional']['data_region'] = $form['data_region'];
      unset($form['data_region']);
    }
    if (isset($form['primary_dc'])) {
      $form['regional']['primary_dc'] = $form['primary_dc'];
      unset($form['primary_dc']);
    }

    // --- Fieldset: Moneda ---
    $form['currency'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Moneda'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    if (isset($form['base_currency'])) {
      $form['currency']['base_currency'] = $form['base_currency'];
      unset($form['base_currency']);
    }
    if (isset($form['display_currencies'])) {
      $form['currency']['display_currencies'] = $form['display_currencies'];
      unset($form['display_currencies']);
    }
    if (isset($form['stripe_account_country'])) {
      $form['currency']['stripe_account_country'] = $form['stripe_account_country'];
      unset($form['stripe_account_country']);
    }

    // --- Fieldset: Fiscal ---
    $form['fiscal'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Fiscal'),
      '#open' => TRUE,
      '#weight' => 2,
    ];

    if (isset($form['vat_number'])) {
      $form['fiscal']['vat_number'] = $form['vat_number'];
      unset($form['vat_number']);
    }
    if (isset($form['vies_validated'])) {
      $form['fiscal']['vies_validated'] = $form['vies_validated'];
      unset($form['vies_validated']);
    }
    if (isset($form['vies_validated_at'])) {
      $form['fiscal']['vies_validated_at'] = $form['vies_validated_at'];
      unset($form['vies_validated_at']);
    }

    // --- Fieldset: GDPR (cerrado por defecto) ---
    $form['gdpr'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('GDPR'),
      '#open' => FALSE,
      '#weight' => 3,
    ];

    if (isset($form['gdpr_representative'])) {
      $form['gdpr']['gdpr_representative'] = $form['gdpr_representative'];
      unset($form['gdpr_representative']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Override del metodo save para mensajes personalizados.
   * Logica: Distingue SAVED_NEW vs SAVED_UPDATED para mostrar el
   *   mensaje correcto con la jurisdiccion como referencia visual.
   *   Redirige a la URL de coleccion de la entidad.
   * Sintaxis: Usa TranslatableMarkup con placeholder %jurisdiction.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $jurisdiction = $entity->get('legal_jurisdiction')->value ?? $entity->id();

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus(new TranslatableMarkup('Region "%jurisdiction" configurada correctamente.', [
        '%jurisdiction' => $jurisdiction,
      ]));
    }
    else {
      $this->messenger()->addStatus(new TranslatableMarkup('Region "%jurisdiction" actualizada.', [
        '%jurisdiction' => $jurisdiction,
      ]));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
