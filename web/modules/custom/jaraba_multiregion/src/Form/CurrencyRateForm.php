<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Formulario de creacion/edicion de tipos de cambio.
 *
 * Estructura: Extiende ContentEntityForm para heredar el manejo
 *   automatico de campos base de la entidad CurrencyRate. Agrupa
 *   los campos en 2 fieldsets: tipo de cambio y metadatos.
 *
 * Logica: Al guardar, distingue entre creacion y actualizacion
 *   mostrando el par de monedas (from -> to) como identificador
 *   visual. Redirige a la coleccion de tipos de cambio.
 *
 * Sintaxis: Todos los strings usan TranslatableMarkup. Se emplean
 *   placeholders %from y %to para mostrar el par de monedas.
 */
class CurrencyRateForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   *
   * Estructura: Construye el formulario con 2 fieldsets.
   * Logica: El fieldset principal agrupa from_currency, to_currency
   *   y rate. El fieldset de metadatos contiene source y fetched_at
   *   que indican el origen y momento de la tasa obtenida.
   * Sintaxis: Ambos fieldsets se muestran abiertos por defecto.
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    // --- Fieldset: Tipo de cambio ---
    $form['exchange_rate'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Tipo de cambio'),
      '#open' => TRUE,
      '#weight' => 0,
    ];

    if (isset($form['from_currency'])) {
      $form['exchange_rate']['from_currency'] = $form['from_currency'];
      unset($form['from_currency']);
    }
    if (isset($form['to_currency'])) {
      $form['exchange_rate']['to_currency'] = $form['to_currency'];
      unset($form['to_currency']);
    }
    if (isset($form['rate'])) {
      $form['exchange_rate']['rate'] = $form['rate'];
      unset($form['rate']);
    }

    // --- Fieldset: Metadatos ---
    $form['metadata'] = [
      '#type' => 'details',
      '#title' => new TranslatableMarkup('Metadatos'),
      '#open' => TRUE,
      '#weight' => 1,
    ];

    if (isset($form['source'])) {
      $form['metadata']['source'] = $form['source'];
      unset($form['source']);
    }
    if (isset($form['fetched_at'])) {
      $form['metadata']['fetched_at'] = $form['fetched_at'];
      unset($form['fetched_at']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Estructura: Override del metodo save para mensajes personalizados.
   * Logica: Distingue SAVED_NEW vs SAVED_UPDATED. En creacion muestra
   *   el par de monedas con flecha unicode. En actualizacion muestra
   *   un mensaje generico ya que el par no cambia.
   * Sintaxis: Usa TranslatableMarkup con placeholders %from y %to.
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);
    $entity = $this->getEntity();
    $from = $entity->get('from_currency')->value ?? '?';
    $to = $entity->get('to_currency')->value ?? '?';

    if ($result === SAVED_NEW) {
      $this->messenger()->addStatus(new TranslatableMarkup('Tipo de cambio %from → %to registrado.', [
        '%from' => $from,
        '%to' => $to,
      ]));
    }
    else {
      $this->messenger()->addStatus(new TranslatableMarkup('Tipo de cambio actualizado.'));
    }

    $form_state->setRedirectUrl($entity->toUrl('collection'));

    return $result;
  }

}
