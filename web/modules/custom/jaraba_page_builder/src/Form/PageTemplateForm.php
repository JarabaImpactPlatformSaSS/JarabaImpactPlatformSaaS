<?php

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Formulario para crear/editar plantillas de página.
 */
class PageTemplateForm extends EntityForm
{

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        /** @var \Drupal\jaraba_page_builder\PageTemplateInterface $template */
        $template = $this->entity;

        $form['label'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre'),
            '#description' => $this->t('Nombre visible de la plantilla.'),
            '#default_value' => $template->label(),
            '#required' => TRUE,
        ];

        $form['id'] = [
            '#type' => 'machine_name',
            '#default_value' => $template->id(),
            '#machine_name' => [
                'exists' => '\Drupal\jaraba_page_builder\Entity\PageTemplate::load',
            ],
            '#disabled' => !$template->isNew(),
        ];

        $form['description'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Descripción'),
            '#description' => $this->t('Descripción de la plantilla para el usuario.'),
            '#default_value' => $template->getDescription(),
            '#rows' => 3,
        ];

        $form['category'] = [
            '#type' => 'select',
            '#title' => $this->t('Categoría'),
            '#description' => $this->t('Categoría de la plantilla.'),
            '#options' => [
                'hero' => $this->t('Hero'),
                'features' => $this->t('Features'),
                'stats' => $this->t('Estadísticas'),
                'testimonials' => $this->t('Testimonios'),
                'pricing' => $this->t('Precios'),
                'cta' => $this->t('Call to Action'),
                'content' => $this->t('Contenido'),
                'landing' => $this->t('Landing Page'),
                'dashboard' => $this->t('Dashboard'),
            ],
            '#default_value' => $template->getCategory(),
            '#required' => TRUE,
        ];

        $form['twig_template'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Template Twig'),
            '#description' => $this->t('Ruta al template Twig (ej: @jaraba_page_builder/blocks/hero-fullscreen.html.twig).'),
            '#default_value' => $template->getTwigTemplate(),
            '#required' => TRUE,
        ];

        $form['fields_schema'] = [
            '#type' => 'textarea',
            '#title' => $this->t('JSON Schema'),
            '#description' => $this->t('Esquema JSON para los campos configurables de la plantilla.'),
            '#default_value' => json_encode($template->getFieldsSchema(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            '#rows' => 15,
            '#attributes' => [
                'style' => 'font-family: monospace;',
            ],
        ];

        $form['plans_required'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Planes requeridos'),
            '#description' => $this->t('Planes que tienen acceso a esta plantilla.'),
            '#options' => [
                'starter' => $this->t('Starter'),
                'professional' => $this->t('Professional'),
                'enterprise' => $this->t('Enterprise'),
            ],
            '#default_value' => $template->getPlansRequired(),
        ];

        $form['is_premium'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Plantilla Premium'),
            '#description' => $this->t('Marcar si usa componentes de Aceternity UI o Magic UI.'),
            '#default_value' => $template->isPremium(),
        ];

        $form['preview_image'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Imagen de preview'),
            '#description' => $this->t('Ruta a la imagen de preview para el selector de plantillas.'),
            '#default_value' => $template->getPreviewImage(),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);

        // Validar JSON Schema.
        $schema = $form_state->getValue('fields_schema');
        if (!empty($schema)) {
            $decoded = json_decode($schema, TRUE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $form_state->setErrorByName('fields_schema', $this->t('El JSON Schema no es válido: @error', [
                    '@error' => json_last_error_msg(),
                ]));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        /** @var \Drupal\jaraba_page_builder\Entity\PageTemplate $template */
        $template = $this->entity;

        // Convertir JSON Schema a array.
        $schema = $form_state->getValue('fields_schema');
        if (!empty($schema)) {
            $template->set('fields_schema', json_decode($schema, TRUE));
        }

        // Limpiar planes vacíos.
        $plans = array_filter($form_state->getValue('plans_required'));
        $template->set('plans_required', array_values($plans));

        $status = $template->save();

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('La plantilla %label ha sido creada.', [
                '%label' => $template->label(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('La plantilla %label ha sido actualizada.', [
                '%label' => $template->label(),
            ]));
        }

        $form_state->setRedirectUrl($template->toUrl('collection'));
    }

}
