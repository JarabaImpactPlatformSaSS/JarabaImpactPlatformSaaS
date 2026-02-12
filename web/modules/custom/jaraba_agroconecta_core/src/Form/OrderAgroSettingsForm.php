<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para entidades OrderAgro (Field UI).
 */
class OrderAgroSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'order_agro_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#markup' => '<p>' . $this->t('Configuración de campos para Pedidos Agro.') . '</p>',
        ];
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
    }

}
