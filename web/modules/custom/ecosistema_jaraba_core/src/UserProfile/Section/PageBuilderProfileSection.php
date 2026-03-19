<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Mis Paginas" — visible si page builder activo.
 *
 * Proporciona acceso rapido a:
 * - Mis paginas (listado de paginas publicadas)
 * - Crear nueva pagina (acceso al Page Builder)
 *
 * OPTIONAL-CROSSMODULE-001: Rutas de jaraba_page_builder opcionales.
 */
class PageBuilderProfileSection extends AbstractUserProfileSection {

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'my_pages';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Mis paginas');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    return (string) $this->t('Crea y gestiona tus paginas web');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'layout-template'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'innovation';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 35;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(int $uid): bool {
    // Visible si existe la ruta de mis paginas (modulo page builder activo).
    return $this->resolveRoute('jaraba_page_builder.my_pages') !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks(int $uid): array {
    return array_values(array_filter([
      $this->makeLink(
        $this->t('Mis paginas'),
        'jaraba_page_builder.my_pages',
        'ui', 'layout-grid', 'innovation',
        ['description' => $this->t('Paginas publicadas y borradores')],
      ),
    ]));
  }

}
