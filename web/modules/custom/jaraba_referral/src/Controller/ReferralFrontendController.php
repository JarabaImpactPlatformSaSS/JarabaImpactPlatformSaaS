<?php

namespace Drupal\jaraba_referral\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_referral\Service\ReferralManagerService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador frontend del programa de referidos.
 *
 * ESTRUCTURA:
 * Controlador que renderiza las páginas frontend del programa de referidos:
 * dashboard personal (/referidos), mi código (/referidos/mi-codigo),
 * y landing de invitación (/ref/{code}).
 *
 * LÓGICA:
 * Todas las páginas son Zero Region (sin bloques de Drupal), renderizan
 * templates Twig propios con datos del ReferralManagerService.
 * El dashboard muestra estadísticas personales y listado de referidos.
 * Mi código genera/muestra el código y permite compartir vía URL.
 * La landing es pública y presenta la propuesta de valor.
 *
 * RELACIONES:
 * - ReferralFrontendController -> ReferralManagerService (consume)
 * - ReferralFrontendController -> jaraba_referral.routing.yml (definido en)
 * - ReferralFrontendController -> templates/ (renderiza)
 */
class ReferralFrontendController extends ControllerBase {

  protected ReferralManagerService $referralManager;

  public function __construct(
    ReferralManagerService $referral_manager,
    AccountProxyInterface $current_user,
  ) {
    $this->referralManager = $referral_manager;
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_referral.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * Dashboard personal de referidos.
   */
  public function dashboard(): array {
    $uid = (int) $this->currentUser->id();
    $referrals = $this->referralManager->getMyReferrals($uid);

    // Preparar datos para template.
    $referral_items = [];
    foreach ($referrals as $referral) {
      $referred = $referral->get('referred_uid')->entity;
      $referral_items[] = [
        'code' => $referral->get('referral_code')->value,
        'status' => $referral->get('status')->value,
        'referred_name' => $referred ? $referred->getDisplayName() : NULL,
        'reward_type' => $referral->get('reward_type')->value,
        'reward_value' => $referral->get('reward_value')->value,
        'created' => $referral->get('created')->value,
      ];
    }

    // Calcular KPIs personales.
    $total = count($referral_items);
    $confirmed = count(array_filter($referral_items, fn($r) => $r['status'] === 'confirmed'));
    $rewarded = count(array_filter($referral_items, fn($r) => $r['status'] === 'rewarded'));
    $total_rewards = array_sum(array_map(fn($r) => (float) $r['reward_value'],
      array_filter($referral_items, fn($r) => $r['status'] === 'rewarded')
    ));

    return [
      '#theme' => 'jaraba_referral_dashboard',
      '#kpis' => [
        'total_referrals' => $total,
        'confirmed' => $confirmed,
        'rewarded' => $rewarded,
        'total_rewards' => $total_rewards,
        'conversion_rate' => $total > 0 ? round((($confirmed + $rewarded) / $total) * 100, 1) : 0,
      ],
      '#referrals' => $referral_items,
      '#attached' => [
        'library' => ['jaraba_referral/referral-dashboard'],
      ],
    ];
  }

  /**
   * Página de Mi Código de referido.
   */
  public function myCode(): array {
    $uid = (int) $this->currentUser->id();

    try {
      $referral = $this->referralManager->generateCode($uid);
      $code = $referral->get('referral_code')->value;
    }
    catch (\Exception $e) {
      $code = NULL;
    }

    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    $share_url = $code ? $base_url . '/ref/' . $code : NULL;

    return [
      '#theme' => 'jaraba_referral_my_code',
      '#code' => $code,
      '#share_url' => $share_url,
      '#user_name' => $this->currentUser->getDisplayName(),
      '#attached' => [
        'library' => ['jaraba_referral/referral-share'],
      ],
    ];
  }

  /**
   * Landing pública de invitación por referido.
   */
  public function referralLanding(string $code): array {
    $storage = $this->entityTypeManager()->getStorage('referral');
    $referrals = $storage->loadByProperties([
      'referral_code' => $code,
    ]);

    $valid = !empty($referrals);
    $referrer_name = NULL;

    if ($valid) {
      $referral = reset($referrals);
      $referrer = $referral->get('referrer_uid')->entity;
      $referrer_name = $referrer ? $referrer->getDisplayName() : $this->t('Un usuario');
    }

    return [
      '#theme' => 'jaraba_referral_landing',
      '#code' => $code,
      '#valid' => $valid,
      '#referrer_name' => $referrer_name,
      '#attached' => [
        'library' => ['jaraba_referral/referral-landing'],
      ],
    ];
  }

}
