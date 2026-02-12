<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para la entidad ReviewAgro.
 *
 * Requerido para Field UI (field_ui_base_route). Permite que los
 * administradores gestionen campos personalizados desde /admin/structure.
 */
class ReviewAgroSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'review_agro_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['review_agro_settings'] = [
            '#markup' => $this->t('Configuración de la entidad Reseña Agro. Utilice las pestañas para gestionar los campos y la visualización.'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // No hay configuración que guardar en esta versión.
    }

}
