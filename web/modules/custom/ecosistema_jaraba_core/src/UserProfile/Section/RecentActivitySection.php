<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Seccion "Actividad reciente" — engagement + motivacion.
 *
 * Muestra links a actividad contextual del usuario:
 * - Content Hub: ultimo articulo publicado (si aplica)
 * - Page Builder: ultima pagina editada (si aplica)
 * - Notificaciones (si ruta existe)
 *
 * Patron SaaS clase mundial: Salesforce, Notion, HubSpot, Linear
 * incluyen actividad reciente en perfil para engagement.
 *
 * OPTIONAL-CROSSMODULE-001: Todas las rutas son opcionales.
 */
class RecentActivitySection extends AbstractUserProfileSection {

  /**
   * Constructs a RecentActivitySection.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($currentUser);
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'recent_activity';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Actividad reciente');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    return (string) $this->t('Tu actividad en la plataforma');
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'clock'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'primary';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 70;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(int $uid): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinks(int $uid): array {
    $links = [];

    // Content Hub — ultimo articulo del usuario.
    $articleLink = $this->buildRecentContentLink($uid);
    if ($articleLink) {
      $links[] = $articleLink;
    }

    // Mis paginas — acceso al page builder.
    $pageLink = $this->makeLink(
      $this->t('Mis paginas'),
      'jaraba_page_builder.my_pages',
      'ui', 'layout-grid', 'primary',
      ['description' => $this->t('Paginas publicadas y borradores')],
    );
    if ($pageLink) {
      $links[] = $pageLink;
    }

    // Centro de ayuda / Soporte.
    $helpLink = $this->makeLink(
      $this->t('Centro de ayuda'),
      'jaraba_tenant_knowledge.help_center',
      'ui', 'help-circle', 'primary',
      ['description' => $this->t('Guias y tutoriales')],
    );
    if ($helpLink) {
      $links[] = $helpLink;
    }

    return $links;
  }

  /**
   * Construye link al ultimo articulo del Content Hub del usuario.
   *
   * OPTIONAL-CROSSMODULE-001: Solo si jaraba_content_hub esta activo
   * y el usuario ha publicado al menos un articulo.
   *
   * @param int $uid
   *   ID del usuario.
   *
   * @return array<string, mixed>|null
   *   Link formateado o NULL si no aplica.
   */
  protected function buildRecentContentLink(int $uid): ?array {
    try {
      if (!$this->entityTypeManager->hasDefinition('content_article')) {
        return NULL;
      }
      $articles = $this->entityTypeManager
        ->getStorage('content_article')
        ->getQuery()
        ->condition('user_id', $uid)
        ->sort('changed', 'DESC')
        ->range(0, 1)
        ->accessCheck(TRUE)
        ->execute();

      if (!empty($articles)) {
        return $this->makeLink(
          $this->t('Mis articulos'),
          'jaraba_content_hub.my_articles',
          'content', 'file-text', 'primary',
          ['description' => $this->t('Contenido publicado en el hub')],
        );
      }
    }
    catch (\Throwable) {
      // Modulo no instalado o entity no disponible.
    }
    return NULL;
  }

}
