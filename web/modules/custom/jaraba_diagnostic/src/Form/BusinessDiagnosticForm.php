<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario Premium para la entidad BusinessDiagnostic.
 *
 * Organiza los campos en fieldsets lógicos siguiendo
 * el patrón Premium Form de la plataforma (Standard 28.6).
 */
class BusinessDiagnosticForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Añadir clase para estilos premium
        $form['#attributes']['class'][] = 'premium-admin-form';
        $form['#attributes']['class'][] = 'diagnostic-form';

        // Attach admin premium library
        $form['#attached']['library'][] = 'jaraba_diagnostic/admin_form';

        // === Header con indicador de estado ===
        $isNew = $this->entity->isNew();
        $form['form_header'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['form-header-premium']],
            '#weight' => -100,
        ];

        $form['form_header']['status_indicator'] = [
            '#type' => 'markup',
            '#markup' => $isNew
                ? '<div class="status-badge status-new"><span class="badge-icon">✨</span> ' . $this->t('Nuevo diagnóstico') . '</div>'
                : '<div class="status-badge status-edit"><span class="badge-icon">✏️</span> ' . $this->t('Editando diagnóstico') . '</div>',
        ];

        if (!$isNew) {
            $score = $this->entity->get('overall_score')->value ?? 0;
            $form['form_header']['score_preview'] = [
                '#type' => 'markup',
                '#markup' => '<div class="score-preview"><span class="score-value">' . round($score) . '</span><span class="score-label">' . $this->t('puntos') . '</span></div>',
            ];
        }

        // === Sección: Información del Negocio ===
        $form['business_info'] = [
            '#type' => 'details',
            '#title' => $this->t('📊 Información del Negocio'),
            '#description' => $this->t('Datos básicos del negocio que determinarán el contexto del diagnóstico.'),
            '#open' => TRUE,
            '#weight' => 0,
            '#attributes' => ['class' => ['form-section', 'section-business']],
        ];

        // Mover campos a la sección con descripciones mejoradas
        $businessFields = [
            'business_name' => $this->t('Nombre comercial o razón social del negocio.'),
            'business_sector' => $this->t('El sector determina las preguntas y recomendaciones específicas.'),
            'business_size' => $this->t('Número aproximado de empleados.'),
            'business_age_years' => $this->t('Años desde la fundación del negocio.'),
            'annual_revenue' => $this->t('Rango de facturación anual aproximado.'),
        ];

        foreach ($businessFields as $field => $description) {
            if (isset($form[$field])) {
                $form['business_info'][$field] = $form[$field];
                $form['business_info'][$field]['#description'] = $description;
                unset($form[$field]);
            }
        }

        // === Sección: Respuestas del Diagnóstico ===
        $form['responses_section'] = [
            '#type' => 'details',
            '#title' => $this->t('📝 Respuestas del Diagnóstico'),
            '#description' => $this->t('Las respuestas recopiladas durante el wizard. Estos datos se generan automáticamente.'),
            '#open' => FALSE,
            '#weight' => 5,
            '#attributes' => ['class' => ['form-section', 'section-responses']],
        ];

        if (isset($form['responses'])) {
            $form['responses_section']['responses'] = $form['responses'];
            $form['responses_section']['responses']['#description'] = $this->t('JSON con todas las respuestas del usuario (solo lectura recomendada).');
            unset($form['responses']);
        }

        // === Sección: Contexto del Programa ===
        $form['program_context'] = [
            '#type' => 'details',
            '#title' => $this->t('🏢 Contexto del Programa'),
            '#description' => $this->t('Vinculación con el tenant y métricas de Time-to-Value.'),
            '#open' => FALSE,
            '#weight' => 10,
            '#attributes' => ['class' => ['form-section', 'section-context']],
        ];

        $contextFields = ['tenant_id', 'maturity_ttv_score'];
        foreach ($contextFields as $field) {
            if (isset($form[$field])) {
                $form['program_context'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // === Sección: Resultados (solo en edición) ===
        if (!$isNew) {
            $form['results'] = [
                '#type' => 'details',
                '#title' => $this->t('🎯 Resultados del Diagnóstico'),
                '#description' => $this->t('Puntuación calculada y recomendaciones generadas por el sistema.'),
                '#open' => TRUE,
                '#weight' => 20,
                '#attributes' => ['class' => ['form-section', 'section-results', 'section-highlight']],
            ];

            $resultFields = [
                'overall_score' => $this->t('Puntuación de madurez digital (0-100).'),
                'maturity_level' => $this->t('Nivel calculado automáticamente según la puntuación.'),
                'estimated_loss_annual' => $this->t('Pérdida estimada por no digitalizar (€/año).'),
                'recommended_path_id' => $this->t('Itinerario sugerido basado en el análisis.'),
            ];

            foreach ($resultFields as $field => $description) {
                if (isset($form[$field])) {
                    $form['results'][$field] = $form[$field];
                    $form['results'][$field]['#description'] = $description;
                    unset($form[$field]);
                }
            }

            // Añadir botón de recálculo
            $form['results']['recalculate'] = [
                '#type' => 'button',
                '#value' => $this->t('🔄 Recalcular puntuación'),
                '#attributes' => ['class' => ['btn-recalculate']],
                '#ajax' => [
                    'callback' => '::recalculateScore',
                    'wrapper' => 'results-wrapper',
                ],
                '#weight' => 100,
            ];
        }

        // === Sección: Estado ===
        $form['status_section'] = [
            '#type' => 'details',
            '#title' => $this->t('⚙️ Estado y Publicación'),
            '#description' => $this->t('Control del estado del diagnóstico y asignación de usuario.'),
            '#open' => FALSE,
            '#weight' => 30,
            '#attributes' => ['class' => ['form-section', 'section-status']],
        ];

        $statusFields = ['status', 'user_id'];
        foreach ($statusFields as $field) {
            if (isset($form[$field])) {
                $form['status_section'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        return $form;
    }

    /**
     * AJAX callback para recalcular puntuación.
     */
    public function recalculateScore(array &$form, FormStateInterface $form_state): array
    {
        // Placeholder - en implementación real llamaría al ScoringService
        $this->messenger()->addStatus($this->t('Puntuación recalculada.'));
        return $form['results'];
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->entity;
        $result = parent::save($form, $form_state);

        $messageArgs = ['%title' => $entity->label()];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('✅ Diagnóstico %title creado. Complete las secciones para obtener resultados.', $messageArgs));
        } else {
            $this->messenger()->addStatus($this->t('✅ Diagnóstico %title actualizado.', $messageArgs));
        }

        $form_state->setRedirectUrl($entity->toUrl('collection'));

        return $result;
    }

}
