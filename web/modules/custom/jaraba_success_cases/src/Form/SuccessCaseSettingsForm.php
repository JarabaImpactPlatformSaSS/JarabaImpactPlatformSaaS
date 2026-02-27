<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Success Case entities.
 *
 * This form serves as the base route for Field UI integration,
 * allowing administrators to add custom fields (e.g. photo_profile,
 * video_testimonial) to the SuccessCase entity via the admin UI.
 *
 * Route: /admin/structure/success-case
 */
class SuccessCaseSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'success_case_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#markup' => '<p>' . $this->t('Use the tabs above to manage fields, form display, and view display for Success Case entities.') . '</p>',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // No settings to save — this form exists solely for Field UI base route.
    }

}
