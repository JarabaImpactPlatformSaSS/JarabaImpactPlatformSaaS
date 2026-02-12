<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear y editar entidades PageExperiment.
 *
 * ESPECIFICACIÓN: Doc 168 - Platform_AB_Testing_Pages_v1
 *
 * Este formulario permite a los usuarios crear y editar experimentos A/B
 * desde la interfaz de administración de Drupal.
 *
 * Los campos del formulario se generan automáticamente desde las
 * definiciones de campos base de la entidad PageExperiment.
 *
 * @package Drupal\jaraba_page_builder\Form
 */
class PageExperimentForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     *
     * Añade configuración adicional al formulario base.
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Agrupar campos en fieldsets para mejor organización visual.
        $form['basic_info'] = [
            '#type' => 'details',
            '#title' => $this->t('Información básica'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        // Mover campos básicos al grupo.
        if (isset($form['name'])) {
            $form['basic_info']['name'] = $form['name'];
            unset($form['name']);
        }

        if (isset($form['page_id'])) {
            $form['basic_info']['page_id'] = $form['page_id'];
            unset($form['page_id']);
        }

        // Grupo para configuración del experimento.
        $form['experiment_config'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración del experimento'),
            '#open' => TRUE,
            '#weight' => 0,
        ];

        // Mover campos de configuración al grupo.
        $config_fields = ['status', 'goal_type', 'goal_target', 'traffic_allocation', 'confidence_threshold'];
        foreach ($config_fields as $field_name) {
            if (isset($form[$field_name])) {
                $form['experiment_config'][$field_name] = $form[$field_name];
                unset($form[$field_name]);
            }
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * Procesa el guardado del experimento.
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->entity;
        $status = parent::save($form, $form_state);

        // Mensaje de confirmación según si es nuevo o editado.
        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('El experimento %name ha sido creado.', [
                '%name' => $entity->label(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('El experimento %name ha sido actualizado.', [
                '%name' => $entity->label(),
            ]));
        }

        // Redirigir a la lista de experimentos.
        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $status;
    }

}
