<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\TenantSettings;

/**
 * Registry de secciones de configuracion de tenant.
 *
 * Las secciones se agregan automaticamente via TenantSettingsSectionPass
 * desde servicios taggeados con 'ecosistema_jaraba_core.tenant_settings_section'.
 */
class TenantSettingsRegistry {

  /**
   * Secciones registradas.
   *
   * @var \Drupal\ecosistema_jaraba_core\TenantSettings\TenantSettingsSectionInterface[]
   */
  protected array $sections = [];

  /**
   * Agrega una seccion al registry.
   */
  public function addSection(TenantSettingsSectionInterface $section): void {
    $this->sections[$section->getId()] = $section;
  }

  /**
   * Devuelve todas las secciones accesibles, ordenadas por peso.
   *
   * @return \Drupal\ecosistema_jaraba_core\TenantSettings\TenantSettingsSectionInterface[]
   */
  public function getAccessibleSections(): array {
    $accessible = array_filter($this->sections, fn($s) => $s->isAccessible());
    usort($accessible, fn($a, $b) => $a->getWeight() <=> $b->getWeight());
    return $accessible;
  }

  /**
   * Devuelve todas las secciones registradas (sin filtrar).
   *
   * @return \Drupal\ecosistema_jaraba_core\TenantSettings\TenantSettingsSectionInterface[]
   */
  public function getAllSections(): array {
    return $this->sections;
  }

  /**
   * Obtiene una seccion por ID.
   */
  public function getSection(string $id): ?TenantSettingsSectionInterface {
    return $this->sections[$id] ?? NULL;
  }

}
