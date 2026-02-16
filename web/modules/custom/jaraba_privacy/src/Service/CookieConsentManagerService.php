<?php

declare(strict_types=1);

namespace Drupal\jaraba_privacy\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jaraba_privacy\Entity\CookieConsent;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * GESTOR DE CONSENTIMIENTO DE COOKIES — CookieConsentManagerService.
 *
 * ESTRUCTURA:
 * Servicio que gestiona el ciclo de vida del consentimiento de cookies
 * según LSSI-CE y Directiva ePrivacy. Permite consentimiento granular
 * por categoría (analíticas, marketing, funcionales, terceros).
 *
 * LÓGICA DE NEGOCIO:
 * - El consentimiento es granular: el usuario elige qué categorías acepta.
 * - Cada interacción genera un registro inmutable (audit trail).
 * - Los usuarios anónimos se identifican por session_id.
 * - El consentimiento expira según cookie_expiry_days (configurable).
 * - El banner se configura por posición desde settings del módulo.
 *
 * RELACIONES:
 * - CookieConsentManagerService → ConfigFactoryInterface (settings banner)
 * - CookieConsentManagerService → RequestStack (IP, session)
 * - CookieConsentManagerService ← CookieBannerController (endpoint público)
 * - CookieConsentManagerService ← PrivacyApiController (API REST)
 *
 * Spec: Doc 183 §4.3. Plan: FASE 2, Stack Compliance Legal N1.
 *
 * @package Drupal\jaraba_privacy\Service
 */
