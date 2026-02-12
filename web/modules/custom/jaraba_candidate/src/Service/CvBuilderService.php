<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jaraba_candidate\Entity\CandidateProfileInterface;

/**
 * Service for building CVs from candidate profiles.
 *
 * Genera CVs en múltiples formatos (HTML, PDF) usando plantillas
 * configurables y datos del perfil del candidato.
 */
class CvBuilderService
{

    /**
     * Available CV templates.
     */
    protected const TEMPLATES = [
        'modern' => 'Modern Style',
        'classic' => 'Classic Style',
        'creative' => 'Creative Style',
        'minimal' => 'Minimal Style',
        'tech' => 'Tech Professional',
    ];

    /**
     * The entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * The profile service.
     */
    protected CandidateProfileService $profileService;

    /**
     * The renderer.
     */
    protected RendererInterface $renderer;

    /**
     * The file system.
     */
    protected FileSystemInterface $fileSystem;

    /**
     * The logger.
     */
    protected $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        CandidateProfileService $profile_service,
        RendererInterface $renderer,
        FileSystemInterface $file_system,
        LoggerChannelFactoryInterface $logger_factory
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->profileService = $profile_service;
        $this->renderer = $renderer;
        $this->fileSystem = $file_system;
        $this->logger = $logger_factory->get('jaraba_candidate');
    }

    /**
     * Generates a CV for a candidate.
     *
     * @param \Drupal\jaraba_candidate\Entity\CandidateProfileInterface $profile
     *   The candidate profile.
     * @param string $template
     *   Template name (modern, classic, creative, minimal, tech).
     * @param string $format
     *   Output format (html, pdf).
     *
     * @return array
     *   Array with 'content' (HTML/bytes) and 'mime_type'.
     */
    public function generateCv(CandidateProfileInterface $profile, string $template = 'modern', string $format = 'html'): array
    {
        $data = $this->collectCvData($profile);

        // Generate HTML
        $html = $this->renderCvToHtml($data, $template);

        if ($format === 'pdf') {
            return $this->convertHtmlToPdf($html, $profile);
        }

        // Cache HTML in profile
        $profile->set('cv_generated_html', $html);
        $profile->set('cv_last_updated', \Drupal::time()->getRequestTime());
        $profile->save();

        return [
            'content' => $html,
            'mime_type' => 'text/html',
            'filename' => $this->generateFilename($profile, 'html'),
        ];
    }

    /**
     * Collects all data needed for CV generation.
     *
     * @param \Drupal\jaraba_candidate\Entity\CandidateProfileInterface $profile
     *   The candidate profile.
     *
     * @return array
     *   Structured CV data.
     */
    public function collectCvData(CandidateProfileInterface $profile): array
    {
        $user_id = $profile->getOwnerId();

        // Get experience records
        $experiences = $this->entityTypeManager
            ->getStorage('candidate_experience')
            ->loadByProperties(['profile_id' => $profile->id()]);

        // Get education records
        $educations = $this->entityTypeManager
            ->getStorage('candidate_education')
            ->loadByProperties(['profile_id' => $profile->id()]);

        // Get skills
        $skills = $this->profileService->getSkills($profile->id());

        // Get certifications from LMS
        $certifications = $this->getCertifications($user_id);

        // Get languages
        $languages = $this->getLanguages($profile->id());

        return [
            'personal' => [
                'full_name' => $profile->getFullName(),
                'email' => $profile->get('email')->value,
                'phone' => $profile->get('phone')->value,
                'city' => $profile->getCity(),
                'country' => $profile->get('country')->value,
                'linkedin' => $profile->get('linkedin_url')->value,
                'github' => $profile->get('github_url')->value,
                'portfolio' => $profile->get('portfolio_url')->value,
                'website' => $profile->get('website_url')->value,
                'photo_url' => $this->getPhotoUrl($profile),
            ],
            'professional' => [
                'headline' => $profile->getHeadline(),
                'summary' => $profile->getSummary(),
                'experience_years' => $profile->getExperienceYears(),
                'experience_level' => $profile->get('experience_level')->value,
            ],
            'experiences' => $this->formatExperiences($experiences),
            'educations' => $this->formatEducations($educations),
            'skills' => $skills,
            'certifications' => $certifications,
            'languages' => $languages,
            'generated_at' => date('Y-m-d'),
        ];
    }

    /**
     * Renders CV data to HTML using a template.
     */
    protected function renderCvToHtml(array $data, string $template): string
    {
        $build = [
            '#theme' => 'cv_template_' . $template,
            '#cv_data' => $data,
            '#attached' => [
                'library' => ['jaraba_candidate/cv_styles'],
            ],
        ];

        return (string) $this->renderer->renderPlain($build);
    }

    /**
     * Converts HTML CV to PDF.
     */
    protected function convertHtmlToPdf(string $html, CandidateProfileInterface $profile): array
    {
        // Use wkhtmltopdf or similar library
        // For now, return a placeholder
        try {
            // Add PDF-specific styles
            $pdf_html = $this->wrapForPdf($html);

            // TODO: Integrate with PDF generation library (dompdf, wkhtmltopdf)
            // For demo purposes, we'll save the HTML as if it were PDF
            $filename = $this->generateFilename($profile, 'pdf');
            $directory = 'private://cvs/' . $profile->id();
            $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

            $this->logger->info('Generated PDF CV for profile @id', ['@id' => $profile->id()]);

            return [
                'content' => $pdf_html, // Would be actual PDF bytes
                'mime_type' => 'application/pdf',
                'filename' => $filename,
            ];
        } catch (\Exception $e) {
            $this->logger->error('PDF generation failed: @msg', ['@msg' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Wraps HTML for PDF generation with print-friendly styles.
     */
    protected function wrapForPdf(string $html): string
    {
        $styles = <<<CSS
@page {
  size: A4;
  margin: 20mm 15mm;
}
body {
  font-family: 'Helvetica Neue', Arial, sans-serif;
  font-size: 11pt;
  line-height: 1.4;
  color: #333;
}
h1 { font-size: 24pt; margin-bottom: 5mm; }
h2 { font-size: 14pt; margin-top: 8mm; border-bottom: 1px solid #ddd; }
h3 { font-size: 12pt; }
.section { margin-bottom: 8mm; }
.experience-item, .education-item { margin-bottom: 5mm; }
.skills-list { columns: 2; }
CSS;

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>{$styles}</style>
</head>
<body>
{$html}
</body>
</html>
HTML;
    }

    /**
     * Formats experience records for CV.
     */
    protected function formatExperiences(array $experiences): array
    {
        $formatted = [];
        foreach ($experiences as $exp) {
            $formatted[] = [
                'title' => $exp->get('job_title')->value,
                'company' => $exp->get('company_name')->value,
                'location' => $exp->get('location')->value,
                'start_date' => $exp->get('start_date')->value,
                'end_date' => $exp->get('end_date')->value ?: t('Present'),
                'is_current' => (bool) $exp->get('is_current')->value,
                'description' => $exp->get('description')->value,
                'achievements' => json_decode($exp->get('achievements')->value ?: '[]', TRUE),
            ];
        }

        // Sort by start_date descending
        usort($formatted, fn($a, $b) => strcmp($b['start_date'], $a['start_date']));

        return $formatted;
    }

    /**
     * Formats education records for CV.
     */
    protected function formatEducations(array $educations): array
    {
        $formatted = [];
        foreach ($educations as $edu) {
            $formatted[] = [
                'degree' => $edu->get('degree')->value,
                'field' => $edu->get('field_of_study')->value,
                'institution' => $edu->get('institution_name')->value,
                'location' => $edu->get('location')->value,
                'start_date' => $edu->get('start_date')->value,
                'end_date' => $edu->get('end_date')->value,
                'grade' => $edu->get('grade')->value,
                'description' => $edu->get('description')->value,
            ];
        }

        // Sort by end_date descending
        usort($formatted, fn($a, $b) => strcmp($b['end_date'] ?? '', $a['end_date'] ?? ''));

        return $formatted;
    }

    /**
     * Gets certifications from LMS enrollments.
     */
    protected function getCertifications(int $user_id): array
    {
        $certs = [];

        // Get completed enrollments with certificates
        $enrollments = $this->entityTypeManager
            ->getStorage('lms_enrollment')
            ->loadByProperties([
                'user_id' => $user_id,
                'status' => 'completed',
                'certificate_issued' => TRUE,
            ]);

        foreach ($enrollments as $enrollment) {
            $course = $enrollment->getCourse();
            if ($course) {
                $certs[] = [
                    'name' => $course->getTitle(),
                    'issuer' => 'Jaraba Impact Platform',
                    'date' => date('Y-m', $enrollment->getCompletedAt()),
                    'credential_id' => 'CERT-' . $enrollment->id(),
                ];
            }
        }

        return $certs;
    }

    /**
     * Gets language proficiencies.
     */
    protected function getLanguages(int $profile_id): array
    {
        // TODO: Load from candidate_language entity
        return [
            ['language' => 'Español', 'level' => 'Native'],
            ['language' => 'English', 'level' => 'Professional'],
        ];
    }

    /**
     * Gets profile photo URL.
     */
    protected function getPhotoUrl(CandidateProfileInterface $profile): ?string
    {
        $file_id = $profile->get('photo')->target_id;
        if (!$file_id) {
            return NULL;
        }

        $file = $this->entityTypeManager->getStorage('file')->load($file_id);
        return $file ? \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()) : NULL;
    }

    /**
     * Generates CV filename.
     */
    protected function generateFilename(CandidateProfileInterface $profile, string $extension): string
    {
        $name = preg_replace('/[^a-z0-9]/i', '_', $profile->getFullName());
        $date = date('Ymd');
        return "CV_{$name}_{$date}.{$extension}";
    }

    /**
     * Gets available templates.
     */
    public function getTemplates(): array
    {
        return self::TEMPLATES;
    }

    /**
     * Previews a CV template with placeholder data.
     */
    public function previewTemplate(string $template): string
    {
        $placeholder_data = [
            'personal' => [
                'full_name' => 'María García López',
                'email' => 'maria.garcia@ejemplo.com',
                'phone' => '+34 600 000 000',
                'city' => 'Madrid',
                'country' => 'ES',
                'linkedin' => 'https://linkedin.com/in/mariagarcia',
                'github' => NULL,
                'portfolio' => NULL,
                'website' => NULL,
                'photo_url' => NULL,
            ],
            'professional' => [
                'headline' => 'Diseñadora UX/UI con 5 años de experiencia',
                'summary' => 'Profesional creativa especializada en diseño de experiencias digitales centradas en el usuario.',
                'experience_years' => 5,
                'experience_level' => 'mid',
            ],
            'experiences' => [
                [
                    'title' => 'Senior UX Designer',
                    'company' => 'Tech Company S.L.',
                    'location' => 'Madrid',
                    'start_date' => '2022-01',
                    'end_date' => 'Actualidad',
                    'is_current' => TRUE,
                    'description' => 'Liderazgo del equipo de diseño...',
                    'achievements' => [],
                ],
            ],
            'educations' => [
                [
                    'degree' => 'Grado en Diseño',
                    'field' => 'Diseño de Interacción',
                    'institution' => 'Universidad Complutense',
                    'location' => 'Madrid',
                    'start_date' => '2015',
                    'end_date' => '2019',
                    'grade' => NULL,
                    'description' => NULL,
                ],
            ],
            'skills' => [
                ['name' => 'Figma', 'level' => 'expert'],
                ['name' => 'Adobe XD', 'level' => 'advanced'],
                ['name' => 'HTML/CSS', 'level' => 'intermediate'],
            ],
            'certifications' => [],
            'languages' => [
                ['language' => 'Español', 'level' => 'Native'],
                ['language' => 'English', 'level' => 'Professional'],
            ],
            'generated_at' => date('Y-m-d'),
        ];

        return $this->renderCvToHtml($placeholder_data, $template);
    }

}
