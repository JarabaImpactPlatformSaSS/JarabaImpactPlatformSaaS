<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_vault\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuracion de Legal Vault.
 *
 * Estructura: FormBase simple para Field UI base route.
 * Logica: Permite configurar el vault via admin.
 */
class VaultSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'jaraba_legal_vault_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['info'] = [
      '#markup' => '<p>' . $this->t('Configuracion de la entidad. Usa las pestanas Field UI para gestionar campos.') . '</p>',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

}
