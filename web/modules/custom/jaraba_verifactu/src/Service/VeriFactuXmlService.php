<?php

declare(strict_types=1);

namespace Drupal\jaraba_verifactu\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord;
use Drupal\jaraba_verifactu\ValueObject\AeatResponse;
use Psr\Log\LoggerInterface;

/**
 * Servicio de construccion y parseo de mensajes SOAP/XML VeriFactu.
 *
 * Construye los mensajes SOAP conformes a los XSD de la AEAT para
 * el envio de registros VeriFactu (SuministroFactEmitidas). Parsea
 * las respuestas AEAT en objetos AeatResponse estructurados.
 *
 * Namespaces SOAP:
 * - soapenv: http://schemas.xmlsoap.org/soap/envelope/
 * - siiR: https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd
 * - sii: https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd
 *
 * Spec: Doc 179, Seccion 3. Plan: FASE 3, entregable F3-1.
 */
class VeriFactuXmlService {

  /**
   * AEAT SOAP namespace URIs.
   */
  const NS_SOAPENV = 'http://schemas.xmlsoap.org/soap/envelope/';
  const NS_SII_R = 'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroLR.xsd';
  const NS_SII = 'https://www2.agenciatributaria.gob.es/static_files/common/internet/dep/aplicaciones/es/aeat/tike/cont/ws/SuministroInformacion.xsd';

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Builds the SOAP envelope for a batch of VeriFactu records.
   *
   * @param array $records
   *   Array of VeriFactuInvoiceRecord entities.
   *
   * @return string
   *   Complete SOAP XML envelope ready for submission.
   */
  public function buildSoapEnvelope(array $records): string {
    if (empty($records)) {
      throw new \InvalidArgumentException('Cannot build SOAP envelope with empty records array.');
    }

    $settings = $this->configFactory->get('jaraba_verifactu.settings');
    $softwareId = $settings->get('software_id') ?: 'JarabaImpactPlatform';
    $softwareVersion = $settings->get('software_version') ?: '1.0.0';
    $softwareName = $settings->get('software_name') ?: 'Jaraba Impact Platform SaaS';
    $softwareNif = $settings->get('software_developer_nif') ?: '';

    /** @var \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord $firstRecord */
    $firstRecord = reset($records);
    $nifEmisor = $firstRecord->get('nif_emisor')->value;
    $nombreEmisor = $firstRecord->get('nombre_emisor')->value;

    $registrosXml = '';
    foreach ($records as $record) {
      $registrosXml .= $this->buildRegistroXml($record);
    }

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="{$this->e(self::NS_SOAPENV)}" xmlns:siiR="{$this->e(self::NS_SII_R)}" xmlns:sii="{$this->e(self::NS_SII)}">
  <soapenv:Header/>
  <soapenv:Body>
    <siiR:SuministroLRFacturasEmitidas>
      <sii:Cabecera>
        <sii:IDVersionSii>1.1</sii:IDVersionSii>
        <sii:Titular>
          <sii:NombreRazon>{$this->e($nombreEmisor)}</sii:NombreRazon>
          <sii:NIF>{$this->e($nifEmisor)}</sii:NIF>
        </sii:Titular>
        <sii:TipoComunicacion>A0</sii:TipoComunicacion>
      </sii:Cabecera>
      <sii:SoftwareGarante>
        <sii:NombreRazon>{$this->e($softwareName)}</sii:NombreRazon>
        <sii:NIF>{$this->e($softwareNif)}</sii:NIF>
        <sii:IdSistemaInformatico>{$this->e($softwareId)}</sii:IdSistemaInformatico>
        <sii:Version>{$this->e($softwareVersion)}</sii:Version>
      </sii:SoftwareGarante>
{$registrosXml}
    </siiR:SuministroLRFacturasEmitidas>
  </soapenv:Body>
</soapenv:Envelope>
XML;

    return $xml;
  }

  /**
   * Builds the XML for a single invoice record.
   *
   * @param \Drupal\jaraba_verifactu\Entity\VeriFactuInvoiceRecord $record
   *   The invoice record entity.
   *
   * @return string
   *   XML fragment for the record.
   */
  protected function buildRegistroXml(VeriFactuInvoiceRecord $record): string {
    $recordType = $record->get('record_type')->value;

    if ($recordType === 'anulacion') {
      return $this->buildAnulacionXml($record);
    }

    return $this->buildAltaXml($record);
  }

