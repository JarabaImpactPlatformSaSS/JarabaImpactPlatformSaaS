<?php

declare(strict_types=1);

namespace Drupal\jaraba_success_cases\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the Success Case entity add/edit forms.
 *
 * Organizes fields into logical fieldsets (Details, Narrative, Quotes,
 * Metrics, Program, SEO) for a clean admin editing experience.
 * Format guidelines are hidden per slide-panel pattern.
 */
class SuccessCaseForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // --- Fieldset: Personal Details ---
        $form['details'] = [
            '#type' => 'details',
            '#title' => $this->t('Personal Details'),
            '#open' => TRUE,
            '#weight' => 0,
        ];
        $details_fields = ['name', 'slug', 'profession', 'company', 'sector', 'location', 'website', 'linkedin'];
        foreach ($details_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['details'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // --- Fieldset: Narrative ---
        $form['narrative'] = [
            '#type' => 'details',
            '#title' => $this->t('History (Challenge → Solution → Result)'),
            '#open' => TRUE,
            '#weight' => 10,
        ];
        $narrative_fields = ['challenge_before', 'solution_during', 'result_after'];
        foreach ($narrative_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['narrative'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // --- Fieldset: Quotes ---
        $form['quotes'] = [
            '#type' => 'details',
            '#title' => $this->t('Testimonial Quotes'),
            '#open' => FALSE,
            '#weight' => 20,
        ];
        $quote_fields = ['quote_short', 'quote_long'];
        foreach ($quote_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['quotes'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // --- Fieldset: Metrics ---
        $form['metrics'] = [
            '#type' => 'details',
            '#title' => $this->t('Quantifiable Metrics'),
            '#open' => FALSE,
            '#weight' => 30,
        ];
        $metric_fields = ['metrics_json', 'rating'];
        foreach ($metric_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['metrics'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // --- Fieldset: Program ---
        $form['program'] = [
            '#type' => 'details',
            '#title' => $this->t('Program / Vertical'),
            '#open' => FALSE,
            '#weight' => 40,
        ];
        $program_fields = ['program_name', 'vertical', 'program_funder', 'program_year'];
        foreach ($program_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['program'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // --- Fieldset: SEO & Control ---
        $form['seo_control'] = [
            '#type' => 'details',
            '#title' => $this->t('SEO & Display Control'),
            '#open' => FALSE,
            '#weight' => 50,
        ];
        $seo_fields = ['meta_description', 'weight', 'featured', 'status'];
        foreach ($seo_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['seo_control'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
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
        $message_args = ['%label' => $entity->toLink()->toString()];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Success case %label has been created.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Success case %label has been updated.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
