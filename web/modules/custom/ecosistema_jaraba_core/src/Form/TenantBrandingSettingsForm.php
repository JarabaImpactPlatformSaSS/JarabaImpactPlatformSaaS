<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\Trait\TenantFormHeroPremiumTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Formulario de branding accesible para tenants.
 *
 * Edita campos de la entidad SiteConfig del tenant actual:
 * site_name, site_tagline, contact_email, contact_phone,
 * social_links, meta_title_suffix.
 *
 * Los campos de archivos (logo, favicon) requieren permisos adicionales
 * y se gestionan desde el customizer de diseno.
 */
class TenantBrandingSettingsForm extends FormBase {

  use TenantFormHeroPremiumTrait;

  public function __construct(
    protected TenantContextService $tenantContext,
    protected EntityTypeManagerInterface $entityTypeManager,
    MessengerInterface $messenger,
  ) {
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('ecosistema_jaraba_core.tenant_context'),
      $container->get('entity_type.manager'),
      $container->get('messenger'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'tenant_branding_settings_form';
  }

  /**
   * Carga o crea la SiteConfig del tenant actual.
   */
  protected function loadOrCreateSiteConfig(): ?ContentEntityInterface {
    $tenantId = $this->tenantContext->getCurrentTenantId();
    if (!$tenantId) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('site_config');
    $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);

    if (!empty($configs)) {
      $config = reset($configs);
      return $config instanceof ContentEntityInterface ? $config : NULL;
    }

    // Auto-crear SiteConfig para el tenant.
    $tenant = $this->tenantContext->getCurrentTenant();
    $config = $storage->create([
      'tenant_id' => $tenantId,
      'site_name' => $tenant ? ($tenant->label() ?? '') : '',
    ]);
    $config->save();

    return $config instanceof ContentEntityInterface ? $config : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $siteConfig = $this->loadOrCreateSiteConfig();

    if (!$siteConfig) {
      $form['no_config'] = [
        '#markup' => '<div class="tenant-form__alert tenant-form__alert--warning">' . $this->t('No se encontro configuracion de sitio para tu organizacion. Contacta al administrador.') . '</div>',
      ];
      return $form;
    }

    $form_state->set('site_config_id', $siteConfig->id());

    $this->attachTenantFormHero(
      $form,
      'palette',
      (string) $this->t('Marca y Branding'),
      (string) $this->t('Logo, nombre, contacto y SEO basico de tu marca.'),
    );

    // Identidad.
    $form['identity'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['identity']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Identidad de Marca') . '</h3>',
    ];

    $form['identity']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nombre del sitio'),
      '#default_value' => $siteConfig->get('site_name')->value ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
    ];

    $form['identity']['site_tagline'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Eslogan'),
      '#default_value' => $siteConfig->get('site_tagline')->value ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('Subtitulo o lema de tu marca.'),
    ];

    // Contacto.
    $form['contact'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['contact']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Informacion de Contacto') . '</h3>',
    ];

    $form['contact']['contact_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email de contacto'),
      '#default_value' => $siteConfig->get('contact_email')->value ?? '',
    ];

    $form['contact']['contact_phone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Telefono de contacto'),
      '#default_value' => $siteConfig->get('contact_phone')->value ?? '',
      '#maxlength' => 50,
    ];

    // SEO basico.
    $form['seo'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['seo']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('SEO Basico') . '</h3>',
    ];

    $form['seo']['meta_title_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sufijo de titulo'),
      '#default_value' => $siteConfig->get('meta_title_suffix')->value ?? '',
      '#maxlength' => 100,
      '#description' => $this->t('Se anade al final de los titulos de pagina (ej: " | Mi Empresa").'),
    ];

    // Redes sociales.
    $form['social'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['tenant-form__section']],
    ];

    $form['social']['section_title'] = [
      '#markup' => '<h3 class="tenant-form__section-title">' . $this->t('Redes Sociales') . '</h3>',
    ];

    $socialLinks = [];
    $socialJson = $siteConfig->get('social_links')->value ?? '';
    if (!empty($socialJson)) {
      $decoded = json_decode($socialJson, TRUE);
      if (is_array($decoded)) {
        $socialLinks = $decoded;
      }
    }

    $socialNetworks = [
      'facebook' => 'Facebook',
      'instagram' => 'Instagram',
      'twitter' => 'X (Twitter)',
      'linkedin' => 'LinkedIn',
      'youtube' => 'YouTube',
    ];

    foreach ($socialNetworks as $key => $label) {
      $form['social']['social_' . $key] = [
        '#type' => 'url',
        '#title' => $label,
        '#default_value' => $socialLinks[$key] ?? '',
        '#maxlength' => 255,
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['tenant-form__actions']],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Guardar cambios'),
      '#attributes' => ['class' => ['tenant-form__btn', 'tenant-form__btn--primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $configId = $form_state->get('site_config_id');
    if (!$configId) {
      return;
    }

    $siteConfig = $this->entityTypeManager
      ->getStorage('site_config')
      ->load($configId);

    if (!$siteConfig instanceof ContentEntityInterface) {
      return;
    }

    // Campos directos.
    $directFields = ['site_name', 'site_tagline', 'contact_email', 'contact_phone', 'meta_title_suffix'];
    foreach ($directFields as $field) {
      $siteConfig->set($field, $form_state->getValue($field));
    }

    // Social links como JSON.
    $socialLinks = [];
    $socialNetworks = ['facebook', 'instagram', 'twitter', 'linkedin', 'youtube'];
    foreach ($socialNetworks as $network) {
      $value = $form_state->getValue('social_' . $network);
      if (!empty($value)) {
        $socialLinks[$network] = $value;
      }
    }
    $siteConfig->set('social_links', !empty($socialLinks) ? json_encode($socialLinks) : NULL);

    $siteConfig->save();

    $this->messenger()->addStatus($this->t('La configuracion de marca se ha guardado correctamente.'));
  }

}
