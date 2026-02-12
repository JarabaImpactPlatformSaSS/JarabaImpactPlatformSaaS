<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio de deteccion unificada de avatar.
 *
 * PROPOSITO:
 * Resuelve el avatar del usuario mediante una cascada de 4 niveles:
 *   1. Domain (subdominio del tenant → avatar)
 *   2. Path/UTM (URL path o parametros UTM de campana)
 *   3. Group (membresia en grupo/tenant)
 *   4. Rol (roles asignados al usuario)
 *
 * ESTRATEGIA:
 * La cascada se evalua en orden de prioridad. El primer nivel que
 * produce un resultado valido se usa como avatar definitivo.
 * Si ningun nivel detecta avatar, retorna el valor por defecto.
 *
 * SINTAXIS:
 * Sigue el patron de CopilotContextService con constantes estaticas
 * y DI explicita. Retorna AvatarDetectionResult (Value Object inmutable).
 *
 * @see \Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult
 * @see \Drupal\ecosistema_jaraba_core\Service\CopilotContextService
 */
class AvatarDetectionService {

  /**
   * Mapeo de subdominios a avatares y verticales.
   *
   * Clave: prefijo del subdominio.
   * Valor: [avatar, vertical].
   */
  protected const DOMAIN_MAP = [
    'empleo' => ['jobseeker', 'empleabilidad'],
    'talento' => ['recruiter', 'empleabilidad'],
    'emprender' => ['entrepreneur', 'emprendimiento'],
  ];

  /**
   * Mapeo de paths URL a avatares y verticales.
   *
   * Clave: segmento de path.
   * Valor: [avatar, vertical].
   */
  protected const PATH_MAP = [
    '/empleabilidad' => ['jobseeker', 'empleabilidad'],
    '/empleo' => ['jobseeker', 'empleabilidad'],
    '/talento' => ['recruiter', 'empleabilidad'],
    '/emprendimiento' => ['entrepreneur', 'emprendimiento'],
    '/comercio' => ['producer', 'comercio'],
  ];

  /**
   * Mapeo de campanas UTM a avatares y verticales.
   *
   * Clave: valor de utm_campaign.
   * Valor: [avatar, vertical, programa].
   */
  protected const UTM_MAP = [
    'empleabilidad_2026' => ['jobseeker', 'empleabilidad', 'Programa Empleabilidad 2026'],
    'talento_digital' => ['recruiter', 'empleabilidad', 'Talento Digital'],
    'emprende_andalucia' => ['entrepreneur', 'emprendimiento', 'Emprende Andalucia'],
  ];

  /**
   * Mapeo de roles a avatares (reutilizado de CopilotContextService).
   */
  protected const ROLE_TO_AVATAR = [
    'candidate' => 'jobseeker',
    'candidato' => 'jobseeker',
    'jobseeker' => 'jobseeker',
    'employer' => 'recruiter',
    'recruiter' => 'recruiter',
    'empleador' => 'recruiter',
    'entrepreneur' => 'entrepreneur',
    'emprendedor' => 'entrepreneur',
    'producer' => 'producer',
    'productor' => 'producer',
    'comercio' => 'producer',
    'mentor' => 'mentor',
    'institution' => 'institution',
  ];

  /**
   * Mapeo de avatares a verticales por defecto.
   */
  protected const AVATAR_TO_VERTICAL = [
    'jobseeker' => 'empleabilidad',
    'recruiter' => 'empleabilidad',
    'entrepreneur' => 'emprendimiento',
    'producer' => 'comercio',
    'mentor' => 'empleabilidad',
    'institution' => 'instituciones',
  ];

  /**
   * Mapeo de avatares a rutas de dashboard.
   */
  protected const AVATAR_DASHBOARD_ROUTES = [
    'jobseeker' => 'jaraba_candidate.dashboard',
    'recruiter' => 'jaraba_job_board.employer_dashboard',
    'entrepreneur' => 'jaraba_business_tools.entrepreneur_dashboard',
    'producer' => 'jaraba_job_board.my_company',
    'mentor' => 'jaraba_mentoring.mentor_dashboard',
  ];

  /**
   * Construye el AvatarDetectionService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Proxy del usuario actual.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Ruta actual.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Pila de peticiones HTTP.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   Canal de logging.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected RouteMatchInterface $routeMatch,
    protected RequestStack $requestStack,
    protected LoggerChannelInterface $logger,
  ) {}

  /**
   * Detecta el avatar del usuario actual usando cascada de 4 niveles.
   *
   * Orden de evaluacion: Domain > Path/UTM > Group > Rol > Default.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult
   *   Resultado de la deteccion con metadatos.
   */
  public function detect(): AvatarDetectionResult {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return AvatarDetectionResult::createDefault();
    }

    // Nivel 1: Deteccion por dominio (confianza maxima).
    $result = $this->detectByDomain($request->getHost());
    if ($result !== NULL) {
      return $result;
    }

    // Nivel 2: Deteccion por path o UTM.
    $result = $this->detectByPathOrUtm(
      $request->getPathInfo(),
      $request->query->get('utm_campaign', '')
    );
    if ($result !== NULL) {
      return $result;
    }

    // Nivel 3: Deteccion por grupo/tenant.
    if ($this->currentUser->isAuthenticated()) {
      $result = $this->detectByGroup((int) $this->currentUser->id());
      if ($result !== NULL) {
        return $result;
      }

      // Nivel 4: Deteccion por rol del usuario.
      $result = $this->detectByRole();
      if ($result !== NULL) {
        return $result;
      }
    }

