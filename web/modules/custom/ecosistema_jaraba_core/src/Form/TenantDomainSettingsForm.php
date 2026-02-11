<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para configurar dominio personalizado del tenant.
 *
 * Permite a los tenants configurar su propio dominio para acceder
 * a su tienda. Requiere validación DNS y configuración del módulo Domain.
 */
class TenantDomainSettingsForm extends FormBase
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
        return 'tenant_domain_settings_form';
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

        // Obtener dominio actual si existe.
        $currentDomain = '';
        if ($tenant->hasField('custom_domain') && !$tenant->get('custom_domain')->isEmpty()) {
            $currentDomain = $tenant->get('custom_domain')->value;
        }

        $form['#prefix'] = '<div class="tenant-domain-form">';
        $form['#suffix'] = '</div>';

        $form['info'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['tenant-form-info']],
        ];

        $form['info']['description'] = [
            '#markup' => '<p>' . $this->t('Configura tu propio dominio para acceder a tu tienda. Una vez configurado, tus clientes podrán acceder a través de tu dominio personalizado.') . '</p>',
        ];

        // Dominio actual del ecosistema.
        $form['current_url'] = [
            '#type' => 'item',
            '#title' => $this->t('URL actual'),
            '#markup' => '<code>' . $tenant->label() . '.plataformadeecosistemas.es</code>',
            '#prefix' => '<div class="tenant-current-domain">',
            '#suffix' => '</div>',
        ];

        $form['custom_domain'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Dominio personalizado'),
            '#description' => $this->t('Introduce tu dominio sin http:// ni www. Ejemplo: mitienda.com'),
            '#default_value' => $currentDomain,
            '#placeholder' => 'mitienda.com',
            '#attributes' => [
                'class' => ['tenant-domain-input'],
            ],
        ];

        // Instrucciones DNS.
        $form['dns_instructions'] = [
            '#type' => 'details',
            '#title' => $this->t('Instrucciones de configuración DNS'),
            '#open' => FALSE,
        ];

        $form['dns_instructions']['steps'] = [
            '#theme' => 'item_list',
            '#items' => [
                $this->t('Accede al panel de control de tu proveedor de dominios'),
                $this->t('Crea un registro CNAME con los siguientes valores:'),
                $this->t('<strong>Nombre:</strong> @ o www'),
                $this->t('<strong>Valor:</strong> tenant.plataformadeecosistemas.es'),
                $this->t('Espera 24-48 horas para la propagación DNS'),
                $this->t('Una vez propagado, vuelve aquí y verifica el dominio'),
            ],
        ];

        // Estado de verificación.
        $form['verification_status'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['tenant-verification-status']],
        ];

        if ($currentDomain) {
            $isVerified = $tenant->hasField('domain_verified') && $tenant->get('domain_verified')->value;

            if ($isVerified) {
                $form['verification_status']['badge'] = [
                    '#markup' => '<span class="tenant-badge tenant-badge--success">✓ ' . $this->t('Dominio verificado') . '</span>',
                ];
            } else {
                $form['verification_status']['badge'] = [
                    '#markup' => '<span class="tenant-badge tenant-badge--warning">⏳ ' . $this->t('Pendiente de verificación') . '</span>',
                ];

                $form['verify'] = [
                    '#type' => 'submit',
                    '#value' => $this->t('Verificar DNS'),
                    '#name' => 'verify',
                    '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--secondary']],
                ];
            }
        }

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Guardar dominio'),
            '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--primary']],
        ];

        $form['actions']['cancel'] = [
            '#type' => 'link',
            '#title' => $this->t('Cancelar'),
            '#url' => \Drupal\Core\Url::fromRoute('ecosistema_jaraba_core.tenant_self_service.settings'),
            '#attributes' => ['class' => ['tenant-btn', 'tenant-btn--secondary']],
        ];

        // Estilos del formulario.
        $form['#attached']['html_head'][] = [
            [
                '#type' => 'html_tag',
                '#tag' => 'style',
                '#value' => '
          .tenant-domain-form {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem;
          }
          .tenant-form-info p {
            color: #64748b;
            margin-bottom: 1.5rem;
          }
          .tenant-current-domain {
            background: #f1f5f9;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
          }
          .tenant-current-domain code {
            font-size: 1rem;
            color: #3b82f6;
          }
          .tenant-domain-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
          }
          .tenant-verification-status {
            margin: 1.5rem 0;
          }
          .tenant-badge--success {
            background: #dcfce7;
            color: #16a34a;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 500;
          }
          .tenant-badge--warning {
            background: #fef3c7;
            color: #d97706;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 500;
          }
        ',
            ],
            'tenant_domain_form_styles',
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $domain = $form_state->getValue('custom_domain');

        if (!empty($domain)) {
            // Limpiar el dominio.
            $domain = preg_replace('#^https?://#', '', $domain);
            $domain = preg_replace('#^www\.#', '', $domain);
            $domain = rtrim($domain, '/');

            // Validar formato.
            if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]*\.[a-zA-Z]{2,}$/', $domain)) {
                $form_state->setErrorByName('custom_domain', $this->t('El formato del dominio no es válido.'));
            }

            // Guardar dominio limpio.
            $form_state->setValue('custom_domain', $domain);
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

        // Si se presionó "Verificar DNS".
        if ($triggeringElement['#name'] === 'verify') {
            $domain = $form_state->getValue('custom_domain');
            $verified = $this->verifyDns($domain);

            if ($verified) {
                if ($tenant->hasField('domain_verified')) {
                    $tenant->set('domain_verified', TRUE);
                    $tenant->save();
                }
                $this->messenger()->addStatus($this->t('¡Dominio verificado correctamente!'));
            } else {
                $this->messenger()->addWarning($this->t('No se pudo verificar el DNS. Asegúrate de que el registro CNAME esté configurado correctamente.'));
            }
            return;
        }

        // Guardar dominio.
        $domain = $form_state->getValue('custom_domain');

        if ($tenant->hasField('custom_domain')) {
            $tenant->set('custom_domain', $domain);
            $tenant->set('domain_verified', FALSE);
            $tenant->save();

            $this->messenger()->addStatus($this->t('Dominio guardado. Configura el DNS y luego verifica el dominio.'));
        } else {
            $this->messenger()->addWarning($this->t('El campo de dominio personalizado no está configurado en el tipo de entidad Tenant.'));
        }

        $form_state->setRedirect('ecosistema_jaraba_core.tenant_self_service.settings');
    }

    /**
     * Verifica la configuración DNS del dominio.
     */
    protected function verifyDns(string $domain): bool
    {
        // Verificar que el CNAME apunte a nuestro servidor.
        $expectedTarget = 'tenant.plataformadeecosistemas.es';

        try {
            $records = dns_get_record($domain, DNS_CNAME);

            foreach ($records as $record) {
                if (isset($record['target']) && strpos($record['target'], 'plataformadeecosistemas') !== FALSE) {
                    return TRUE;
                }
            }

            // También verificar registro A.
            $records = dns_get_record($domain, DNS_A);
            // Aquí verificaríamos la IP del servidor.

        } catch (\Exception $e) {
            $this->getLogger('ecosistema_jaraba_core')->warning('DNS verification failed: @error', ['@error' => $e->getMessage()]);
        }

        return FALSE;
    }

}
