<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar preferencias de notificación AgroConecta.
 *
 * Presenta los 4 canales como checkboxes agrupados.
 */
class NotificationPreferenceAgroForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Grupo de canales.
        $form['channels'] = [
            '#type' => 'details',
            '#title' => $this->t('Canales de notificación'),
            '#open' => TRUE,
            '#weight' => 0,
            '#description' => $this->t('Seleccione los canales por los que desea recibir este tipo de notificación.'),
        ];

        $channel_fields = ['channel_email', 'channel_push', 'channel_sms', 'channel_in_app'];
        foreach ($channel_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['channels'][$field_name] = $form[$field_name];
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

        /** @var \Drupal\jaraba_agroconecta_core\Entity\NotificationPreferenceAgro $entity */
        $entity = $this->getEntity();
        $message_args = ['%type' => $entity->get('notification_type')->value];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Preferencia para %type creada correctamente.', $message_args));
        } else {
            $this->messenger()->addStatus($this->t('Preferencia para %type actualizada correctamente.', $message_args));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
