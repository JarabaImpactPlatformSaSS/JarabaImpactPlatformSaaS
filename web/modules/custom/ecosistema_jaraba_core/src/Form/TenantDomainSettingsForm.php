<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Trait\TenantFormHeroPremiumTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario para configurar dominio personalizado del tenant.
 */
class TenantDomainSettingsForm extends FormBase {

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
    return 'tenant_domain_settings_form';
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

    $currentDomain = '';
    if ($tenant->hasField('custom_domain') && !$tenant->get('custom_domain')->isEmpty()) {
      $currentDomain = $tenant->get('custom_domain')->value;
    }

    $this->attachTenantFormHero(
      $form,
      'globe',
      (string) $this->t('Dominio'),
      (string) $this->t('Configura tu dominio personalizado y SSL.'),
    );

    // Dominio actual.
    $form['current_section'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['current_section']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('URL actual') . '</h3>',
    ];

    $form['current_section']['current_url'] = [
      '#markup' => '<div class="tenant-form__code-block"><code>' . $tenant->label() . '.plataformadeecosistemas.es</code></div>',
    ];

    // Dominio personalizado.
    $form['domain_section'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['domain_section']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Dominio personalizado') . '</h3>',
    ];

    $form['domain_section']['custom_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tu dominio'),
      '#description' => $this->t('Introduce tu dominio sin http:// ni www. Ejemplo: mitienda.com'),
      '#default_value' => $currentDomain,
      '#placeholder' => 'mitienda.com',
    ];

    // Instrucciones DNS.
    $form['dns_section'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['dns_section']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Configuracion DNS') . '</h3>',
    ];

    $form['dns_section']['steps'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Accede al panel de control de tu proveedor de dominios'),
        $this->t('Crea un registro CNAME:'),
        $this->t('Nombre: @ o www'),
        $this->t('Valor: tenant.plataformadeecosistemas.es'),
        $this->t('Espera 24-48 horas para la propagacion DNS'),
        $this->t('Vuelve aqui y verifica el dominio'),
      ],
      '#attributes' => ['class' => ['tenant-form__steps']],
    ];

    // Estado de verificacion.
    if ($currentDomain) {
      $isVerified = $tenant->hasField('domain_verified') && $tenant->get('domain_verified')->value;

      $form['verification_section'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['tenant-form__section']],
      ];

      $form['verification_section']['section_title'] = [
        '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Estado de verificacion') . '</h3>',
      ];

      if ($isVerified) {
        $form['verification_section']['badge'] = [
          '#markup' => '<span class="tenant-form__badge tenant-form__badge--success">' . $this->t('Dominio verificado') . '</span>',
        ];
      }
      else {
        $form['verification_section']['badge'] = [
          '#markup' => '<span class="tenant-form__badge tenant-form__badge--warning">' . $this->t('Pendiente de verificacion') . '</span>',
        ];

        $form['verification_section']['verify'] = [
          '#type' => 'submit',
          '#value' => $this->t('Verificar DNS'),
          '#name' => 'verify',
          '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--secondary']],
        ];
      }
    }

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['tenant-form__actions']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar dominio'),
      '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $domain = $form_state->getValue('custom_domain');

    if (!empty($domain)) {
      $domain = preg_replace('#^https?://#', '', $domain);
      $domain = preg_replace('#^www\.#', '', $domain);
      $domain = rtrim($domain, '/');

      if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]*\.[a-zA-Z]{2,}$/', $domain)) {
        $form_state->setErrorByName('custom_domain', $this->t('El formato del dominio no es valido.'));
      }

      $form_state->setValue('custom_domain', $domain);
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

    // Verificar DNS.
    if (($triggeringElement['#name'] ?? '') === 'verify') {
      $domain = $form_state->getValue('custom_domain');
      $verified = $this->verifyDns($domain);

      if ($verified) {
        if ($tenant->hasField('domain_verified')) {
          $tenant->set('domain_verified', TRUE);
          $tenant->save();
        }
        $this->messenger()->addStatus($this->t('Dominio verificado correctamente.'));
      }
      else {
        $this->messenger()->addWarning($this->t('No se pudo verificar el DNS. Asegurate de que el registro CNAME este configurado correctamente.'));
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
    }
    else {
      $this->messenger()->addWarning($this->t('El campo de dominio personalizado no esta configurado en el tipo de entidad Tenant.'));
    }

    $form_state->setRedirect('ecosistema_jaraba_core.tenant_self_service.settings');
  }

  /**
   * Verifica la configuracion DNS del dominio.
   */
  protected function verifyDns(string $domain): bool {
    try {
      $records = dns_get_record($domain, DNS_CNAME);

      foreach ($records as $record) {
        if (isset($record['target']) && str_contains($record['target'], 'plataformadeecosistemas')) {
          return TRUE;
        }
      }
    }
    catch (\Throwable $e) {
      $this->getLogger('ecosistema_jaraba_core')->warning('DNS verification failed: @error', ['@error' => $e->getMessage()]);
    }

    return FALSE;
  }

}
