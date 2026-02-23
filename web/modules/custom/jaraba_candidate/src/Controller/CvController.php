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
     * Template descriptions for each CV style.
     */
    protected const TEMPLATE_DESCRIPTIONS = [
        'modern' => 'Diseño limpio y contemporáneo ideal para perfiles tech y creativos.',
        'classic' => 'Formato tradicional optimizado para sectores corporativos.',
        'creative' => 'Layout atrevido con elementos visuales para perfiles creativos.',
        'minimal' => 'Estilo minimalista que destaca el contenido esencial.',
        'tech' => 'Enfocado en skills técnicos con secciones para proyectos y código.',
    ];

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

        // Build structured template data for the Twig template.
        $rawTemplates = $this->cvBuilder->getTemplates();
        $module_path = \Drupal::service('extension.list.module')->getPath('jaraba_candidate');
        $templates = [];
        foreach ($rawTemplates as $id => $name) {
            $svg_file = $module_path . '/images/cv-preview-' . $id . '.svg';
            $templates[] = [
                'id' => $id,
                'name' => $name,
                'description' => self::TEMPLATE_DESCRIPTIONS[$id] ?? '',
                'preview' => file_exists(DRUPAL_ROOT . '/' . $svg_file) ? '/' . $svg_file : NULL,
            ];
        }

        return [
            '#theme' => 'cv_builder',
            '#profile' => [
                'full_name' => $profile->getFullName(),
                'completion' => $profile->getCompletionPercent(),
            ],
            '#templates' => $templates,
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
