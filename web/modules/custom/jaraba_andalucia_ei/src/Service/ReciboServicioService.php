<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio universal para generar recibos de servicio de actuaciones.
 *
 * Genera un recibo PDF por cada actuación realizada (orientación, formación,
 * tutoría, etc.) y lo almacena como ExpedienteDocumento. Reemplaza la
 * generación aislada de HojaServicioMentoriaService con un servicio
 * transversal para todos los tipos de actuación.
 *
 * PRESAVE-RESILIENCE-001: BrandedPdfService opcional con try-catch.
 */
class ReciboServicioService {

  /**
   * Mapa tipo_actuacion → categoría de documento en expediente.
   */
  private const CATEGORIA_MAP = [
    'orientacion_individual' => 'orientacion_hoja_servicio',
    'orientacion_grupal' => 'orientacion_hoja_servicio',
    'formacion' => 'formacion_hoja_servicio',
    'tutoria' => 'mentoria_hoja_servicio',
    'prospeccion' => 'prospeccion_informe',
    'intermediacion' => 'intermediacion_informe',
  ];

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ExpedienteService $expedienteService,
    protected readonly ?object $brandedPdfService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Genera un recibo de servicio para una actuación STO.
   *
   * @param int $actuacionId
   *   ID de la entidad ActuacionSto.
   *
   * @return int|null
   *   ID del ExpedienteDocumento creado, o NULL si falla.
   */
  public function generarRecibo(int $actuacionId): ?int {
    try {
      if (!$this->entityTypeManager->hasDefinition('actuacion_sto')) {
        return NULL;
      }

      $actuacion = $this->entityTypeManager
        ->getStorage('actuacion_sto')
        ->load($actuacionId);

      if (!$actuacion) {
        $this->logger->warning('Actuación #@id no encontrada.', ['@id' => $actuacionId]);
        return NULL;
      }

      // Ya tiene recibo generado?
      $reciboExistente = $actuacion->get('recibo_servicio_id')->target_id;
      if ($reciboExistente) {
        return (int) $reciboExistente;
      }

      // Datos de la actuación.
      $participanteRef = $actuacion->get('participante_id')->entity;
      $orientadorRef = $actuacion->get('orientador_id')->entity;

      $tipoActuacion = $actuacion->get('tipo_actuacion')->value ?? '';
      $fecha = $actuacion->get('fecha')->value ?? date('Y-m-d');
      $horaInicio = $actuacion->get('hora_inicio')->value ?? '';
      $horaFin = $actuacion->get('hora_fin')->value ?? '';
      $duracion = (int) ($actuacion->get('duracion_minutos')->value ?? 0);
      $contenido = $actuacion->get('contenido')->value ?? '';
      $resultado = $actuacion->get('resultado')->value ?? '';
      $lugar = $actuacion->get('lugar')->value ?? '';

      $participanteId = $participanteRef ? (int) $participanteRef->id() : 0;
      $participanteNombre = $participanteRef ? ($participanteRef->label() ?? '-') : '-';
      $orientadorNombre = $orientadorRef ? ($orientadorRef->getDisplayName() ?? '-') : '-';
      $tenantId = $actuacion->get('tenant_id')->target_id;

      // Construir datos del recibo.
      $reciboData = [
        'titulo' => sprintf('Recibo de Servicio - %s', ucfirst(str_replace('_', ' ', $tipoActuacion))),
        'actuacion' => [
          'tipo' => $tipoActuacion,
          'fecha' => $fecha,
          'hora_inicio' => $horaInicio,
          'hora_fin' => $horaFin,
          'duracion_minutos' => $duracion,
          'contenido' => $contenido,
          'resultado' => $resultado,
          'lugar' => $lugar,
        ],
        'participante' => [
          'id' => $participanteId,
          'nombre' => $participanteNombre,
        ],
        'orientador' => [
          'nombre' => $orientadorNombre,
        ],
        'fecha_generacion' => date('Y-m-d H:i:s'),
      ];

      // Generar PDF.
      $pdfContent = NULL;
      if ($this->brandedPdfService && method_exists($this->brandedPdfService, 'generateReport')) {
        try {
          $pdfContent = $this->brandedPdfService->generateReport(
            'recibo_servicio_actuacion',
            $reciboData,
            ['format' => 'A4', 'orientation' => 'portrait'],
          );
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error generando PDF recibo: @msg', ['@msg' => $e->getMessage()]);
        }
      }

      // Categoría de documento.
      $categoria = self::CATEGORIA_MAP[$tipoActuacion] ?? 'orientacion_hoja_servicio';

      // Crear documento en expediente.
      $documentoId = $this->expedienteService->createDocument(
        $participanteId,
        $categoria,
        sprintf('Recibo - %s - %s - %s', ucfirst(str_replace('_', ' ', $tipoActuacion)), $participanteNombre, $fecha),
        $pdfContent,
        $tenantId ? (int) $tenantId : NULL,
      );

      if ($documentoId) {
        // Vincular recibo a la actuación.
        $actuacion->set('recibo_servicio_id', $documentoId);
        $actuacion->save();

        $this->logger->info('Recibo #@doc generado para actuación #@act', [
          '@doc' => $documentoId,
          '@act' => $actuacionId,
        ]);
      }

      return $documentoId;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando recibo de servicio: @msg', ['@msg' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Genera recibos para todas las actuaciones sin recibo de un participante.
   *
   * @return int
   *   Número de recibos generados.
   */
  public function generarRecibosPendientes(int $participanteId): int {
    $count = 0;

    try {
      if (!$this->entityTypeManager->hasDefinition('actuacion_sto')) {
        return 0;
      }

      $storage = $this->entityTypeManager->getStorage('actuacion_sto');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->notExists('recibo_servicio_id')
        ->execute();

      foreach ($ids as $id) {
        if ($this->generarRecibo((int) $id) !== NULL) {
          $count++;
        }
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando recibos pendientes: @msg', ['@msg' => $e->getMessage()]);
    }

    return $count;
  }

}
