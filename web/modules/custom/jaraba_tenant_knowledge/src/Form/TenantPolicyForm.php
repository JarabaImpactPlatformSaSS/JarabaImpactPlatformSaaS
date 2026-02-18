<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * FORMULARIO POLÍTICA EN SLIDE-PANEL
 *
 * PROPÓSITO:
 * Formulario para crear/editar políticas del tenant.
 * Incluye selector de templates predefinidos.
 *
 * PATRÓN:
 * Sigue el patrón slide-panel del proyecto.
 */
class TenantPolicyForm extends ContentEntityForm
{


    /**
     * Tenant context service. // AUDIT-CONS-N10: Proper DI for tenant context.
     */
    protected ?TenantContextService $tenantContext = NULL;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        $instance = parent::create($container);
        if ($container->has('ecosistema_jaraba_core.tenant_context')) {
            $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context'); // AUDIT-CONS-N10: Proper DI for tenant context.
        }
        return $instance;
    }

    /**
     * Templates predefinidos por tipo de política.
     */
    protected const TEMPLATES = [
        'returns' => [
            'title' => 'Política de Devoluciones',
            'content' => "## Política de Devoluciones\n\n### Plazo de Devolución\nAceptamos devoluciones dentro de los [X] días posteriores a la compra.\n\n### Condiciones\n- El producto debe estar sin usar y en su embalaje original\n- Debe incluir todos los accesorios y etiquetas\n- Se requiere el ticket de compra o factura\n\n### Proceso\n1. Contacta a nuestro servicio de atención al cliente\n2. Recibirás un número de autorización de devolución\n3. Envía el producto a nuestra dirección\n4. El reembolso se procesará en [X] días hábiles\n\n### Excepciones\n- Productos personalizados no admiten devolución\n- Artículos en oferta tienen condiciones especiales",
        ],
        'shipping' => [
            'title' => 'Política de Envíos',
            'content' => "## Política de Envíos\n\n### Tiempos de Entrega\n- **Nacional**: 2-5 días hábiles\n- **Internacional**: 7-15 días hábiles\n\n### Costes de Envío\n- Pedidos superiores a [X]€: Envío gratuito\n- Resto de pedidos: Tarifa según peso y destino\n\n### Seguimiento\nRecibirás un email con el número de seguimiento una vez enviado tu pedido.\n\n### Zonas de Cobertura\nRealizamos envíos a todo el territorio nacional y países de la UE.",
        ],
        'privacy' => [
            'title' => 'Política de Privacidad',
            'content' => "## Política de Privacidad\n\n### Responsable del Tratamiento\n[Nombre de la Empresa]\n\n### Datos Recopilados\n- Datos de identificación (nombre, email)\n- Datos de facturación\n- Historial de compras\n\n### Finalidad\n- Gestión de pedidos y entregas\n- Comunicaciones comerciales (si consientes)\n- Mejora de nuestros servicios\n\n### Derechos\nPuedes ejercer tus derechos de acceso, rectificación, cancelación y oposición contactando a [email].",
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state): array
    {
        $form = parent::buildForm($form, $form_state);

        $form['#attributes']['class'][] = 'jaraba-premium-form';
        $form['#attributes']['class'][] = 'slide-panel__form';
        $form['#attributes']['class'][] = 'policy-form';

        $entity = $this->getEntity();

        // Asignar tenant automáticamente si es nueva entidad.
        if ($entity->isNew()) {
            $tenantId = $this->getCurrentTenantId();
            if ($tenantId) {
                $entity->set('tenant_id', $tenantId);
            }

            // Añadir selector de template para nuevas políticas.
            $form['template_selector'] = [
                '#type' => 'select',
                '#title' => $this->t('Usar plantilla'),
                '#options' => [
                    '' => $this->t('- Sin plantilla -'),
                    'returns' => $this->t('Política de Devoluciones'),
                    'shipping' => $this->t('Política de Envíos'),
                    'privacy' => $this->t('Política de Privacidad'),
                ],
                '#weight' => -10,
                '#ajax' => [
                    'callback' => '::applyTemplateAjax',
                    'wrapper' => 'policy-form-wrapper',
                ],
            ];
        }

        // Wrapper para AJAX.
        $form['#prefix'] = '<div id="policy-form-wrapper">';
        $form['#suffix'] = '</div>';

        // Contenedor principal.
        $form['content_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['policy-form__content']],
        ];

        // Mover campos al wrapper.
        foreach (['policy_type', 'title', 'content', 'summary'] as $field) {
            if (isset($form[$field])) {
                $form['content_wrapper'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Fieldset de configuración avanzada.
        $form['settings_wrapper'] = [
            '#type' => 'details',
            '#title' => $this->t('Configuración Avanzada'),
            '#open' => FALSE,
        ];

        foreach (['version_notes', 'is_published', 'effective_date'] as $field) {
            if (isset($form[$field])) {
                $form['settings_wrapper'][$field] = $form[$field];
                unset($form[$field]);
            }
        }

        // Ocultar campos de sistema.
        foreach (['tenant_id', 'content_hash', 'qdrant_point_id', 'version_number'] as $field) {
            if (isset($form[$field])) {
                $form[$field]['#access'] = FALSE;
            }
        }

        return $form;
    }

    /**
     * AJAX callback para aplicar template.
     */
    public function applyTemplateAjax(array &$form, FormStateInterface $form_state): array
    {
        $template = $form_state->getValue('template_selector');

        if (!empty($template) && isset(self::TEMPLATES[$template])) {
            $templateData = self::TEMPLATES[$template];

            // Establecer valores del template.
            $form['content_wrapper']['policy_type']['widget'][0]['value']['#value'] = $template;
            $form['content_wrapper']['title']['widget'][0]['value']['#value'] = $templateData['title'];
            $form['content_wrapper']['content']['widget'][0]['value']['#value'] = $templateData['content'];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $form, FormStateInterface $form_state): int
    {
        $entity = $this->getEntity();
        $isNew = $entity->isNew();

        // Incrementar versión si no es nueva y el contenido cambió.
        if (!$isNew && $entity->needsRegeneration()) {
            $entity->incrementVersion();
        }

        // Actualizar hash de contenido.
        $entity->updateContentHash();

        $status = parent::save($form, $form_state);

        // Mensaje de confirmación.
        $messenger = \Drupal::messenger();
        if ($isNew) {
            $messenger->addStatus($this->t('Política "@title" creada correctamente.', [
                '@title' => $entity->getTitle(),
            ]));
        } else {
            $messenger->addStatus($this->t('Política "@title" actualizada (v@version).', [
                '@title' => $entity->getTitle(),
                '@version' => $entity->getVersionNumber(),
            ]));
        }

        $form_state->setRedirectUrl(Url::fromRoute('jaraba_tenant_knowledge.policies'));

        return $status;
    }

    /**
     * Obtiene el tenant ID actual.
     */
    protected function getCurrentTenantId(): ?int
    {
        if ($this->tenantContext !== NULL) {
            $tenantContext = $this->tenantContext;
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
