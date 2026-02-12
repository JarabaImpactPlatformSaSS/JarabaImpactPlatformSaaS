<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Group Resource Field UI.
 */
class GroupResourceSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'group_resource_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#markup' => '<p>' . $this->t('Configuración de campos para Recursos de Grupo.') . '</p>',
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
