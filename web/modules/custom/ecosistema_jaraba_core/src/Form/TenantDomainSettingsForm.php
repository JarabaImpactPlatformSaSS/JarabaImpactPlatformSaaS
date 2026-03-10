<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
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

    // Resolver dominio base desde Settings (misma fuente de verdad que
    // Tenant::provisionDomainIfNeeded y TenantOnboardingService).
    $baseDomain = Settings::get('jaraba_base_domain', 'plataformadeecosistemas.com');
    $tenantSlug = mb_strtolower(preg_replace('/[^a-zA-Z0-9-]/', '-', (string) $tenant->label()));
    $cnameTarget = $tenantSlug . '.' . $baseDomain;

    $this->attachTenantFormHero(
      $form,
      'globe',
      (string) $this->t('Dominio personalizado'),
      (string) $this->t('Conecta tu propio dominio a tu metasitio y SSL se configurara automaticamente.'),
    );

    // ── Section 1: Current URL ──────────────────────────────────────────────
    $form['current_section'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['current_section']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Tu URL actual') . '</h3>',
    ];

    $form['current_section']['current_url'] = [
      '#markup' => '<div class="tenant-form__code-block">'
        . '<code>' . $cnameTarget . '</code>'
        . '</div>'
        . '<p class="domain-guide__hint">' . $this->t('Esta es la URL por defecto de tu metasitio. Puedes conectar tu propio dominio para una experiencia profesional.') . '</p>',
    ];

    // ── Section 2: Custom domain input ──────────────────────────────────────
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

    // ── Section 3: DNS Guide — Visual Steps ─────────────────────────────────
    $form['dns_guide'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section', 'domain-guide']],
    ];

    $form['dns_guide']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Guia de configuracion DNS') . '</h3>'
        . '<p class="domain-guide__intro">' . $this->t('Sigue estos pasos para conectar tu dominio. El proceso es sencillo y lo tendras listo en minutos.') . '</p>',
    ];

    // Step 1: Enter domain.
    $form['dns_guide']['step1'] = [
      '#markup' => '<div class="domain-guide__step">'
        . '<div class="domain-guide__step-number">1</div>'
        . '<div class="domain-guide__step-content">'
        .   '<h4 class="domain-guide__step-title">' . $this->t('Introduce tu dominio arriba') . '</h4>'
        .   '<p class="domain-guide__step-text">' . $this->t('Escribe el dominio que quieres conectar (ej: miempresa.com) y pulsa Guardar.') . '</p>'
        . '</div>'
        . '</div>',
    ];

    // Step 2: CNAME record — the key piece.
    // Markup::create() needed: Xss::filterAdmin() strips <button> and <svg>.
    $form['dns_guide']['step2'] = [
      '#markup' => Markup::create(
        '<div class="domain-guide__step">'
        . '<div class="domain-guide__step-number">2</div>'
        . '<div class="domain-guide__step-content">'
        .   '<h4 class="domain-guide__step-title">' . $this->t('Crea un registro CNAME en tu proveedor DNS') . '</h4>'
        .   '<p class="domain-guide__step-text">' . $this->t('Accede al panel de tu proveedor de dominios y crea el siguiente registro:') . '</p>'
        .   '<div class="domain-guide__record">'
        .     '<div class="domain-guide__record-row">'
        .       '<span class="domain-guide__record-label">' . $this->t('Tipo') . '</span>'
        .       '<span class="domain-guide__record-value">CNAME</span>'
        .     '</div>'
        .     '<div class="domain-guide__record-row">'
        .       '<span class="domain-guide__record-label">' . $this->t('Nombre / Host') . '</span>'
        .       '<span class="domain-guide__record-value">www</span>'
        .     '</div>'
        .     '<div class="domain-guide__record-row">'
        .       '<span class="domain-guide__record-label">' . $this->t('Valor / Apunta a') . '</span>'
        .       '<span class="domain-guide__record-value domain-guide__record-value--cname" data-domain-cname>'
        .         $cnameTarget
        .       '</span>'
        .       '<button type="button" class="domain-guide__copy-btn" data-domain-copy="' . $cnameTarget . '" aria-label="' . $this->t('Copiar valor CNAME') . '">'
        .         '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
        .       '</button>'
        .     '</div>'
        .     '<div class="domain-guide__record-row">'
        .       '<span class="domain-guide__record-label">TTL</span>'
        .       '<span class="domain-guide__record-value">3600 <span class="domain-guide__record-hint">(' . $this->t('o automatico') . ')</span></span>'
        .     '</div>'
        .   '</div>'
        .   '<p class="domain-guide__tip">'
        .     $this->t('Si quieres usar el dominio raiz (miempresa.com sin www), algunos proveedores soportan CNAME flattening o registros ALIAS. Consulta la guia de tu proveedor mas abajo.')
        .   '</p>'
        . '</div>'
        . '</div>'
      ),
    ];

    // Step 3: Wait propagation.
    $form['dns_guide']['step3'] = [
      '#markup' => '<div class="domain-guide__step">'
        . '<div class="domain-guide__step-number">3</div>'
        . '<div class="domain-guide__step-content">'
        .   '<h4 class="domain-guide__step-title">' . $this->t('Espera la propagacion DNS') . '</h4>'
        .   '<p class="domain-guide__step-text">' . $this->t('Los cambios DNS pueden tardar entre 5 minutos y 48 horas en propagarse, aunque normalmente es menos de 1 hora. Puedes verificar el estado en cualquier momento.') . '</p>'
        . '</div>'
        . '</div>',
    ];

    // Step 4: Verify.
    $form['dns_guide']['step4'] = [
      '#markup' => '<div class="domain-guide__step">'
        . '<div class="domain-guide__step-number">4</div>'
        . '<div class="domain-guide__step-content">'
        .   '<h4 class="domain-guide__step-title">' . $this->t('Verifica y activa') . '</h4>'
        .   '<p class="domain-guide__step-text">' . $this->t('Pulsa el boton "Verificar DNS" de abajo. Si la configuracion es correcta, tu dominio se activara y el certificado SSL se generara automaticamente.') . '</p>'
        . '</div>'
        . '</div>',
    ];

    // ── Section 4: Provider-specific guides ─────────────────────────────────
    $form['providers'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section', 'domain-guide']],
    ];

    $form['providers']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Guias por proveedor') . '</h3>'
        . '<p class="domain-guide__intro">' . $this->t('Selecciona tu proveedor de dominios para ver instrucciones paso a paso.') . '</p>',
    ];

    $providers = $this->getProviderGuides($cnameTarget);
    $providerMarkup = '<div class="domain-guide__providers" data-domain-providers>';
    foreach ($providers as $id => $provider) {
      $providerMarkup .= '<div class="domain-guide__provider" data-domain-provider="' . $id . '">'
        . '<button type="button" class="domain-guide__provider-header" data-domain-provider-toggle="' . $id . '" aria-expanded="false" aria-controls="provider-' . $id . '">'
        .   '<span class="domain-guide__provider-name">' . $provider['name'] . '</span>'
        .   '<svg class="domain-guide__provider-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>'
        . '</button>'
        . '<div class="domain-guide__provider-body" id="provider-' . $id . '" hidden>'
        .   '<ol class="domain-guide__provider-steps">';
      foreach ($provider['steps'] as $step) {
        $providerMarkup .= '<li>' . $step . '</li>';
      }
      $providerMarkup .= '</ol>';
      if (!empty($provider['note'])) {
        $providerMarkup .= '<p class="domain-guide__provider-note">' . $provider['note'] . '</p>';
      }
      $providerMarkup .= '</div></div>';
    }
    $providerMarkup .= '</div>';

    // Markup::create() needed: contains <button> and <svg> stripped by filterAdmin().
    $form['providers']['guides'] = [
      '#markup' => Markup::create($providerMarkup),
    ];

    // ── Section 5: Verification status ──────────────────────────────────────
    $form['verification_section'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['verification_section']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Estado de verificacion') . '</h3>',
    ];

    if ($currentDomain) {
      $isVerified = $tenant->hasField('domain_verified') && $tenant->get('domain_verified')->value;

      // Markup::create() needed: contains <svg> stripped by filterAdmin().
      if ($isVerified) {
        $form['verification_section']['status'] = [
          '#markup' => Markup::create(
            '<div class="domain-guide__status domain-guide__status--verified">'
            . '<div class="domain-guide__status-icon">'
            .   '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
            . '</div>'
            . '<div class="domain-guide__status-text">'
            .   '<strong>' . $this->t('Dominio verificado') . '</strong>'
            .   '<p>' . $this->t('Tu dominio @domain esta activo y funcionando. El certificado SSL esta configurado.', ['@domain' => $currentDomain]) . '</p>'
            . '</div>'
            . '</div>'
          ),
        ];
      }
      else {
        $form['verification_section']['status'] = [
          '#markup' => Markup::create(
            '<div class="domain-guide__status domain-guide__status--pending">'
            . '<div class="domain-guide__status-icon">'
            .   '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'
            . '</div>'
            . '<div class="domain-guide__status-text">'
            .   '<strong>' . $this->t('Pendiente de verificacion') . '</strong>'
            .   '<p>' . $this->t('Configura el registro CNAME de @domain apuntando a @target y luego verifica.', ['@domain' => $currentDomain, '@target' => $cnameTarget]) . '</p>'
            . '</div>'
            . '</div>'
          ),
        ];

      }
    }
    else {
      $form['verification_section']['status'] = [
        '#markup' => Markup::create(
          '<div class="domain-guide__status domain-guide__status--empty">'
          . '<div class="domain-guide__status-icon">'
          .   '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'
          . '</div>'
          . '<div class="domain-guide__status-text">'
          .   '<strong>' . $this->t('Sin dominio configurado') . '</strong>'
          .   '<p>' . $this->t('Introduce tu dominio arriba y sigue la guia de configuracion DNS.') . '</p>'
          . '</div>'
          . '</div>'
        ),
      ];
    }

    // ── Section 6: Troubleshooting ──────────────────────────────────────────
    $form['troubleshooting'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section', 'domain-guide']],
    ];

    $form['troubleshooting']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Preguntas frecuentes') . '</h3>',
    ];

    $faqs = $this->getTroubleshootingFaqs($baseDomain);
    $faqMarkup = '<div class="domain-guide__faqs" data-domain-faqs>';
    foreach ($faqs as $index => $faq) {
      $faqMarkup .= '<div class="domain-guide__faq">'
        . '<button type="button" class="domain-guide__faq-question" data-domain-faq-toggle="' . $index . '" aria-expanded="false" aria-controls="faq-' . $index . '">'
        .   '<span>' . $faq['q'] . '</span>'
        .   '<svg class="domain-guide__faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>'
        . '</button>'
        . '<div class="domain-guide__faq-answer" id="faq-' . $index . '" hidden>'
        .   '<p>' . $faq['a'] . '</p>'
        . '</div>'
        . '</div>';
    }
    $faqMarkup .= '</div>';

    // Markup::create() needed: contains <button> and <svg> stripped by filterAdmin().
    $form['troubleshooting']['faqs'] = [
      '#markup' => Markup::create($faqMarkup),
    ];

    // ── Sticky Action Bar — always visible ──────────────────────────────────
    // Uses customizer-actions pattern (position: sticky, backdrop-filter).
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['customizer-actions']],
    ];

    // Verificar DNS: only when there is a domain pending verification.
    if ($currentDomain) {
      $isVerified = $tenant->hasField('domain_verified') && $tenant->get('domain_verified')->value;
      if (!$isVerified) {
        $form['actions']['verify'] = [
          '#type' => 'submit',
          '#value' => $this->t('Verificar DNS'),
          '#name' => 'verify',
          '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--secondary']],
        ];
      }
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar dominio'),
      '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--primary']],
    ];

    return $form;
  }

  /**
   * Returns provider-specific DNS configuration guides.
   *
   * @return array<string, array{name: string, steps: string[], note?: string}>
   */
  protected function getProviderGuides(string $cnameTarget): array {
    return [
      'cloudflare' => [
        'name' => 'Cloudflare',
        'steps' => [
          (string) $this->t('Inicia sesion en <strong>dash.cloudflare.com</strong>'),
          (string) $this->t('Selecciona tu dominio y ve a la seccion <strong>DNS</strong>'),
          (string) $this->t('Pulsa <strong>Agregar registro</strong>'),
          (string) $this->t('Tipo: <strong>CNAME</strong>, Nombre: <strong>www</strong>, Destino: <strong>@cname</strong>', ['@cname' => $cnameTarget]),
          (string) $this->t('Desactiva el proxy (nube naranja) para que el SSL funcione correctamente'),
          (string) $this->t('Guarda el registro'),
        ],
        'note' => (string) $this->t('Cloudflare soporta CNAME flattening: puedes usar @ como nombre para el dominio raiz.'),
      ],
      'godaddy' => [
        'name' => 'GoDaddy',
        'steps' => [
          (string) $this->t('Accede a <strong>Mi cuenta > Dominios</strong> en godaddy.com'),
          (string) $this->t('Selecciona tu dominio y pulsa <strong>DNS</strong>'),
          (string) $this->t('Pulsa <strong>Agregar</strong> en la seccion de Registros'),
          (string) $this->t('Tipo: <strong>CNAME</strong>, Nombre: <strong>www</strong>, Valor: <strong>@cname</strong>, TTL: <strong>1 hora</strong>', ['@cname' => $cnameTarget]),
          (string) $this->t('Guarda los cambios'),
        ],
        'note' => (string) $this->t('Para dominio raiz (@), GoDaddy requiere redireccion via Forwarding > Add Forwarding apuntando a www.tudominio.com.'),
      ],
      'ionos' => [
        'name' => 'IONOS (1&1)',
        'steps' => [
          (string) $this->t('Accede a <strong>my.ionos.es > Dominios y SSL</strong>'),
          (string) $this->t('Selecciona tu dominio y pulsa <strong>DNS</strong>'),
          (string) $this->t('Pulsa <strong>Agregar registro</strong>'),
          (string) $this->t('Tipo: <strong>CNAME</strong>, Nombre de host: <strong>www</strong>, Apunta a: <strong>@cname</strong>', ['@cname' => $cnameTarget]),
          (string) $this->t('Guarda el registro'),
        ],
      ],
      'namecheap' => [
        'name' => 'Namecheap',
        'steps' => [
          (string) $this->t('Accede a <strong>Dashboard > Domain List</strong> en namecheap.com'),
          (string) $this->t('Pulsa <strong>Manage</strong> junto a tu dominio'),
          (string) $this->t('Ve a la pestana <strong>Advanced DNS</strong>'),
          (string) $this->t('Pulsa <strong>Add New Record</strong>'),
          (string) $this->t('Tipo: <strong>CNAME Record</strong>, Host: <strong>www</strong>, Value: <strong>@cname</strong>, TTL: <strong>Automatic</strong>', ['@cname' => $cnameTarget]),
        ],
        'note' => (string) $this->t('Namecheap soporta ALIAS record para dominio raiz: usa Host @ con el mismo valor CNAME.'),
      ],
      'ovh' => [
        'name' => 'OVH / OVHcloud',
        'steps' => [
          (string) $this->t('Accede a <strong>ovh.es > Panel de control</strong>'),
          (string) $this->t('Ve a <strong>Dominios > Zona DNS</strong>'),
          (string) $this->t('Pulsa <strong>Anadir un registro</strong>'),
          (string) $this->t('Selecciona <strong>CNAME</strong>'),
          (string) $this->t('Subdominio: <strong>www</strong>, Destino: <strong>@cname.</strong> (con punto final)', ['@cname' => $cnameTarget]),
        ],
        'note' => (string) $this->t('OVH requiere un punto final (.) despues del valor CNAME. El sistema lo anade automaticamente en la mayoria de casos.'),
      ],
      'hostinger' => [
        'name' => 'Hostinger',
        'steps' => [
          (string) $this->t('Accede a <strong>hPanel > Dominios</strong>'),
          (string) $this->t('Selecciona tu dominio y ve a <strong>DNS / Nameservers</strong>'),
          (string) $this->t('En la seccion DNS Records, pulsa <strong>Agregar registro</strong>'),
          (string) $this->t('Tipo: <strong>CNAME</strong>, Nombre: <strong>www</strong>, Apunta a: <strong>@cname</strong>, TTL: <strong>14400</strong>', ['@cname' => $cnameTarget]),
          (string) $this->t('Pulsa <strong>Agregar registro</strong>'),
        ],
      ],
    ];
  }

  /**
   * Returns troubleshooting FAQs.
   *
   * @return array<int, array{q: string, a: string}>
   */
  protected function getTroubleshootingFaqs(string $baseDomain): array {
    return [
      [
        'q' => (string) $this->t('La verificacion falla despues de configurar el CNAME'),
        'a' => (string) $this->t('La propagacion DNS puede tardar hasta 48 horas, aunque normalmente es mucho menos. Espera al menos 15 minutos antes de intentar verificar de nuevo. Puedes comprobar la propagacion en herramientas como <strong>dnschecker.org</strong>.'),
      ],
      [
        'q' => (string) $this->t('Puedo usar mi dominio raiz (sin www)?'),
        'a' => (string) $this->t('Depende de tu proveedor DNS. El estandar CNAME solo funciona con subdominios (como www). Algunos proveedores ofrecen CNAME flattening (Cloudflare), ALIAS records (Namecheap, DNSimple) o ANAME records que permiten usar el dominio raiz. Consulta la documentacion de tu proveedor.'),
      ],
      [
        'q' => (string) $this->t('Se configurara SSL automaticamente?'),
        'a' => (string) $this->t('Si. Una vez verificado el dominio, se genera automaticamente un certificado SSL gratuito via Let\'s Encrypt. El proceso tarda unos minutos y tu sitio estara accesible via HTTPS sin configuracion adicional.'),
      ],
      [
        'q' => (string) $this->t('Que pasa con mi dominio anterior mientras configuro el nuevo?'),
        'a' => (string) $this->t('Tu URL por defecto (tu-nombre.@base_domain) sigue funcionando en todo momento. El dominio personalizado se activa solo cuando la verificacion DNS es exitosa. No hay tiempo de inactividad.', ['@base_domain' => $baseDomain]),
      ],
      [
        'q' => (string) $this->t('Puedo volver a usar la URL por defecto?'),
        'a' => (string) $this->t('Si. Simplemente borra el dominio personalizado del campo de arriba y guarda. Tu metasitio volvera a estar disponible unicamente en la URL por defecto.'),
      ],
    ];
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
    $baseDomain = Settings::get('jaraba_base_domain', 'plataformadeecosistemas.com');

    try {
      $records = dns_get_record($domain, DNS_CNAME);

      foreach ($records as $record) {
        if (isset($record['target']) && str_contains($record['target'], $baseDomain)) {
          return TRUE;
        }
      }
    }
    catch (\Throwable $e) {
      $this->getLogger('ecosistema_jaraba_core')->warning('DNS verification failed for @domain: @error', [
        '@domain' => $domain,
        '@error' => $e->getMessage(),
      ]);
    }

    return FALSE;
  }

}
