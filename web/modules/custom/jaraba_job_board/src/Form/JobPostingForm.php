<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for JobPosting entity.
 */
class JobPostingForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Job Details
        $form['job_details'] = [
            '#type' => 'details',
            '#title' => $this->t('Job Details'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        $fields_for_details = ['title', 'description', 'requirements', 'responsibilities'];
        foreach ($fields_for_details as $field) {
            if (isset($form[$field])) {
                $form['job_details'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Location & Work Type
        $form['location'] = [
            '#type' => 'details',
            '#title' => $this->t('Location & Work Type'),
            '#open' => TRUE,
            '#weight' => -5,
        ];

        $location_fields = ['location_city', 'location_country', 'remote_type', 'job_type'];
        foreach ($location_fields as $field) {
            if (isset($form[$field])) {
                $form['location'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Compensation
        $form['compensation'] = [
            '#type' => 'details',
            '#title' => $this->t('Compensation'),
            '#open' => FALSE,
            '#weight' => 0,
        ];

        $comp_fields = ['salary_min', 'salary_max', 'salary_currency', 'benefits'];
        foreach ($comp_fields as $field) {
            if (isset($form[$field])) {
                $form['compensation'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Publishing
        $form['publishing'] = [
            '#type' => 'details',
            '#title' => $this->t('Publishing Options'),
            '#open' => FALSE,
            '#weight' => 5,
        ];

        $pub_fields = ['status', 'publish_at', 'expires_at', 'is_featured'];
        foreach ($pub_fields as $field) {
            if (isset($form[$field])) {
                $form['publishing'][$field] = $form[$field];
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
        $message_args = ['%label' => $entity->label()];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Job posting %label has been created.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Job posting %label has been updated.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
