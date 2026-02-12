<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario Premium para DigitalizationPath.
 *
 * Organiza campos en secciones lógicas con UX mejorado
 * siguiendo el patrón Premium Form (Standard 28.6).
 */
class DigitalizationPathForm extends ContentEntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state): array
    {
        $form = parent::form($form, $form_state);

        // Añadir clases para estilos premium
        $form['#attributes']['class'][] = 'premium-admin-form';
        $form['#attributes']['class'][] = 'path-form';

        // Attach admin premium library
        $form['#attached']['library'][] = 'jaraba_paths/admin_form';

        $isNew = $this->entity->isNew();

        // === Header Premium ===
        $form['form_header'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['form-header-premium']],
            '#weight' => -100,
        ];

        $form['form_header']['status_indicator'] = [
            '#type' => 'markup',
            '#markup' => $isNew
                ? '<div class="status-badge status-new"><span class="badge-icon">🚀</span> ' . $this->t('Nuevo itinerario') . '</div>'
                : '<div class="status-badge status-edit"><span class="badge-icon">✏️</span> ' . $this->t('Editando itinerario') . '</div>',
        ];

        if (!$isNew) {
            $phasesCount = $this->countPhases();
            $form['form_header']['phases_count'] = [
                '#type' => 'markup',
                '#markup' => '<div class="stat-preview"><span class="stat-value">' . $phasesCount . '</span><span class="stat-label">' . $this->t('fases') . '</span></div>',
            ];
        }

        // === Sección: Información Básica ===
        $form['basic_info'] = [
            '#type' => 'details',
            '#title' => $this->t('📚 Información Básica'),
            '#description' => $this->t('Datos esenciales del itinerario que verán los usuarios.'),
            '#open' => TRUE,
            '#weight' => 0,
            '#attributes' => ['class' => ['form-section', 'section-basic']],
        ];

        $basicFields = [
            'title' => $this->t('Nombre descriptivo que aparecerá en el catálogo.'),
            'short_description' => $this->t('Resumen breve para la tarjeta del catálogo (máx. 160 caracteres).'),
            'description' => $this->t('Descripción completa con los objetivos y beneficios del itinerario.'),
            'image' => $this->t('Imagen de cabecera para el catálogo (recomendado: 800x400px).'),
            'outcomes' => $this->t('Lista de resultados que el usuario logrará (uno por línea).'),
        ];

        foreach ($basicFields as $field => $description) {
            if (isset($form[$field])) {
                $form['basic_info'][$field] = $form[$field];
                $form['basic_info'][$field]['#description'] = $description;
                unset($form[$field]);
            }
        }

        // === Sección: Público Objetivo ===
        $form['targeting'] = [
            '#type' => 'details',
            '#title' => $this->t('🎯 Público Objetivo'),
            '#description' => $this->t('Define a quién va dirigido este itinerario para mejorar las recomendaciones automáticas.'),
            '#open' => TRUE,
            '#weight' => 10,
            '#attributes' => ['class' => ['form-section', 'section-targeting']],
        ];

        $targetFields = [
            'target_sector' => $this->t('Sector empresarial principal (usado para filtrar en catálogo).'),
            'target_maturity_level' => $this->t('Nivel de madurez digital recomendado para empezar.'),
            'target_business_size' => $this->t('Tamaño de empresa ideal para este itinerario.'),
            'difficulty_level' => $this->t('Complejidad general del itinerario.'),
        ];

        foreach ($targetFields as $field => $description) {
            if (isset($form[$field])) {
                $form['targeting'][$field] = $form[$field];
                $form['targeting'][$field]['#description'] = $description;
                unset($form[$field]);
            }
        }

        // === Sección: Métricas ===
        $form['metrics'] = [
            '#type' => 'details',
            '#title' => $this->t('📊 Métricas y Duración'),
            '#description' => $this->t('Estimaciones de tiempo y retorno para ayudar al usuario a decidir.'),
            '#open' => FALSE,
            '#weight' => 20,
            '#attributes' => ['class' => ['form-section', 'section-metrics']],
        ];

        $metricFields = [
            'estimated_weeks' => $this->t('Tiempo estimado para completar el itinerario.'),
            'expected_roi_percent' => $this->t('ROI esperado tras implementar las mejoras (ej: 150).'),
            'total_steps' => $this->t('Total de pasos/actividades (se calcula automáticamente).'),
            'total_quick_wins' => $this->t('Quick wins incluidos que dan resultados inmediatos.'),
        ];

        foreach ($metricFields as $field => $description) {
            if (isset($form[$field])) {
                $form['metrics'][$field] = $form[$field];
                $form['metrics'][$field]['#description'] = $description;
                unset($form[$field]);
            }
        }

        // === Sección: Gestión de Contenido (solo edición) ===
        if (!$isNew) {
            $form['content_management'] = [
                '#type' => 'details',
                '#title' => $this->t('📋 Gestión de Contenido'),
                '#description' => $this->t('Accesos rápidos para gestionar las fases y módulos del itinerario.'),
                '#open' => TRUE,
                '#weight' => 25,
                '#attributes' => ['class' => ['form-section', 'section-content', 'section-highlight']],
            ];

            $form['content_management']['quick_links'] = [
                '#type' => 'markup',
                '#markup' => '
          <div class="quick-actions-grid">
            <a href="/admin/content/path-phase?path_id=' . $this->entity->id() . '" class="quick-action-btn">
              <span class="action-icon">📦</span>
              <span class="action-label">' . $this->t('Gestionar Fases') . '</span>
            </a>
            <a href="/admin/content/path-module" class="quick-action-btn">
              <span class="action-icon">📖</span>
              <span class="action-label">' . $this->t('Ver Módulos') . '</span>
            </a>
            <a href="/admin/content/path-step" class="quick-action-btn">
              <span class="action-icon">✅</span>
              <span class="action-label">' . $this->t('Ver Pasos') . '</span>
            </a>
          </div>
        ',
            ];
        }

        // === Sección: Publicación ===
        $form['publishing'] = [
            '#type' => 'details',
            '#title' => $this->t('⚙️ Publicación'),
            '#description' => $this->t('Control de visibilidad y asignación del itinerario.'),
            '#open' => FALSE,
            '#weight' => 30,
            '#attributes' => ['class' => ['form-section', 'section-publishing']],
        ];

        $pubFields = [
            'status' => $this->t('Solo los itinerarios publicados aparecen en el catálogo.'),
            'is_featured' => $this->t('Los destacados aparecen primero en el catálogo.'),
            'tenant_id' => $this->t('Tenant propietario del itinerario.'),
            'author' => $this->t('Usuario autor/responsable del itinerario.'),
        ];

        foreach ($pubFields as $field => $description) {
            if (isset($form[$field])) {
                $form['publishing'][$field] = $form[$field];
                $form['publishing'][$field]['#description'] = $description;
                unset($form[$field]);
            }
        }

        return $form;
    }

    /**
     * Cuenta las fases del itinerario actual.
     */
    protected function countPhases(): int
    {
        if ($this->entity->isNew()) {
            return 0;
        }

        return (int) \Drupal::entityTypeManager()
            ->getStorage('path_phase')
            ->getQuery()
            ->condition('path_id', $this->entity->id())
            ->accessCheck(TRUE)
            ->count()
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $result = parent::save($form, $form_state);

        $messageArgs = ['%title' => $this->entity->label()];

        if ($result === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('🚀 Itinerario %title creado. Añade fases y módulos para completarlo.', $messageArgs));
        } else {
            $this->messenger()->addStatus($this->t('✅ Itinerario %title actualizado.', $messageArgs));
        }

        $form_state->setRedirectUrl($this->entity->toUrl('collection'));

        return $result;
    }

}
