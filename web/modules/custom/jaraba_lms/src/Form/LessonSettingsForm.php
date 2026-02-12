<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Lesson entity.
 *
 * Provides a valid route for Field UI integration.
 */
class LessonSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'lms_lesson_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#markup' => $this->t('<p>Settings for Lesson entity. Use the tabs above to manage fields and display settings.</p>'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // No submit action needed.
    }

}