  /**
   * Builds XML for an alta (new/rectificativa) record.
   */
  protected function buildAltaXml(VeriFactuInvoiceRecord $record): string {
    $fecha = $this->formatDateForAeat($record->get('fecha_expedicion')->value);
    $hashPrevious = $record->get('hash_previous')->value ?? '';

    return <<<XML
      <siiR:RegistroLRFacturasEmitidas>
        <sii:PeriodoLiquidacion>
          <sii:Ejercicio>{$this->e(substr($fecha, 6, 4))}</sii:Ejercicio>
          <sii:Periodo>{$this->e(substr($fecha, 3, 2))}</sii:Periodo>
        </sii:PeriodoLiquidacion>
        <siiR:IDFactura>
          <sii:IDEmisorFactura>
            <sii:NIF>{$this->e($record->get('nif_emisor')->value)}</sii:NIF>
          </sii:IDEmisorFactura>
          <sii:NumSerieFacturaEmisor>{$this->e($record->get('numero_factura')->value)}</sii:NumSerieFacturaEmisor>
          <sii:FechaExpedicionFacturaEmisor>{$this->e($fecha)}</sii:FechaExpedicionFacturaEmisor>
        </siiR:IDFactura>
        <siiR:FacturaExpedida>
          <sii:TipoFactura>{$this->e($record->get('tipo_factura')->value)}</sii:TipoFactura>
          <sii:ClaveRegimenEspecialOTrascendencia>{$this->e($record->get('clave_regimen')->value)}</sii:ClaveRegimenEspecialOTrascendencia>
          <sii:ImporteTotal>{$this->e($record->get('importe_total')->value)}</sii:ImporteTotal>
          <sii:DescripcionOperacion>Factura</sii:DescripcionOperacion>
          <sii:TipoDesglose>
            <sii:DesgloseFactura>
              <sii:Sujeta>
                <sii:NoExenta>
                  <sii:TipoNoExenta>S1</sii:TipoNoExenta>
                  <sii:DesgloseIVA>
                    <sii:DetalleIVA>
                      <sii:TipoImpositivo>{$this->e($record->get('tipo_impositivo')->value)}</sii:TipoImpositivo>
                      <sii:BaseImponible>{$this->e($record->get('base_imponible')->value)}</sii:BaseImponible>
                      <sii:CuotaRepercutida>{$this->e($record->get('cuota_tributaria')->value)}</sii:CuotaRepercutida>
                    </sii:DetalleIVA>
                  </sii:DesgloseIVA>
                </sii:NoExenta>
              </sii:Sujeta>
            </sii:DesgloseFactura>
          </sii:TipoDesglose>
          <sii:Huella>
            <sii:Hash>{$this->e($record->get('hash_record')->value)}</sii:Hash>
            <sii:HashAnterior>{$this->e($hashPrevious)}</sii:HashAnterior>
          </sii:Huella>
        </siiR:FacturaExpedida>
      </siiR:RegistroLRFacturasEmitidas>

XML;
  }

  /**
   * Builds XML for an anulacion (cancellation) record.
   */
  protected function buildAnulacionXml(VeriFactuInvoiceRecord $record): string {
    $fecha = $this->formatDateForAeat($record->get('fecha_expedicion')->value);

    return <<<XML
      <siiR:RegistroLRBajaExpedidas>
        <sii:PeriodoLiquidacion>
          <sii:Ejercicio>{$this->e(substr($fecha, 6, 4))}</sii:Ejercicio>
          <sii:Periodo>{$this->e(substr($fecha, 3, 2))}</sii:Periodo>
        </sii:PeriodoLiquidacion>
        <siiR:IDFactura>
          <sii:IDEmisorFactura>
            <sii:NIF>{$this->e($record->get('nif_emisor')->value)}</sii:NIF>
          </sii:IDEmisorFactura>
          <sii:NumSerieFacturaEmisor>{$this->e($record->get('numero_factura')->value)}</sii:NumSerieFacturaEmisor>
          <sii:FechaExpedicionFacturaEmisor>{$this->e($fecha)}</sii:FechaExpedicionFacturaEmisor>
        </siiR:IDFactura>
        <sii:Huella>
          <sii:Hash>{$this->e($record->get('hash_record')->value)}</sii:Hash>
        </sii:Huella>
      </siiR:RegistroLRBajaExpedidas>

XML;
  }

