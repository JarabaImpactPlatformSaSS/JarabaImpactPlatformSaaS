<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Mi Negocio" — solo si el usuario tiene tenant activo.
 *
 * Migrada desde ecosistema_jaraba_theme.theme lineas 3610-3650.
 */
class MyBusinessSection extends AbstractUserProfileSection {

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Usuario actual.
   * @param object|null $tenantContext
   *   Servicio ecosistema_jaraba_core.tenant_context (opcional).
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    protected readonly ?object $tenantContext = NULL,
  ) {
    parent::__construct($currentUser);
  }

  public function getId(): string {
    return 'my_business';
  }

  public function getTitle(int $uid): string {
    return (string) $this->t('Mi Negocio');
  }

  public function getSubtitle(int $uid): string {
    return (string) $this->t('Administra tu organizacion y suscripcion');
  }

  public function getIcon(): array {
    return ['category' => 'business', 'name' => 'briefcase'];
  }

  public function getColor(): string {
    return 'corporate';
  }

  public function getWeight(): int {
    return 30;
  }

  public function isApplicable(int $uid): bool {
    return $this->resolveTenant() !== NULL;
  }

  public function getLinks(int $uid): array {
    return array_values(array_filter([
      $this->makeLink(
        $this->t('Dashboard Tenant'),
        'ecosistema_jaraba_core.tenant.dashboard',
        'ui', 'dashboard', 'corporate',
        [
          'description' => $this->t('Metricas y gestion de tu negocio'),
          'slide_panel' => TRUE,
        ],
      ),
      $this->makeLink(
        $this->t('Configuracion'),
        'ecosistema_jaraba_core.tenant_self_service.settings',
        'ui', 'settings', 'corporate',
        ['description' => $this->t('Marca, diseno, dominio, API y mas')],
      ),
      $this->makeLink(
        $this->t('Cambiar plan'),
        'ecosistema_jaraba_core.tenant.change_plan',
        'commerce', 'tag', 'corporate',
        [
          'description' => $this->t('Explora planes y funcionalidades'),
          'slide_panel' => TRUE,
        ],
      ),
      $this->makeLink(
        $this->t('Centro de ayuda'),
        'jaraba_tenant_knowledge.help_center',
        'ui', 'help-circle', 'corporate',
        ['description' => $this->t('Guias, tutoriales y soporte')],
      ),
    ]));
  }

  private function resolveTenant(): ?object {
    if (!$this->tenantContext) {
      return NULL;
    }
    try {
      return $this->tenantContext->getCurrentTenant();
    }
    catch (\Throwable) {
      return NULL;
    }
  }

}
