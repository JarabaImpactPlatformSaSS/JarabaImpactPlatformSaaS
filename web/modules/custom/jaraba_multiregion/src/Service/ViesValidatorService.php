<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de validacion de numeros de IVA contra el sistema VIES.
 *
 * ESTRUCTURA:
 * Valida numeros de IVA intracomunitarios consultando el servicio SOAP
 * VIES (VAT Information Exchange System) de la Comision Europea.
 * Almacena los resultados como entidades ViesValidation para cache
 * y auditoria de validaciones previas.
 *
 * LOGICA:
 * - Parsea el codigo de pais de los 2 primeros caracteres del VAT.
 * - Realiza la consulta SOAP al endpoint oficial de VIES.
 * - Almacena el resultado como entidad ViesValidation.
 * - Implementa cache temporal (configurable) para evitar consultas repetidas.
 * - Si VIES no esta disponible, retorna error controlado sin bloquear.
 *
 * SINTAXIS:
 * - Constructor con propiedades promovidas (PHP 8.3).
 * - Cadenas visibles usan TranslatableMarkup.
 * - Todas las llamadas HTTP envueltas en try/catch con logger.
 * - Retorna arrays estructurados con datos de validacion.
 *
 * @see https://ec.europa.eu/taxation_customs/vies/ Portal VIES
 */
class ViesValidatorService {

  /**
   * URL del endpoint SOAP de VIES para validacion de VAT.
   *
   * @var string
   */
  protected const VIES_SOAP_URL = 'https://ec.europa.eu/taxation_customs/vies/services/checkVatService';

  /**
   * Horas por defecto para considerar una validacion como expirada.
   *
   * @var int
   */
  protected const DEFAULT_CACHE_HOURS = 24;

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceder al storage de ViesValidation.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar las peticiones SOAP a VIES.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logger para registrar errores y validaciones.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ClientInterface $httpClient,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Valida un numero de IVA contra el servicio VIES de la Comision Europea.
   *
   * LOGICA:
   * 1. Parsea el codigo de pais de los 2 primeros caracteres del VAT.
   * 2. Construye el envelope SOAP segun la especificacion VIES.
   * 3. Realiza la peticion HTTP POST al endpoint SOAP.
   * 4. Parsea la respuesta XML extrayendo valid, name y address.
   * 5. Almacena el resultado como entidad ViesValidation.
   * 6. Retorna array estructurado con los datos de la validacion.
   *
   * Si el servicio VIES no esta disponible (timeout, error HTTP, etc.),
   * retorna un array con is_valid=FALSE y un mensaje de error descriptivo.
   *
   * @param string $vatNumber
   *   Numero de IVA completo incluyendo prefijo de pais (ej: 'ES12345678A').
   *
   * @return array
   *   Array estructurado con:
   *   - 'is_valid': (bool) TRUE si el VAT es valido segun VIES.
   *   - 'company_name': (string) Nombre de la empresa registrada.
   *   - 'company_address': (string) Direccion fiscal registrada.
   *   - 'request_id': (string) ID unico de la peticion de validacion.
   *   - 'country_code': (string) Codigo de pais extraido del VAT.
   *   - 'vat_body': (string) Numero de VAT sin el prefijo de pais.
   *   - 'validated_at': (string) Fecha/hora de la validacion ISO 8601.
   *   - 'error': (string|null) Mensaje de error si la validacion fallo.
   */
  public function validate(string $vatNumber): array {
    // Sanitizar el numero de VAT: eliminar espacios y puntos.
    $vatNumber = preg_replace('/[\s.\-]/', '', strtoupper(trim($vatNumber)));

    if (strlen($vatNumber) < 4) {
      return [
        'is_valid' => FALSE,
        'company_name' => '',
        'company_address' => '',
        'request_id' => '',
        'country_code' => '',
        'vat_body' => '',
        'validated_at' => date('c'),
        'error' => (string) new TranslatableMarkup('Numero de IVA demasiado corto. Formato esperado: prefijo de pais (2 letras) + numero.'),
      ];
    }

    // Extraer codigo de pais (2 primeros caracteres) y cuerpo del VAT.
    $countryCode = substr($vatNumber, 0, 2);
    $vatBody = substr($vatNumber, 2);

    // Generar ID de peticion unico para trazabilidad.
    $requestId = sprintf('VIES-%s-%s', date('YmdHis'), bin2hex(random_bytes(4)));

    try {
      // Construir el envelope SOAP para la consulta checkVat.
      $soapEnvelope = $this->buildSoapEnvelope($countryCode, $vatBody);

      // Realizar la peticion HTTP POST al servicio VIES.
      $response = $this->httpClient->request('POST', self::VIES_SOAP_URL, [
        'headers' => [
          'Content-Type' => 'text/xml; charset=utf-8',
          'SOAPAction' => '',
        ],
        'body' => $soapEnvelope,
        'timeout' => 30,
        'connect_timeout' => 10,
      ]);

      $statusCode = $response->getStatusCode();
      $body = (string) $response->getBody();

      if ($statusCode !== 200) {
        $this->logger->warning('ViesValidator: Respuesta HTTP @code de VIES para VAT @vat', [
          '@code' => $statusCode,
          '@vat' => $vatNumber,
        ]);
        return $this->buildErrorResponse($countryCode, $vatBody, $requestId,
          (string) new TranslatableMarkup('Servicio VIES respondio con codigo HTTP @code', ['@code' => $statusCode])
        );
      }

      // Parsear la respuesta XML de VIES.
      $result = $this->parseSoapResponse($body);

      $validationData = [
        'is_valid' => $result['valid'],
        'company_name' => $result['name'],
        'company_address' => $result['address'],
        'request_id' => $requestId,
        'country_code' => $countryCode,
        'vat_body' => $vatBody,
        'validated_at' => date('c'),
        'error' => NULL,
      ];

      // Almacenar el resultado como entidad ViesValidation.
      $this->storeValidation($vatNumber, $validationData);

      $this->logger->info('ViesValidator: VAT @vat validado: @valid (empresa: @name)', [
        '@vat' => $vatNumber,
        '@valid' => $result['valid'] ? 'VALIDO' : 'NO VALIDO',
        '@name' => $result['name'],
      ]);

      return $validationData;

    }
    catch (\Exception $e) {
      $this->logger->error('ViesValidator: Error validando VAT @vat contra VIES: @error', [
        '@vat' => $vatNumber,
        '@error' => $e->getMessage(),
      ]);
      return $this->buildErrorResponse($countryCode, $vatBody, $requestId,
        (string) new TranslatableMarkup('Servicio VIES no disponible')
      );
    }
  }

