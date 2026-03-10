<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar el Recibí del Incentivo €528.
 *
 * Documento oficial: Recibi_Incentivo_ICV25.odt
 * El participante firma un recibí cuando percibe el incentivo económico
 * de 528€ (base) menos 2% IRPF (10,56€) = 517,44€ netos.
 *
 * PRESAVE-RESILIENCE-001: Servicios opcionales con try-catch.
 */
class IncentiveReceiptService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ExpedienteService $expedienteService,
    protected readonly ?object $brandedPdfService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera el recibí del incentivo para un participante.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return int|null
   *   ID del ExpedienteDocumento creado, o NULL si falla.
   */
  public function generarRecibi(int $participanteId): ?int {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        $this->logger->warning('Participante #@id no encontrado para Recibí Incentivo.', ['@id' => $participanteId]);
        return NULL;
      }

      // Verificar si ya ha recibido el incentivo.
      if ($participante->hasReceivedIncentivo()) {
        $this->logger->info('Participante #@id ya tiene recibí de incentivo.', ['@id' => $participanteId]);
        return NULL;
      }

      // Verificar que no haya renunciado.
      if ($participante->hasRenunciadoIncentivo()) {
        $this->logger->warning('Participante #@id renunció al incentivo. No se puede generar recibí.', ['@id' => $participanteId]);
        return NULL;
      }

      $owner = $participante->getOwner();
      $dniNie = $participante->get('dni_nie')->value ?? '';
      $nombreCompleto = $owner ? ($owner->getDisplayName() ?? $owner->getAccountName()) : $dniNie;
      $tenantId = $participante->get('tenant_id')->target_id;

      $recibiData = [
        'titulo' => 'Recibí del Incentivo Económico — PIIL ICV25',
        'participante' => [
          'nombre' => $nombreCompleto,
          'dni_nie' => $dniNie,
        ],
        'importes' => [
          'bruto' => 528.00,
          'irpf_porcentaje' => 2.0,
          'irpf_importe' => 10.56,
          'neto' => 517.44,
        ],
        'fecha_generacion' => date('Y-m-d'),
      ];

      // Generar PDF si BrandedPdfService disponible.
      $pdfContent = NULL;
      if ($this->brandedPdfService && method_exists($this->brandedPdfService, 'generateReport')) {
        try {
          $pdfContent = $this->brandedPdfService->generateReport(
            'recibi_incentivo',
            $recibiData,
            ['format' => 'A4', 'orientation' => 'portrait'],
          );
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error generando PDF Recibí Incentivo: @msg', ['@msg' => $e->getMessage()]);
        }
      }

      $documentoId = $this->expedienteService->createDocument(
        $participanteId,
        'sto_recibi_incentivo',
        sprintf('Recibí Incentivo €528 - %s - %s', $nombreCompleto, date('d/m/Y')),
        $pdfContent,
        $tenantId ? (int) $tenantId : NULL,
      );

      if ($documentoId) {
        $this->logger->info('Recibí Incentivo generado para participante #@id (doc #@doc)', [
          '@id' => $participanteId,
          '@doc' => $documentoId,
        ]);
      }

      return $documentoId;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando Recibí Incentivo: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Registra el pago del incentivo y genera el recibí.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return array{success: bool, message: string, documento_id: int|null}
   */
  public function registrarPago(int $participanteId): array {
    $documentoId = $this->generarRecibi($participanteId);
    if ($documentoId === NULL) {
      return [
        'success' => FALSE,
        'message' => 'No se pudo generar el recibí del incentivo.',
        'documento_id' => NULL,
      ];
    }

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if ($participante) {
        $participante->set('incentivo_recibido', TRUE);
        $participante->set('incentivo_fecha_pago', date('Y-m-d'));
        $participante->save();
      }

      return [
        'success' => TRUE,
        'message' => 'Incentivo registrado y recibí generado correctamente.',
        'documento_id' => $documentoId,
      ];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error registrando pago incentivo: @msg', ['@msg' => $e->getMessage()]);
      return [
        'success' => FALSE,
        'message' => 'Recibí generado pero error al registrar el pago.',
        'documento_id' => $documentoId,
      ];
    }
  }

}
