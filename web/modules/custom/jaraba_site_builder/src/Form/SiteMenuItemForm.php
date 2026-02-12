<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar items de menú.
 */
class SiteMenuItemForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    $form['basic'] = [
      '#type' => 'details',
      '#title' => $this->t('Información del Item'),
      '#open' => TRUE,
      '#weight' => 0,
    ];
    $form['basic']['title'] = $form['title'];
    $form['basic']['menu_id'] = $form['menu_id'];
    $form['basic']['parent_id'] = $form['parent_id'];
    $form['basic']['item_type'] = $form['item_type'];
    unset($form['title'], $form['menu_id'], $form['parent_id'], $form['item_type']);

    $form['link'] = [
      '#type' => 'details',
      '#title' => $this->t('Enlace'),
      '#open' => TRUE,
      '#weight' => 1,
    ];
    $form['link']['url'] = $form['url'];
    $form['link']['page_id'] = $form['page_id'];
    $form['link']['open_in_new_tab'] = $form['open_in_new_tab'];
    unset($form['url'], $form['page_id'], $form['open_in_new_tab']);

    $form['appearance'] = [
      '#type' => 'details',
      '#title' => $this->t('Apariencia'),
      '#open' => FALSE,
      '#weight' => 2,
    ];
    $form['appearance']['icon'] = $form['icon'];
    $form['appearance']['badge_text'] = $form['badge_text'];
    $form['appearance']['badge_color'] = $form['badge_color'];
    $form['appearance']['highlight'] = $form['highlight'];
    unset($form['icon'], $form['badge_text'], $form['badge_color'], $form['highlight']);

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Avanzado'),
      '#open' => FALSE,
      '#weight' => 3,
    ];
    $form['advanced']['mega_content'] = $form['mega_content'];
    $form['advanced']['is_enabled'] = $form['is_enabled'];
    $form['advanced']['weight'] = $form['weight'];
    $form['advanced']['depth'] = $form['depth'];
    unset($form['mega_content'], $form['is_enabled'], $form['weight'], $form['depth']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $this->messenger()->addStatus($this->t('Item de menú "%title" guardado.', [
      '%title' => $this->entity->label(),
    ]));

    $form_state->setRedirect('entity.site_menu_item.collection');
    return $result;
  }

}
