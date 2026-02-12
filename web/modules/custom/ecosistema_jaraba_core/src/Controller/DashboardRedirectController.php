<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller for unified dashboard redirects.
 *
 * Provides consistent /dashboard/{avatar} URLs that redirect to
 * the actual dashboard routes in their respective modules.
 * Incluye redireccion automatica por avatar detectado via cascada.
 *
 * @see ecosistema_jaraba_core.routing.yml
 * @see \Drupal\ecosistema_jaraba_core\Service\AvatarDetectionService
 */
class DashboardRedirectController extends ControllerBase {

  /**
   * El servicio de deteccion de avatar.
   */
  protected ?AvatarDetectionService $avatarDetection = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->avatarDetection = $container->get('ecosistema_jaraba_core.avatar_detection');
    return $instance;
  }

  /**
   * Redirect to dashboard based on detected avatar.
   *
   * Usa AvatarDetectionService para resolver automaticamente el avatar
   * del usuario mediante cascada Domain > Path/UTM > Group > Rol.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the appropriate dashboard.
   */
  public function redirectByAvatar(): RedirectResponse {
    $routeName = $this->avatarDetection->resolveDashboardRoute();

    try {
      $url = Url::fromRoute($routeName)->toString();
    }
    catch (\Exception $e) {
      // Fallback al dashboard del tenant si la ruta no existe.
      $url = Url::fromRoute('ecosistema_jaraba_core.tenant.dashboard')->toString();
    }

    return new RedirectResponse($url, 302);
  }

  /**
   * Redirect to Job Seeker dashboard.
   *
   * /dashboard/career → /jobseeker
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the career dashboard.
   */
  public function redirectToCareer(): RedirectResponse {
    $url = Url::fromRoute('jaraba_candidate.dashboard')->toString();
    return new RedirectResponse($url, 302);
  }

  /**
   * Redirect to Recruiter/Employer dashboard.
   *
   * /dashboard/recruiter → /employer
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the employer dashboard.
   */
  public function redirectToRecruiter(): RedirectResponse {
    $url = Url::fromRoute('jaraba_job_board.employer_dashboard')->toString();
    return new RedirectResponse($url, 302);
  }

  /**
   * Redirect to Entrepreneur dashboard.
   *
   * /dashboard/entrepreneur → /entrepreneur/dashboard
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the entrepreneur dashboard.
   */
  public function redirectToEntrepreneur(): RedirectResponse {
    $url = Url::fromRoute('jaraba_business_tools.entrepreneur_dashboard')->toString();
    return new RedirectResponse($url, 302);
  }

  /**
   * Redirect to Producer/Business dashboard.
   *
   * /dashboard/producer → /my-company
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the my-company dashboard.
   */
  public function redirectToProducer(): RedirectResponse {
    $url = Url::fromRoute('jaraba_job_board.my_company')->toString();
    return new RedirectResponse($url, 302);
  }

  /**
   * Redirect to Mentor dashboard.
   *
   * /dashboard/mentor → /mentor/dashboard
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to the mentor dashboard.
   */
  public function redirectToMentor(): RedirectResponse {
    $url = Url::fromRoute('jaraba_mentoring.mentor_dashboard')->toString();
    return new RedirectResponse($url, 302);
  }

}
