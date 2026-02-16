<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para configurar webhooks del tenant.
 *
 * Permite a los tenants configurar endpoints para recibir notificaciones
 * automáticas sobre eventos como nuevos pedidos, pagos, etc.
 */
class TenantWebhooksForm extends FormBase
{

    /**
     * Eventos disponibles para webhooks.
     */
    protected const WEBHOOK_EVENTS = [
        'order.created' => 'Nuevo pedido creado',
        'order.paid' => 'Pedido pagado',
        'order.shipped' => 'Pedido enviado',
        'order.completed' => 'Pedido completado',
        'order.cancelled' => 'Pedido cancelado',
        'customer.created' => 'Nuevo cliente registrado',
        'product.created' => 'Nuevo producto creado',
        'product.updated' => 'Producto actualizado',
        'payment.received' => 'Pago recibido',
        'payment.failed' => 'Pago fallido',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected TenantContextService $tenantContext,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('ecosistema_jaraba_core.tenant_context')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'tenant_webhooks_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $tenant = $this->tenantContext->getCurrentTenant();

        if (!$tenant) {
            $form['error'] = [
                '#markup' => $this->t('No tienes un tenant asignado.'),
            ];
            return $form;
        }

        $form['#prefix'] = '<div class="tenant-webhooks-form">';
        $form['#suffix'] = '</div>';

        $form['info'] = [
            '#markup' => '<div class="tenant-form-info">
        <p>' . $this->t('Los webhooks te permiten recibir notificaciones en tiempo real cuando ocurren eventos en tu tienda. Configura una URL de tu servidor para recibir estos eventos.') . '</p>
      </div>',
        ];

        // Obtener webhooks existentes.
        $webhooks = $this->getWebhooks($tenant->id());

        if (!empty($webhooks)) {
            $form['existing_webhooks'] = [
                '#type' => 'table',
                '#header' => [
                    $this->t('URL'),
                    $this->t('Eventos'),
                    $this->t('Estado'),
                    $this->t('Acciones'),
                ],
                '#empty' => $this->t('No hay webhooks configurados'),
                '#attributes' => ['class' => ['tenant-webhooks-table']],
            ];

            foreach ($webhooks as $webhook) {
                $eventCount = count($webhook['events']);
                $form['existing_webhooks'][$webhook['id']] = [
                    'url' => ['#markup' => '<code>' . $this->truncateUrl($webhook['url']) . '</code>'],
                    'events' => ['#markup' => $eventCount . ' ' . $this->t('eventos')],
                    'status' => [
                        '#markup' => $webhook['active']
                            ? '<span class="tenant-badge tenant-badge--success">' . $this->t('Activo') . '</span>'
                            : '<span class="tenant-badge tenant-badge--warning">' . $this->t('Inactivo') . '</span>',
                    ],
                    'actions' => [
                        '#type' => 'container',
                        'toggle' => [
                            '#type' => 'submit',
                            '#value' => $webhook['active'] ? $this->t('Desactivar') : $this->t('Activar'),
                            '#name' => 'toggle_' . $webhook['id'],
                            '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--sm']],
                        ],
                        'delete' => [
                            '#type' => 'submit',
                            '#value' => $this->t('Eliminar'),
                            '#name' => 'delete_' . $webhook['id'],
                            '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--danger', 'tenant-btn--sm']],
                        ],
                    ],
                ];
            }
        }

        // Formulario para crear nuevo webhook.
        $form['new_webhook'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Crear nuevo webhook'),
        ];

        $form['new_webhook']['webhook_url'] = [
            '#type' => 'url',
            '#title' => $this->t('URL del endpoint'),
            '#description' => $this->t('La URL donde se enviarán las notificaciones (debe ser HTTPS)'),
            '#placeholder' => 'https://tu-servidor.com/webhook',
            '#required' => TRUE,
        ];

        $form['new_webhook']['events'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Eventos a notificar'),
            '#options' => array_map(function ($label) {
                return $this->t($label);
            }, self::WEBHOOK_EVENTS),
            '#required' => TRUE,
        ];

        // Mostrar signing secret si hay webhooks.
        if (!empty($webhooks)) {
            $signingSecret = $this->getSigningSecret($tenant->id());
            $form['signing_secret'] = [
                '#type' => 'details',
                '#title' => $this->t('Signing Secret'),
                '#open' => FALSE,
            ];
            $form['signing_secret']['info'] = [
                '#markup' => '<p>' . $this->t('Usa este secret para verificar que los webhooks provienen de nuestra plataforma:') . '</p>
          <code class="tenant-secret">' . $signingSecret . '</code>
          <p class="tenant-form-hint">' . $this->t('Cada petición incluirá una cabecera X-Webhook-Signature con el HMAC-SHA256 del payload usando este secret.') . '</p>',
            ];
        }

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Crear webhook'),
            '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--primary']],
        ];

        $form['actions']['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Volver'),
            '#url' => \Drupal\Core\Url::fromRoute('ecosistema_jaraba_core.tenant_self_service.settings'),
            '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--secondary']],
        ];

        // Estilos.
        $form['#attached']['html_head'][] = [
            [
                '#type' => 'html_tag',
                '#tag' => 'style',
                '#value' => '
          .tenant-webhooks-form {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
          }
          .tenant-form-info p { color: #64748b; margin-bottom: 1.5rem; }
          .tenant-webhooks-table { width: 100%; background: white; border-radius: 0.5rem; margin-bottom: 2rem; }
          .tenant-webhooks-table code { background: #f1f5f9; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; }
          .tenant-badge--success { background: #dcfce7; color: #16a34a; padding: 0.25rem 0.5rem; border-radius: 1rem; font-size: 0.75rem; }
          .tenant-badge--warning { background: #fef3c7; color: #d97706; padding: 0.25rem 0.5rem; border-radius: 1rem; font-size: 0.75rem; }
          .tenant-secret { display: block; background: #1e293b; color: #10b981; padding: 0.75rem; border-radius: 0.5rem; font-family: monospace; margin: 0.5rem 0; }
          .tenant-form-hint { color: #94a3b8; font-size: 0.875rem; }
          .tenant-btn--danger { background: transparent; color: #ef4444; border: 1px solid #ef4444; }
          .tenant-btn--sm { padding: 0.25rem 0.75rem; font-size: 0.875rem; margin-left: 0.25rem; }
        ',
            ],
            'tenant_webhooks_styles',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $url = $form_state->getValue('webhook_url');

        if (!empty($url) && strpos($url, 'https://') !== 0) {
            $form_state->setErrorByName('webhook_url', $this->t('La URL debe usar HTTPS por seguridad.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        $triggeringElement = $form_state->getTriggeringElement();

        if (!$tenant) {
            $this->messenger()->addError($this->t('Error: No se encontró el tenant.'));
            return;
        }

        // Toggle webhook.
        if (strpos($triggeringElement['#name'], 'toggle_') === 0) {
            $webhookId = str_replace('toggle_', '', $triggeringElement['#name']);
            $this->toggleWebhook($tenant->id(), $webhookId);
            $this->messenger()->addStatus($this->t('Estado del webhook actualizado.'));
            return;
        }

        // Eliminar webhook.
        if (strpos($triggeringElement['#name'], 'delete_') === 0) {
            $webhookId = str_replace('delete_', '', $triggeringElement['#name']);
            $this->deleteWebhook($tenant->id(), $webhookId);
            $this->messenger()->addStatus($this->t('Webhook eliminado.'));
            return;
        }

        // Crear nuevo webhook.
        $url = $form_state->getValue('webhook_url');
        $events = array_filter($form_state->getValue('events'));

        $this->createWebhook($tenant->id(), $url, $events);
        $this->messenger()->addStatus($this->t('Webhook creado correctamente.'));
    }

    /**
     * Crea un nuevo webhook.
     */
    protected function createWebhook(string $tenantId, string $url, array $events): void
    {
        $state = \Drupal::state();
        $webhooks = $state->get('tenant_webhooks.' . $tenantId, []);

        $webhooks[] = [
            'id' => Crypt::randomBytesBase64(8),
            'url' => $url,
            'events' => array_values($events),
            'active' => TRUE,
            'created' => date('Y-m-d H:i'),
        ];

        $state->set('tenant_webhooks.' . $tenantId, $webhooks);

        // Generar signing secret si no existe.
        $secretKey = 'tenant_webhook_secret.' . $tenantId;
        if (!$state->get($secretKey)) {
            $state->set($secretKey, 'whsec_' . Crypt::randomBytesBase64(24));
        }
    }

    /**
     * Obtiene los webhooks del tenant.
     */
    protected function getWebhooks(string $tenantId): array
    {
        return \Drupal::state()->get('tenant_webhooks.' . $tenantId, []);
    }

    /**
     * Activa/desactiva un webhook.
     */
    protected function toggleWebhook(string $tenantId, string $webhookId): void
    {
        $state = \Drupal::state();
        $webhooks = $state->get('tenant_webhooks.' . $tenantId, []);

        foreach ($webhooks as &$webhook) {
            if ($webhook['id'] === $webhookId) {
                $webhook['active'] = !$webhook['active'];
                break;
            }
        }

        $state->set('tenant_webhooks.' . $tenantId, $webhooks);
    }

    /**
     * Elimina un webhook.
     */
    protected function deleteWebhook(string $tenantId, string $webhookId): void
    {
        $state = \Drupal::state();
        $webhooks = $state->get('tenant_webhooks.' . $tenantId, []);

        $webhooks = array_filter($webhooks, fn($w) => $w['id'] !== $webhookId);

        $state->set('tenant_webhooks.' . $tenantId, array_values($webhooks));
    }

    /**
     * Obtiene el signing secret del tenant.
     */
    protected function getSigningSecret(string $tenantId): string
    {
        $state = \Drupal::state();
        $secret = $state->get('tenant_webhook_secret.' . $tenantId);

        if (!$secret) {
            $secret = 'whsec_' . Crypt::randomBytesBase64(24); // generated random, not hardcoded
            $state->set('tenant_webhook_secret.' . $tenantId, $secret);
        }

        return $secret;
    }

    /**
     * Trunca URL para mostrar.
     */
    protected function truncateUrl(string $url): string
    {
        if (strlen($url) > 50) {
            return substr($url, 0, 47) . '...';
        }
        return $url;
    }

}
