<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Access;

use Drupal\Core\Access\AccessCheckInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Avatar-based route access checker.
 *
 * PROPOSITO:
 * Reemplaza permisos de Drupal como `_permission: 'access entrepreneur dashboard'`
 * con una verificación basada en el avatar del usuario persistido en JourneyState.
 * Esto elimina la necesidad de crear 19+ roles de Drupal para permisos de dashboard.
 *
 * DISEÑO:
 * Los avatares se resuelven con cascada de 2 niveles:
 *   1. JourneyState entity (avatar persistido al registrarse — fuente fiable)
 *   2. Fallback: administradores siempre tienen acceso
 *
 * No usa AvatarDetectionService porque es contextual (depende de URL) y en
 * rutas de dashboard ya estamos EN la URL correcta. JourneyState tiene el
 * avatar REAL del usuario independiente de la URL actual.
 *
 * NOMENCLATURA:
 * Las rutas declaran avatares en formato JourneyState (español): emprendedor,
 * productor, comerciante. El checker normaliza via JOURNEY_TO_CANONICAL del
 * AvatarWizardBridgeService para aceptar ambas nomenclaturas.
 *
 * USO EN ROUTING.YML:
 *   jaraba_business_tools.entrepreneur_dashboard:
 *     path: '/entrepreneur/dashboard'
 *     requirements:
 *       _avatar_access: 'emprendedor,mentor,gestor_programa'
 *
 * DIRECTRICES:
 * - TENANT-001: Acceso basado en identidad del usuario, no del tenant.
 * - OPTIONAL-CROSSMODULE-001: EntityTypeManager es core, no necesita @?.
 * - ACCESS-RETURN-TYPE-001: Retorna AccessResultInterface (no AccessResult).
 *
 * @see \Drupal\jaraba_journey\Entity\JourneyState
 * @see \Drupal\ecosistema_jaraba_core\Service\AvatarWizardBridgeService
 */
class AvatarAccessCheck implements AccessCheckInterface {

  /**
   * Normaliza avatares de ambas nomenclaturas a un set unificado.
   *
   * Acepta tanto JourneyState español (emprendedor) como
   * AvatarDetectionService inglés (entrepreneur). Ambos normalizan
   * a la misma clave canónica para comparación.
   */
  protected const AVATAR_ALIASES = [
    // JourneyState español → canónico
    'job_seeker' => 'jobseeker',
    'emprendedor' => 'entrepreneur',
    'productor' => 'producer',
    'comerciante' => 'merchant',
    'profesional' => 'profesional',
    'estudiante' => 'student',
    'formador' => 'instructor',
    'orientador' => 'orientador',
    'beneficiario_ei' => 'beneficiario_ei',
    'tecnico_sto' => 'orientador_ei',
    'admin_ei' => 'coordinador_ei',
    'gestor_programa' => 'gestor_programa',
    'comprador_b2b' => 'buyer_b2b',
    'consumidor' => 'consumer',
    'comprador_local' => 'buyer_local',
    'cliente_servicios' => 'cliente_servicios',
    'admin_lms' => 'admin_lms',
    // AvatarDetection inglés → identidad
    'jobseeker' => 'jobseeker',
    'recruiter' => 'recruiter',
    'entrepreneur' => 'entrepreneur',
    'producer' => 'producer',
    'merchant' => 'merchant',
    'student' => 'student',
    'mentor' => 'mentor',
    'legal_professional' => 'legal_professional',
    'instructor' => 'instructor',
    'coordinador_ei' => 'coordinador_ei',
    'orientador_ei' => 'orientador_ei',
  ];

  /**
   * Construye el AvatarAccessCheck.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para cargar JourneyState.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route): bool {
    return $route->hasRequirement('_avatar_access');
  }

  /**
   * Verifica acceso basado en el avatar del usuario.
   *
   * Flujo:
   * 1. Administradores: acceso siempre (bypass).
   * 2. Carga JourneyState del usuario.
   * 3. Normaliza el avatar del usuario y los avatares requeridos.
   * 4. Verifica intersección.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   Ruta con requirement _avatar_access.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Match de la ruta actual.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Cuenta del usuario solicitante.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Allowed si el avatar del usuario está en la lista requerida.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account): AccessResultInterface {
    // Administradores siempre tienen acceso a todos los dashboards.
    if (in_array('administrator', $account->getRoles(), TRUE)) {
      return AccessResult::allowed()->setCacheMaxAge(0);
    }

    // Usuarios anónimos nunca tienen acceso.
    if ($account->isAnonymous()) {
      return AccessResult::forbidden('Acceso requiere autenticación.')->setCacheMaxAge(0);
    }

    // Obtener avatares requeridos de la ruta.
    $requirement = $route->getRequirement('_avatar_access');
    if (empty($requirement)) {
      return AccessResult::forbidden('No avatar requirement defined.')->setCacheMaxAge(0);
    }

    $requiredAvatars = array_map('trim', explode(',', $requirement));

    // Normalizar avatares requeridos a canónicos.
    $normalizedRequired = [];
    foreach ($requiredAvatars as $avatar) {
      $normalizedRequired[] = self::AVATAR_ALIASES[$avatar] ?? $avatar;
    }

    // Obtener avatar del usuario desde JourneyState.
    $userAvatar = $this->getUserAvatar((int) $account->id());
    if (!$userAvatar) {
      return AccessResult::forbidden('Usuario sin avatar asignado.')
        ->setCacheMaxAge(0);
    }

    // Normalizar avatar del usuario.
    $normalizedUser = self::AVATAR_ALIASES[$userAvatar] ?? $userAvatar;

    // Verificar si el avatar del usuario está en los requeridos.
    if (in_array($normalizedUser, $normalizedRequired, TRUE)) {
      return AccessResult::allowed()
        ->setCacheMaxAge(0);
    }

    return AccessResult::forbidden('Avatar no autorizado para este dashboard.')
      ->setCacheMaxAge(0);
  }

  /**
   * Obtiene el avatar_type del JourneyState del usuario.
   *
   * @param int $uid
   *   ID del usuario.
   *
   * @return string|null
   *   Avatar type del usuario, o NULL si no tiene JourneyState.
   */
  protected function getUserAvatar(int $uid): ?string {
    try {
      if (!$this->entityTypeManager->hasDefinition('journey_state')) {
        return NULL;
      }

      $states = $this->entityTypeManager
        ->getStorage('journey_state')
        ->loadByProperties(['user_id' => $uid]);

      if (empty($states)) {
        return NULL;
      }

      $state = reset($states);
      $avatar = $state->get('avatar_type')->value ?? '';

      return (!empty($avatar) && $avatar !== 'pending') ? $avatar : NULL;
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

}
