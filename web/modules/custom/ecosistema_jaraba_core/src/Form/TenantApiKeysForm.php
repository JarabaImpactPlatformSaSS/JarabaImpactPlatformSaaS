<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para gestionar API Keys del tenant.
 *
 * Permite a los tenants generar, regenerar y revocar claves de API
 * para integraciones con sistemas externos.
 */
class TenantApiKeysForm extends FormBase
{

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
        return 'tenant_api_keys_form';
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

        $form['#prefix'] = '<div class="tenant-api-keys-form">';
        $form['#suffix'] = '</div>';

        // Información.
        $form['info'] = [
            '#markup' => '<div class="tenant-form-info">
        <p>' . $this->t('Las API Keys te permiten conectar sistemas externos con tu tienda. Mantén tus claves seguras y nunca las compartas públicamente.') . '</p>
      </div>',
        ];

        // Obtener API keys existentes.
        $apiKeys = $this->getApiKeys($tenant->id());

        if (!empty($apiKeys)) {
            $form['existing_keys'] = [
                '#type' => 'table',
                '#header' => [
                    $this->t('Nombre'),
                    $this->t('Clave (prefijo)'),
                    $this->t('Creada'),
                    $this->t('Último uso'),
                    $this->t('Acciones'),
                ],
                '#empty' => $this->t('No hay claves de API'),
                '#attributes' => ['class' => ['tenant-api-keys-table']],
            ];

            foreach ($apiKeys as $key) {
                $form['existing_keys'][$key['id']] = [
                    'name' => ['#markup' => $key['name']],
                    'prefix' => ['#markup' => '<code>' . $key['prefix'] . '...</code>'],
                    'created' => ['#markup' => $key['created']],
                    'last_used' => ['#markup' => $key['last_used'] ?: $this->t('Nunca')],
                    'actions' => [
                        '#type' => 'container',
                        'revoke' => [
                            '#type' => 'submit',
                            '#value' => $this->t('Revocar'),
                            '#name' => 'revoke_' . $key['id'],
                            '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--danger', 'tenant-btn--sm']],
                        ],
                    ],
                ];
            }
        }

        // Formulario para crear nueva clave.
        $form['new_key'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Crear nueva API Key'),
        ];

        $form['new_key']['key_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Nombre de la clave'),
            '#description' => $this->t('Un nombre descriptivo para identificar esta clave'),
            '#placeholder' => 'Mi Integración',
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
        ];

        $form['actions']['generate'] = [
            '#type' => 'submit',
            '#value' => $this->t('Generar API Key'),
            '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--primary']],
        ];

        $form['actions']['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Volver'),
            '#url' => \Drupal\Core\Url::fromRoute('ecosistema_jaraba_core.tenant_self_service.settings'),
            '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--secondary']],
        ];

        // Mostrar clave recién generada.
        $newKey = $form_state->get('new_api_key');
        if ($newKey) {
            $form['new_key_display'] = [
                '#type' => 'container',
                '#attributes' => ['class' => ['tenant-new-key-display']],
                '#weight' => -100,
                'message' => [
                    '#markup' => '<div class="tenant-alert tenant-alert--success">
            <strong>⚠️ ' . $this->t('Guarda esta clave ahora - no podrás verla de nuevo') . '</strong>
            <code class="tenant-api-key-full">' . $newKey . '</code>
          </div>',
                ],
            ];
        }

        // Estilos.
        $form['#attached']['html_head'][] = [
            [
                '#type' => 'html_tag',
                '#tag' => 'style',
                '#value' => '
          .tenant-api-keys-form {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
          }
          .tenant-form-info p {
            color: #64748b;
            margin-bottom: 1.5rem;
          }
          .tenant-api-keys-table {
            width: 100%;
            background: white;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
          }
          .tenant-api-keys-table code {
            background: #f1f5f9;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
          }
          .tenant-new-key-display {
            margin-bottom: 2rem;
          }
          .tenant-alert--success {
            background: #dcfce7;
            border: 1px solid #16a34a;
            border-radius: 0.5rem;
            padding: 1rem;
          }
          .tenant-api-key-full {
            display: block;
            margin-top: 0.5rem;
            background: #1e293b;
            color: #10b981;
            padding: 1rem;
            border-radius: 0.5rem;
            font-family: monospace;
            word-break: break-all;
          }
          .tenant-btn--danger {
            background: transparent;
            color: #ef4444;
            border: 1px solid #ef4444;
          }
          .tenant-btn--sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
          }
        ',
            ],
            'tenant_api_keys_styles',
        ];

        return $form;
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

        // Revocar clave existente.
        if (strpos($triggeringElement['#name'], 'revoke_') === 0) {
            $keyId = str_replace('revoke_', '', $triggeringElement['#name']);
            $this->revokeApiKey($keyId);
            $this->messenger()->addStatus($this->t('Clave de API revocada correctamente.'));
            return;
        }

        // Generar nueva clave.
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
    protected function generateApiKey(string $tenantId, string $name, array $permissions): string
    {
        // Generar clave segura.
        $key = 'jip_' . Crypt::randomBytesBase64(32);
        $keyHash = hash('sha256', $key);
        $prefix = substr($key, 0, 8);

        // Guardar en state (en producción, usar tabla dedicada).
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
    protected function getApiKeys(string $tenantId): array
    {
        $state = \Drupal::state();
        return $state->get('tenant_api_keys.' . $tenantId, []);
    }

    /**
     * Revoca una API Key.
     */
    protected function revokeApiKey(string $keyId): void
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        if (!$tenant) {
            return;
        }

        $state = \Drupal::state();
        $keys = $state->get('tenant_api_keys.' . $tenant->id(), []);

        $keys = array_filter($keys, function ($key) use ($keyId) {
            return $key['id'] !== $keyId;
        });

        $state->set('tenant_api_keys.' . $tenant->id(), array_values($keys));
    }

}
