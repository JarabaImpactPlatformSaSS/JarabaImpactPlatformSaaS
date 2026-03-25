<?php

declare(strict_types=1);

namespace Drupal\jaraba_theming\Entity;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List builder para TenantThemeConfig con galería visual de design tokens.
 *
 * Muestra cada configuración con:
 * - Paleta de colores (swatch visual primary/secondary/accent)
 * - Nombre vinculado, tenant asociado, vertical
 * - Tipografía (headings / body)
 * - Variantes de layout (header, footer)
 * - Estado activo/inactivo con badge de color
 * - Fecha de última modificación.
 */
class TenantThemeConfigListBuilder extends EntityListBuilder {

  /**
   * El servicio de formateo de fechas.
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * Tenant context service.
   */
  protected ?TenantContextService $tenantContext = NULL;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    DateFormatterInterface $date_formatter,
    ?TenantContextService $tenantContext = NULL,
  ) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
    $this->tenantContext = $tenantContext;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    $instance = new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
    );
    if ($container->has('ecosistema_jaraba_core.tenant_context')) {
      $instance->tenantContext = $container->get('ecosistema_jaraba_core.tenant_context');
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header = [];
    $header['color_preview'] = [
      'data' => $this->t('Paleta'),
      'class' => ['theme-config-color-preview'],
    ];
    $header['name'] = $this->t('Nombre');
    $header['tenant'] = $this->t('Tenant');
    $header['vertical'] = [
      'data' => $this->t('Vertical'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['fonts'] = [
      'data' => $this->t('Tipografía'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['layout'] = [
      'data' => $this->t('Layout'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['is_active'] = $this->t('Estado');
    $header['changed'] = [
      'data' => $this->t('Modificado'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\jaraba_theming\Entity\TenantThemeConfig $entity */
    $row = [];

    // Paleta: swatches circulares primary/secondary/accent.
    $swatches = '';
    foreach (['color_primary', 'color_secondary', 'color_accent'] as $field) {
      $hex = (string) $entity->get($field)->value;
      if ($hex !== '') {
        $hex = htmlspecialchars($hex, ENT_QUOTES);
        $label = str_replace('color_', '', $field);
        $swatches .= "<span style=\"display:inline-block;width:20px;height:20px;border-radius:50%;background:{$hex};border:1px solid rgba(0,0,0,0.1);margin-right:4px;vertical-align:middle;\" title=\"{$label}: {$hex}\"></span>";
      }
    }
    $row['color_preview'] = [
      'data' => ['#markup' => $swatches !== '' ? $swatches : '<span style="color:#9CA3AF;">—</span>'],
    ];

    // Nombre con enlace a edición.
    $entityLabel = $entity->label();
    $row['name'] = $entity->toLink($entityLabel !== NULL && $entityLabel !== '' ? $entityLabel : $this->t('(sin nombre)'));

    // Tenant asociado via entity_reference.
    $tenantRef = $entity->get('tenant_id');
    $tenantId = $tenantRef->target_id;
    if ($tenantId !== NULL && $tenantRef->entity !== NULL) {
      $row['tenant'] = $tenantRef->entity->label() ?? '—';
    }
    else {
      $row['tenant'] = ['data' => ['#markup' => '<span style="color:#9CA3AF;">' . $this->t('Sin asignar') . '</span>']];
    }

    // Vertical con etiqueta legible.
    $vertical_value = (string) $entity->get('vertical')->value;
    $allowed_values = $entity->getFieldDefinition('vertical')
      ->getSetting('allowed_values');
    $row['vertical'] = is_array($allowed_values) && isset($allowed_values[$vertical_value])
      ? $allowed_values[$vertical_value]
      : ($vertical_value !== '' ? $vertical_value : '—');

    // Tipografía: heading / body.
    $fontHeading = (string) ($entity->get('font_headings')->value ?? '');
    $fontBody = (string) ($entity->get('font_body')->value ?? '');
    $fontDisplay = $fontHeading;
    if ($fontBody !== '' && $fontBody !== $fontHeading) {
      $fontDisplay .= ' / ' . $fontBody;
    }
    $row['fonts'] = $fontDisplay !== '' ? $fontDisplay : '—';

    // Layout: header + footer variants como tags.
    $headerVariant = (string) ($entity->get('header_variant')->value ?? '');
    $footerVariant = (string) ($entity->get('footer_variant')->value ?? '');
    $layoutTags = '';
    if ($headerVariant !== '') {
      $hv = htmlspecialchars(ucfirst($headerVariant), ENT_QUOTES);
      $layoutTags .= "<span class=\"theme-config-tag\">{$hv}</span>";
    }
    if ($footerVariant !== '') {
      $fv = htmlspecialchars(ucfirst($footerVariant), ENT_QUOTES);
      $layoutTags .= "<span class=\"theme-config-tag\">{$fv}</span>";
    }
    $row['layout'] = [
      'data' => ['#markup' => $layoutTags !== '' ? $layoutTags : '—'],
    ];

    // Estado: badge con color.
    $isActive = (bool) $entity->get('is_active')->value;
    $row['is_active'] = ['data' => $this->getStatusBadge($isActive)];

    // Fecha de última modificación.
    $changed = $entity->get('changed')->value;
    $row['changed'] = $changed !== NULL
      ? $this->dateFormatter->format((int) $changed, 'short')
      : '—';

    return $row + parent::buildRow($entity);
  }

  /**
   * Genera un badge de estado con color.
   *
   * @param bool $isActive
   *   Si la configuración está activa.
   *
   * @return array
   *   Render array del badge.
   */
  protected function getStatusBadge(bool $isActive): array {
    $label = $isActive
      ? ['text' => $this->t('Activo'), 'color' => 'green']
      : ['text' => $this->t('Inactivo'), 'color' => 'gray'];

    return [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#value' => $label['text'],
      '#attributes' => [
        'class' => ['badge', 'badge--' . $label['color']],
        'style' => 'padding: 2px 8px; border-radius: 4px; font-size: 12px;',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity): array {
    $operations = parent::getDefaultOperations($entity);

    // Vista previa del tema.
    if ($entity->access('view')) {
      $operations['preview'] = [
        'title' => $this->t('Vista previa'),
        'weight' => 15,
        'url' => Url::fromRoute('jaraba_theming.preview', [], [
          'query' => ['theme_config' => $entity->id()],
        ]),
      ];
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds(): array {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort('is_active', 'DESC')
      ->sort('changed', 'DESC');

    // Filtrar por tenant para no-superadmins.
    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('administer theme config')) {
      if ($this->tenantContext !== NULL) {
        $tenantId = $this->tenantContext->getCurrentTenantId();
        if ($tenantId !== NULL && $tenantId !== 0) {
          $query->condition('tenant_id', $tenantId);
        }
      }
    }

    if ($this->limit !== FALSE && $this->limit > 0) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    // Descripción de la página.
    $build['description'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Configuraciones de Design Tokens por tenant. Cada configuración activa sobreescribe la paleta, tipografía y layout del tema base para su tenant.'),
      '#attributes' => [
        'style' => 'color: #6B7280; margin-bottom: 1rem;',
      ],
      '#weight' => -10,
    ];

    // Estado vacío con enlace de creación.
    $build['table']['#empty'] = $this->t('No hay configuraciones de tema. <a href=":url">Crear la primera configuración</a>.', [
      ':url' => Url::fromRoute('entity.tenant_theme_config.add_form')->toString(),
    ]);

    // Estilos inline para badges y tags.
    $build['#attached']['html_head'][] = [
      [
        '#type' => 'html_tag',
        '#tag' => 'style',
        '#value' => '
          .badge--green { background-color: #10B981; color: white; }
          .badge--gray { background-color: #6B7280; color: white; }
          .theme-config-tag {
            display: inline-block;
            padding: 2px 8px;
            background: #F1F5F9;
            border-radius: 4px;
            font-size: 11px;
            margin-right: 4px;
            color: #475569;
          }
        ',
      ],
      'theme_config_list_styles',
    ];

    return $build;
  }

}
