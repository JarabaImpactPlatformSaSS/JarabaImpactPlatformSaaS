<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials_emprendimiento\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_credentials_emprendimiento\Service\EmprendimientoCredentialService;
use Drupal\jaraba_credentials_emprendimiento\Service\EmprendimientoExpertiseService;
use Drupal\jaraba_credentials_emprendimiento\Service\EmprendimientoJourneyTracker;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * API controller para credenciales de emprendimiento.
 */
class EmprendimientoCredentialsApiController extends ControllerBase {

  protected EmprendimientoCredentialService $credentialService;
  protected EmprendimientoExpertiseService $expertiseService;
  protected EmprendimientoJourneyTracker $journeyTracker;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = new static();
    $instance->credentialService = $container->get('jaraba_credentials_emprendimiento.credential');
    $instance->expertiseService = $container->get('jaraba_credentials_emprendimiento.expertise');
    $instance->journeyTracker = $container->get('jaraba_credentials_emprendimiento.journey_tracker');
    return $instance;
  }

  /**
   * Catálogo de badges disponibles de emprendimiento.
   */
  public function catalog(): JsonResponse {
    $templates = [];
    foreach (EmprendimientoCredentialService::TEMPLATES as $machineName) {
      $loaded = $this->entityTypeManager()->getStorage('credential_template')
        ->loadByProperties(['machine_name' => $machineName]);
      if (!empty($loaded)) {
        $template = reset($loaded);
        $templates[] = [
          'id' => $template->id(),
          'machine_name' => $machineName,
          'name' => $template->get('name')->value,
          'description' => $template->get('description')->value ?? '',
          'credential_type' => $template->get('credential_type')->value ?? '',
          'level' => $template->get('level')->value ?? '',
        ];
      }
    }

    return new JsonResponse(['catalog' => $templates]);
  }

  /**
   * Progreso del usuario hacia próximos badges.
   */
  public function progress(): JsonResponse {
    $uid = (int) $this->currentUser()->id();
    $phaseProgress = $this->journeyTracker->getProgressByPhase($uid);
    $userTemplates = $this->credentialService->getUserEmprendimientoTemplates($uid);

    return new JsonResponse([
      'earned_badges' => $userTemplates,
      'total_available' => count(EmprendimientoCredentialService::TEMPLATES),
      'total_earned' => count($userTemplates),
      'phase_progress' => $phaseProgress,
    ]);
  }

  /**
   * Mapa visual del journey de emprendimiento.
   */
  public function journey(): JsonResponse {
    $uid = (int) $this->currentUser()->id();
    $journeyMap = $this->journeyTracker->getJourneyMap($uid);
    $expertiseLevel = $this->expertiseService->evaluateUserLevel($uid);

    return new JsonResponse([
      'phases' => array_values($journeyMap),
      'expertise_level' => $expertiseLevel,
      'xp' => $this->expertiseService->getUserXp($uid),
    ]);
  }

  /**
   * Siguiente badge recomendado.
   */
  public function nextRecommended(): JsonResponse {
    $uid = (int) $this->currentUser()->id();
    $next = $this->journeyTracker->getNextRecommendedBadge($uid);

    if (!$next) {
      return new JsonResponse(['next' => NULL, 'message' => 'All badges earned']);
    }

    // Enrich with template details.
    $loaded = $this->entityTypeManager()->getStorage('credential_template')
      ->loadByProperties(['machine_name' => $next['machine_name']]);
    if (!empty($loaded)) {
      $template = reset($loaded);
      $next['name'] = $template->get('name')->value;
      $next['description'] = $template->get('description')->value ?? '';
    }

    return new JsonResponse(['next' => $next]);
  }

  /**
   * Nivel de expertise actual del usuario.
   */
  public function expertiseLevel(): JsonResponse {
    $uid = (int) $this->currentUser()->id();
    $level = $this->expertiseService->evaluateUserLevel($uid);
    $xp = $this->expertiseService->getUserXp($uid);
    $benefits = $this->expertiseService->getLevelBenefits($level);

    // Calculate XP to next level.
    $levels = EmprendimientoExpertiseService::LEVELS;
    $levelNames = array_keys($levels);
    $currentIndex = array_search($level, $levelNames);
    $nextLevel = $levelNames[$currentIndex + 1] ?? NULL;
    $xpToNext = $nextLevel ? $levels[$nextLevel] - $xp : 0;

    return new JsonResponse([
      'level' => $level,
      'xp' => $xp,
      'benefits' => $benefits,
      'next_level' => $nextLevel,
      'xp_to_next' => max(0, $xpToNext),
    ]);
  }

}
