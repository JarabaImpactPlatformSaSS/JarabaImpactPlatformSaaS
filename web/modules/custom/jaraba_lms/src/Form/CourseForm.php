<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for Course entity.
 */
class CourseForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Basic Information fieldset
        $form['basic_info'] = [
            '#type' => 'details',
            '#title' => $this->t('Basic Information'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        if (isset($form['title'])) {
            $form['basic_info']['title'] = $form['title'];
            unset($form['title']);
        }

        if (isset($form['machine_name'])) {
            $form['basic_info']['machine_name'] = $form['machine_name'];
            unset($form['machine_name']);
        }

        if (isset($form['summary'])) {
            $form['basic_info']['summary'] = $form['summary'];
            unset($form['summary']);
        }

        if (isset($form['description'])) {
            $form['basic_info']['description'] = $form['description'];
            unset($form['description']);
        }

        // Course Settings fieldset
        $form['course_settings'] = [
            '#type' => 'details',
            '#title' => $this->t('Course Settings'),
            '#open' => TRUE,
            '#weight' => -5,
        ];

        if (isset($form['duration_minutes'])) {
            $form['course_settings']['duration_minutes'] = $form['duration_minutes'];
            unset($form['duration_minutes']);
        }

        if (isset($form['difficulty_level'])) {
            $form['course_settings']['difficulty_level'] = $form['difficulty_level'];
            unset($form['difficulty_level']);
        }

        if (isset($form['vertical_id'])) {
            $form['course_settings']['vertical_id'] = $form['vertical_id'];
            unset($form['vertical_id']);
        }

        // Publishing fieldset
        $form['publishing'] = [
            '#type' => 'details',
            '#title' => $this->t('Publishing Options'),
            '#open' => FALSE,
            '#weight' => 0,
        ];

        if (isset($form['is_published'])) {
            $form['publishing']['is_published'] = $form['is_published'];
            unset($form['is_published']);
        }

        if (isset($form['is_premium'])) {
            $form['publishing']['is_premium'] = $form['is_premium'];
            unset($form['is_premium']);
        }

        // Pricing fieldset
        $form['pricing'] = [
            '#type' => 'details',
            '#title' => $this->t('Pricing'),
            '#open' => FALSE,
            '#weight' => 5,
        ];

        if (isset($form['price'])) {
            $form['pricing']['price'] = $form['price'];
            unset($form['price']);
        }

        if (isset($form['completion_credits'])) {
            $form['pricing']['completion_credits'] = $form['completion_credits'];
            unset($form['completion_credits']);
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
            $this->messenger()->addStatus($this->t('Course %label has been created.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Course %label has been updated.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
