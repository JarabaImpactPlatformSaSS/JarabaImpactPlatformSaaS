<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar el Acuerdo de Participación.
 *
 * Documento oficial: Acuerdo_participacion_ICV25.odt
 * Acuerdo bilateral firmado por el participante al inicio del programa
 * Andalucía +ei (PIIL CV 2025). DISTINTO del DACI (Anexo_DACI_ICV25.odt).
 *
 * El Acuerdo de Participación es obligatorio en la fase de Acogida.
 *
 * PRESAVE-RESILIENCE-001: Servicios opcionales con try-catch.
 */
class AcuerdoParticipacionService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ExpedienteService $expedienteService,
    protected readonly ?object $brandedPdfService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera el Acuerdo de Participación y lo almacena en el expediente.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return int|null
   *   ID del ExpedienteDocumento creado, o NULL si falla.
   */
  public function generarAcuerdo(int $participanteId): ?int {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        $this->logger->warning('Participante #@id no encontrado para Acuerdo de Participación.', ['@id' => $participanteId]);
        return NULL;
      }

      // Verificar si ya tiene Acuerdo firmado.
      if (method_exists($participante, 'isAcuerdoParticipacionFirmado') && $participante->isAcuerdoParticipacionFirmado()) {
        $this->logger->info('Participante #@id ya tiene Acuerdo de Participación firmado.', ['@id' => $participanteId]);
        return NULL;
      }

      // Obtener datos del participante.
      $owner = $participante->getOwner();
      $dniNie = $participante->get('dni_nie')->value ?? '';
      $nombreCompleto = $owner ? ($owner->getDisplayName() ?? $owner->getAccountName()) : $dniNie;
      $provincia = $participante->get('provincia_participacion')->value ?? '';
      $colectivo = $participante->get('colectivo')->value ?? '';
      $carril = $participante->get('carril')->value ?? '';
      $tenantId = $participante->get('tenant_id')->target_id;

      // Construir datos del Acuerdo de Participación.
      $acuerdoData = [
        'titulo' => 'Acuerdo de Participación — Programa Andalucía +ei (PIIL CV 2025)',
        'participante' => [
          'nombre' => $nombreCompleto,
          'dni_nie' => $dniNie,
          'provincia' => $provincia,
          'colectivo' => $colectivo,
          'carril' => $carril,
        ],
        'clausulas' => $this->getClausulas(),
        'fecha_generacion' => date('Y-m-d'),
      ];

      // Generar PDF si BrandedPdfService disponible.
      $pdfContent = NULL;
      if ($this->brandedPdfService && method_exists($this->brandedPdfService, 'generateReport')) {
        try {
          $pdfContent = $this->brandedPdfService->generateReport(
            'acuerdo_participacion',
            $acuerdoData,
            ['format' => 'A4', 'orientation' => 'portrait'],
          );
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error generando PDF Acuerdo de Participación: @msg', ['@msg' => $e->getMessage()]);
        }
      }

      // Crear documento en expediente.
      $documentoId = $this->expedienteService->createDocument(
        $participanteId,
        'sto_acuerdo_participacion',
        sprintf('Acuerdo de Participación - %s - %s', $nombreCompleto, date('d/m/Y')),
        $pdfContent,
        $tenantId ? (int) $tenantId : NULL,
      );

      if ($documentoId) {
        $this->logger->info('Acuerdo de Participación generado para participante #@id (doc #@doc)', [
          '@id' => $participanteId,
          '@doc' => $documentoId,
        ]);
      }

      return $documentoId;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando Acuerdo de Participación: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Marca el Acuerdo de Participación como firmado.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return bool
   *   TRUE si se marcó correctamente.
   */
  public function firmarAcuerdo(int $participanteId): bool {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return FALSE;
      }

      $participante->set('acuerdo_participacion_firmado', TRUE);
      $participante->set('acuerdo_participacion_fecha', date('Y-m-d'));
      $participante->save();

      $this->logger->info('Acuerdo de Participación firmado para participante #@id', ['@id' => $participanteId]);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error firmando Acuerdo de Participación: @msg', ['@msg' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Genera Acuerdo y lo marca como firmado en un solo paso.
   *
   * @return array{success: bool, message: string, documento_id: int|null}
   */
  public function generarYFirmarAcuerdo(int $participanteId): array {
    $documentoId = $this->generarAcuerdo($participanteId);
    if ($documentoId === NULL) {
      return [
        'success' => FALSE,
        'message' => 'No se pudo generar el Acuerdo de Participación.',
        'documento_id' => NULL,
      ];
    }

    $firmado = $this->firmarAcuerdo($participanteId);
    if (!$firmado) {
      return [
        'success' => FALSE,
        'message' => 'Acuerdo generado pero no se pudo marcar como firmado.',
        'documento_id' => $documentoId,
      ];
    }

    return [
      'success' => TRUE,
      'message' => 'Acuerdo de Participación generado y firmado correctamente.',
      'documento_id' => $documentoId,
    ];
  }

  /**
   * Cláusulas del Acuerdo de Participación (contenido normativo PIIL).
   *
   * Basado en Acuerdo_participacion_ICV25.odt.
   *
   * @return string[]
   */
  protected function getClausulas(): array {
    return [
      'El/la participante acepta participar voluntariamente en el Programa de Itinerarios Integrados y Personalizados de Inserción Laboral (PIIL CV 2025).',
      'El programa tiene una duración máxima de 12 meses desde la fecha de alta.',
      'La entidad se compromete a proporcionar un itinerario personalizado que incluye orientación individual, formación, mentoría y acompañamiento para la inserción laboral.',
      'El/la participante se compromete a mantener su inscripción como demandante de empleo en el SAE durante su participación.',
      'El/la participante se compromete a asistir a las sesiones programadas y a comunicar ausencias con antelación.',
      'El/la participante podrá percibir un incentivo económico de 528€ al cumplir los requisitos mínimos de participación.',
      'Ambas partes podrán resolver este acuerdo de forma unilateral mediante comunicación por escrito.',
      'Los datos personales serán tratados conforme al RGPD y la LOPDGDD, con la finalidad exclusiva de gestión del programa.',
    ];
  }

}
