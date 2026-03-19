<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\SetupWizard;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\ecosistema_jaraba_core\SetupWizard\SetupWizardStepInterface;

/**
 * Setup Wizard step: Personalizar contenido.
 *
 * Tercer paso del wizard del Page Builder.
 * Se completa cuando alguna PageContent del tenant tiene canvas_data con
 * al menos 3 componentes, indicando personalizacion real (no solo plantilla
 * sin modificar).
 *
 * SETUP-WIZARD-DAILY-001: Patron premium transversal.
 * TENANT-001: Filtra por tenant_id.
 */
class PersonalizarContenidoStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  /**
   * Numero minimo de componentes para considerar personalizacion real.
   */
  protected const MIN_COMPONENTS = 3;

  public function __construct(
    protected ?EntityTypeManagerInterface $entityTypeManager = NULL,
    protected ?TenantContextService $tenantContext = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'page_builder.personalizar_contenido';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return 'page_builder';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->t('Personaliza tu contenido');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Anade tu logo, textos y colores de marca usando el editor visual');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return 30;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    return [
      'category' => 'ui',
      'name' => 'sparkles',
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'jaraba_page_builder.my_pages';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function useSlidePanel(): bool {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSlidePanelSize(): string {
    return 'medium';
  }

  /**
   * {@inheritdoc}
   */
  public function isComplete(int $tenantId): bool {
    if ($this->entityTypeManager === NULL) {
      return FALSE;
    }

    try {
      $pages = $this->entityTypeManager
        ->getStorage('page_content')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('canvas_data', '', '<>')
        ->execute();

      if ($pages === []) {
        return FALSE;
      }

      /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
      $entities = $this->entityTypeManager
        ->getStorage('page_content')
        ->loadMultiple($pages);

      foreach ($entities as $entity) {
        $canvasData = $entity->get('canvas_data')->value ?? '';
        if ($canvasData === '') {
          continue;
        }
        $data = json_decode($canvasData, TRUE);
        if (is_array($data) && count($data['components'] ?? []) >= self::MIN_COMPONENTS) {
          return TRUE;
        }
      }

      return FALSE;
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $complete = $this->isComplete($tenantId);
    return [
      'label' => $complete
        ? $this->t('Contenido personalizado')
        : $this->t('Pendiente'),
      'count' => $complete ? 1 : 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

}
