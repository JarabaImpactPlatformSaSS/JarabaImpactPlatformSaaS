<?php

declare(strict_types=1);

namespace Drupal\jaraba_business_tools\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Business Model Canvas edit forms.
 */
class BusinessModelCanvasForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        $currentUser = \Drupal::currentUser();
        $canCreateTemplates = $currentUser->hasPermission('create business model canvas template')
            || $currentUser->hasPermission('administer business model canvas');

        // Hide template fields from users without permission
        if (!$canCreateTemplates) {
            if (isset($form['is_template'])) {
                $form['is_template']['#access'] = FALSE;
            }
            if (isset($form['tenant_id'])) {
                $form['tenant_id']['#access'] = FALSE;
            }
        } else {
            // Show template fields with better labels for mentors
            if (isset($form['is_template'])) {
                $form['is_template']['widget']['value']['#title'] = $this->t('Guardar como plantilla reutilizable');
                $form['is_template']['widget']['value']['#description'] = $this->t('Las plantillas pueden ser usadas por emprendedores para crear sus propios canvas.');
            }
            if (isset($form['tenant_id'])) {
                $form['tenant_id']['widget']['#title'] = $this->t('Programa asociado (opcional)');
                $form['tenant_id']['widget']['#description'] = $this->t('Si se selecciona, la plantilla solo será visible para emprendedores de este programa.');
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

        switch ($result) {
            case SAVED_NEW:
                $this->messenger()->addStatus($this->t('Canvas %label creado.', $message_args));
                break;

            case SAVED_UPDATED:
                $this->messenger()->addStatus($this->t('Canvas %label actualizado.', $message_args));
                break;
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}