  /**
   * Obtiene la ultima validacion almacenada para un numero de IVA.
   *
   * LOGICA:
   * Consulta las entidades ViesValidation filtrando por vat_number,
   * ordenadas por fecha de creacion descendente, y retorna la mas reciente.
   *
   * @param string $vatNumber
   *   Numero de IVA completo (ej: 'ES12345678A').
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   La entidad ViesValidation mas reciente, o NULL si no hay ninguna.
   */
  public function getLastValidation(string $vatNumber): mixed {
    $vatNumber = preg_replace('/[\s.\-]/', '', strtoupper(trim($vatNumber)));

    try {
      $storage = $this->entityTypeManager->getStorage('vies_validation');

      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('vat_number', $vatNumber)
        ->sort('created', 'DESC')
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      return $storage->load(reset($ids));

    }
    catch (\Exception $e) {
      $this->logger->error('ViesValidator: Error consultando validacion previa para @vat: @error', [
        '@vat' => $vatNumber,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Verifica si la ultima validacion de un VAT ha expirado.
   *
   * LOGICA:
   * Compara la fecha de la ultima validacion almacenada con la hora actual.
   * Si han pasado mas de $cacheHours horas, se considera expirada.
   * Si no hay validacion previa, se considera expirada (necesita revalidar).
   *
   * @param string $vatNumber
   *   Numero de IVA completo (ej: 'ES12345678A').
   * @param int $cacheHours
   *   Horas de validez de la cache (por defecto 24).
   *
   * @return bool
   *   TRUE si la validacion ha expirado o no existe.
   */
  public function isExpired(string $vatNumber, int $cacheHours = self::DEFAULT_CACHE_HOURS): bool {
    try {
      $lastValidation = $this->getLastValidation($vatNumber);

      if (!$lastValidation) {
        // No hay validacion previa: se considera expirada.
        return TRUE;
      }

      // Obtener el timestamp de creacion de la validacion.
      $createdTimestamp = (int) ($lastValidation->get('created')->value ?? 0);

      if ($createdTimestamp === 0) {
        return TRUE;
      }

      // Calcular si ha superado las horas de cache.
      $expirationTimestamp = $createdTimestamp + ($cacheHours * 3600);

      return time() > $expirationTimestamp;

    }
    catch (\Exception $e) {
      $this->logger->error('ViesValidator: Error verificando expiracion para @vat: @error', [
        '@vat' => $vatNumber,
        '@error' => $e->getMessage(),
      ]);
      // En caso de error, asumir expirada para forzar revalidacion.
      return TRUE;
    }
  }

  /**
   * Construye el envelope SOAP para la consulta checkVat de VIES.
   *
   * LOGICA:
   * Genera el XML SOAP 1.1 con el namespace del servicio VIES
   * incluyendo el codigo de pais y el numero de IVA como parametros.
   *
   * @param string $countryCode
   *   Codigo ISO del pais (2 letras).
   * @param string $vatBody
   *   Numero de IVA sin el prefijo de pais.
   *
   * @return string
   *   El envelope SOAP completo como cadena XML.
   */
  protected function buildSoapEnvelope(string $countryCode, string $vatBody): string {
    // Escapar valores para prevenir inyeccion XML.
    $countryCode = htmlspecialchars($countryCode, ENT_XML1, 'UTF-8');
    $vatBody = htmlspecialchars($vatBody, ENT_XML1, 'UTF-8');

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
                  xmlns:urn="urn:ec.europa.eu:taxud:vies:services:checkVat:types">
  <soapenv:Header/>
  <soapenv:Body>
    <urn:checkVat>
      <urn:countryCode>{$countryCode}</urn:countryCode>
      <urn:vatNumber>{$vatBody}</urn:vatNumber>
    </urn:checkVat>
  </soapenv:Body>
</soapenv:Envelope>
XML;
  }

  /**
   * Parsea la respuesta SOAP de VIES extrayendo los datos de validacion.
   *
   * LOGICA:
   * Extrae los campos valid, name y address del XML de respuesta
   * usando SimpleXML con manejo de namespaces SOAP.
   *
   * @param string $xmlBody
   *   Cuerpo de la respuesta HTTP (XML SOAP).
   *
   * @return array
   *   Array con:
   *   - 'valid': (bool) Resultado de la validacion.
   *   - 'name': (string) Nombre de la empresa.
   *   - 'address': (string) Direccion fiscal.
   */
  protected function parseSoapResponse(string $xmlBody): array {
    $default = [
      'valid' => FALSE,
      'name' => '',
      'address' => '',
    ];

    try {
      // Suprimir warnings de XML malformado para manejarlos manualmente.
      $xml = @simplexml_load_string($xmlBody);
      if ($xml === FALSE) {
        $this->logger->warning('ViesValidator: No se pudo parsear la respuesta XML de VIES.');
        return $default;
      }

      // Registrar namespaces SOAP para navegar el XML.
      $namespaces = $xml->getNamespaces(TRUE);
      $soapNs = $namespaces['soap'] ?? $namespaces['soapenv'] ?? 'http://schemas.xmlsoap.org/soap/envelope/';

      $body = $xml->children($soapNs)->Body ?? NULL;
      if (!$body) {
        return $default;
      }

      // El namespace de la respuesta VIES.
      $viesNs = 'urn:ec.europa.eu:taxud:vies:services:checkVat:types';
      $response = $body->children($viesNs)->checkVatResponse ?? NULL;

      if (!$response) {
        return $default;
      }

      return [
        'valid' => strtolower((string) ($response->valid ?? 'false')) === 'true',
        'name' => trim((string) ($response->name ?? '')),
        'address' => trim((string) ($response->address ?? '')),
      ];

    }
    catch (\Exception $e) {
      $this->logger->error('ViesValidator: Error parseando respuesta SOAP: @error', [
        '@error' => $e->getMessage(),
      ]);
      return $default;
    }
  }

  /**
   * Almacena el resultado de una validacion como entidad ViesValidation.
   *
   * LOGICA:
   * Crea una nueva entidad ViesValidation con todos los datos de la
   * validacion para mantener un historial de consultas y servir de cache.
   *
   * @param string $vatNumber
   *   Numero de IVA completo.
   * @param array $validationData
   *   Datos de la validacion a almacenar.
   */
  protected function storeValidation(string $vatNumber, array $validationData): void {
    try {
      $storage = $this->entityTypeManager->getStorage('vies_validation');

      $entity = $storage->create([
        'vat_number' => $vatNumber,
        'country_code' => $validationData['country_code'],
        'vat_body' => $validationData['vat_body'],
        'is_valid' => $validationData['is_valid'],
        'company_name' => $validationData['company_name'],
        'company_address' => $validationData['company_address'],
        'request_id' => $validationData['request_id'],
      ]);

      $entity->save();

    }
    catch (\Exception $e) {
      // No lanzar excepcion si falla el almacenamiento; la validacion ya se realizo.
      $this->logger->warning('ViesValidator: Error almacenando validacion para @vat: @error', [
        '@vat' => $vatNumber,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Construye un array de respuesta de error estandarizado.
   *
   * LOGICA:
   * Genera la estructura de respuesta con is_valid=FALSE y el mensaje
   * de error proporcionado, manteniendo el formato consistente.
   *
   * @param string $countryCode
   *   Codigo de pais extraido del VAT.
   * @param string $vatBody
   *   Numero de VAT sin prefijo.
   * @param string $requestId
   *   ID de la peticion generado.
   * @param string $errorMessage
   *   Mensaje descriptivo del error.
   *
   * @return array
   *   Array de respuesta con formato estandar y error.
   */
  protected function buildErrorResponse(string $countryCode, string $vatBody, string $requestId, string $errorMessage): array {
    return [
      'is_valid' => FALSE,
      'company_name' => '',
      'company_address' => '',
      'request_id' => $requestId,
      'country_code' => $countryCode,
      'vat_body' => $vatBody,
      'validated_at' => date('c'),
      'error' => $errorMessage,
    ];
  }

}
