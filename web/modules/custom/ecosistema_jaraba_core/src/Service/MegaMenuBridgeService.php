<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Psr\Log\LoggerInterface;

/**
 * Puente entre SiteMenuItem entities y el catálogo de verticales.
 *
 * Fuente única de verdad (SSOT) para la estructura de verticales del SaaS.
 * Consumidores:
 * - Mega menú del header (_header-classic.html.twig)
 * - Email de bienvenida (user_welcome en hook_mail)
 * - Quiz de recomendación de vertical.
 *
 * Prioridad de datos:
 * 1. SiteMenuItems configurados en DB → datos dinámicos editables desde admin.
 * 2. Fallback → getVerticalCatalog() (array PHP canónico).
 */
class MegaMenuBridgeService {

  use StringTranslationTrait;

  /**
   * Machine name del menú para el mega menú del SaaS principal.
   */
  private const MEGA_MENU_NAME = 'mega_menu_soluciones';

  /**
   * Map de nombres de color de marca a hex para contextos inline (emails).
   *
   * @var array<string, string>
   */
  public const COLOR_MAP = [
    'verde-innovacion' => '#00A9A5',
    'naranja-impulso' => '#FF8C42',
    'azul-corporativo' => '#233D63',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtener las columnas del mega menú.
   *
   * Intenta cargar desde SiteMenuItem entities (DB). Si no hay menú
   * configurado, devuelve el catálogo canónico de fallback.
   *
   * URLs devueltas son rutas relativas SIN prefijo de idioma (ej: /empleabilidad).
   * El consumidor es responsable de añadir el prefijo si lo necesita.
   *
   * @return array
   *   Array de columnas para el mega menú. Nunca vacío.
   */
  public function getMegaMenuColumns(): array {
    $dbColumns = $this->loadFromDatabase();
    return $dbColumns ?? $this->getVerticalCatalog();
  }

  /**
   * Catálogo canónico de verticales del SaaS.
   *
   * SSOT para las 10 verticales organizadas en 4 categorías.
   * Cualquier cambio aquí se refleja automáticamente en:
   * - Mega menú del header (via getMegaMenuColumns())
   * - Email de bienvenida (via getVerticalCatalog())
   * - Cualquier otro consumidor futuro.
   *
   * @return array<int, array<string, mixed>>
   *   Array de columnas para el mega menú. Cada columna tiene:
   *   title (string), has_promo (bool), items (array de verticales).
   */
  public function getVerticalCatalog(): array {
    return [
      [
        'title' => (string) $this->t('Para Personas'),
        'has_promo' => FALSE,
        'items' => [
          [
            'title' => (string) $this->t('Empleabilidad'),
            'subtitle' => (string) $this->t('Impulsa tu carrera con IA'),
            'icon_cat' => 'verticals',
            'icon_name' => 'empleabilidad',
            'color' => 'verde-innovacion',
            'url' => '/empleabilidad',
          ],
          [
            'title' => (string) $this->t('Formación'),
            'subtitle' => (string) $this->t('Cursos y certificaciones'),
            'icon_cat' => 'verticals',
            'icon_name' => 'formacion',
            'color' => 'verde-innovacion',
            'url' => '/formacion',
          ],
        ],
      ],
      [
        'title' => (string) $this->t('Para Empresas'),
        'has_promo' => FALSE,
        'items' => [
          [
            'title' => (string) $this->t('Emprendimiento'),
            'subtitle' => (string) $this->t('De la idea al negocio'),
            'icon_cat' => 'verticals',
            'icon_name' => 'emprendimiento',
            'color' => 'naranja-impulso',
            'url' => '/emprendimiento',
          ],
          [
            'title' => (string) $this->t('ComercioConecta'),
            'subtitle' => (string) $this->t('Digitaliza tu comercio'),
            'icon_cat' => 'verticals',
            'icon_name' => 'comercioconecta',
            'color' => 'naranja-impulso',
            'url' => '/comercioconecta',
          ],
          [
            'title' => (string) $this->t('AgroConecta'),
            'subtitle' => (string) $this->t('Marketplace agroalimentario'),
            'icon_cat' => 'verticals',
            'icon_name' => 'agroconecta',
            'color' => 'naranja-impulso',
            'url' => '/agroconecta',
          ],
          [
            'title' => (string) $this->t('ServiciosConecta'),
            'subtitle' => (string) $this->t('Gestión de servicios'),
            'icon_cat' => 'verticals',
            'icon_name' => 'serviciosconecta',
            'color' => 'naranja-impulso',
            'url' => '/serviciosconecta',
          ],
        ],
      ],
      [
        'title' => (string) $this->t('Para Profesionales'),
        'has_promo' => FALSE,
        'items' => [
          [
            'title' => (string) $this->t('JarabaLex'),
            'subtitle' => (string) $this->t('Inteligencia legal con IA'),
            'icon_cat' => 'verticals',
            'icon_name' => 'jarabalex',
            'color' => 'azul-corporativo',
            'url' => '/jarabalex',
          ],
          [
            'title' => (string) $this->t('Content Hub'),
            'subtitle' => (string) $this->t('Crea y publica contenido'),
            'icon_cat' => 'content',
            'icon_name' => 'edit',
            'color' => 'azul-corporativo',
            'url' => '/content-hub',
          ],
        ],
      ],
      [
        'title' => (string) $this->t('Para Instituciones'),
        'has_promo' => TRUE,
        'items' => [
          [
            'title' => (string) $this->t('Desarrollo Local'),
            'subtitle' => (string) $this->t('Digitaliza tu territorio'),
            'icon_cat' => 'verticals',
            'icon_name' => 'desarrollo-local',
            'color' => 'verde-innovacion',
            'url' => '/instituciones',
          ],
          [
            'title' => (string) $this->t('Andalucía +ei'),
            'subtitle' => (string) $this->t('Empleo e innovación regional'),
            'icon_cat' => 'verticals',
            'icon_name' => 'andalucia-ei',
            'color' => 'verde-innovacion',
            'url' => '/andalucia-ei',
          ],
        ],
      ],
    ];
  }

  /**
   * Estructura de navegación corporativa para PED (meta-sitio group_id 7).
   *
   * PED es el sitio corporativo de la empresa (plataformadeecosistemas.es),
   * NO el SaaS de producto. Su mega menú refleja la estructura institucional:
   * quiénes somos, qué hacemos, con quién trabajamos, qué hemos logrado.
   *
   * Usa el mismo formato de columnas que getVerticalCatalog() para reutilizar
   * el componente visual _header-classic.html.twig sin modificaciones.
   *
   * URLs relativas SIN prefijo de idioma (el consumidor lo añade).
   *
   * @return array<int, array<string, mixed>>
   *   Array de 4 columnas para el mega menú corporativo PED.
   */
  public function getPedCorporateColumns(): array {
    return [
      [
        'title' => (string) $this->t('Empresa'),
        'has_promo' => FALSE,
        'items' => [
          [
            'title' => (string) $this->t('Sobre nosotros'),
            'subtitle' => (string) $this->t('Misión, visión y valores'),
            'icon_cat' => 'business',
            'icon_name' => 'building',
            'color' => 'azul-corporativo',
            'url' => '/sobre-nosotros',
          ],
          [
            'title' => (string) $this->t('Equipo directivo'),
            'subtitle' => (string) $this->t('Las personas detrás del proyecto'),
            'icon_cat' => 'users',
            'icon_name' => 'group',
            'color' => 'azul-corporativo',
            'url' => '/equipo',
          ],
          [
            'title' => (string) $this->t('Transparencia'),
            'subtitle' => (string) $this->t('Gobernanza y rendición de cuentas'),
            'icon_cat' => 'compliance',
            'icon_name' => 'shield',
            'color' => 'azul-corporativo',
            'url' => '/transparencia',
          ],
          [
            'title' => (string) $this->t('Certificaciones'),
            'subtitle' => (string) $this->t('Estándares y acreditaciones'),
            'icon_cat' => 'compliance',
            'icon_name' => 'certificate',
            'color' => 'azul-corporativo',
            'url' => '/certificaciones',
          ],
        ],
      ],
      [
        'title' => (string) $this->t('Plataforma'),
        'has_promo' => FALSE,
        'items' => [
          [
            'title' => (string) $this->t('Ecosistema'),
            'subtitle' => (string) $this->t('10 verticales en una sola plataforma'),
            'icon_cat' => 'verticals',
            'icon_name' => 'ecosystem',
            'color' => 'verde-innovacion',
            'url' => '/ecosistema',
          ],
          [
            'title' => (string) $this->t('Impacto'),
            'subtitle' => (string) $this->t('Resultados medibles y verificables'),
            'icon_cat' => 'analytics',
            'icon_name' => 'target',
            'color' => 'verde-innovacion',
            'url' => '/impacto',
          ],
        ],
      ],
      [
        'title' => (string) $this->t('Colaboraciones'),
        'has_promo' => FALSE,
        'items' => [
          [
            'title' => (string) $this->t('Partners'),
            'subtitle' => (string) $this->t('Colaboradores estratégicos'),
            'icon_cat' => 'business',
            'icon_name' => 'handshake',
            'color' => 'naranja-impulso',
            'url' => '/partners',
          ],
          [
            'title' => (string) $this->t('Prensa'),
            'subtitle' => (string) $this->t('Noticias y apariciones en medios'),
            'icon_cat' => 'business',
            'icon_name' => 'megaphone',
            'color' => 'naranja-impulso',
            'url' => '/prensa',
          ],
        ],
      ],
      [
        'title' => (string) $this->t('Resultados'),
        'has_promo' => TRUE,
        'items' => [
          [
            'title' => (string) $this->t('Casos de Éxito'),
            'subtitle' => (string) $this->t('Historias reales de transformación'),
            'icon_cat' => 'achievement',
            'icon_name' => 'trophy',
            'color' => 'naranja-impulso',
            'url' => '/casos-de-exito',
          ],
          [
            'title' => (string) $this->t('Contacto'),
            'subtitle' => (string) $this->t('Habla con nuestro equipo'),
            'icon_cat' => 'ui',
            'icon_name' => 'mail',
            'color' => 'azul-corporativo',
            'url' => '/contacto',
          ],
        ],
      ],
    ];
  }

  /**
   * Resuelve un nombre de color de marca a su valor hex.
   *
   * @param string $colorName
   *   Nombre de marca (ej: 'verde-innovacion').
   *
   * @return string
   *   Valor hex (ej: '#00A9A5'). Fallback: azul-corporativo.
   */
  public static function resolveColorHex(string $colorName): string {
    return self::COLOR_MAP[$colorName] ?? self::COLOR_MAP['azul-corporativo'];
  }

  /**
   * Carga columnas del mega menú desde SiteMenuItem entities en DB.
   *
   * @return array<int, array<string, mixed>>|null
   *   Array de columnas si hay menú configurado, NULL si no.
   */
  protected function loadFromDatabase(): ?array {
    try {
      if (!$this->entityTypeManager->hasDefinition('site_menu_item')) {
        return NULL;
      }

      $storage = $this->entityTypeManager->getStorage('site_menu_item');
      $menuStorage = $this->entityTypeManager->getStorage('site_menu');
      $menus = $menuStorage->loadByProperties([
        'machine_name' => self::MEGA_MENU_NAME,
      ]);

      if (empty($menus)) {
        return NULL;
      }

      $menu = reset($menus);
      $menuId = $menu->id();

      $items = $storage->loadByProperties([
        'menu_id' => $menuId,
        'parent_id' => NULL,
        'status' => TRUE,
      ]);

      if (empty($items)) {
        return NULL;
      }

      uasort($items, fn($a, $b) => ($a->get('weight')->value ?? 0) <=> ($b->get('weight')->value ?? 0));

      $columns = [];
      foreach ($items as $item) {
        $column = [
          'title' => $item->get('title')->value ?? '',
          'items' => [],
          'has_promo' => FALSE,
        ];

        $children = $storage->loadByProperties([
          'menu_id' => $menuId,
          'parent_id' => $item->id(),
          'status' => TRUE,
        ]);

        uasort($children, fn($a, $b) => ($a->get('weight')->value ?? 0) <=> ($b->get('weight')->value ?? 0));

        foreach ($children as $child) {
          $itemType = $child->get('item_type')->value ?? 'link';

          if ($itemType === 'divider') {
            $column['has_promo'] = TRUE;
            continue;
          }

          $megaContent = $child->getMegaContent();
          $column['items'][] = [
            'title' => $child->get('title')->value ?? '',
            'subtitle' => $megaContent['subtitle'] ?? '',
            'icon_cat' => $megaContent['icon_cat'] ?? 'verticals',
            'icon_name' => $megaContent['icon_name'] ?? $child->get('icon')->value ?? '',
            'color' => $megaContent['color'] ?? 'azul-corporativo',
            'url' => $child->get('url')->value ?? '#',
          ];
        }

        $columns[] = $column;
      }

      return !empty($columns) ? $columns : NULL;
    }
    catch (\Throwable $e) {
      $this->logger->warning('MegaMenuBridge error: @e', ['@e' => $e->getMessage()]);
      return NULL;
    }
  }

}
