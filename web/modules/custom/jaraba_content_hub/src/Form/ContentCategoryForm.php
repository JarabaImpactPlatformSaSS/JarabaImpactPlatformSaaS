<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the ContentCategory entity.
 */
class ContentCategoryForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        // Add color picker styles.
        if (isset($form['color'])) {
            $form['color']['widget'][0]['value']['#attributes']['class'][] = 'color-picker';
            $form['color']['widget'][0]['value']['#description'] = $this->t('Enter a hex color code (e.g., #233D63).');
        }

        // Add icon preview.
        if (isset($form['icon'])) {
            $form['icon']['widget'][0]['value']['#description'] = $this->t('Enter an icon name from the icon library (e.g., folder, tag, bookmark).');
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
            $this->messenger()->addStatus($this->t('Category %label has been created.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Category %label has been updated.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
