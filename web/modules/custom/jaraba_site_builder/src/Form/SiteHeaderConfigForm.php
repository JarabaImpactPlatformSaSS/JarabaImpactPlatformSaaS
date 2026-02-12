<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar configuración del header.
 */
class SiteHeaderConfigForm extends ContentEntityForm {

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
    $form['layout']['header_type'] = $form['header_type'];
    $form['layout']['main_menu_position'] = $form['main_menu_position'];
    $form['layout']['main_menu_id'] = $form['main_menu_id'];
    unset($form['header_type'], $form['main_menu_position'], $form['main_menu_id']);

    $form['branding'] = [
      '#type' => 'details',
      '#title' => $this->t('Logo y Marca'),
      '#open' => TRUE,
      '#weight' => 1,
    ];
    $form['branding']['logo_id'] = $form['logo_id'];
    $form['branding']['logo_alt'] = $form['logo_alt'];
    $form['branding']['logo_width'] = $form['logo_width'];
    $form['branding']['logo_mobile_id'] = $form['logo_mobile_id'];
    unset($form['logo_id'], $form['logo_alt'], $form['logo_width'], $form['logo_mobile_id']);

    $form['behavior'] = [
      '#type' => 'details',
      '#title' => $this->t('Comportamiento'),
      '#open' => FALSE,
      '#weight' => 2,
    ];
    $form['behavior']['is_sticky'] = $form['is_sticky'];
    $form['behavior']['sticky_offset'] = $form['sticky_offset'];
    $form['behavior']['transparent_on_hero'] = $form['transparent_on_hero'];
    $form['behavior']['hide_on_scroll_down'] = $form['hide_on_scroll_down'];
    unset($form['is_sticky'], $form['sticky_offset'], $form['transparent_on_hero'], $form['hide_on_scroll_down']);

    $form['cta'] = [
      '#type' => 'details',
      '#title' => $this->t('Botón CTA'),
      '#open' => FALSE,
      '#weight' => 3,
    ];
    $form['cta']['show_cta'] = $form['show_cta'];
    $form['cta']['cta_text'] = $form['cta_text'];
    $form['cta']['cta_url'] = $form['cta_url'];
    $form['cta']['cta_style'] = $form['cta_style'];
    $form['cta']['cta_icon'] = $form['cta_icon'];
    unset($form['show_cta'], $form['cta_text'], $form['cta_url'], $form['cta_style'], $form['cta_icon']);

    $form['elements'] = [
      '#type' => 'details',
      '#title' => $this->t('Elementos Opcionales'),
      '#open' => FALSE,
      '#weight' => 4,
    ];
    $form['elements']['show_search'] = $form['show_search'];
    $form['elements']['show_language_switcher'] = $form['show_language_switcher'];
    $form['elements']['show_user_menu'] = $form['show_user_menu'];
    $form['elements']['show_phone'] = $form['show_phone'];
    $form['elements']['show_email'] = $form['show_email'];
    unset($form['show_search'], $form['show_language_switcher'], $form['show_user_menu'], $form['show_phone'], $form['show_email']);

    $form['topbar'] = [
      '#type' => 'details',
      '#title' => $this->t('Barra Superior'),
      '#open' => FALSE,
      '#weight' => 5,
    ];
    $form['topbar']['show_topbar'] = $form['show_topbar'];
    $form['topbar']['topbar_content'] = $form['topbar_content'];
    $form['topbar']['topbar_bg_color'] = $form['topbar_bg_color'];
    $form['topbar']['topbar_text_color'] = $form['topbar_text_color'];
    unset($form['show_topbar'], $form['topbar_content'], $form['topbar_bg_color'], $form['topbar_text_color']);

    $form['colors'] = [
      '#type' => 'details',
      '#title' => $this->t('Colores y Dimensiones'),
      '#open' => FALSE,
      '#weight' => 6,
    ];
    $form['colors']['bg_color'] = $form['bg_color'];
    $form['colors']['text_color'] = $form['text_color'];
    $form['colors']['height_desktop'] = $form['height_desktop'];
    $form['colors']['height_mobile'] = $form['height_mobile'];
    $form['colors']['shadow'] = $form['shadow'];
    unset($form['bg_color'], $form['text_color'], $form['height_desktop'], $form['height_mobile'], $form['shadow']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Configuración del header guardada.'));
    $form_state->setRedirect('entity.site_header_config.collection');
    return $result;
  }

}
