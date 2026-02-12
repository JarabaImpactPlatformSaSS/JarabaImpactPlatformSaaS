<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_candidate\Service\CvBuilderService;
use Drupal\jaraba_candidate\Service\CandidateProfileService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for CV Builder.
 */
class CvController extends ControllerBase
{

    /**
     * The CV builder service.
     */
    protected CvBuilderService $cvBuilder;

    /**
     * The profile service.
     */
    protected CandidateProfileService $profileService;

    /**
     * Constructor.
     */
    public function __construct(CvBuilderService $cv_builder, CandidateProfileService $profile_service)
    {
        $this->cvBuilder = $cv_builder;
        $this->profileService = $profile_service;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_candidate.cv_builder'),
            $container->get('jaraba_candidate.profile')
        );
    }

    /**
     * Displays the CV Builder page.
     */
    public function builder(): array
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            return [
                '#markup' => $this->t('Please <a href="@url">complete your profile</a> before building your CV.', [
                    '@url' => '/my-profile/edit',
                ]),
            ];
        }

        return [
            '#theme' => 'cv_builder',
            '#profile' => [
                'full_name' => $profile->getFullName(),
                'completion' => $profile->getCompletionPercent(),
            ],
            '#templates' => $this->cvBuilder->getTemplates(),
            '#attached' => [
                'library' => ['jaraba_candidate/cv_builder'],
            ],
        ];
    }

    /**
     * Previews a CV template.
     */
    public function preview(string $template): array
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            $html = $this->cvBuilder->previewTemplate($template);
        } else {
            $result = $this->cvBuilder->generateCv($profile, $template, 'html');
            $html = $result['content'];
        }

        return [
            '#markup' => $html,
            '#attached' => [
                'library' => ['jaraba_candidate/cv_styles'],
            ],
        ];
    }

    /**
     * Downloads the CV in specified format.
     */
    public function download(string $format): Response
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $result = $this->cvBuilder->generateCv($profile, 'modern', $format);

        $response = new Response($result['content']);
        $response->headers->set('Content-Type', $result['mime_type']);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $result['filename'] . '"');

        return $response;
    }

    /**
     * Generates a new CV.
     */
    public function generate(Request $request): Response
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            return new Response(json_encode(['error' => 'No profile found']), 404, ['Content-Type' => 'application/json']);
        }

        $template = $request->request->get('template', 'modern');
        $format = $request->request->get('format', 'html');

        $result = $this->cvBuilder->generateCv($profile, $template, $format);

        return new Response(json_encode([
            'success' => true,
            'message' => $this->t('CV generated successfully'),
        ]), 200, ['Content-Type' => 'application/json']);
    }

}
