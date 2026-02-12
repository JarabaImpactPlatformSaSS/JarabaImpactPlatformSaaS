<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Psr\Log\LoggerInterface;
use Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface;

/**
 * Servicio de generación de informes de diagnóstico.
 *
 * Genera informes PDF con los resultados del diagnóstico,
 * visualizaciones y recomendaciones.
 */
class DiagnosticReportService
{

    protected EntityTypeManagerInterface $entityTypeManager;
    protected RendererInterface $renderer;
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        RendererInterface $renderer,
        LoggerInterface $loggerFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->renderer = $renderer;
        $this->logger = $loggerFactory;
    }

    /**
     * Genera el contenido HTML del informe.
     *
     * @param \Drupal\jaraba_diagnostic\Entity\BusinessDiagnosticInterface $diagnostic
     *   El diagnóstico.
     *
     * @return array
     *   Render array del informe.
     */
    public function generateReportContent(BusinessDiagnosticInterface $diagnostic): array
    {
        // Obtener datos calculados
        $priorityGaps = json_decode($diagnostic->get('priority_gaps')->value ?? '[]', TRUE);

        return [
            '#theme' => 'diagnostic_report',
            '#diagnostic' => $diagnostic,
            '#business_name' => $diagnostic->getBusinessName(),
            '#sector' => $diagnostic->getBusinessSector(),
            '#overall_score' => $diagnostic->getOverallScore(),
            '#maturity_level' => $diagnostic->getMaturityLevel(),
            '#estimated_loss' => $diagnostic->getEstimatedLoss(),
            '#priority_gaps' => $priorityGaps,
            '#generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Renderiza el informe a HTML.
     */
    public function renderToHtml(BusinessDiagnosticInterface $diagnostic): string
    {
        $build = $this->generateReportContent($diagnostic);
        return (string) $this->renderer->renderPlain($build);
    }

    /**
     * Genera PDF del informe.
     *
     * TODO: Implementar con DomPDF o Puppeteer cuando esté disponible.
     */
    public function generatePdf(BusinessDiagnosticInterface $diagnostic): ?string
    {
        $this->logger->info('PDF generation requested for diagnostic @id', [
            '@id' => $diagnostic->id(),
        ]);

        // Por ahora retornar NULL hasta que se implemente el generador PDF
        return NULL;
    }

}
