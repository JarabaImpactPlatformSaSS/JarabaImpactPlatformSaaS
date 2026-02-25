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
     * Returns translatable template metadata (name + description).
     *
     * Cannot use const because $this->t() requires runtime context.
     *
     * @return array<string, array{name: string, description: string}>
     */
    protected function getTemplateMetadata(): array
    {
        return [
            'modern' => [
                'name' => $this->t('Modern Style'),
                'description' => $this->t('Clean, contemporary design ideal for tech and creative profiles.'),
            ],
            'classic' => [
                'name' => $this->t('Classic Style'),
                'description' => $this->t('Traditional format optimized for corporate sectors.'),
            ],
            'creative' => [
                'name' => $this->t('Creative Style'),
                'description' => $this->t('Bold layout with visual elements for creative profiles.'),
            ],
            'minimal' => [
                'name' => $this->t('Minimal Style'),
                'description' => $this->t('Minimalist style that highlights essential content.'),
            ],
            'tech' => [
                'name' => $this->t('Tech Professional'),
                'description' => $this->t('Focused on technical skills with sections for projects and code.'),
            ],
        ];
    }

    /**
     * Displays the CV Builder page.
     */
    public function builder(Request $request): array|Response
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            $build = [
                '#markup' => $this->t('Please <a href="@url">complete your profile</a> before building your CV.', [
                    '@url' => '/my-profile/edit',
                ]),
            ];
            if ($request->isXmlHttpRequest() && !$request->query->has('_wrapper_format')) {
                $html = (string) \Drupal::service('renderer')->render($build);
                return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
            }
            return $build;
        }

        // Build structured template data for the Twig template.
        $rawTemplates = $this->cvBuilder->getTemplates();
        $metadata = $this->getTemplateMetadata();
        $module_path = \Drupal::service('extension.list.module')->getPath('jaraba_candidate');
        $templates = [];
        foreach ($rawTemplates as $id => $name) {
            $png_file = $module_path . '/images/cv-preview-' . $id . '.png';
            $svg_file = $module_path . '/images/cv-preview-' . $id . '.svg';
            // Prefer PNG (premium previews) over SVG (legacy wireframes).
            if (file_exists(DRUPAL_ROOT . '/' . $png_file)) {
                $preview = '/' . $png_file;
            } elseif (file_exists(DRUPAL_ROOT . '/' . $svg_file)) {
                $preview = '/' . $svg_file;
            } else {
                $preview = NULL;
            }
            $templates[] = [
                'id' => $id,
                'name' => $metadata[$id]['name'] ?? $name,
                'description' => $metadata[$id]['description'] ?? '',
                'preview' => $preview,
            ];
        }

        // Build shareable public profile URL using Drupal's URL generator
        // so it includes the correct language prefix (e.g. /es/profile/5).
        // Only generate the URL if the profile is set to public.
        $is_public = $profile->isPublic();
        $profile_url = NULL;
        if ($is_public) {
            $profile_url = \Drupal\Core\Url::fromRoute(
                'jaraba_candidate.profile_view',
                ['candidate_profile' => $profile->id()],
                ['absolute' => TRUE]
            )->toString();
        }

        $build = [
            '#theme' => 'cv_builder',
            '#profile' => [
                'full_name' => $profile->getFullName(),
                'completion' => $profile->getCompletionPercent(),
                'share_url' => $profile_url,
                'is_public' => $is_public,
            ],
            '#templates' => $templates,
            '#attached' => [
                'library' => ['jaraba_candidate/cv_builder'],
            ],
        ];

        if ($request->isXmlHttpRequest() && !$request->query->has('_wrapper_format')) {
            $html = (string) \Drupal::service('renderer')->render($build);
            return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        return $build;
    }

    /**
     * Previews a CV template.
     */
    public function preview(string $template): array
    {
        // Validate template name.
        $validTemplates = array_keys($this->cvBuilder->getTemplates());
        if (!in_array($template, $validTemplates, TRUE)) {
            $template = 'modern';
        }

        try {
            $user_id = (int) $this->currentUser()->id();
            $profile = $this->profileService->getProfileByUserId($user_id);

            if (!$profile) {
                $html = $this->cvBuilder->previewTemplate($template);
            } else {
                $result = $this->cvBuilder->generateCv($profile, $template, 'html');
                $html = $result['content'] ?? '';
            }
        } catch (\Exception $e) {
            // Fall back to placeholder preview if real data fails.
            \Drupal::logger('jaraba_candidate')->warning(
                'CV preview error: @msg',
                ['@msg' => $e->getMessage()]
            );
            try {
                $html = $this->cvBuilder->previewTemplate($template);
            } catch (\Exception $e2) {
                $html = '<div class="cv-preview-error"><p>' .
                    $this->t('No se pudo generar la vista previa. Intenta de nuevo más tarde.') .
                    '</p></div>';
            }
        }

        return [
            '#markup' => $html,
            '#allowed_tags' => array_merge(\Drupal\Component\Utility\Xss::getAdminTagList(), ['style', 'header', 'footer', 'article', 'section', 'main']),
            '#attached' => [
                'library' => ['jaraba_candidate/cv_styles'],
            ],
        ];
    }

    /**
     * Downloads the CV in specified format.
     */
    public function download(string $format, Request $request): Response
    {
        $user_id = (int) $this->currentUser()->id();
        $profile = $this->profileService->getProfileByUserId($user_id);

        if (!$profile) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $template = $request->query->get('template', 'modern');

        try {
            $result = $this->cvBuilder->generateCv($profile, $template, $format);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_candidate')->error(
                'CV download error: @msg',
                ['@msg' => $e->getMessage()]
            );
            return new Response(
                $this->t('Error al generar el CV. Inténtalo de nuevo más tarde.'),
                500,
                ['Content-Type' => 'text/plain; charset=UTF-8']
            );
        }

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
