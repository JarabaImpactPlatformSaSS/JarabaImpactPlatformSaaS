<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for CandidateSkill entity (Field UI base route).
 */
class CandidateSkillSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'candidate_skill_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['description'] = [
            '#markup' => $this->t('<p>Use this page to configure settings for Candidate Skills. Use the <em>Manage fields</em> and <em>Manage display</em> tabs to add custom fields.</p>'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // Nothing to submit for now.
    }

}
