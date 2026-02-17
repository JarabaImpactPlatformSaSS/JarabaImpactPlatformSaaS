<?php

declare(strict_types=1);

namespace Drupal\jaraba_multiregion\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de conversion de monedas con tasas del Banco Central Europeo.
 *
 * ESTRUCTURA:
 * Obtiene y almacena tasas de cambio diarias publicadas por el BCE
 * (Banco Central Europeo) y proporciona conversion entre monedas.
 * Las tasas se almacenan como entidades CurrencyRate para persistencia.
 *
 * LOGICA:
 * - Descarga el feed XML diario del BCE con las tasas de referencia.
 * - Parsea el XML y almacena cada tasa como entidad CurrencyRate.
 * - La conversion usa EUR como moneda pivote: si ninguna de las monedas
 *   es EUR, se calcula el tipo cruzado via EUR (from->EUR->to).
 * - Las tasas del BCE se publican cada dia habil a las 16:00 CET.
 *
 * SINTAXIS:
 * - Constructor con propiedades promovidas (PHP 8.3).
 * - Cadenas visibles usan TranslatableMarkup.
 * - Llamadas HTTP envueltas en try/catch con logger.
 * - Retorna arrays estructurados con importes, tasas y metadatos.
 *
 * @see https://www.ecb.europa.eu/stats/policy_and_exchange_rates/euro_reference_exchange_rates/
 */
class CurrencyConverterService {

  /**
   * URL del feed XML diario del BCE con tasas de referencia.
   *
   * @var string
   */
  protected const ECB_DAILY_RATES_URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

  /**
   * Constructor del servicio.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para acceder al storage de CurrencyRate.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para descargar las tasas del BCE.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de logger para registrar errores y operaciones.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ClientInterface $httpClient,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Convierte un importe entre dos monedas.
   *
   * LOGICA:
   * 1. Si las monedas origen y destino son iguales, retorna el mismo importe.
   * 2. Obtiene la tasa de cambio entre las dos monedas via getRate().
   * 3. Si no hay tasa disponible, intenta descargar tasas frescas del BCE.
   * 4. Aplica la conversion y retorna el resultado con metadatos.
   *
   * @param float $amount
   *   Importe a convertir en la moneda de origen.
   * @param string $from
   *   Codigo ISO 4217 de la moneda origen (ej: 'EUR').
   * @param string $to
   *   Codigo ISO 4217 de la moneda destino (ej: 'USD').
   *
   * @return array
   *   Array estructurado con:
   *   - 'amount': (float) Importe convertido a la moneda destino.
   *   - 'rate': (float) Tasa de cambio aplicada.
   *   - 'from': (string) Codigo de moneda origen.
   *   - 'to': (string) Codigo de moneda destino.
   *   - 'fetched_at': (string) Fecha/hora de la tasa utilizada (ISO 8601).
   *   - 'error': (string|null) Mensaje de error si la conversion fallo.
   */
  public function convert(float $amount, string $from, string $to): array {
    $from = strtoupper(trim($from));
    $to = strtoupper(trim($to));

    // Misma moneda: sin conversion necesaria.
    if ($from === $to) {
      return [
        'amount' => $amount,
        'rate' => 1.0,
        'from' => $from,
        'to' => $to,
        'fetched_at' => date('c'),
        'error' => NULL,
      ];
    }

    try {
      // Intentar obtener la tasa almacenada.
      $rate = $this->getRate($from, $to);

      // Si no hay tasa disponible, intentar descargar tasas frescas del BCE.
      if ($rate === NULL) {
        $this->logger->info('CurrencyConverter: Tasa @from->@to no disponible, descargando tasas del BCE.', [
          '@from' => $from,
          '@to' => $to,
        ]);
        $this->fetchRates('EUR');
        $rate = $this->getRate($from, $to);
      }

      // Si aun no hay tasa, la conversion no es posible.
      if ($rate === NULL) {
        return [
          'amount' => 0.0,
          'rate' => 0.0,
          'from' => $from,
          'to' => $to,
          'fetched_at' => date('c'),
          'error' => (string) new TranslatableMarkup(
            'No se encontro tasa de cambio para @from a @to.',
            ['@from' => $from, '@to' => $to]
          ),
        ];
      }

      $convertedAmount = round($amount * $rate, 4);

      return [
        'amount' => $convertedAmount,
        'rate' => $rate,
        'from' => $from,
        'to' => $to,
        'fetched_at' => date('c'),
        'error' => NULL,
      ];

    }
    catch (\Exception $e) {
      $this->logger->error('CurrencyConverter: Error convirtiendo @amount @from a @to: @error', [
        '@amount' => $amount,
        '@from' => $from,
        '@to' => $to,
        '@error' => $e->getMessage(),
      ]);
      return [
        'amount' => 0.0,
        'rate' => 0.0,
        'from' => $from,
        'to' => $to,
        'fetched_at' => date('c'),
        'error' => (string) new TranslatableMarkup('Error en la conversion de moneda: @error', ['@error' => $e->getMessage()]),
      ];
    }
  }