  /**
   * Parses an AEAT SOAP response into an AeatResponse ValueObject.
   *
   * @param string $responseXml
   *   The raw AEAT response XML.
   *
   * @return \Drupal\jaraba_verifactu\ValueObject\AeatResponse
   *   The parsed response.
   */
  public function parseAeatResponse(string $responseXml): AeatResponse {
    try {
      // Suppress warnings for invalid XML.
      $previousUseErrors = libxml_use_internal_errors(TRUE);
      $doc = simplexml_load_string($responseXml);
      libxml_use_internal_errors($previousUseErrors);

      if ($doc === FALSE) {
        return AeatResponse::error('Failed to parse AEAT response XML.', $responseXml);
      }

      // Register namespaces for XPath queries.
      $doc->registerXPathNamespace('soap', self::NS_SOAPENV);
      $doc->registerXPathNamespace('siiR', self::NS_SII_R);
      $doc->registerXPathNamespace('sii', self::NS_SII);

      // Check for SOAP fault.
      $faults = $doc->xpath('//soap:Fault');
      if (!empty($faults)) {
        $faultString = (string) ($faults[0]->faultstring ?? 'Unknown SOAP fault');
        return AeatResponse::error('SOAP Fault: ' . $faultString, $responseXml);
      }

      // Parse the response body.
      $csv = '';
      $csvNodes = $doc->xpath('//sii:CSV');
      if (!empty($csvNodes)) {
        $csv = (string) $csvNodes[0];
      }

      // Parse per-record results.
      $recordResults = [];
      $acceptedCount = 0;
      $rejectedCount = 0;

      $respuestas = $doc->xpath('//siiR:RespuestaLinea') ?: $doc->xpath('//sii:RespuestaLinea') ?: [];

      foreach ($respuestas as $respuesta) {
        $respuesta->registerXPathNamespace('sii', self::NS_SII);

        $status = (string) ($respuesta->xpath('sii:EstadoRegistro')[0] ?? 'Desconocido');
        $code = (string) ($respuesta->xpath('sii:CodigoErrorRegistro')[0] ?? '');
        $message = (string) ($respuesta->xpath('sii:DescripcionErrorRegistro')[0] ?? '');
        $invoice = (string) ($respuesta->xpath('sii:IDFactura/sii:NumSerieFacturaEmisor')[0] ?? '');

        $recordResults[] = [
          'invoice' => $invoice,
          'status' => $status,
          'code' => $code,
          'message' => $message,
        ];

        if ($status === 'Correcto' || $status === 'AceptadoConErrores') {
          $acceptedCount++;
        }
        else {
          $rejectedCount++;
        }
      }

      // Determine global status.
      if ($rejectedCount === 0 && $acceptedCount > 0) {
        return AeatResponse::success($csv, $recordResults, $acceptedCount, $responseXml);
      }

      if ($acceptedCount > 0 && $rejectedCount > 0) {
        return AeatResponse::partial($csv, $recordResults, $acceptedCount, $rejectedCount, $responseXml);
      }

      if ($rejectedCount > 0 && $acceptedCount === 0) {
        $errorMsg = !empty($recordResults) ? $recordResults[0]['message'] : 'All records rejected';
        return AeatResponse::error($errorMsg, $responseXml);
      }

      // No line responses found - try global status.
      $estadoGlobal = $doc->xpath('//sii:EstadoEnvio');
      if (!empty($estadoGlobal)) {
        $estado = (string) $estadoGlobal[0];
        if ($estado === 'Correcto') {
          return AeatResponse::success($csv, [], 0, $responseXml);
        }
        return AeatResponse::error('AEAT global status: ' . $estado, $responseXml);
      }

      return AeatResponse::error('Could not determine AEAT response status.', $responseXml);
    }
    catch (\Exception $e) {
      $this->logger->error('Error parsing AEAT response: @message', [
        '@message' => $e->getMessage(),
      ]);
      return AeatResponse::error('Exception parsing response: ' . $e->getMessage(), $responseXml);
    }
  }

  /**
   * Formats a date for AEAT XML (DD-MM-YYYY).
   */
  protected function formatDateForAeat(string $date): string {
    $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if ($dateObj === FALSE) {
      $dateObj = new \DateTimeImmutable($date);
    }
    return $dateObj->format('d-m-Y');
  }

  /**
   * Escapes a string for safe XML inclusion.
   */
  protected function e(string $value): string {
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
  }

}
