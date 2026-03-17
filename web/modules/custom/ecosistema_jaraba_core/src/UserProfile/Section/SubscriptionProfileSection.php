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
   */
  public function getIcon(): array {
    return ['category' => 'ui', 'name' => 'credit-card'];
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
   */
  public function getLinks(int $uid): array {
    $links = [];

    // Link a la pricing page.
    $link = $this->makeLink(
      $this->t('Ver planes disponibles'),
      'ecosistema_jaraba_core.pricing.page',
      'ui',
      'tag',
      'impulse',
    );
    if ($link) {
      $links[] = $link;
    }

    // Link al dashboard financiero (si existe).
    $link = $this->makeLink(
      $this->t('Mi facturación'),
      'jaraba_billing.financial_dashboard',
      'ui',
      'receipt',
      'corporate',
    );
    if ($link) {
      $links[] = $link;
    }

    return $links;
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
