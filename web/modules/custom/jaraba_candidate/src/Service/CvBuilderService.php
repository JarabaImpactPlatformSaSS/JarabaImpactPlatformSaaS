<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\jaraba_candidate\Entity\CandidateProfileInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;

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
     * The tenant context service.
     */
    protected TenantContextService $tenantContext;

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
        LoggerChannelFactoryInterface $logger_factory,
        TenantContextService $tenantContext, // AUDIT-CONS-N10: Proper DI for tenant context.
    ) {
        $this->tenantContext = $tenantContext; // AUDIT-CONS-N10: Proper DI for tenant context.
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
        // Feature gate: cv_builder (Plan Elevación Empleabilidad v1 — Fase 4).
        try {
            /** @var \Drupal\ecosistema_jaraba_core\Service\EmployabilityFeatureGateService $featureGate */
            $featureGate = \Drupal::service('ecosistema_jaraba_core.employability_feature_gate');
            $gateResult = $featureGate->check((int) $profile->getOwnerId(), 'cv_builder');
            if (!$gateResult->isAllowed()) {
                // Fire upgrade trigger (Plan Elevación Empleabilidad v1 — Fase 5).
                try {
                    $tenantContext = $this->tenantContext;
                    $tenant = $tenantContext->getCurrentTenant();
                    if ($tenant) {
                        \Drupal::service('ecosistema_jaraba_core.upgrade_trigger')
                            ->fire('limit_reached', $tenant, [
                                'feature_key' => 'cv_builder',
                                'current_usage' => $gateResult->used,
                                'vertical' => 'empleabilidad',
                            ]);
                    }
                } catch (\Exception $e) {
                    // Non-critical — fail silently.
                }

                return [
                    'content' => '',
                    'mime_type' => 'text/html',
                    'denied' => TRUE,
                    'gate_result' => $gateResult->toArray(),
                ];
            }
        } catch (\Exception $e) {
            // Service not available — allow generation (fail-open).
        }

        $data = $this->collectCvData($profile);

        // Generate HTML
        $html = $this->renderCvToHtml($data, $template);

        if ($format === 'pdf') {
            return $this->convertHtmlToPdf($html, $profile);
        }

        // Record feature usage (Plan Elevación Empleabilidad v1 — Fase 4).
        try {
            /** @var \Drupal\ecosistema_jaraba_core\Service\EmployabilityFeatureGateService $featureGate */
            $featureGate = \Drupal::service('ecosistema_jaraba_core.employability_feature_gate');
            $featureGate->recordUsage((int) $profile->getOwnerId(), 'cv_builder');
        } catch (\Exception $e) {
            // Service not available — skip recording.
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
        $user_id = (int) $profile->getOwnerId();

        // Get experience records (resilient to missing entity type).
        $experiences = [];
        try {
            $experiences = $this->entityTypeManager
                ->getStorage('candidate_experience')
                ->loadByProperties(['profile_id' => $profile->id()]);
        } catch (\Exception $e) {
            // Entity type may not exist yet.
        }

        // Get education records (resilient to missing entity type).
        $educations = [];
        try {
            $educations = $this->entityTypeManager
                ->getStorage('candidate_education')
                ->loadByProperties(['profile_id' => $profile->id()]);
        } catch (\Exception $e) {
            // Entity type may not exist yet.
        }

        // Get skills.
        $skills = [];
        try {
            $skills = $this->profileService->getSkills($user_id);
        } catch (\Exception $e) {
            // Skills method may fail.
        }

        // Get certifications from LMS.
        $certifications = [];
        try {
            $certifications = $this->getCertifications($user_id);
        } catch (\Exception $e) {
            // LMS may not be available.
        }

        // Get languages.
        $languages = [];
        try {
            $languages = $this->getLanguages($user_id);
        } catch (\Exception $e) {
            // Language entity may not exist.
        }

        // Safely get personal data fields.
        $personal = [
            'full_name' => $profile->getFullName(),
            'email' => NULL,
            'phone' => NULL,
            'city' => NULL,
            'country' => NULL,
            'linkedin' => NULL,
            'github' => NULL,
            'portfolio' => NULL,
            'website' => NULL,
            'photo_url' => NULL,
        ];
        try {
            $personal['email'] = $profile->get('email')->value ?? NULL;
            $personal['phone'] = $profile->get('phone')->value ?? NULL;
            $personal['city'] = $profile->getCity();
            $personal['country'] = $profile->get('country')->value ?? NULL;
            $personal['linkedin'] = $profile->get('linkedin_url')->value ?? NULL;
            $personal['github'] = $profile->get('github_url')->value ?? NULL;
            $personal['portfolio'] = $profile->get('portfolio_url')->value ?? NULL;
            $personal['website'] = $profile->get('website_url')->value ?? NULL;
            $personal['photo_url'] = $this->getPhotoUrl($profile);
        } catch (\Exception $e) {
            // Some fields may not exist on the entity.
        }

        // Safely get professional data.
        $professional = [
            'headline' => '',
            'summary' => '',
            'experience_years' => 0,
            'experience_level' => '',
        ];
        try {
            $professional['headline'] = $profile->getHeadline() ?? '';
            $professional['summary'] = $profile->getSummary() ?? '';
            $professional['experience_years'] = $profile->getExperienceYears() ?? 0;
            $professional['experience_level'] = $profile->get('experience_level')->value ?? '';
        } catch (\Exception $e) {
            // Some professional fields may not exist.
        }

        return [
            'personal' => $personal,
            'professional' => $professional,
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
     * Converts HTML CV to PDF using Dompdf.
     *
     * Genera un PDF A4 portrait con estilos de Design Tokens inyectados.
     * Guarda el resultado en private://cv_exports/ para descarga.
     *
     * @param string $html
     *   El HTML del CV renderizado.
     * @param \Drupal\jaraba_candidate\Entity\CandidateProfileInterface $profile
     *   El perfil del candidato.
     *
     * @return array
     *   Array con 'content' (bytes PDF), 'mime_type' y 'filename'.
     */
    protected function convertHtmlToPdf(string $html, CandidateProfileInterface $profile): array
    {
        try {
            // Preparar HTML con estilos PDF-friendly.
            $pdfHtml = $this->wrapForPdf($html);

            // Crear instancia Dompdf con configuracion segura.
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', FALSE);
            $options->set('isHtml5ParserEnabled', TRUE);
            $options->set('defaultFont', 'Helvetica');
            $options->set('dpi', 150);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($pdfHtml);

            // Configurar A4 portrait.
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdfContent = $dompdf->output();
            $filename = $this->generateFilename($profile, 'pdf');

            // Guardar en private://cv_exports/.
            $directory = 'private://cv_exports';
            $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

            $filePath = $directory . '/' . $filename;
            $this->fileSystem->saveData($pdfContent, $filePath, FileSystemInterface::EXISTS_REPLACE);

            $this->logger->info('PDF CV generado para perfil @id: @filename', [
                '@id' => $profile->id(),
                '@filename' => $filename,
            ]);

            return [
                'content' => $pdfContent,
                'mime_type' => 'application/pdf',
                'filename' => $filename,
                'file_path' => $filePath,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error generando PDF CV: @msg', ['@msg' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Wraps HTML for PDF generation with full CV template styles.
     *
     * Reads compiled cv-styles.css and embeds it inline since Dompdf
     * cannot load external stylesheets. Resolves CSS var() to fallback
     * values because Dompdf does not support custom properties.
     */
    protected function wrapForPdf(string $html): string
    {
        // Read compiled CV template CSS.
        $module_path = \Drupal::service('extension.list.module')->getPath('jaraba_candidate');
        $cssFile = DRUPAL_ROOT . '/' . $module_path . '/css/cv-styles.css';
        $cvStyles = '';
        if (file_exists($cssFile)) {
            $cvStyles = file_get_contents($cssFile);
            // Dompdf does not support CSS var() — resolve to fallback values.
            $cvStyles = preg_replace(
                '/var\(--[a-zA-Z0-9_-]+,\s*([^)]+)\)/',
                '$1',
                $cvStyles
            );
            // Remove source map reference.
            $cvStyles = preg_replace('/\/\*#\s*sourceMappingURL=.*?\*\//', '', $cvStyles);
        }

        $pdfOverrides = <<<CSS
@page {
  size: A4;
  margin: 15mm 12mm;
}
body {
  margin: 0;
  padding: 0;
}
/* Override web-only styles for print/PDF */
.cv-template {
  max-width: 100%;
  margin: 0;
  box-shadow: none;
  border-radius: 0;
  border: none;
}
.cv-header {
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}
/* Dompdf does not support grid — fallback for languages */
.cv-languages {
  display: block;
}
.cv-lang-item {
  display: inline-block;
  margin-right: 1rem;
  margin-bottom: 0.5rem;
}
/* Dompdf does not support backdrop-filter */
.cv-header__links a {
  background: rgba(255, 255, 255, 0.2);
}
CSS;

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <style>{$cvStyles}</style>
  <style>{$pdfOverrides}</style>
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
    protected function getLanguages(int $userId): array
    {
        try {
            $languageEntities = $this->entityTypeManager
                ->getStorage('candidate_language')
                ->loadByProperties(['user_id' => $userId]);

            if (empty($languageEntities)) {
                return [];
            }

            $languages = [];
            foreach ($languageEntities as $lang) {
                $level = $lang->isNative() ? 'Native' : $lang->getProficiencyLevel();
                $languages[] = [
                    'language' => $lang->getLanguageName(),
                    'level' => $level,
                    'certification' => $lang->getCertification(),
                ];
            }

            return $languages;
        } catch (\Exception $e) {
            return [];
        }
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
