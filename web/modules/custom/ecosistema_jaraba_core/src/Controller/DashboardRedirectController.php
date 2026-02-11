<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller for unified dashboard redirects.
 *
 * Provides consistent /dashboard/{avatar} URLs that redirect to
 * the actual dashboard routes in their respective modules.
 *
 * @see ecosistema_jaraba_core.routing.yml
 */
class DashboardRedirectController extends ControllerBase {

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
