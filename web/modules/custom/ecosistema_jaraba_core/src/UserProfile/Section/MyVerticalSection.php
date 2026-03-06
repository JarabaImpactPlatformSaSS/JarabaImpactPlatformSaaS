<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Mi Vertical" — dinamica por avatar del usuario.
 *
 * Migrada desde ecosistema_jaraba_theme.theme lineas 3540-3608.
 * Solo visible si el usuario tiene un avatar != 'general'.
 */
class MyVerticalSection extends AbstractUserProfileSection {

  /**
   * Labels legibles por tipo de avatar.
   */
  private const AVATAR_LABELS = [
    'jobseeker' => 'Candidato',
    'recruiter' => 'Reclutador',
    'entrepreneur' => 'Emprendedor',
    'producer' => 'Productor',
    'buyer' => 'Comprador',
    'merchant' => 'Comerciante',
    'service_provider' => 'Proveedor de Servicios',
    'profesional' => 'Profesional',
    'student' => 'Estudiante',
    'mentor' => 'Mentor',
    'legal_professional' => 'Profesional Legal',
    'tenant_admin' => 'Admin Tenant',
  ];

  /**
   * Colores por vertical.
   */
  private const VERTICAL_COLORS = [
    'empleabilidad' => 'innovation',
    'emprendimiento' => 'impulse',
    'agroconecta' => 'innovation',
    'comercioconecta' => 'impulse',
    'serviciosconecta' => 'servicios',
    'jarabalex' => 'corporate',
  ];

  /**
   * Resultado cacheado de deteccion de avatar (por request).
   */
  private ?object $cachedResult = NULL;

  private bool $detected = FALSE;

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Usuario actual.
   * @param object|null $avatarDetection
   *   Servicio ecosistema_jaraba_core.avatar_detection (opcional).
   * @param object|null $avatarNavigation
   *   Servicio ecosistema_jaraba_core.avatar_navigation (opcional).
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    protected readonly ?object $avatarDetection = NULL,
    protected readonly ?object $avatarNavigation = NULL,
  ) {
    parent::__construct($currentUser);
  }

  public function getId(): string {
    return 'my_vertical';
  }

  public function getTitle(int $uid): string {
    $result = $this->detectAvatar();
    $label = $this->getAvatarLabel($result);
    if ($label) {
      return (string) $this->t('Mi Vertical: @label', ['@label' => $label]);
    }
    return (string) $this->t('Mi Vertical');
  }

  public function getSubtitle(int $uid): string {
    $result = $this->detectAvatar();
    $label = $this->getAvatarLabel($result);
    if ($label) {
      return (string) $this->t('Accesos directos a tus herramientas de @label', ['@label' => $label]);
    }
    return (string) $this->t('Accesos directos a tus herramientas');
  }

  public function getIcon(): array {
    return ['category' => 'verticals', 'name' => 'rocket'];
  }

  public function getColor(): string {
    $result = $this->detectAvatar();
    if ($result) {
      return self::VERTICAL_COLORS[$result->vertical ?? ''] ?? 'primary';
    }
    return 'primary';
  }

  public function getWeight(): int {
    return 20;
  }

  public function isApplicable(int $uid): bool {
    $result = $this->detectAvatar();
    return $result !== NULL && $result->avatarType !== 'general';
  }

  public function getLinks(int $uid): array {
    if (!$this->avatarNavigation) {
      return [];
    }

    $result = $this->detectAvatar();
    $color = $this->getColor();

    try {
      $navItems = $this->avatarNavigation->getNavigationItems();
    }
    catch (\Throwable) {
      return [];
    }

    $links = [];
    foreach ($navItems as $item) {
      $links[] = [
        'label' => $item['label'],
        'url' => $item['url'],
        'icon_category' => $item['icon_category'] ?? 'ui',
        'icon_name' => $item['icon_name'] ?? 'arrow-right',
        'color' => $color,
        'description' => '',
        'slide_panel' => FALSE,
        'slide_panel_title' => $item['label'],
        'cross_vertical' => !empty($item['cross_vertical']),
      ];
    }

    return $links;
  }

  /**
   * Detecta avatar cacheando resultado por request.
   */
  private function detectAvatar(): ?object {
    if ($this->detected) {
      return $this->cachedResult;
    }
    $this->detected = TRUE;

    if (!$this->avatarDetection) {
      return NULL;
    }

    try {
      $this->cachedResult = $this->avatarDetection->detect();
    }
    catch (\Throwable) {
      $this->cachedResult = NULL;
    }

    return $this->cachedResult;
  }

  private function getAvatarLabel(?object $result): ?string {
    if (!$result || !isset($result->avatarType)) {
      return NULL;
    }
    $type = $result->avatarType;
    if ($type === 'general' || !isset(self::AVATAR_LABELS[$type])) {
      return NULL;
    }
    return (string) $this->t(self::AVATAR_LABELS[$type]);
  }

}
