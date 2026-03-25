<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Trait\TenantFormHeroPremiumTrait;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para gestionar API Keys del tenant.
 */
class TenantApiKeysForm extends FormBase {

  use TenantFormHeroPremiumTrait;

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
    return 'tenant_api_keys_form';
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
      'code',
      (string) $this->t('Claves API'),
      (string) $this->t('Gestiona tus claves de acceso a la API.'),
    );

    // Mostrar clave recien generada.
    $newKey = $form_state->get('new_api_key');
    if ($newKey) {
      $form['new_key_display'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['tenant-form__alert', 'tenant-form__alert--key']],
        '#weight' => -50,
        'message' => [
          '#markup' => '<strong>' . $this->t('Guarda esta clave ahora — no podras verla de nuevo') . '</strong><code class="tenant-form__key-display">' . $newKey . '</code>',
        ],
      ];
    }

    // Claves existentes.
    $apiKeys = $this->getApiKeys($tenant->id());

    if (!empty($apiKeys)) {
      $form['keys_section'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['tenant-form__section']],
      ];

      $form['keys_section']['section_title'] = [
        '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Claves activas') . '</h3>',
      ];

      $form['keys_section']['existing_keys'] = [
        '#type' => 'table',
        '#header' => [
          $this->t('Nombre'),
          $this->t('Prefijo'),
          $this->t('Creada'),
          $this->t('Ultimo uso'),
          $this->t('Acciones'),
        ],
        '#empty' => $this->t('No hay claves de API'),
        '#attributes' => ['class' => ['tenant-form__table']],
      ];

      foreach ($apiKeys as $key) {
        $form['keys_section']['existing_keys'][$key['id']] = [
          'name' => ['#markup' => $key['name']],
          'prefix' => ['#markup' => '<code class="tenant-form__code">' . $key['prefix'] . '...</code>'],
          'created' => ['#markup' => $key['created']],
          'last_used' => ['#markup' => $key['last_used'] ?: $this->t('Nunca')],
          'actions' => [
            '#type' => 'container',
            'revoke' => [
              '#type' => 'submit',
              '#value' => $this->t('Revocar'),
              '#name' => 'revoke_' . $key['id'],
              '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--danger', 'tenant-form__btn--sm']],
            ],
          ],
        ];
      }
    }

    // Nueva clave.
    $form['new_key'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['new_key']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Crear nueva API Key') . '</h3>',
    ];

    $form['new_key']['key_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre de la clave'),
      '#description' => $this->t('Un nombre descriptivo para identificar esta clave'),
      '#placeholder' => 'Mi Integracion',
      '#required' => TRUE,
    ];

    $form['new_key']['permissions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Permisos'),
      '#options' => [
        'read_products' => $this->t('Leer productos'),
        'write_products' => $this->t('Crear/editar productos'),
        'read_orders' => $this->t('Leer pedidos'),
        'write_orders' => $this->t('Gestionar pedidos'),
        'read_customers' => $this->t('Leer clientes'),
      ],
      '#default_value' => ['read_products', 'read_orders'],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['tenant-form__actions']],
    ];

    $form['actions']['generate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generar API Key'),
      '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--primary']],
    ];

    return $form;
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

    // Revocar.
    if (str_starts_with($triggeringElement['#name'] ?? '', 'revoke_')) {
      $keyId = str_replace('revoke_', '', $triggeringElement['#name']);
      $this->revokeApiKey($keyId);
      $this->messenger()->addStatus($this->t('Clave de API revocada correctamente.'));
      return;
    }

    // Generar nueva.
    $keyName = $form_state->getValue('key_name');
    $permissions = array_filter($form_state->getValue('permissions'));

    if (empty($keyName)) {
      $this->messenger()->addError($this->t('El nombre de la clave es requerido.'));
      return;
    }

    $apiKey = $this->generateApiKey($tenant->id(), $keyName, $permissions);

    $form_state->set('new_api_key', $apiKey);
    $form_state->setRebuild(TRUE);

    $this->messenger()->addStatus($this->t('API Key generada correctamente.'));
  }

  /**
   * Genera una nueva API Key.
   */
  protected function generateApiKey(string $tenantId, string $name, array $permissions): string {
    $key = 'jip_' . Crypt::randomBytesBase64(32);
    $keyHash = hash('sha256', $key);
    $prefix = substr($key, 0, 8);

    $state = \Drupal::state();
    $existingKeys = $state->get('tenant_api_keys.' . $tenantId, []);

    $existingKeys[] = [
      'id' => Crypt::randomBytesBase64(8),
      'name' => $name,
      'hash' => $keyHash,
      'prefix' => $prefix,
      'permissions' => $permissions,
      'created' => date('Y-m-d H:i'),
      'last_used' => NULL,
    ];

    $state->set('tenant_api_keys.' . $tenantId, $existingKeys);

    return $key;
  }

  /**
   * Obtiene las API Keys del tenant.
   */
  protected function getApiKeys(string $tenantId): array {
    return \Drupal::state()->get('tenant_api_keys.' . $tenantId, []);
  }

  /**
   * Revoca una API Key.
   */
  protected function revokeApiKey(string $keyId): void {
    $tenant = $this->tenantContext->getCurrentTenant();
    if (!$tenant) {
      return;
    }

    $state = \Drupal::state();
    $keys = $state->get('tenant_api_keys.' . $tenant->id(), []);
    $keys = array_filter($keys, fn($key) => $key['id'] !== $keyId);
    $state->set('tenant_api_keys.' . $tenant->id(), array_values($keys));
  }

}