  /**
   * Descarga y almacena las tasas de cambio diarias del BCE.
   *
   * LOGICA:
   * 1. Descarga el feed XML eurofxref-daily.xml del BCE.
   * 2. Parsea el XML extrayendo pares moneda/tasa del nodo Cube.
   * 3. Para cada tasa, crea o actualiza una entidad CurrencyRate.
   * 4. Retorna el numero total de tasas almacenadas.
   *
   * Las tasas del BCE usan EUR como moneda base. Cada Cube contiene
   * la tasa de una moneda con respecto al EUR.
   *
   * @param string $baseCurrency
   *   Moneda base del feed (siempre 'EUR' para el BCE).
   *
   * @return int
   *   Numero de tasas almacenadas correctamente.
   */
  public function fetchRates(string $baseCurrency = 'EUR'): int {
    $storedCount = 0;

    try {
      // Descargar el feed XML del BCE.
      $response = $this->httpClient->request('GET', self::ECB_DAILY_RATES_URL, [
        'timeout' => 30,
        'connect_timeout' => 10,
        'headers' => [
          'Accept' => 'application/xml',
        ],
      ]);

      $statusCode = $response->getStatusCode();
      if ($statusCode !== 200) {
        $this->logger->warning('CurrencyConverter: El BCE respondio con codigo HTTP @code.', [
          '@code' => $statusCode,
        ]);
        return 0;
      }

      $xmlBody = (string) $response->getBody();

      // Parsear el XML del BCE.
      $rates = $this->parseEcbXml($xmlBody);

      if (empty($rates)) {
        $this->logger->warning('CurrencyConverter: No se extrajeron tasas del feed del BCE.');
        return 0;
      }

      // Almacenar cada tasa como entidad CurrencyRate.
      $storage = $this->entityTypeManager->getStorage('currency_rate');

      foreach ($rates as $currencyCode => $rate) {
        try {
          // Buscar si ya existe una tasa para esta moneda con base EUR.
          $existing = $storage->loadByProperties([
            'base_currency' => $baseCurrency,
            'target_currency' => $currencyCode,
          ]);

          if (!empty($existing)) {
            // Actualizar la tasa existente.
            $entity = reset($existing);
            $entity->set('rate', $rate);
            $entity->set('fetched_at', date('c'));
          }
          else {
            // Crear nueva entidad CurrencyRate.
            $entity = $storage->create([
              'base_currency' => $baseCurrency,
              'target_currency' => $currencyCode,
              'rate' => $rate,
              'fetched_at' => date('c'),
            ]);
          }

          $entity->save();
          $storedCount++;

        }
        catch (\Exception $e) {
          $this->logger->warning('CurrencyConverter: Error almacenando tasa @currency: @error', [
            '@currency' => $currencyCode,
            '@error' => $e->getMessage(),
          ]);
        }
      }

      $this->logger->info('CurrencyConverter: @count tasas de cambio actualizadas desde el BCE.', [
        '@count' => $storedCount,
      ]);

    }
    catch (\Exception $e) {
      $this->logger->error('CurrencyConverter: Error descargando tasas del BCE: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $storedCount;
  }

  /**
   * Obtiene la tasa de cambio entre dos monedas.
   *
   * LOGICA:
   * Las tasas del BCE usan EUR como base. Para obtener la tasa entre
   * dos monedas cualesquiera:
   * 1. Si from='EUR': busqueda directa de la tasa EUR->to.
   * 2. Si to='EUR': inversa de la tasa EUR->from (1/rate).
   * 3. Caso general: tipo cruzado via EUR = (EUR->to) / (EUR->from).
   *
   * @param string $from
   *   Codigo ISO 4217 de la moneda origen.
   * @param string $to
   *   Codigo ISO 4217 de la moneda destino.
   *
   * @return float|null
   *   La tasa de cambio, o NULL si no hay datos disponibles.
   */
  public function getRate(string $from, string $to): ?float {
    $from = strtoupper(trim($from));
    $to = strtoupper(trim($to));

    // Misma moneda: tasa = 1.
    if ($from === $to) {
      return 1.0;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('currency_rate');

      // Caso 1: Conversion directa desde EUR.
      if ($from === 'EUR') {
        return $this->loadRate($storage, 'EUR', $to);
      }

      // Caso 2: Conversion hacia EUR (inversa).
      if ($to === 'EUR') {
        $rateFromEur = $this->loadRate($storage, 'EUR', $from);
        if ($rateFromEur === NULL || $rateFromEur == 0.0) {
          return NULL;
        }
        return round(1.0 / $rateFromEur, 6);
      }

      // Caso 3: Tipo cruzado via EUR (from->EUR->to).
      $rateFromEurToFrom = $this->loadRate($storage, 'EUR', $from);
      $rateFromEurToTo = $this->loadRate($storage, 'EUR', $to);

      if ($rateFromEurToFrom === NULL || $rateFromEurToTo === NULL || $rateFromEurToFrom == 0.0) {
        return NULL;
      }

      return round($rateFromEurToTo / $rateFromEurToFrom, 6);

    }
    catch (\Exception $e) {
      $this->logger->error('CurrencyConverter: Error obteniendo tasa @from->@to: @error', [
        '@from' => $from,
        '@to' => $to,
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Carga la tasa de cambio desde una entidad CurrencyRate almacenada.
   *
   * LOGICA:
   * Busca la entidad CurrencyRate con la combinacion base/target
   * y retorna el valor del campo rate.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Storage de la entidad CurrencyRate.
   * @param string $baseCurrency
   *   Moneda base (normalmente 'EUR').
   * @param string $targetCurrency
   *   Moneda destino.
   *
   * @return float|null
   *   La tasa almacenada, o NULL si no existe.
   */
  protected function loadRate(mixed $storage, string $baseCurrency, string $targetCurrency): ?float {
    $results = $storage->loadByProperties([
      'base_currency' => $baseCurrency,
      'target_currency' => $targetCurrency,
    ]);

    if (empty($results)) {
      return NULL;
    }

    $entity = reset($results);
    $rateValue = $entity->get('rate')->value ?? NULL;

    return $rateValue !== NULL ? (float) $rateValue : NULL;
  }

  /**
   * Parsea el feed XML diario del BCE extrayendo las tasas de cambio.
   *
   * LOGICA:
   * El XML del BCE tiene la estructura:
   * <gesmes:Envelope>
   *   <Cube>
   *     <Cube time="YYYY-MM-DD">
   *       <Cube currency="USD" rate="1.1234"/>
   *       ...
   *     </Cube>
   *   </Cube>
   * </gesmes:Envelope>
   *
   * Se extraen los atributos currency y rate de cada nodo Cube interno.
   *
   * @param string $xmlBody
   *   Contenido XML del feed del BCE.
   *
   * @return array
   *   Array asociativo [codigo_moneda => tasa_float] (ej: ['USD' => 1.1234]).
   */
  protected function parseEcbXml(string $xmlBody): array {
    $rates = [];

    try {
      // Suprimir warnings de XML para manejarlos manualmente.
      $xml = @simplexml_load_string($xmlBody);

      if ($xml === FALSE) {
        $this->logger->warning('CurrencyConverter: No se pudo parsear el XML del BCE.');
        return [];
      }

      // Registrar namespaces del XML del BCE.
      $namespaces = $xml->getNamespaces(TRUE);

      // El namespace principal del BCE para los datos.
      $ecbNs = $namespaces[''] ?? 'http://www.ecb.int/vocabulary/2002-08-01/eurofxref';

      // Navegar la estructura: Envelope > Cube > Cube[@time] > Cube[@currency]
      $cubeRoot = $xml->Cube ?? NULL;
      if (!$cubeRoot) {
        return [];
      }

      // El primer nivel de Cube contiene el Cube con atributo time.
      foreach ($cubeRoot->Cube as $timeCube) {
        // Dentro del Cube con time, estan los Cube con currency y rate.
        foreach ($timeCube->Cube as $rateCube) {
          $attributes = $rateCube->attributes();
          $currency = (string) ($attributes['currency'] ?? '');
          $rate = (string) ($attributes['rate'] ?? '');

          if (!empty($currency) && !empty($rate)) {
            $rates[$currency] = (float) $rate;
          }
        }
      }

    }
    catch (\Exception $e) {
      $this->logger->error('CurrencyConverter: Error parseando XML del BCE: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $rates;
  }

}
