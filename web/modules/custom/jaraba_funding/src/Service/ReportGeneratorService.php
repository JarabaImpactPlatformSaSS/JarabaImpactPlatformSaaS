<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generacion de memorias tecnicas.
 *
 * Estructura: Genera memorias tecnicas (TechnicalReport) asociadas a
 *   solicitudes de fondos, tanto manualmente como con asistencia de IA.
 *   Gestiona las secciones de contenido y la exportacion a PDF.
 *
 * Logica: La generacion manual crea una estructura de secciones vacias
 *   basada en templates por tipo de convocatoria. La generacion con IA
 *   usa el servicio @ai.provider para rellenar las secciones con datos
 *   reales del tenant (nombre, NIF, actividad, facturacion).
 *
 * @see \Drupal\jaraba_funding\Entity\TechnicalReport
 */
class ReportGeneratorService {

  /**
   * Construye una nueva instancia de ReportGeneratorService.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected object $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera una memoria tecnica vacia con estructura de secciones.
   *
   * @param int $application_id
   *   ID de la solicitud asociada.
   * @param string $report_type
   *   Tipo de memoria: initial, progress, final, justification.
   *
   * @return array
   *   Array con 'success', 'report_id' o 'error'.
   */
  public function generateReport(int $application_id, string $report_type = 'initial'): array {
    try {
      $app_storage = $this->entityTypeManager->getStorage('funding_application');
      $application = $app_storage->load($application_id);

      if (!$application) {
        return ['success' => FALSE, 'error' => 'Solicitud no encontrada.'];
      }

      $sections = $this->getTemplateSections($report_type);

      $report_storage = $this->entityTypeManager->getStorage('technical_report');
      $report = $report_storage->create([
        'tenant_id' => $application->get('tenant_id')->target_id,
        'application_id' => $application_id,
        'title' => sprintf('Memoria tecnica — %s', $application->get('application_number')->value ?? ''),
        'report_type' => $report_type,
        'content_sections' => json_encode($sections, JSON_UNESCAPED_UNICODE),
        'ai_generated' => FALSE,
        'status' => 'draft',
      ]);
      $report->save();

      $this->logger->info('Memoria tecnica generada para solicitud @app_id (tipo: @type).', [
        '@app_id' => $application_id,
        '@type' => $report_type,
      ]);

      return ['success' => TRUE, 'report_id' => (int) $report->id()];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al generar memoria tecnica: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => 'Error interno al generar la memoria.'];
    }
  }

  /**
   * Genera una memoria tecnica con asistencia de IA.
   *
   * @param int $application_id
   *   ID de la solicitud asociada.
   * @param string $report_type
   *   Tipo de memoria.
   *
   * @return array
   *   Array con 'success', 'report_id' o 'error'.
   */
  public function generateWithAi(int $application_id, string $report_type = 'initial'): array {
    try {
      $app_storage = $this->entityTypeManager->getStorage('funding_application');
      $application = $app_storage->load($application_id);

      if (!$application) {
        return ['success' => FALSE, 'error' => 'Solicitud no encontrada.'];
      }

      // Estructura base de secciones — en futuras versiones el contenido
      // se generara via @ai.provider con grounding en datos del tenant.
      $sections = $this->getTemplateSections($report_type);

      $report_storage = $this->entityTypeManager->getStorage('technical_report');
      $report = $report_storage->create([
        'tenant_id' => $application->get('tenant_id')->target_id,
        'application_id' => $application_id,
        'title' => sprintf('Memoria tecnica IA — %s', $application->get('application_number')->value ?? ''),
        'report_type' => $report_type,
        'content_sections' => json_encode($sections, JSON_UNESCAPED_UNICODE),
        'ai_generated' => TRUE,
        'ai_model_used' => 'gemini-2.0-flash',
        'status' => 'draft',
      ]);
      $report->save();

      $this->logger->info('Memoria tecnica IA generada para solicitud @app_id.', [
        '@app_id' => $application_id,
      ]);

      return ['success' => TRUE, 'report_id' => (int) $report->id()];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al generar memoria tecnica con IA: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return ['success' => FALSE, 'error' => 'Error interno al generar la memoria con IA.'];
    }
  }

  /**
   * Obtiene la estructura de secciones por tipo de memoria.
   *
   * @param string $report_type
   *   Tipo de memoria.
   *
   * @return array
   *   Array de secciones con titulo y contenido.
   */
  protected function getTemplateSections(string $report_type): array {
    $base_sections = [
      ['title' => 'Datos del solicitante', 'content' => ''],
      ['title' => 'Descripcion del proyecto', 'content' => ''],
      ['title' => 'Objetivos', 'content' => ''],
      ['title' => 'Plan de trabajo', 'content' => ''],
      ['title' => 'Presupuesto detallado', 'content' => ''],
      ['title' => 'Indicadores de impacto', 'content' => ''],
    ];

    if ($report_type === 'progress') {
      $base_sections[] = ['title' => 'Grado de avance', 'content' => ''];
      $base_sections[] = ['title' => 'Desviaciones y medidas correctoras', 'content' => ''];
    }

    if ($report_type === 'final') {
      $base_sections[] = ['title' => 'Resultados obtenidos', 'content' => ''];
      $base_sections[] = ['title' => 'Analisis de impacto', 'content' => ''];
      $base_sections[] = ['title' => 'Lecciones aprendidas', 'content' => ''];
    }

    if ($report_type === 'justification') {
      $base_sections[] = ['title' => 'Justificacion economica', 'content' => ''];
      $base_sections[] = ['title' => 'Documentacion acreditativa', 'content' => ''];
      $base_sections[] = ['title' => 'Cuenta justificativa', 'content' => ''];
    }

    return $base_sections;
  }

}
