<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Service to generate contextual menu based on user role in Employability vertical.
 */
class EmployabilityMenuService
{

    use StringTranslationTrait;

    /**
     * Current user.
     */
    protected AccountProxyInterface $currentUser;

    /**
     * Constructor.
     */
    public function __construct(AccountProxyInterface $current_user)
    {
        $this->currentUser = $current_user;
    }

    /**
     * Detects user's primary role in the Employability vertical.
     *
     * @return string
     *   Role: 'candidate', 'employer', 'student', or 'anonymous'.
     */
    public function detectUserRole(): string
    {
        if ($this->currentUser->isAnonymous()) {
            return 'anonymous';
        }

        // Check permissions to determine role
        if ($this->currentUser->hasPermission('create job postings')) {
            return 'employer';
        }

        if ($this->currentUser->hasPermission('view own applications')) {
            return 'candidate';
        }

        if ($this->currentUser->hasPermission('view own enrollments')) {
            return 'student';
        }

        return 'candidate'; // Default for authenticated users
    }

    /**
     * Gets contextual menu items based on user role.
     *
     * @return array
     *   Array of menu items with 'title', 'url', 'icon', 'active'.
     */
    public function getMenuItems(): array
    {
        $role = $this->detectUserRole();

        switch ($role) {
            case 'employer':
                return $this->getEmployerMenu();

            case 'student':
                return $this->getStudentMenu();

            case 'candidate':
                return $this->getCandidateMenu();

            default:
                return $this->getAnonymousMenu();
        }
    }

    /**
     * Menu for Candidate role.
     */
    protected function getCandidateMenu(): array
    {
        return [
            [
                'id' => 'profile',
                'title' => $this->t('Mi perfil'),
                'url' => Url::fromRoute('jaraba_candidate.my_profile')->toString(),
                'icon_category' => 'ui',
                'icon_name' => 'user',
                'weight' => 0,
            ],
            [
                'id' => 'jobs',
                'title' => $this->t('Ofertas'),
                'url' => Url::fromRoute('jaraba_job_board.search')->toString(),
                'icon_category' => 'verticals',
                'icon_name' => 'briefcase',
                'weight' => 1,
            ],
            [
                'id' => 'applications',
                'title' => $this->t('Candidaturas'),
                'url' => Url::fromRoute('jaraba_job_board.my_applications')->toString(),
                'icon_category' => 'actions',
                'icon_name' => 'check',
                'weight' => 2,
            ],
            [
                'id' => 'courses',
                'title' => $this->t('Formación'),
                'url' => Url::fromRoute('jaraba_lms.my_learning')->toString(),
                'icon_category' => 'ui',
                'icon_name' => 'book',
                'weight' => 3,
            ],
            [
                'id' => 'coach',
                'title' => $this->t('Coach IA'),
                'url' => '#coach',
                'icon_category' => 'ai',
                'icon_name' => 'copilot',
                'weight' => 4,
                'is_agent' => TRUE,
            ],
        ];
    }

    /**
     * Menu for Employer role.
     */
    protected function getEmployerMenu(): array
    {
        return [
            [
                'id' => 'dashboard',
                'title' => $this->t('Panel'),
                'url' => Url::fromRoute('jaraba_job_board.employer_dashboard')->toString(),
                'icon_category' => 'analytics',
                'icon_name' => 'gauge',
                'weight' => 0,
            ],
            [
                'id' => 'my_jobs',
                'title' => $this->t('Mis ofertas'),
                'url' => Url::fromRoute('jaraba_job_board.employer_jobs')->toString(),
                'icon_category' => 'business',
                'icon_name' => 'diagnostic',
                'weight' => 1,
            ],
            [
                'id' => 'candidates',
                'title' => $this->t('Candidatos'),
                'url' => Url::fromRoute('jaraba_job_board.employer_applications')->toString(),
                'icon_category' => 'ui',
                'icon_name' => 'users',
                'weight' => 2,
            ],
            [
                'id' => 'recruiter',
                'title' => $this->t('Recruiter IA'),
                'url' => '#recruiter',
                'icon_category' => 'ai',
                'icon_name' => 'copilot',
                'weight' => 3,
                'is_agent' => TRUE,
            ],
        ];
    }

    /**
     * Menu for Student role (LMS focused).
     */
    protected function getStudentMenu(): array
    {
        return [
            [
                'id' => 'my_courses',
                'title' => $this->t('Mis cursos'),
                'url' => Url::fromRoute('jaraba_lms.my_learning')->toString(),
                'icon_category' => 'ui',
                'icon_name' => 'book',
                'weight' => 0,
            ],
            [
                'id' => 'catalog',
                'title' => $this->t('Catálogo'),
                'url' => Url::fromRoute('jaraba_lms.catalog')->toString(),
                'icon_category' => 'ui',
                'icon_name' => 'search',
                'weight' => 1,
            ],
            [
                'id' => 'certificates',
                'title' => $this->t('Certificados'),
                'url' => Url::fromRoute('jaraba_lms.my_certificates')->toString(),
                'icon_category' => 'business',
                'icon_name' => 'achievement',
                'weight' => 2,
            ],
            [
                'id' => 'tutor',
                'title' => $this->t('Tutor IA'),
                'url' => '#tutor',
                'icon_category' => 'ai',
                'icon_name' => 'copilot',
                'weight' => 3,
                'is_agent' => TRUE,
            ],
        ];
    }

    /**
     * Menu for anonymous users.
     */
    protected function getAnonymousMenu(): array
    {
        return [
            [
                'id' => 'jobs',
                'title' => $this->t('Ofertas de empleo'),
                'url' => Url::fromRoute('jaraba_job_board.search')->toString(),
                'icon_category' => 'verticals',
                'icon_name' => 'briefcase',
                'weight' => 0,
            ],
            [
                'id' => 'courses',
                'title' => $this->t('Cursos'),
                'url' => Url::fromRoute('jaraba_lms.catalog')->toString(),
                'icon_category' => 'ui',
                'icon_name' => 'book',
                'weight' => 1,
            ],
            [
                'id' => 'login',
                'title' => $this->t('Iniciar sesión'),
                'url' => Url::fromRoute('user.login')->toString(),
                'icon_category' => 'ui',
                'icon_name' => 'lock',
                'weight' => 2,
            ],
        ];
    }

    /**
     * Gets current role label for display.
     */
    public function getRoleLabel(): string
    {
        $labels = [
            'candidate' => $this->t('Candidato'),
            'employer' => $this->t('Empleador'),
            'student' => $this->t('Estudiante'),
            'anonymous' => $this->t('Visitante'),
        ];

        return (string) ($labels[$this->detectUserRole()] ?? $this->t('Usuario'));
    }

}
