<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile;

/**
 * Registry de secciones de perfil de usuario.
 *
 * Las secciones se agregan automaticamente via UserProfileSectionPass
 * desde servicios taggeados con 'ecosistema_jaraba_core.user_profile_section'.
 *
 * Patron identico a TenantSettingsRegistry.
 *
 * @see \Drupal\ecosistema_jaraba_core\TenantSettings\TenantSettingsRegistry
 */
class UserProfileSectionRegistry {

  /**
   * Secciones registradas.
   *
   * @var \Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionInterface[]
   */
  protected array $sections = [];

  /**
   * Agrega una seccion al registry.
   */
  public function addSection(UserProfileSectionInterface $section): void {
    $this->sections[$section->getId()] = $section;
  }

  /**
   * Devuelve secciones aplicables al usuario, ordenadas por peso.
   *
   * @param int $uid
   *   ID del usuario cuyo perfil se visualiza.
   *
   * @return \Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionInterface[]
   */
  public function getApplicableSections(int $uid): array {
    $applicable = array_filter(
      $this->sections,
      static fn(UserProfileSectionInterface $s) => $s->isApplicable($uid),
    );
    usort($applicable, static fn($a, $b) => $a->getWeight() <=> $b->getWeight());
    return $applicable;
  }

  /**
   * Construye el array completo para $variables['user_quick_sections'].
   *
   * Output identico al formato que consume page--user.html.twig,
   * garantizando compatibilidad total sin cambios en el template.
   *
   * @param int $uid
   *   ID del usuario cuyo perfil se visualiza.
   *
   * @return array<int, array<string, mixed>>
   */
  public function buildSectionsArray(int $uid): array {
    $result = [];
    foreach ($this->getApplicableSections($uid) as $section) {
      $links = $section->getLinks($uid);
      if (empty($links)) {
        continue;
      }
      $icon = $section->getIcon();
      $entry = [
        'id' => $section->getId(),
        'title' => $section->getTitle($uid),
        'subtitle' => $section->getSubtitle($uid),
        'icon_category' => $icon['category'] ?? 'ui',
        'icon_name' => $icon['name'] ?? 'info',
        'links' => array_values($links),
        'color' => $section->getColor(),
      ];
      $extraData = $section->getExtraData($uid);
      if (!empty($extraData)) {
        $entry = array_merge($entry, $extraData);
      }
      $result[] = $entry;
    }
    return $result;
  }

  /**
   * Devuelve todas las secciones registradas (sin filtrar).
   *
   * @return \Drupal\ecosistema_jaraba_core\UserProfile\UserProfileSectionInterface[]
   */
  public function getAllSections(): array {
    return $this->sections;
  }

  /**
   * Obtiene una seccion por ID.
   */
  public function getSection(string $id): ?UserProfileSectionInterface {
    return $this->sections[$id] ?? NULL;
  }

}
