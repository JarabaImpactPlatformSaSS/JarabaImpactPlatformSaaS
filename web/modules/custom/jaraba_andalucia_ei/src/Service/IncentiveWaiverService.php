<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar la Renuncia al Incentivo €528.
 *
 * Documento oficial: Renuncia_Incentivo_ICV25.odt
 * El participante puede renunciar voluntariamente al incentivo económico
 * de 528€. Genera un documento firmable y actualiza el estado del participante.
 *
 * PRESAVE-RESILIENCE-001: Servicios opcionales con try-catch.
 */
class IncentiveWaiverService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ExpedienteService $expedienteService,
    protected readonly ?object $brandedPdfService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera el documento de renuncia al incentivo.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return int|null
   *   ID del ExpedienteDocumento creado, o NULL si falla.
   */
  public function generarRenuncia(int $participanteId): ?int {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        $this->logger->warning('Participante #@id no encontrado para Renuncia Incentivo.', ['@id' => $participanteId]);
        return NULL;
      }

      // Verificar que no haya ya recibido el incentivo.
      if ($participante->hasReceivedIncentivo()) {
        $this->logger->warning('Participante #@id ya recibió el incentivo. No se puede renunciar.', ['@id' => $participanteId]);
        return NULL;
      }

      // Verificar si ya renunció.
      if ($participante->hasRenunciadoIncentivo()) {
        $this->logger->info('Participante #@id ya tiene renuncia al incentivo.', ['@id' => $participanteId]);
        return NULL;
      }

      $owner = $participante->getOwner();
      $dniNie = $participante->get('dni_nie')->value ?? '';
      $nombreCompleto = $owner ? ($owner->getDisplayName() ?? $owner->getAccountName()) : $dniNie;
      $tenantId = $participante->get('tenant_id')->target_id;

      $renunciaData = [
        'titulo' => 'Renuncia al Incentivo Económico — PIIL ICV25',
        'participante' => [
          'nombre' => $nombreCompleto,
          'dni_nie' => $dniNie,
        ],
        'importe_renunciado' => 528.00,
        'motivo' => 'Renuncia voluntaria del participante.',
        'fecha_generacion' => date('Y-m-d'),
      ];

      // Generar PDF si BrandedPdfService disponible.
      $pdfContent = NULL;
      if ($this->brandedPdfService && method_exists($this->brandedPdfService, 'generateReport')) {
        try {
          $pdfContent = $this->brandedPdfService->generateReport(
            'renuncia_incentivo',
            $renunciaData,
            ['format' => 'A4', 'orientation' => 'portrait'],
          );
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error generando PDF Renuncia Incentivo: @msg', ['@msg' => $e->getMessage()]);
        }
      }

      $documentoId = $this->expedienteService->createDocument(
        $participanteId,
        'sto_renuncia_incentivo',
        sprintf('Renuncia Incentivo €528 - %s - %s', $nombreCompleto, date('d/m/Y')),
        $pdfContent,
        $tenantId ? (int) $tenantId : NULL,
      );

      if ($documentoId) {
        $this->logger->info('Renuncia Incentivo generada para participante #@id (doc #@doc)', [
          '@id' => $participanteId,
          '@doc' => $documentoId,
        ]);
      }

      return $documentoId;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando Renuncia Incentivo: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Registra la renuncia al incentivo y genera el documento.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return array{success: bool, message: string, documento_id: int|null}
   */
  public function registrarRenuncia(int $participanteId): array {
    $documentoId = $this->generarRenuncia($participanteId);
    if ($documentoId === NULL) {
      return [
        'success' => FALSE,
        'message' => 'No se pudo generar la renuncia al incentivo.',
        'documento_id' => NULL,
      ];
    }

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if ($participante) {
        $participante->set('incentivo_renuncia', TRUE);
        $participante->set('incentivo_renuncia_fecha', date('Y-m-d'));
        $participante->save();
      }

      return [
        'success' => TRUE,
        'message' => 'Renuncia al incentivo registrada correctamente.',
        'documento_id' => $documentoId,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error registrando renuncia incentivo: @msg', ['@msg' => $e->getMessage()]);
      return [
        'success' => FALSE,
        'message' => 'Renuncia generada pero error al registrar estado.',
        'documento_id' => $documentoId,
      ];
    }
  }

}
