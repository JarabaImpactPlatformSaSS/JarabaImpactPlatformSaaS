<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Collaboration Group Field UI.
 */
class CollaborationGroupSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'collaboration_group_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#markup' => '<p>' . $this->t('Configuración de campos para Grupos de Colaboración.') . '</p>',
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
