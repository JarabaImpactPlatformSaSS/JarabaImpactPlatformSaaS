<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Drupal\jaraba_sepe_teleformacion\Service\SepeSoapService;
use Psr\Log\LoggerInterface;

/**
 * Servicio para exportación de datos al STO (Servicio Telemático de Orientación).
 *
 * Genera paquetes de datos compatibles con el formato XML/SOAP del STO
 * para sincronización de participantes y horas de orientación.
 */
class StoExportService {

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\jaraba_sepe_teleformacion\Service\SepeSoapService $sepeSoapService
   *   Servicio SOAP de SEPE reutilizado.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del módulo.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory para obtener configuración del módulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SepeSoapService $sepeSoapService,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Sincroniza un participante individual con el STO.
   *
   * Public facade called from jaraba_andalucia_ei.module cron sync.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface $participante
   *   The participant entity to sync.
   *
   * @return bool
   *   TRUE if sync was successful.
   */
  public function syncParticipante(ProgramaParticipanteEiInterface $participante): bool {
    $config = $this->configFactory->get('jaraba_andalucia_ei.settings');

    if (!$config->get('sto_sync_enabled')) {
      $this->logger->notice('STO sync disabled, skipping participant @id', [
        '@id' => $participante->id(),
      ]);
      return FALSE;
    }

    $result = $this->generarPaqueteExportacion([(int) $participante->id()]);

    if (!$result['success']) {
      $this->logger->error('STO sync failed for participant @id: @msg', [
        '@id' => $participante->id(),
        '@msg' => $result['message'] ?? 'Unknown error',
      ]);
      return FALSE;
    }

    $this->logger->info('STO sync completed for participant @id', [
      '@id' => $participante->id(),
    ]);

    return TRUE;
  }

  /**
   * Genera el paquete de exportación para el STO.
   *
   * @param array $participante_ids
   *   IDs de participantes a exportar.
   *
   * @return array
   *   Array con 'success', 'data' (XML string) y 'count'.
   */
  public function generarPaqueteExportacion(array $participante_ids): array {
    try {
      $participantes = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->loadMultiple($participante_ids);

      if (empty($participantes)) {
        return [
          'success' => FALSE,
          'message' => t('No hay participantes para exportar.'),
          'count' => 0,
        ];
      }

      $datos = [];
      foreach ($participantes as $participante) {
        $datos[] = [
          'dni_nie' => $participante->getDniNie(),
          'colectivo' => $participante->getColectivo(),
          'provincia' => $participante->get('provincia_participacion')->value,
          'fase' => $participante->getFaseActual(),
          'horas_orientacion' => $participante->getTotalHorasOrientacion(),
          'horas_formacion' => (float) ($participante->get('horas_formacion')->value ?? 0),
          'incentivo_recibido' => $participante->hasReceivedIncentivo(),
          'tipo_insercion' => $participante->get('tipo_insercion')->value ?? NULL,
          'fecha_insercion' => $participante->get('fecha_insercion')->value ?? NULL,
        ];
      }

      // Generar XML de exportación.
      $xml = $this->generarXmlSto($datos);

      $this->logger->info('Paquete STO generado: @count participantes', [
        '@count' => count($datos),
      ]);

      return [
        'success' => TRUE,
        'data' => $xml,
        'count' => count($datos),
        'message' => t('Paquete generado correctamente.'),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error generando paquete STO: @message', ['@message' => $e->getMessage()]);
      return [
        'success' => FALSE,
        'message' => t('Error: @error', ['@error' => $e->getMessage()]),
        'count' => 0,
      ];
    }
  }

  /**
   * Genera el XML de exportación en formato STO.
   *
   * @param array $datos
   *   Datos de participantes.
   *
   * @return string
   *   XML string.
   */
  protected function generarXmlSto(array $datos): string {
    $xml = new \SimpleXMLElement(
      '<?xml version="1.0" encoding="UTF-8"?><PaqueteSTO xmlns="http://www.juntadeandalucia.es/sto"/>'
    );

    $xml->addChild('FechaGeneracion', date('Y-m-d\TH:i:s'));
    $xml->addChild('Programa', 'ANDALUCIA_EI');
    $xml->addChild('TotalParticipantes', (string) count($datos));

    $participantesNode = $xml->addChild('Participantes');

    foreach ($datos as $dato) {
      $partNode = $participantesNode->addChild('Participante');
      $partNode->addChild('DniNie', htmlspecialchars($dato['dni_nie']));
      $partNode->addChild('Colectivo', $dato['colectivo']);
      $partNode->addChild('Provincia', $dato['provincia']);
      $partNode->addChild('Fase', $dato['fase']);
      $partNode->addChild('HorasOrientacion', number_format($dato['horas_orientacion'], 2, '.', ''));
      $partNode->addChild('HorasFormacion', number_format($dato['horas_formacion'], 2, '.', ''));
      $partNode->addChild('IncentivoRecibido', $dato['incentivo_recibido'] ? 'S' : 'N');

      if ($dato['tipo_insercion']) {
        $insercion = $partNode->addChild('Insercion');
        $insercion->addChild('Tipo', $dato['tipo_insercion']);
        $insercion->addChild('Fecha', $dato['fecha_insercion'] ?? '');
      }
    }

    return $xml->asXML();
  }

  /**
   * Sincroniza los participantes marcados como pending con el STO.
   *
   * @return array
   *   Resultado de la sincronización.
   */
  public function sincronizarConSto(): array {
    $config = $this->configFactory->get('jaraba_andalucia_ei.settings');

    if (!$config->get('sto_sync_enabled')) {
      return [
        'success' => FALSE,
        'message' => t('Sincronización STO no habilitada.'),
      ];
    }

    // Obtener participantes pendientes.
    $participantes = $this->entityTypeManager
      ->getStorage('programa_participante_ei')
      ->loadByProperties(['sto_sync_status' => 'pending']);

    if (empty($participantes)) {
      return [
        'success' => TRUE,
        'message' => t('No hay participantes pendientes de sincronización.'),
        'count' => 0,
      ];
    }

    $ids = array_keys($participantes);
    $resultado = $this->generarPaqueteExportacion($ids);

    if ($resultado['success']) {
      // Marcar como sincronizados.
      foreach ($participantes as $participante) {
        $participante->set('sto_sync_status', 'synced');
        $participante->save();
      }
    }

    return $resultado;
  }

}
