<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar el DACI (Documento de Aceptación de Compromisos e Información).
 *
 * Documento oficial: Anexo_DACI_ICV25.odt
 * El DACI es obligatorio para todos los participantes PIIL. En él, el
 * participante acepta los compromisos del programa y es informado de sus
 * derechos. DISTINTO del Acuerdo de Participación (Acuerdo_participacion_ICV25.odt).
 *
 * @see \Drupal\jaraba_andalucia_ei\Service\AcuerdoParticipacionService
 *   Para el Acuerdo de Participación bilateral (documento separado).
 *
 * PRESAVE-RESILIENCE-001: Servicios opcionales con try-catch.
 */
class DaciService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ExpedienteService $expedienteService,
    protected readonly ?object $brandedPdfService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera el DACI para un participante y lo almacena en su expediente.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return int|null
   *   ID del ExpedienteDocumento creado, o NULL si falla.
   */
  public function generarDaci(int $participanteId): ?int {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        $this->logger->warning('Participante #@id no encontrado para DACI.', ['@id' => $participanteId]);
        return NULL;
      }

      // Verificar si ya tiene DACI firmado.
      if (method_exists($participante, 'isDaciFirmado') && $participante->isDaciFirmado()) {
        $this->logger->info('Participante #@id ya tiene DACI firmado.', ['@id' => $participanteId]);
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

      // Construir datos del DACI.
      $daciData = [
        'titulo' => 'Documento de Aceptación de Compromisos e Información (DACI)',
        'participante' => [
          'nombre' => $nombreCompleto,
          'dni_nie' => $dniNie,
          'provincia' => $provincia,
          'colectivo' => $colectivo,
          'carril' => $carril,
        ],
        'compromisos' => $this->getCompromisos(),
        'derechos' => $this->getDerechos(),
        'fecha_generacion' => date('Y-m-d'),
      ];

      // Generar PDF si BrandedPdfService disponible.
      $pdfContent = NULL;
      if ($this->brandedPdfService && method_exists($this->brandedPdfService, 'generateReport')) {
        try {
          $pdfContent = $this->brandedPdfService->generateReport(
            'daci_participante',
            $daciData,
            ['format' => 'A4', 'orientation' => 'portrait'],
          );
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error generando PDF DACI: @msg', ['@msg' => $e->getMessage()]);
        }
      }

      // Crear documento en expediente.
      $documentoId = $this->expedienteService->createDocument(
        $participanteId,
        'sto_daci',
        sprintf('DACI - %s - %s', $nombreCompleto, date('d/m/Y')),
        $pdfContent,
        $tenantId ? (int) $tenantId : NULL,
      );

      if ($documentoId) {
        $this->logger->info('DACI generado para participante #@id (doc #@doc)', [
          '@id' => $participanteId,
          '@doc' => $documentoId,
        ]);
      }

      return $documentoId;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando DACI: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Marca el DACI como firmado para un participante.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return bool
   *   TRUE si se marcó correctamente.
   */
  public function firmarDaci(int $participanteId): bool {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return FALSE;
      }

      $participante->set('daci_firmado', TRUE);
      $participante->set('daci_fecha_firma', date('Y-m-d'));
      $participante->save();

      $this->logger->info('DACI firmado para participante #@id', ['@id' => $participanteId]);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error firmando DACI: @msg', ['@msg' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Genera DACI y lo marca como firmado en un solo paso.
   *
   * @return array{success: bool, message: string, documento_id: int|null}
   */
  public function generarYFirmarDaci(int $participanteId): array {
    $documentoId = $this->generarDaci($participanteId);
    if ($documentoId === NULL) {
      return [
        'success' => FALSE,
        'message' => 'No se pudo generar el DACI.',
        'documento_id' => NULL,
      ];
    }

    $firmado = $this->firmarDaci($participanteId);
    if (!$firmado) {
      return [
        'success' => FALSE,
        'message' => 'DACI generado pero no se pudo marcar como firmado.',
        'documento_id' => $documentoId,
      ];
    }

    return [
      'success' => TRUE,
      'message' => 'DACI generado y firmado correctamente.',
      'documento_id' => $documentoId,
    ];
  }

  /**
   * Compromisos del participante (contenido normativo PIIL).
   *
   * @return string[]
   */
  protected function getCompromisos(): array {
    return [
      'Asistir a las sesiones de orientación individual y grupal programadas.',
      'Participar activamente en las acciones formativas asignadas.',
      'Colaborar en la búsqueda activa de empleo y/o en el desarrollo del plan de autoempleo.',
      'Comunicar cualquier cambio en su situación laboral o personal que afecte a su participación.',
      'Firmar las hojas de servicio y recibos de las actuaciones realizadas.',
      'Facilitar los datos necesarios para el seguimiento del programa y la recogida de indicadores FSE+.',
      'Mantener la inscripción como demandante de empleo en el SAE durante su participación.',
      'Comunicar con antelación la imposibilidad de asistir a las citas programadas.',
    ];
  }

  /**
   * Derechos del participante (contenido normativo PIIL).
   *
   * @return string[]
   */
  protected function getDerechos(): array {
    return [
      'Recibir orientación profesional individualizada adaptada a su perfil.',
      'Participar en acciones formativas acordes con su itinerario personalizado.',
      'Recibir acompañamiento continuo a través de mentoría humana y/o asistida por IA.',
      'Percibir el incentivo económico de 528€ al completar las horas mínimas de orientación y formación.',
      'Solicitar la baja voluntaria del programa en cualquier momento.',
      'Ser informado del tratamiento de sus datos personales conforme al RGPD.',
      'Presentar reclamaciones o sugerencias sobre el desarrollo del programa.',
    ];
  }

}
