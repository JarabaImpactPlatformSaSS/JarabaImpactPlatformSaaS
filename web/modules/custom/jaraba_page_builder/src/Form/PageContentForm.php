<?php

namespace Drupal\jaraba_page_builder\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jaraba_page_builder\Service\FormBuilderService;
use Drupal\jaraba_page_builder\Service\TenantResolverService;
use Drupal\jaraba_page_builder\Service\QuotaManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para crear/editar páginas del Page Builder.
 *
 * PROPÓSITO:
 * Este formulario combina campos estáticos (título, URL, SEO) con
 * campos dinámicos generados desde el JSON Schema de la plantilla.
 *
 * FLUJO:
 * 1. Usuario selecciona plantilla en Template Picker
 * 2. Se redirige aquí con ?template=<template_id>
 * 3. Se cargan campos estáticos + campos dinámicos del schema
 * 4. Al guardar, se validan y almacenan en content_data (JSON)
 */
class PageContentForm extends ContentEntityForm
{

    /**
     * Servicio de generación de formularios.
     *
     * @var \Drupal\jaraba_page_builder\Service\FormBuilderService
     */
    protected FormBuilderService $formBuilder;

    /**
     * Servicio de resolución de tenant.
     *
     * @var \Drupal\jaraba_page_builder\Service\TenantResolverService
     */
    protected TenantResolverService $tenantResolver;

