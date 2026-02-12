<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for CandidateProfile entity.
 */
class CandidateProfileForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Personal Information
        $form['personal'] = [
            '#type' => 'details',
            '#title' => $this->t('Personal Information'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        $personal_fields = ['first_name', 'last_name', 'email', 'phone', 'photo'];
        foreach ($personal_fields as $field) {
            if (isset($form[$field])) {
                $form['personal'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Professional Profile
        $form['professional'] = [
            '#type' => 'details',
            '#title' => $this->t('Professional Profile'),
            '#open' => TRUE,
            '#weight' => -5,
        ];

        $pro_fields = ['headline', 'summary', 'experience_years', 'experience_level', 'education_level'];
        foreach ($pro_fields as $field) {
            if (isset($form[$field])) {
                $form['professional'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Location
        $form['location'] = [
            '#type' => 'details',
            '#title' => $this->t('Location'),
            '#open' => FALSE,
            '#weight' => 0,
        ];

        $location_fields = ['city', 'province', 'country', 'postal_code', 'willing_to_relocate'];
        foreach ($location_fields as $field) {
            if (isset($form[$field])) {
                $form['location'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Job Preferences
        $form['preferences'] = [
            '#type' => 'details',
            '#title' => $this->t('Job Preferences'),
            '#open' => FALSE,
            '#weight' => 5,
        ];

        $pref_fields = ['availability_status', 'salary_expectation', 'salary_currency'];
        foreach ($pref_fields as $field) {
            if (isset($form[$field])) {
                $form['preferences'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Online Presence
        $form['online'] = [
            '#type' => 'details',
            '#title' => $this->t('Online Presence'),
            '#open' => FALSE,
            '#weight' => 10,
        ];

        $online_fields = ['linkedin_url', 'github_url', 'portfolio_url', 'website_url'];
        foreach ($online_fields as $field) {
            if (isset($form[$field])) {
                $form['online'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Privacy Settings
        $form['privacy'] = [
            '#type' => 'details',
            '#title' => $this->t('Privacy Settings'),
            '#open' => FALSE,
            '#weight' => 15,
        ];

        $privacy_fields = ['is_public', 'show_photo', 'show_contact'];
        foreach ($privacy_fields as $field) {
            if (isset($form[$field])) {
                $form['privacy'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $entity = $this->getEntity();
        $message_args = ['%label' => $entity->getFullName() ?: $this->t('Candidate')];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Profile for %label has been created.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Profile for %label has been updated.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
