<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\SetupWizard;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Auto-complete step: Vertical configured — muestra CUÁL vertical.
 *
 * GAP-WC-008: Zeigarnik effect — pre-load progress bar at ~25%.
 * This step is always complete since the vertical was chosen at registration.
 * It appears second in every wizard (weight: -10) as a visual anchor.
 *
 * MEJORA UX CLASE MUNDIAL: Resuelve dinámicamente el vertical del usuario
 * via AvatarWizardBridgeService (lazy, @?) para mostrar "ComercioConecta
 * configurado" en vez del genérico "Vertical configurado". Incluye icono
 * específico del vertical (ICON-CONVENTION-001, ICON-DUOTONE-001).
 *
 * Registered in ALL wizards via SetupWizardRegistry::GLOBAL_WIZARD_ID.
 */
class AutoCompleteVerticalStep implements SetupWizardStepInterface {

  use StringTranslationTrait;

  /**
   * Mapa vertical → label humano.
   *
   * Debe coincidir con los labels de VERTICAL-CANONICAL-001.
   */
  protected const VERTICAL_LABELS = [
    'empleabilidad' => 'Empleabilidad',
    'emprendimiento' => 'Emprendimiento',
    'comercioconecta' => 'ComercioConecta',
    'agroconecta' => 'AgroConecta',
    'jarabalex' => 'JarabaLex',
    'serviciosconecta' => 'ServiciosConecta',
    'formacion' => 'Formación',
    'andalucia_ei' => 'Andalucía +ei',
    'jaraba_content_hub' => 'Content Hub',
    'demo' => 'Demo',
  ];

  /**
   * Mapa vertical → icono SVG en images/icons/verticals/.
   *
   * Cada vertical tiene su icono duotone dedicado (ICON-CONVENTION-001).
   */
  protected const VERTICAL_ICONS = [
    'empleabilidad' => 'empleabilidad',
    'emprendimiento' => 'emprendimiento',
    'comercioconecta' => 'comercioconecta',
    'agroconecta' => 'agroconecta',
    'jarabalex' => 'jarabalex',
    'serviciosconecta' => 'serviciosconecta',
    'formacion' => 'formacion',
    'andalucia_ei' => 'andalucia-ei',
    'jaraba_content_hub' => 'info',
    'demo' => 'rocket',
  ];

  /**
   * Constructs an AutoCompleteVerticalStep.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user proxy para generar route params del dashboard.
   */
  public function __construct(
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return '__global__.vertical_configurado';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardId(): string {
    return AutoCompleteAccountStep::GLOBAL_WIZARD_ID;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    $vertical = $this->resolveVerticalLabel();
    if ($vertical) {
      return $this->t('@vertical configurado', ['@vertical' => $vertical]);
    }
    return $this->t('Vertical configurado');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    $vertical = $this->resolveVerticalLabel();
    if ($vertical) {
      return $this->t('Tu vertical @vertical ha sido asignado a tu cuenta.', [
        '@vertical' => $vertical,
      ]);
    }
    return $this->t('Tu vertical ha sido asignado a tu cuenta.');
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return -10;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcon(): array {
    $verticalKey = $this->resolveVerticalKey();
    $iconName = self::VERTICAL_ICONS[$verticalKey] ?? 'ecosystem';

    return [
      'category' => 'verticals',
      'name' => $iconName,
      'variant' => 'duotone',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getRoute(): string {
    return 'ecosistema_jaraba_core.tenant_self_service.plan';
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
   *
   * Always complete — vertical was chosen during registration.
   */
  public function isComplete(int $tenantId): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletionData(int $tenantId): array {
    $vertical = $this->resolveVerticalLabel();
    return [
      'label' => $vertical
        ? $this->t('@vertical activo', ['@vertical' => $vertical])
        : $this->t('Completado'),
      'count' => 1,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isOptional(): bool {
    return FALSE;
  }

  /**
   * Resuelve la clave del vertical del usuario actual (lazy, fault-tolerant).
   *
   * Usa AvatarWizardBridgeService via \Drupal::hasService() para evitar
   * dependencia circular — este step es un servicio tagged recolectado
   * por el mismo bridge que queremos consultar.
   * PRESAVE-RESILIENCE-001: hasService() + try-catch.
   *
   * @return string|null
   *   Clave canónica del vertical (ej: 'comercioconecta'), o NULL.
   */
  protected function resolveVerticalKey(): ?string {
    try {
      if (\Drupal::hasService('ecosistema_jaraba_core.avatar_wizard_bridge')) {
        $bridge = \Drupal::service('ecosistema_jaraba_core.avatar_wizard_bridge');
        $mapping = $bridge->resolveForCurrentUser();
        if ($mapping && $mapping->vertical) {
          return $mapping->vertical;
        }
      }
    }
    catch (\Throwable) {
      // Graceful degradation: mostrar genérico.
    }
    return NULL;
  }

  /**
   * Resuelve el label humano del vertical del usuario actual.
   *
   * @return string|null
   *   Label legible (ej: 'ComercioConecta'), o NULL.
   */
  protected function resolveVerticalLabel(): ?string {
    $key = $this->resolveVerticalKey();
    if ($key && isset(self::VERTICAL_LABELS[$key])) {
      return self::VERTICAL_LABELS[$key];
    }
    return NULL;
  }

}
