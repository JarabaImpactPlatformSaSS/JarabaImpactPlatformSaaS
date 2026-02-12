<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar entidades ExperimentVariant.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Las variantes se crean y editan desde dentro del contexto
 * de un experimento padre. El formulario organiza los campos
 * en grupos lógicos para facilitar la edición.
 *
 * @package Drupal\jaraba_page_builder\Form
 */
class ExperimentVariantForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     *
     * Configura el formulario con fieldsets para organización visual.
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Grupo de información básica.
        $form['basic_info'] = [
            '#type' => 'details',
            '#title' => $this->t('Información de la variante'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        // Mover campos básicos al grupo.
        $basic_fields = ['name', 'is_control', 'traffic_weight'];
        foreach ($basic_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['basic_info'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        // Grupo de contenido modificado.
        $form['content_modifications'] = [
            '#type' => 'details',
            '#title' => $this->t('Modificaciones de contenido'),
            '#description' => $this->t('Define los cambios que esta variante aplicará respecto al control.'),
            '#open' => FALSE,
            '#weight' => 0,
        ];

        // Campo de datos de contenido en el grupo de modificaciones.
        if (isset($form['content_data'])) {
            $form['content_modifications']['content_data'] = $form['content_data'];
            $form['content_modifications']['content_data']['#description'] = $this->t(
                'JSON con modificaciones: textos, estilos, clases, visibilidad. Ejemplo: {"texts": {"#hero-title": "Nuevo título"}}'
            );
            unset($form['content_data']);
        }

        // Grupo de métricas (solo lectura para información).
        $form['metrics'] = [
            '#type' => 'details',
            '#title' => $this->t('Métricas'),
            '#description' => $this->t('Datos de rendimiento (actualizados automáticamente).'),
            '#open' => FALSE,
            '#weight' => 10,
        ];

        // Mover campos de métricas al grupo.
        $metric_fields = ['visitors', 'conversions'];
        foreach ($metric_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['metrics'][$field_name] = $form[$field_name];
                // Hacer campos de métricas de solo lectura.
                $form['metrics'][$field_name]['#disabled'] = TRUE;
                unset($form[$field_name]);
            }
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * Guarda la variante y muestra mensaje de confirmación.
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->entity;
        $status = parent::save($form, $form_state);

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('La variante %name ha sido creada.', [
                '%name' => $entity->label(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('La variante %name ha sido actualizada.', [
                '%name' => $entity->label(),
            ]));
        }

        // Redirigir de vuelta al experimento padre.
        $experimentId = $entity->getExperimentId();
        if ($experimentId) {
            $form_state->setRedirect('entity.page_experiment.canonical', [
                'page_experiment' => $experimentId,
            ]);
        }

        return $status;
    }

}
