<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the ContentArticle entity.
 */
class ContentArticleForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        // Group fields into vertical tabs.
        $form['#attached']['library'][] = 'core/drupal.vertical-tabs';

        $form['tabs'] = [
            '#type' => 'vertical_tabs',
            '#weight' => 99,
        ];

        // Content group.
        $form['content_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Content'),
            '#group' => 'tabs',
            '#weight' => 0,
            '#open' => TRUE,
        ];

        // Move content fields to group.
        $content_fields = ['title', 'slug', 'excerpt', 'body', 'answer_capsule', 'featured_image'];
        foreach ($content_fields as $field) {
            if (isset($form[$field])) {
                $form[$field]['#group'] = 'content_group';
            }
        }

        // Taxonomy group.
        $form['taxonomy_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Taxonomy'),
            '#group' => 'tabs',
            '#weight' => 10,
        ];

        if (isset($form['category'])) {
            $form['category']['#group'] = 'taxonomy_group';
        }

        // Publishing group.
        $form['publishing_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Publishing'),
            '#group' => 'tabs',
            '#weight' => 20,
        ];

        $publishing_fields = ['status', 'publish_date', 'author'];
        foreach ($publishing_fields as $field) {
            if (isset($form[$field])) {
                $form[$field]['#group'] = 'publishing_group';
            }
        }

        // SEO group.
        $form['seo_group'] = [
            '#type' => 'details',
            '#title' => $this->t('SEO'),
            '#group' => 'tabs',
            '#weight' => 30,
        ];

        $seo_fields = ['seo_title', 'seo_description'];
        foreach ($seo_fields as $field) {
            if (isset($form[$field])) {
                $form[$field]['#group'] = 'seo_group';
            }
        }

        // Metadata group.
        $form['metadata_group'] = [
            '#type' => 'details',
            '#title' => $this->t('Metadata'),
            '#group' => 'tabs',
            '#weight' => 40,
        ];

        $metadata_fields = ['ai_generated', 'reading_time', 'engagement_score'];
        foreach ($metadata_fields as $field) {
            if (isset($form[$field])) {
                $form[$field]['#group'] = 'metadata_group';
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
            $this->messenger()->addStatus($this->t('Article %label has been created.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Article %label has been updated.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
