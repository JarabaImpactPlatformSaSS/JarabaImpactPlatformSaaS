<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Trait\TenantFormHeroPremiumTrait;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para configurar webhooks del tenant.
 */
class TenantWebhooksForm extends FormBase {

  use TenantFormHeroPremiumTrait;

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

  public function __construct(
    protected TenantContextService $tenantContext,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.tenant_context'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tenant_webhooks_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $tenant = $this->tenantContext->getCurrentTenant();

    if (!$tenant) {
      $form['error'] = [
        '#markup' => '<div class="tenant-form__alert tenant-form__alert--warning">' . $this->t('No tienes un tenant asignado.') . '</div>',
      ];
      return $form;
    }

    $this->attachTenantFormHero(
      $form,
      'webhook',
      (string) $this->t('Webhooks'),
      (string) $this->t('Configura notificaciones automaticas a sistemas externos.'),
    );

    // Webhooks existentes.
    $webhooks = $this->getWebhooks($tenant->id());

    if (!empty($webhooks)) {
      $form['webhooks_section'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['tenant-form__section']],
      ];

      $form['webhooks_section']['section_title'] = [
        '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Webhooks activos') . '</h3>',
      ];

      $form['webhooks_section']['existing_webhooks'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('URL'),
          $this->t('Eventos'),
          $this->t('Estado'),
          $this->t('Acciones'),
        ],
        '#empty' => $this->t('No hay webhooks configurados'),
        '#attributes' => ['class' => ['tenant-form__table']],
      ];

      foreach ($webhooks as $webhook) {
        $eventCount = count($webhook['events']);
        $form['webhooks_section']['existing_webhooks'][$webhook['id']] = [
          'url' => ['#markup' => '<code class="tenant-form__code">' . $this->truncateUrl($webhook['url']) . '</code>'],
          'events' => ['#markup' => $eventCount . ' ' . $this->t('eventos')],
          'status' => [
            '#markup' => $webhook['active']
              ? '<span class="tenant-form__badge tenant-form__badge--success">' . $this->t('Activo') . '</span>'
              : '<span class="tenant-form__badge tenant-form__badge--warning">' . $this->t('Inactivo') . '</span>',
          ],
          'actions' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['tenant-form__table-actions']],
            'toggle' => [
              '#type' => 'submit',
              '#value' => $webhook['active'] ? $this->t('Desactivar') : $this->t('Activar'),
              '#name' => 'toggle_' . $webhook['id'],
              '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--sm']],
            ],
            'delete' => [
              '#type' => 'submit',
              '#value' => $this->t('Eliminar'),
              '#name' => 'delete_' . $webhook['id'],
              '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--danger', 'tenant-form__btn--sm']],
            ],
          ],
        ];
      }

      // Signing secret.
      $signingSecret = $this->getSigningSecret($tenant->id());
      $form['secret_section'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['tenant-form__section']],
      ];

      $form['secret_section']['section_title'] = [
        '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Signing Secret') . '</h3>',
      ];

      $form['secret_section']['info'] = [
        '#markup' => '<p class="tenant-form__hint">' . $this->t('Usa este secret para verificar que los webhooks provienen de nuestra plataforma:') . '</p><code class="tenant-form__key-display">' . $signingSecret . '</code><p class="tenant-form__hint">' . $this->t('Cada peticion incluira una cabecera X-Webhook-Signature con el HMAC-SHA256 del payload.') . '</p>',
      ];
    }

    // Nuevo webhook.
    $form['new_webhook'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['new_webhook']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Crear nuevo webhook') . '</h3>',
    ];

    $form['new_webhook']['webhook_url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL del endpoint'),
      '#description' => $this->t('La URL donde se enviaran las notificaciones (debe ser HTTPS)'),
      '#placeholder' => 'https://tu-servidor.com/webhook',
      '#required' => TRUE,
    ];

    $form['new_webhook']['events'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Eventos a notificar'),
      '#options' => array_map(fn($label) => $this->t($label), self::WEBHOOK_EVENTS),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['tenant-form__actions']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Crear webhook'),
      '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $url = $form_state->getValue('webhook_url');

    if (!empty($url) && !str_starts_with($url, 'https://')) {
      $form_state->setErrorByName('webhook_url', $this->t('La URL debe usar HTTPS por seguridad.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $tenant = $this->tenantContext->getCurrentTenant();
    $triggeringElement = $form_state->getTriggeringElement();

    if (!$tenant) {
      $this->messenger()->addError($this->t('Error: No se encontro el tenant.'));
      return;
    }

    $name = $triggeringElement['#name'] ?? '';

    if (str_starts_with($name, 'toggle_')) {
      $webhookId = str_replace('toggle_', '', $name);
      $this->toggleWebhook($tenant->id(), $webhookId);
      $this->messenger()->addStatus($this->t('Estado del webhook actualizado.'));
      return;
    }

    if (str_starts_with($name, 'delete_')) {
      $webhookId = str_replace('delete_', '', $name);
      $this->deleteWebhook($tenant->id(), $webhookId);
      $this->messenger()->addStatus($this->t('Webhook eliminado.'));
      return;
    }

    // Crear nuevo.
    $url = $form_state->getValue('webhook_url');
    $events = array_filter($form_state->getValue('events'));
    $this->createWebhook($tenant->id(), $url, $events);
    $this->messenger()->addStatus($this->t('Webhook creado correctamente.'));
  }

  /**
   * Crea un nuevo webhook.
   */
  protected function createWebhook(string $tenantId, string $url, array $events): void {
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

    $secretKey = 'tenant_webhook_secret.' . $tenantId;
    if (!$state->get($secretKey)) {
      $state->set($secretKey, 'whsec_' . Crypt::randomBytesBase64(24));
    }
  }

  /**
   * Obtiene los webhooks del tenant.
   */
  protected function getWebhooks(string $tenantId): array {
    return \Drupal::state()->get('tenant_webhooks.' . $tenantId, []);
  }

  /**
   * Activa/desactiva un webhook.
   */
  protected function toggleWebhook(string $tenantId, string $webhookId): void {
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
  protected function deleteWebhook(string $tenantId, string $webhookId): void {
    $state = \Drupal::state();
    $webhooks = $state->get('tenant_webhooks.' . $tenantId, []);
    $webhooks = array_filter($webhooks, fn($w) => $w['id'] !== $webhookId);
    $state->set('tenant_webhooks.' . $tenantId, array_values($webhooks));
  }

  /**
   * Obtiene el signing secret del tenant.
   */
  protected function getSigningSecret(string $tenantId): string {
    $state = \Drupal::state();
    $secret = $state->get('tenant_webhook_secret.' . $tenantId);

    if (!$secret) {
      $secret = 'whsec_' . Crypt::randomBytesBase64(24);
      $state->set('tenant_webhook_secret.' . $tenantId, $secret);
    }

    return $secret;
  }

  /**
   * Trunca URL para mostrar.
   */
  protected function truncateUrl(string $url): string {
    return strlen($url) > 50 ? substr($url, 0, 47) . '...' : $url;
  }

}
