<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar plantillas de notificación AgroConecta.
 *
 * Agrupa campos en secciones: identificación, contenido, configuración.
 */
class NotificationTemplateAgroForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Grupo de identificación.
        $form['identification'] = [
            '#type' => 'details',
            '#title' => $this->t('Identificación'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        $id_fields = ['name', 'type', 'channel', 'language'];
        foreach ($id_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['identification'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Grupo de contenido.
        $form['content'] = [
            '#type' => 'details',
            '#title' => $this->t('Contenido de la plantilla'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        $content_fields = ['subject', 'body', 'body_html', 'tokens'];
        foreach ($content_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['content'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Grupo de configuración.
        $form['config'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración'),
            '#open' => FALSE,
            '#weight' => 5,
        ];

        if (isset($form['is_active'])) {
            $form['config']['is_active'] = $form['is_active'];
            unset($form['is_active']);
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void
    {
        parent::validateForm($form, $form_state);

        // Validar canal válido.
        $valid_channels = ['email', 'push', 'sms', 'in_app'];
        $channel = $form_state->getValue(['channel', 0, 'value']);
        if ($channel && !in_array($channel, $valid_channels)) {
            $form_state->setErrorByName('channel', $this->t('Canal no válido. Canales permitidos: @channels.', [
                '@channels' => implode(', ', $valid_channels),
            ]));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationTemplateAgro $entity */
        $entity = $this->getEntity();
        $message_args = ['%name' => $entity->get('name')->value];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Plantilla %name creada correctamente.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Plantilla %name actualizada correctamente.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
