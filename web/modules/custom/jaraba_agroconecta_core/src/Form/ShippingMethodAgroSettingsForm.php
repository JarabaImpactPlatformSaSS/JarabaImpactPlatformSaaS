<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para ShippingMethodAgro.
 */
class ShippingMethodAgroSettingsForm extends FormBase
{

    public function getFormId(): string
    {
        return 'shipping_method_agro_settings';
    }

    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#markup' => '<p>' . $this->t('Configuración de Métodos de Envío AgroConecta.') . '</p>',
        ];
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $this->messenger()->addStatus($this->t('Configuración guardada.'));
    }

}