    return AvatarDetectionResult::createDefault();
  }

  /**
   * Resuelve la ruta de dashboard segun el avatar detectado.
   *
   * @return string
   *   Nombre de ruta Drupal del dashboard correspondiente.
   */
  public function resolveDashboardRoute(): string {
    $result = $this->detect();
    return self::AVATAR_DASHBOARD_ROUTES[$result->avatarType]
      ?? 'ecosistema_jaraba_core.tenant.dashboard';
  }

  /**
   * Nivel 1: Deteccion por subdominio.
   *
   * Analiza el host de la peticion buscando subdominios conocidos.
   * Confianza: 1.0 (la mas alta).
   *
   * @param string $host
   *   Hostname de la peticion.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult|null
   *   Resultado si se detecta, NULL si no.
   */
  protected function detectByDomain(string $host): ?AvatarDetectionResult {
    $parts = explode('.', $host);
    $subdomain = $parts[0] ?? '';

    foreach (self::DOMAIN_MAP as $prefix => [$avatar, $vertical]) {
      if ($subdomain === $prefix) {
        $this->logger->info('Avatar detectado por dominio: @avatar (@host)', [
          '@avatar' => $avatar,
          '@host' => $host,
        ]);
        return new AvatarDetectionResult(
          avatarType: $avatar,
          vertical: $vertical,
          detectionSource: 'domain',
          programaOrigen: NULL,
          confidence: 1.0,
        );
      }
    }

    return NULL;
  }

  /**
   * Nivel 2: Deteccion por path URL o parametros UTM.
   *
   * Analiza el path de la peticion y los parametros UTM de campana.
   * UTM tiene prioridad sobre path (confianza 0.9 vs 0.8).
   *
   * @param string $path
   *   Path de la peticion.
   * @param string $utmCampaign
   *   Valor del parametro utm_campaign.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult|null
   *   Resultado si se detecta, NULL si no.
   */
  protected function detectByPathOrUtm(string $path, string $utmCampaign): ?AvatarDetectionResult {
    // UTM primero (mayor especificidad).
    if (!empty($utmCampaign) && isset(self::UTM_MAP[$utmCampaign])) {
      [$avatar, $vertical, $programa] = self::UTM_MAP[$utmCampaign];
      $this->logger->info('Avatar detectado por UTM: @avatar (@campaign)', [
        '@avatar' => $avatar,
        '@campaign' => $utmCampaign,
      ]);
      return new AvatarDetectionResult(
        avatarType: $avatar,
        vertical: $vertical,
        detectionSource: 'utm',
        programaOrigen: $programa,
        confidence: 0.9,
      );
    }

    // Luego path.
    foreach (self::PATH_MAP as $pathPrefix => [$avatar, $vertical]) {
      if (str_starts_with($path, $pathPrefix)) {
        $this->logger->info('Avatar detectado por path: @avatar (@path)', [
          '@avatar' => $avatar,
          '@path' => $path,
        ]);
        return new AvatarDetectionResult(
          avatarType: $avatar,
          vertical: $vertical,
          detectionSource: 'path',
          programaOrigen: NULL,
          confidence: 0.8,
        );
      }
    }

    return NULL;
  }

  /**
   * Nivel 3: Deteccion por grupo/tenant del usuario.
   *
   * Busca tenants donde el usuario es admin y resuelve vertical.
   * Confianza: 0.7.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult|null
   *   Resultado si se detecta, NULL si no.
   */
  protected function detectByGroup(int $userId): ?AvatarDetectionResult {
    try {
      $tenants = $this->entityTypeManager
        ->getStorage('tenant')
        ->loadByProperties(['admin_user_id' => $userId]);

      if (!empty($tenants)) {
        $tenant = reset($tenants);
        $vertical = NULL;

        if ($tenant->hasField('vertical') && !$tenant->get('vertical')->isEmpty()) {
          $verticalEntity = $tenant->get('vertical')->entity;
          if ($verticalEntity) {
            $vertical = strtolower($verticalEntity->label());
          }
        }

        return new AvatarDetectionResult(
          avatarType: 'admin',
          vertical: $vertical,
          detectionSource: 'group',
          programaOrigen: $tenant->label(),
          confidence: 0.7,
        );
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error detectando avatar por grupo: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Nivel 4: Deteccion por roles del usuario autenticado.
   *
   * Mapea roles de Drupal a avatares del ecosistema.
   * Confianza: 0.6.
   *
   * @return \Drupal\ecosistema_jaraba_core\ValueObject\AvatarDetectionResult|null
   *   Resultado si se detecta, NULL si no.
   */
  protected function detectByRole(): ?AvatarDetectionResult {
    $roles = $this->currentUser->getAccount()->getRoles();

    foreach ($roles as $role) {
      $roleLower = strtolower($role);
      if (isset(self::ROLE_TO_AVATAR[$roleLower])) {
        $avatar = self::ROLE_TO_AVATAR[$roleLower];
        $vertical = self::AVATAR_TO_VERTICAL[$avatar] ?? NULL;

        $this->logger->info('Avatar detectado por rol: @avatar (@role)', [
          '@avatar' => $avatar,
          '@role' => $role,
        ]);

        return new AvatarDetectionResult(
          avatarType: $avatar,
          vertical: $vertical,
          detectionSource: 'role',
          programaOrigen: NULL,
          confidence: 0.6,
        );
      }
    }

    return NULL;
  }

}
