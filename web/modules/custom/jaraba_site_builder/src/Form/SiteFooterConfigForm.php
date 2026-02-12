<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar configuración del footer.
 */
class SiteFooterConfigForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['layout'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout y Tipo'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['layout']['footer_type'] = $form['footer_type'];
    $form['layout']['columns_config'] = $form['columns_config'];
    unset($form['footer_type'], $form['columns_config']);

    $form['branding'] = [
      '#type' => 'details',
      '#title' => $this->t('Logo y Descripción'),
      '#open' => TRUE,
      '#weight' => 1,
    ];
    $form['branding']['logo_id'] = $form['logo_id'];
    $form['branding']['show_logo'] = $form['show_logo'];
    $form['branding']['description'] = $form['description'];
    unset($form['logo_id'], $form['show_logo'], $form['description']);

    $form['social'] = [
      '#type' => 'details',
      '#title' => $this->t('Redes Sociales'),
      '#open' => FALSE,
      '#weight' => 2,
    ];
    $form['social']['show_social'] = $form['show_social'];
    $form['social']['social_position'] = $form['social_position'];
    unset($form['show_social'], $form['social_position']);

    $form['newsletter'] = [
      '#type' => 'details',
      '#title' => $this->t('Newsletter'),
      '#open' => FALSE,
      '#weight' => 3,
    ];
    $form['newsletter']['show_newsletter'] = $form['show_newsletter'];
    $form['newsletter']['newsletter_title'] = $form['newsletter_title'];
    $form['newsletter']['newsletter_placeholder'] = $form['newsletter_placeholder'];
    $form['newsletter']['newsletter_cta'] = $form['newsletter_cta'];
    unset($form['show_newsletter'], $form['newsletter_title'], $form['newsletter_placeholder'], $form['newsletter_cta']);

    $form['footer_cta'] = [
      '#type' => 'details',
      '#title' => $this->t('Llamada a la Acción'),
      '#open' => FALSE,
      '#weight' => 4,
    ];
    $form['footer_cta']['cta_title'] = $form['cta_title'];
    $form['footer_cta']['cta_subtitle'] = $form['cta_subtitle'];
    $form['footer_cta']['cta_button_text'] = $form['cta_button_text'];
    $form['footer_cta']['cta_button_url'] = $form['cta_button_url'];
    unset($form['cta_title'], $form['cta_subtitle'], $form['cta_button_text'], $form['cta_button_url']);

    $form['legal'] = [
      '#type' => 'details',
      '#title' => $this->t('Copyright y Legal'),
      '#open' => FALSE,
      '#weight' => 5,
    ];
    $form['legal']['copyright_text'] = $form['copyright_text'];
    $form['legal']['show_legal_links'] = $form['show_legal_links'];
    unset($form['copyright_text'], $form['show_legal_links']);

    $form['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Colores'),
      '#open' => FALSE,
      '#weight' => 6,
    ];
    $form['colors']['bg_color'] = $form['bg_color'];
    $form['colors']['text_color'] = $form['text_color'];
    $form['colors']['accent_color'] = $form['accent_color'];
    unset($form['bg_color'], $form['text_color'], $form['accent_color']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Configuración del footer guardada.'));
    $form_state->setRedirect('entity.site_footer_config.collection');
    return $result;
  }

}