    /**
     * Servicio de gestión de cuotas.
     *
     * @var \Drupal\jaraba_page_builder\Service\QuotaManagerService
     */
    protected QuotaManagerService $quotaManager;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): self
    {
        $instance = parent::create($container);
        $instance->formBuilder = $container->get('jaraba_page_builder.form_builder');
        $instance->tenantResolver = $container->get('jaraba_page_builder.tenant_resolver');
        $instance->quotaManager = $container->get('jaraba_page_builder.quota_manager');
        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function form(array $form, FormStateInterface $form_state)
    {
        $form = parent::form($form, $form_state);

        /** @var \Drupal\jaraba_page_builder\PageContentInterface $page */
        $page = $this->entity;

        // Verificar cuota antes de mostrar formulario de creación.
        if ($page->isNew()) {
            $quota_check = $this->quotaManager->checkCanCreatePage();
            if (!$quota_check['allowed']) {
                $form['quota_exceeded'] = [
                    '#type' => 'container',
                    '#attributes' => ['class' => ['messages', 'messages--error']],
                    'message' => [
                        '#markup' => $quota_check['message'],
                    ],
                    '#weight' => -100,
                ];
                // Deshabilitar el resto del formulario.
                $form['#disabled'] = TRUE;
            } else {
                // Mostrar páginas restantes.
                if (isset($quota_check['remaining'])) {
                    $form['quota_info'] = [
                        '#type' => 'container',
                        '#attributes' => ['class' => ['messages', 'messages--status']],
                        'message' => [
                            '#markup' => $this->t('Puedes crear @count páginas más con tu plan actual.', [
                                '@count' => $quota_check['remaining'],
                            ]),
                        ],
                        '#weight' => -100,
                    ];
                }
            }
        }

        // Grupo de información básica.
        $form['basic'] = [
            '#type' => 'details',
            '#title' => $this->t('Información básica'),
            '#open' => TRUE,
            '#weight' => -10,
        ];

        // Mover título al grupo.
        if (isset($form['title'])) {
            $form['basic']['title'] = $form['title'];
            unset($form['title']);
        }

        // Mover path alias al grupo.
        if (isset($form['path_alias'])) {
            $form['basic']['path_alias'] = $form['path_alias'];
            unset($form['path_alias']);
        }

        // ============================================
        // CAMPOS DINÁMICOS SEGÚN PLANTILLA
        // ============================================
        $template = $this->getSelectedTemplate($form_state);

        if ($template) {
            $schema = $template->getFieldsSchema();

            if (!empty($schema)) {
                // Obtener valores actuales.
                $current_values = $page->get('content_data')->value;
                $values = is_string($current_values) ? json_decode($current_values, TRUE) : [];

                // Grupo de contenido dinámico.
                $form['content'] = [
                    '#type' => 'details',
                    '#title' => $this->t('Contenido de la página'),
                    '#open' => TRUE,
                    '#weight' => 0,
                    '#tree' => TRUE,
                ];

                // Generar campos dinámicos.
                $dynamic_fields = $this->formBuilder->buildForm($schema, $values ?? []);
                $form['content'] += $dynamic_fields;

                // Mostrar info de la plantilla.
                $form['template_info'] = [
                    '#type' => 'container',
                    '#attributes' => ['class' => ['template-info-box']],
                    '#weight' => -5,
                    'info' => [
                        '#markup' => '<div class="template-badge">' .
                            '<strong>' . $this->t('Plantilla:') . '</strong> ' .
                            $template->label() .
                            ($template->isPremium() ? ' <span class="premium-badge">★ Premium</span>' : '') .
                            '</div>',
                    ],
                ];
            }
        }

        // Grupo de SEO.
        $form['seo'] = [
            '#type' => 'details',
            '#title' => $this->t('SEO'),
            '#open' => FALSE,
            '#weight' => 10,
        ];

        if (isset($form['meta_title'])) {
            $form['seo']['meta_title'] = $form['meta_title'];
            unset($form['meta_title']);
        }

        if (isset($form['meta_description'])) {
            $form['seo']['meta_description'] = $form['meta_description'];
            unset($form['meta_description']);
        }

        // Grupo de publicación.
        $form['publishing'] = [
            '#type' => 'details',
            '#title' => $this->t('Publicación'),
            '#open' => FALSE,
            '#weight' => 20,
        ];

        if (isset($form['status'])) {
            $form['publishing']['status'] = $form['status'];
            unset($form['status']);
        }

        if (isset($form['menu_link'])) {
            $form['publishing']['menu_link'] = $form['menu_link'];
            unset($form['menu_link']);
        }

        // Grupo de Modo Avanzado - Solo visible para administradores.
        $is_admin = $this->currentUser()->hasPermission('administer page builder');

        if ($is_admin) {
            $form['advanced'] = [
                '#type' => 'details',
                '#title' => $this->t('🔧 Modo Avanzado'),
                '#open' => FALSE,
                '#weight' => 100,
                '#description' => $this->t('Campos técnicos para desarrolladores. Modificar con precaución.'),
                '#attributes' => ['class' => ['advanced-mode-section']],
            ];

            // Mover campos técnicos al grupo avanzado.
            if (isset($form['template_id'])) {
                $form['advanced']['template_id'] = $form['template_id'];
                unset($form['template_id']);
            }

            if (isset($form['content_data'])) {
                $form['advanced']['content_data'] = $form['content_data'];
                $form['advanced']['content_data']['#description'] = $this->t(
                    'Datos JSON del contenido según el schema de la plantilla. Editar solo si sabes lo que haces.'
                );
                unset($form['content_data']);
            }

            if (isset($form['tenant_id'])) {
                $form['advanced']['tenant_id'] = $form['tenant_id'];
                unset($form['tenant_id']);
            }
        } else {
            // Ocultar completamente para usuarios normales.
            $form['template_id']['#access'] = FALSE;
            $form['content_data']['#access'] = FALSE;
            $form['tenant_id']['#access'] = FALSE;
        }

        // Añadir librería de estilos.
        $form['#attached']['library'][] = 'jaraba_page_builder/builder';

        // Añadir librería de generación de contenido con IA.
        $form['#attached']['library'][] = 'jaraba_page_builder/ai-content-generator';

        // Añadir clase CSS para que el JS pueda detectar el Form Builder.
        $form['#attributes']['class'][] = 'page-builder-form';
        $form['#attributes']['class'][] = 'jaraba-form-builder';

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);

        // Validar cuota.
        if ($this->entity->isNew()) {
            $quota_check = $this->quotaManager->checkCanCreatePage();
            if (!$quota_check['allowed']) {
                $form_state->setErrorByName('', $quota_check['message']);
            }
        }

        // Validar campos dinámicos según schema.
        $template = $this->getSelectedTemplate($form_state);
        if ($template) {
            $schema = $template->getFieldsSchema();
            $content_values = $form_state->getValue('content') ?? [];
            $this->validateAgainstSchema($schema, $content_values, $form_state);
        }
    }

    /**
     * Valida valores contra el JSON Schema.
     *
     * @param array $schema
     *   El schema.
     * @param array $values
     *   Los valores a validar.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   Estado del formulario.
     */
    protected function validateAgainstSchema(array $schema, array $values, FormStateInterface $form_state): void
    {
        if (!isset($schema['properties'])) {
            return;
        }

        $required = $schema['required'] ?? [];

        foreach ($schema['properties'] as $field_name => $field_schema) {
            $value = $values[$field_name] ?? NULL;

            // Validar requeridos.
            if (in_array($field_name, $required, TRUE) && empty($value)) {
                $label = $field_schema['title'] ?? $field_name;
                $form_state->setErrorByName(
                    "content][$field_name",
                    $this->t('El campo @field es obligatorio.', ['@field' => $label])
                );
            }

            // Validar longitud mínima.
            if (isset($field_schema['minLength']) && is_string($value)) {
                if (strlen($value) < $field_schema['minLength']) {
                    $form_state->setErrorByName(
                        "content][$field_name",
                        $this->t('El campo @field debe tener al menos @min caracteres.', [
                            '@field' => $field_schema['title'] ?? $field_name,
                            '@min' => $field_schema['minLength'],
                        ])
                    );
                }
            }

            // Validar patrón regex.
            if (isset($field_schema['pattern']) && is_string($value)) {
                if (!preg_match('/' . $field_schema['pattern'] . '/', $value)) {
                    $form_state->setErrorByName(
                        "content][$field_name",
                        $this->t('El campo @field no tiene un formato válido.', [
                            '@field' => $field_schema['title'] ?? $field_name,
                        ])
                    );
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state)
    {
        /** @var \Drupal\jaraba_page_builder\PageContentInterface $page */
        $page = $this->entity;

        // Asignar tenant del usuario actual si no está definido.
        if (!$page->getTenantId()) {
            $tenant_id = $this->tenantResolver->getCurrentTenantId();
            if ($tenant_id) {
                $page->set('tenant_id', $tenant_id);
            }
        }

        // Extraer y guardar contenido dinámico.
        $template = $this->getSelectedTemplate($form_state);
        if ($template) {
            $schema = $template->getFieldsSchema();
            $content_values = $form_state->getValue('content') ?? [];
            $extracted_values = $this->formBuilder->extractValues($schema, $content_values);
            $page->set('content_data', json_encode($extracted_values, JSON_UNESCAPED_UNICODE));
        }

        $status = parent::save($form, $form_state);

        if ($status === SAVED_NEW) {
            $this->messenger()->addStatus($this->t('La página %title ha sido creada.', [
                '%title' => $page->label(),
            ]));
        } else {
            $this->messenger()->addStatus($this->t('La página %title ha sido actualizada.', [
                '%title' => $page->label(),
            ]));
        }

        $form_state->setRedirectUrl($page->toUrl('collection'));

        return $status;
    }

    /**
     * Obtiene la plantilla seleccionada.
     *
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   Estado del formulario.
     *
     * @return \Drupal\jaraba_page_builder\PageTemplateInterface|null
     *   La plantilla o NULL.
     */
    protected function getSelectedTemplate(FormStateInterface $form_state): ?\Drupal\jaraba_page_builder\PageTemplateInterface
    {
        /** @var \Drupal\jaraba_page_builder\PageContentInterface $page */
        $page = $this->entity;

        // Obtener template_id de la entidad.
        $template_id = $page->get('template_id')->value;

        // O del query parameter si es nueva página.
        if (!$template_id) {
            $template_id = \Drupal::request()->query->get('template');
        }

        if (!$template_id) {
            return NULL;
        }

        // Cargar la plantilla.
        $template = \Drupal::entityTypeManager()
            ->getStorage('page_template')
            ->load($template_id);

        return $template instanceof \Drupal\jaraba_page_builder\PageTemplateInterface ? $template : NULL;
    }

}
