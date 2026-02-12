<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_self_discovery\Entity\LifeTimeline;

/**
 * Formulario Phase 2 del Timeline: describir un evento en detalle.
 *
 * Permite al usuario enriquecer un evento existente del timeline con
 * factores de satisfaccion, habilidades demostradas y valores reflejados.
 */
class TimelinePhase2Form extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'jaraba_timeline_phase2_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, ?LifeTimeline $life_timeline = NULL): array
    {
        if (!$life_timeline) {
            $form['error'] = [
                '#markup' => '<p>' . $this->t('Evento no encontrado.') . '</p>',
            ];
            return $form;
        }

        $form['#prefix'] = '<div id="timeline-phase2-wrapper" class="timeline-phase2">';
        $form['#suffix'] = '</div>';

        $form['#attached']['library'][] = 'jaraba_self_discovery/global';

        // Guardar referencia al evento.
        $form['event_id'] = [
            '#type' => 'hidden',
            '#value' => $life_timeline->id(),
        ];

        // Cabecera con informacion del evento.
        $form['event_info'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['timeline-phase2__event-info']],
            'title' => [
                '#markup' => '<h3>' . htmlspecialchars($life_timeline->get('title')->value) . '</h3>',
            ],
            'date' => [
                '#markup' => '<p class="timeline-phase2__date">' . htmlspecialchars($life_timeline->get('event_date')->value) . ' - ' . $life_timeline->getEventTypeLabel() . '</p>',
            ],
        ];

        // Descripcion detallada.
        $form['event_description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Describe este evento en detalle'),
            '#description' => $this->t('Que paso exactamente, como te sentiste, que aprendiste.'),
            '#default_value' => $life_timeline->get('description')->value ?? '',
            '#rows' => 5,
            '#required' => TRUE,
        ];

        // Factores de satisfaccion.
        $form['satisfaction_factors'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Factores de satisfaccion presentes'),
            '#description' => $this->t('Selecciona los factores que estuvieron presentes en este evento.'),
            '#options' => [
                'logro' => $this->t('Logro'),
                'reconocimiento' => $this->t('Reconocimiento'),
                'autonomia' => $this->t('Autonomia'),
                'proposito' => $this->t('Proposito'),
                'crecimiento' => $this->t('Crecimiento'),
            ],
            '#default_value' => $life_timeline->getSatisfactionFactors(),
        ];

        // Habilidades demostradas.
        $form['skills_demonstrated'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Habilidades demostradas'),
            '#description' => $this->t('Separa las habilidades con comas (ej: comunicacion, liderazgo, organizacion).'),
            '#default_value' => implode(', ', $life_timeline->getSkills()),
            '#maxlength' => 512,
        ];

        // Valores reflejados.
        $form['values_reflected'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Valores reflejados'),
            '#description' => $this->t('Que valores personales se reflejaron en este evento. Separa con comas.'),
            '#default_value' => implode(', ', $life_timeline->getValuesDiscovered()),
            '#maxlength' => 512,
        ];

        // Aprendizajes.
        $form['learnings'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Aprendizajes clave'),
            '#description' => $this->t('Que aprendiste de esta experiencia.'),
            '#default_value' => $life_timeline->get('learnings')->value ?? '',
            '#rows' => 3,
        ];

        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Guardar descripcion'),
            '#attributes' => ['class' => ['btn', 'btn--primary']],
        ];

        $form['actions']['back'] = [
            '#type' => 'link',
            '#title' => $this->t('Volver al Timeline'),
            '#url' => \Drupal\Core\Url::fromRoute('jaraba_self_discovery.timeline'),
            '#attributes' => ['class' => ['btn', 'btn--secondary']],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        $event_id = $form_state->getValue('event_id');

        try {
            $storage = \Drupal::entityTypeManager()->getStorage('life_timeline');
            $entity = $storage->load($event_id);

            if (!$entity) {
                $this->messenger()->addError($this->t('Evento no encontrado.'));
                return;
            }

            // Actualizar descripcion.
            $entity->set('description', $form_state->getValue('event_description'));

            // Actualizar factores de satisfaccion.
            $factors = array_filter($form_state->getValue('satisfaction_factors') ?: []);
            $entity->set('satisfaction_factors', json_encode(array_values($factors)));

            // Actualizar habilidades.
            $skills = $form_state->getValue('skills_demonstrated');
            $skillsArray = $skills ? array_map('trim', explode(',', $skills)) : [];
            $entity->set('skills_used', json_encode(array_filter($skillsArray)));

            // Actualizar valores.
            $values = $form_state->getValue('values_reflected');
            $valuesArray = $values ? array_map('trim', explode(',', $values)) : [];
            $entity->set('values_discovered', json_encode(array_filter($valuesArray)));

            // Actualizar aprendizajes.
            $entity->set('learnings', $form_state->getValue('learnings'));

            $entity->save();

            $this->messenger()->addStatus($this->t('Evento actualizado correctamente.'));
        }
        catch (\Exception $e) {
            $this->messenger()->addError($this->t('Error al guardar: @error', [
                '@error' => $e->getMessage(),
            ]));
        }

        $form_state->setRedirectUrl(\Drupal\Core\Url::fromRoute('jaraba_self_discovery.timeline'));
    }

}
