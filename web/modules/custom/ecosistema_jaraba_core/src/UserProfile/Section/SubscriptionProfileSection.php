<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\UserProfile\Section;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\SubscriptionContextService;
use Drupal\ecosistema_jaraba_core\UserProfile\AbstractUserProfileSection;

/**
 * Sección de perfil: Tarjeta de suscripción + Features + Uso.
 *
 * Muestra el plan actual del usuario, features incluidos/bloqueados,
 * barras de uso vs límites, y CTA de upgrade. Diseñada para PLG
 * (Product-Led Growth) — el usuario siempre sabe qué tiene y qué le falta.
 *
 * PLG-UPGRADE-UI-001 §3.1
 * Patrón: UserProfileSectionRegistry (tagged service + CompilerPass).
 * OPTIONAL-CROSSMODULE-001: SubscriptionContextService via @?.
 */
class SubscriptionProfileSection extends AbstractUserProfileSection {

  public function __construct(
    AccountProxyInterface $currentUser,
    protected ?SubscriptionContextService $subscriptionContext,
  ) {
    parent::__construct($currentUser);
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'subscription_upgrade';
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(int $uid): string {
    return (string) $this->t('Mi suscripción');
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtitle(int $uid): string {
    if (!$this->subscriptionContext) {
      return '';
    }
    $context = $this->subscriptionContext->getContextForUser($uid);
    if (empty($context['plan'])) {
      return (string) $this->t('Elige un plan para desbloquear funcionalidades');
    }
    return (string) $this->t('Plan @plan — @status', [
      '@plan' => $context['plan']['tier_label'],
      '@status' => $context['subscription']['status_label'],
    ]);
  }

  /**
   * {@inheritdoc}
   *
   * Usa finance/plan-upgrade (capas con flecha up) — icono especifico
   * para suscripcion/plan. NUNCA ui/credit-card (no existe → 📌).
   */
  public function getIcon(): array {
    return ['category' => 'finance', 'name' => 'plan-upgrade'];
  }

  /**
   * {@inheritdoc}
   */
  public function getColor(): string {
    return 'impulse';
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    // Peso 5: aparece ANTES del professional_profile (peso 10).
    return 5;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(int $uid): bool {
    // Mostrar siempre para usuarios autenticados (incluso sin plan = CTA de registro).
    return $this->currentUser->isAuthenticated()
      && (int) $this->currentUser->id() === $uid;
  }

  /**
   * {@inheritdoc}
   *
   * Hub de conversion PLG: upgrade, add-ons, servicios profesionales.
   * El perfil es el motor de conversion — el usuario ve que tiene,
   * que le falta y como escalar.
   *
   * Iconos: SOLO categorias con SVGs existentes verificados.
   * Rutas: SOLO tenant-facing o publicas (NUNCA /admin/*).
   */
  public function getLinks(int $uid): array {
    $hasPlan = $this->hasPaidPlan($uid);

    return array_values(array_filter([
      // CTA principal contextual:
      // Con plan → /tenant/change-plan (comparador con plan actual marcado).
      // Sin plan → /planes (pricing page con checkout).
      $hasPlan
        ? $this->makeLink(
            $this->t('Cambiar o mejorar plan'),
            'ecosistema_jaraba_core.tenant.change_plan',
            'finance', 'plan-upgrade', 'impulse',
            ['description' => $this->t('Compara planes y cambia el tuyo')],
          )
        : $this->makeLink(
            $this->t('Elegir plan'),
            'ecosistema_jaraba_core.pricing.page',
            'finance', 'plan-upgrade', 'impulse',
            ['description' => $this->t('Compara planes y desbloquea funcionalidades')],
          ),
      // Add-ons y complementos (siempre visible — conversion).
      $this->makeLink(
        $this->t('Complementos y add-ons'),
        'jaraba_addons.catalog',
        'commerce', 'tag', 'impulse',
        ['description' => $this->t('Amplia las capacidades de tu plan')],
      ),
      // Servicios profesionales: mentorias, workshops.
      $this->makeLink(
        $this->t('Servicios profesionales'),
        'jaraba_mentoring.service_catalog',
        'users', 'users', 'impulse',
        ['description' => $this->t('Mentorias, workshops y programas con expertos')],
      ),
    ]));
  }

  /**
   * Determina si el usuario tiene un plan paid (no free).
   */
  private function hasPaidPlan(int $uid): bool {
    if (!$this->subscriptionContext) {
      return FALSE;
    }
    try {
      $context = $this->subscriptionContext->getContextForUser($uid);
      return !empty($context['plan']) && !($context['plan']['is_free'] ?? TRUE);
    }
    catch (\Throwable) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraData(int $uid): array {
    if (!$this->subscriptionContext) {
      return [];
    }

    $context = $this->subscriptionContext->getContextForUser($uid);
    if (empty($context)) {
      return [];
    }

    return [
      'subscription_widget' => $context,
    ];
  }

}