class CookieConsentManagerService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected RequestStack $requestStack,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Registra un nuevo consentimiento de cookies.
   *
   * @param array $consent_data
   *   Array con las categorías aceptadas:
   *   - analytics: bool
   *   - marketing: bool
   *   - functional: bool
   *   - thirdparty: bool
   * @param string $ip_address
   *   Dirección IP del usuario.
   * @param int|null $user_id
   *   ID del usuario autenticado (NULL si anónimo).
   * @param string|null $session_id
   *   ID de sesión para usuarios anónimos.
   *
   * @return \Drupal\jaraba_privacy\Entity\CookieConsent
   *   Registro de consentimiento creado.
   */
  public function recordConsent(array $consent_data, string $ip_address, ?int $user_id, ?string $session_id): CookieConsent {
    $storage = $this->entityTypeManager->getStorage('cookie_consent');

    // Determinar tenant_id del contexto actual.
    $tenant_id = $this->resolveTenantId();

    /** @var \Drupal\jaraba_privacy\Entity\CookieConsent $consent */
    $consent = $storage->create([
      'tenant_id' => $tenant_id,
      'user_id' => $user_id,
      'session_id' => $session_id,
      'consent_analytics' => !empty($consent_data['analytics']),
      'consent_marketing' => !empty($consent_data['marketing']),
      'consent_functional' => !empty($consent_data['functional']),
      'consent_thirdparty' => !empty($consent_data['thirdparty']),
      'ip_address' => $ip_address,
      'consented_at' => time(),
    ]);

    $consent->save();

    $this->logger->info('Consentimiento de cookies registrado. Analytics: @a, Marketing: @m, Functional: @f, ThirdParty: @t.', [
      '@a' => $consent_data['analytics'] ? 'Sí' : 'No',
      '@m' => $consent_data['marketing'] ? 'Sí' : 'No',
      '@f' => $consent_data['functional'] ? 'Sí' : 'No',
      '@t' => $consent_data['thirdparty'] ? 'Sí' : 'No',
    ]);

    return $consent;
  }

  /**
   * Obtiene el último consentimiento vigente de un usuario.
   *
   * @param int|null $user_id
   *   ID del usuario.
   * @param string|null $session_id
   *   ID de sesión.
   *
   * @return \Drupal\jaraba_privacy\Entity\CookieConsent|null
   *   Último consentimiento o NULL si no existe.
   */
  public function getCurrentConsent(?int $user_id, ?string $session_id): ?CookieConsent {
    $storage = $this->entityTypeManager->getStorage('cookie_consent');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->sort('consented_at', 'DESC')
      ->range(0, 1);

    if ($user_id) {
      $query->condition('user_id', $user_id);
    }
    elseif ($session_id) {
      $query->condition('session_id', $session_id);
    }
    else {
      return NULL;
    }

    // Verificar si no ha expirado.
    $config = $this->configFactory->get('jaraba_privacy.settings');
    $expiry_days = $config->get('cookie_expiry_days') ?? 365;
    $expiry_timestamp = time() - ($expiry_days * 86400);
    $query->condition('consented_at', $expiry_timestamp, '>=');

    $ids = $query->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Revoca un consentimiento de cookies.
   *
   * No se elimina el registro original (inmutable), pero se crea un nuevo
   * registro con todas las categorías en FALSE.
   *
   * @param int $consent_id
   *   ID del consentimiento a revocar.
   *
   * @return \Drupal\jaraba_privacy\Entity\CookieConsent
   *   Nuevo registro con consentimiento revocado.
   */
  public function withdrawConsent(int $consent_id): CookieConsent {
    $original = $this->entityTypeManager->getStorage('cookie_consent')->load($consent_id);

    if (!$original) {
      throw new \RuntimeException(
        (string) new TranslatableMarkup('El consentimiento con ID @id no existe.', ['@id' => $consent_id])
      );
    }

    // Crear un nuevo registro con todo revocado (el original es inmutable).
    return $this->recordConsent(
      ['analytics' => FALSE, 'marketing' => FALSE, 'functional' => FALSE, 'thirdparty' => FALSE],
      $original->get('ip_address')->value ?? '',
      $original->get('user_id')->target_id ? (int) $original->get('user_id')->target_id : NULL,
      $original->get('session_id')->value
    );
  }

  /**
   * Obtiene la configuración del banner de cookies.
   *
   * @param int $tenant_id
   *   ID del tenant.
   *
   * @return array
   *   Configuración del banner: posición, textos, links.
   */
  public function getBannerConfig(int $tenant_id): array {
    $config = $this->configFactory->get('jaraba_privacy.settings');

    return [
      'enabled' => (bool) $config->get('enable_cookie_banner'),
      'position' => $config->get('cookie_banner_position') ?? 'bottom-bar',
      'expiry_days' => $config->get('cookie_expiry_days') ?? 365,
      'categories' => [
        'necessary' => [
          'label' => (string) new TranslatableMarkup('Necesarias'),
          'description' => (string) new TranslatableMarkup('Cookies esenciales para el funcionamiento del sitio.'),
          'required' => TRUE,
        ],
        'functional' => [
          'label' => (string) new TranslatableMarkup('Funcionales'),
          'description' => (string) new TranslatableMarkup('Cookies que mejoran la funcionalidad del sitio.'),
          'required' => FALSE,
        ],
        'analytics' => [
          'label' => (string) new TranslatableMarkup('Analíticas'),
          'description' => (string) new TranslatableMarkup('Cookies para medir el uso del sitio y mejorar la experiencia.'),
          'required' => FALSE,
        ],
        'marketing' => [
          'label' => (string) new TranslatableMarkup('Marketing'),
          'description' => (string) new TranslatableMarkup('Cookies para publicidad personalizada y remarketing.'),
          'required' => FALSE,
        ],
        'thirdparty' => [
          'label' => (string) new TranslatableMarkup('Terceros'),
          'description' => (string) new TranslatableMarkup('Cookies establecidas por servicios de terceros integrados.'),
          'required' => FALSE,
        ],
      ],
      'texts' => [
        'title' => (string) new TranslatableMarkup('Configuración de cookies'),
        'description' => (string) new TranslatableMarkup('Utilizamos cookies para mejorar tu experiencia. Puedes personalizar tus preferencias.'),
        'accept_all' => (string) new TranslatableMarkup('Aceptar todas'),
        'reject_all' => (string) new TranslatableMarkup('Rechazar todas'),
        'customize' => (string) new TranslatableMarkup('Personalizar'),
        'save' => (string) new TranslatableMarkup('Guardar preferencias'),
      ],
    ];
  }

  /**
   * Verifica si un usuario ha consentido una categoría de cookie.
   *
   * @param string $category
   *   Categoría de cookie (analytics, marketing, functional, thirdparty).
   * @param int|null $user_id
   *   ID del usuario.
   * @param string|null $session_id
   *   ID de sesión.
   *
   * @return bool
   *   TRUE si el usuario ha consentido esa categoría.
   */
  public function hasConsent(string $category, ?int $user_id, ?string $session_id): bool {
    $consent = $this->getCurrentConsent($user_id, $session_id);

    if (!$consent) {
      return FALSE;
    }

    $field_name = 'consent_' . $category;
    if (!$consent->hasField($field_name)) {
      return FALSE;
    }

    return (bool) $consent->get($field_name)->value;
  }

  /**
   * Resuelve el tenant_id del contexto actual.
   */
  protected function resolveTenantId(): ?int {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return NULL;
    }

    // Intentar obtener del header o del dominio.
    $tenant_header = $request->headers->get('X-Tenant-Id');
    if ($tenant_header) {
      return (int) $tenant_header;
    }

    return NULL;
  }

}
