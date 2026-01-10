<?php

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar entidades Vertical.
 */
class VerticalForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildForm($form, $form_state);

        // Agrupar campos en fieldsets.
        $form['basic'] = [
            '#type' => 'details',
            '#title' => $this->t('Información Básica'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        $form['basic']['name'] = $form['name'];
        $form['basic']['machine_name'] = $form['machine_name'];
        $form['basic']['description'] = $form['description'];
        $form['basic']['status'] = $form['status'];
        unset($form['name'], $form['machine_name'], $form['description'], $form['status']);

        $form['features'] = [
            '#type' => 'details',
            '#title' => $this->t('Features y Agentes'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        if (isset($form['enabled_features'])) {
            $form['features']['enabled_features'] = $form['enabled_features'];
            unset($form['enabled_features']);
        }

        if (isset($form['ai_agents'])) {
            $form['features']['ai_agents'] = $form['ai_agents'];
            unset($form['ai_agents']);
        }

        $form['theming'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración de Tema'),
            '#open' => FALSE,
            '#weight' => 10,
        ];

        if (isset($form['theme_settings'])) {
            $form['theming']['theme_settings'] = $form['theme_settings'];
            $form['theming']['theme_settings']['#description'] = $this->t('JSON con colores, tipografía y logo. Ejemplo: {"color_primario": "#FF8C42", "color_secundario": "#2D3436"}');
            unset($form['theme_settings']);
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);

        // Validar que machine_name sea alfanumérico.
        $machine_name = $form_state->getValue(['machine_name', 0, 'value']);
        if ($machine_name && !preg_match('/^[a-z0-9_]+$/', $machine_name)) {
            $form_state->setErrorByName('machine_name', $this->t('El machine name solo puede contener letras minúsculas, números y guiones bajos.'));
        }

        // Validar JSON de theme_settings.
        $theme_settings = $form_state->getValue(['theme_settings', 0, 'value']);
        if ($theme_settings && json_decode($theme_settings) === NULL) {
            $form_state->setErrorByName('theme_settings', $this->t('La configuración de tema debe ser un JSON válido.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        $result = parent::save($form, $form_state);

        $entity = $this->entity;
        $message_arguments = ['%label' => $entity->label()];

        if ($result == SAVED_NEW) {
            $this->messenger()->addStatus($this->t('Vertical %label creada.', $message_arguments));
        } else {
            $this->messenger()->addStatus($this->t('Vertical %label actualizada.', $message_arguments));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));
        return $result;
    }

}
