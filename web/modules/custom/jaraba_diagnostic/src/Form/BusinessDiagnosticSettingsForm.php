<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario de configuración para BusinessDiagnostic.
 *
 * Proporciona punto de anclaje para Field UI según patrón
 * Skeleton Settings Form del proyecto.
 */
class BusinessDiagnosticSettingsForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId(): string
    {
        return 'business_diagnostic_settings';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form['info'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['messages', 'messages--status']],
        ];

        $form['info']['content'] = [
            '#markup' => '<h3>' . $this->t('Configuración de Diagnósticos Empresariales') . '</h3>'
                . '<p>' . $this->t('Utiliza las pestañas superiores para:') . '</p>'
                . '<ul>'
                . '<li>' . $this->t('<strong>Administrar campos</strong>: Añadir campos personalizados a los diagnósticos.') . '</li>'
                . '<li>' . $this->t('<strong>Administrar presentación</strong>: Configurar la visualización de campos.') . '</li>'
                . '</ul>'
                . '<p>' . $this->t('Los diagnósticos se gestionan desde <a href="/admin/content/diagnostics">Contenido → Diagnósticos</a>.') . '</p>',
        ];

        $form['scoring'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración de Scoring'),
            '#open' => TRUE,
        ];

        $form['scoring']['info'] = [
            '#markup' => '<p>' . $this->t('La lógica de scoring se define en las Secciones de Diagnóstico. Cada sección tiene un peso que contribuye al score global.') . '</p>',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void
    {
        // No hay configuración que guardar por ahora
    }

}
