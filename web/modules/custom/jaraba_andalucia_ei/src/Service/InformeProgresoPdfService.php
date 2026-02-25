<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generación de informe de progreso en PDF.
 *
 * Usa BrandedPdfService (ecosistema_jaraba_core) para generar
 * informes de progreso del participante con branding del programa.
 */
class InformeProgresoPdfService {

  /**
   * Constructs an InformeProgresoPdfService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param object|null $brandedPdf
   *   The branded PDF service (ecosistema_jaraba_core.branded_pdf).
   * @param \Drupal\jaraba_andalucia_ei\Service\ExpedienteService $expedienteService
   *   The document folder service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ?object $brandedPdf,
    protected ExpedienteService $expedienteService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera el informe de progreso de un participante.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $participante
   *   El participante.
   *
   * @return string|null
   *   URI del PDF generado (private://) o NULL si falla.
   */
  public function generarInforme(ProgramaParticipanteEiInterface $participante): ?string {
    if (!$this->brandedPdf) {
      $this->logger->warning('BrandedPdfService not available for progress report.');
      return NULL;
    }

    $tenantId = NULL;
    if ($participante->hasField('tenant_id') && !$participante->get('tenant_id')->isEmpty()) {
      $tenantId = (int) $participante->get('tenant_id')->target_id;
    }

    $completitud = $this->expedienteService->getCompletuDocumental((int) $participante->id());

    $faseLabels = [
      'atencion' => 'Atención',
      'insercion' => 'Inserción',
      'baja' => 'Baja',
    ];

    $carrilLabels = [
      'impulso_digital' => 'Impulso Digital',
      'acelera_pro' => 'Acelera Pro',
      'hibrido' => 'Híbrido',
    ];

    $data = [
      'title' => 'Informe de Progreso - Andalucía +ei',
      'subtitle' => 'Programa de Emprendimiento e Inserción Laboral Aumentado con IA',
      'sections' => [
        [
          'title' => 'Datos del participante',
          'rows' => [
            ['label' => 'DNI/NIE', 'value' => $participante->getDniNie()],
            ['label' => 'Fase actual', 'value' => $faseLabels[$participante->getFaseActual()] ?? $participante->getFaseActual()],
            ['label' => 'Carril', 'value' => $carrilLabels[$participante->get('carril')->value ?? ''] ?? 'Sin asignar'],
            ['label' => 'Fecha alta STO', 'value' => $participante->get('fecha_alta_sto')->value ?? 'N/A'],
          ],
        ],
        [
          'title' => 'Desglose de horas',
          'table' => [
            'headers' => ['Concepto', 'Horas'],
            'rows' => [
              ['Orientación individual', number_format((float) ($participante->get('horas_orientacion_ind')->value ?? 0), 1) . 'h'],
              ['Orientación grupal', number_format((float) ($participante->get('horas_orientacion_grup')->value ?? 0), 1) . 'h'],
              ['Mentoría IA', number_format($participante->getHorasMentoriaIa(), 1) . 'h'],
              ['Mentoría humana', number_format($participante->getHorasMentoriaHumana(), 1) . 'h'],
              ['Formación', number_format((float) ($participante->get('horas_formacion')->value ?? 0), 1) . 'h'],
              ['TOTAL orientación', number_format($participante->getTotalHorasOrientacion(), 1) . 'h'],
            ],
          ],
        ],
        [
          'title' => 'Documentación',
          'rows' => [
            ['label' => 'Documentos STO completados', 'value' => $completitud['completados'] . ' de ' . $completitud['total_requeridos']],
            ['label' => 'Completitud documental', 'value' => $completitud['porcentaje'] . '%'],
          ],
        ],
        [
          'title' => 'Requisitos para transición',
          'rows' => [
            ['label' => 'Orientación ≥10h', 'value' => $participante->getTotalHorasOrientacion() >= 10 ? 'Sí' : 'No (' . number_format($participante->getTotalHorasOrientacion(), 1) . 'h)'],
            ['label' => 'Formación ≥50h', 'value' => (float) ($participante->get('horas_formacion')->value ?? 0) >= 50 ? 'Sí' : 'No (' . number_format((float) ($participante->get('horas_formacion')->value ?? 0), 1) . 'h)'],
            ['label' => 'Puede transitar a Inserción', 'value' => $participante->canTransitToInsercion() ? 'Sí' : 'No'],
          ],
        ],
      ],
      'footer_note' => 'Informe generado automáticamente por la plataforma Andalucía +ei. Fecha: ' . date('d/m/Y H:i'),
    ];

    try {
      return $this->brandedPdf->generateReport($data, $tenantId);
    }
    catch (\Exception $e) {
      $this->logger->error('Error generating progress report for participant @id: @message', [
        '@id' => $participante->id(),
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
