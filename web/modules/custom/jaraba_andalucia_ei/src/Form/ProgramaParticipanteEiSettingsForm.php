<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración de la entidad ProgramaParticipanteEi.
 *
 * Proporciona la ruta base para Field UI.
 */
class ProgramaParticipanteEiSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'programa_participante_ei_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#markup' => '<p>' . $this->t('Esta página proporciona acceso a la configuración de campos para la entidad Participante Andalucía +ei.') . '</p>',
        ];

        $form['info_fields'] = [
            '#markup' => '<p>' . $this->t('Use las pestañas "Administrar campos" y "Administrar visualización de formulario" para personalizar los campos adicionales.') . '</p>',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // No hay configuración que guardar - esta página es solo para Field UI.
    }

}
