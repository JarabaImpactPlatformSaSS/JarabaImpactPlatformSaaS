<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Spider para DGT (Direccion General de Tributos).
 *
 * ESTRUCTURA:
 * Conector que extrae consultas vinculantes de la base de datos INFORMA
 * de la Direccion General de Tributos (DGT). Las consultas vinculantes
 * son respuestas oficiales de la Administracion tributaria a preguntas
 * concretas de contribuyentes, con efectos vinculantes para la Agencia
 * Tributaria. Su referencia sigue el patron V0123-24 (V + numero + anho).
 *
 * LOGICA:
 * Accede al sistema PETETE de consultas vinculantes de la Hacienda Publica.
 * Construye peticiones con filtros de fecha y parsea la respuesta para
 * extraer: referencia V-numero, titulo/descripcion, texto de la consulta,
 * fecha de emision y organo emisor (siempre DGT). La jurisdiccion es
 * siempre 'fiscal' y el tipo es siempre 'consulta_vinculante'. El texto
 * completo se extrae via Apache Tika en fases posteriores del pipeline.
 *
 * RELACIONES:
 * - DgtSpider -> SpiderInterface: implementa el contrato del spider.
 * - DgtSpider -> GuzzleHttp\ClientInterface: peticiones HTTP a PETETE.
 * - DgtSpider -> ConfigFactoryInterface: lee jaraba_legal_intelligence.sources
 *   para obtener la base_url configurada.
 * - DgtSpider <- LegalIngestionService: invocado via crawl() durante la
 *   ingesta programada semanal.
 *
 * SINTAXIS:
 * Servicio Drupal registrado como jaraba_legal_intelligence.spider.dgt.
 * Inyecta http_client, config.factory y logger.channel.jaraba_legal_intelligence.
 */
class DgtSpider implements SpiderInterface {

  /**
   * Cliente HTTP para peticiones a PETETE.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Factoria de configuracion de Drupal.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Logger del modulo Legal Intelligence.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Construye una nueva instancia de DgtSpider.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para realizar peticiones al sistema PETETE de la DGT.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para acceder a las fuentes configuradas.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    ClientInterface $httpClient,
    ConfigFactoryInterface $configFactory,
    LoggerInterface $logger,
  ) {
    $this->httpClient = $httpClient;
    $this->configFactory = $configFactory;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return 'dgt';
  }

  /**
   * {@inheritdoc}
   */
  public function getFrequency(): string {
    return 'weekly';
  }

  /**
   * {@inheritdoc}
   */
  public function supports(string $sourceId): bool {
    return $sourceId === 'dgt';
  }

  /**
   * {@inheritdoc}
   *
   * Rastreo de consultas vinculantes de la DGT.
   *
   * Construye una peticion al sistema PETETE de Hacienda con filtros de
   * fecha para obtener las ultimas consultas vinculantes publicadas.
   * Parsea la respuesta HTML para extraer la referencia V-numero, titulo,
   * fecha de emision y URL del documento completo.
   *
   * @note Production deployment requires validation against live API responses.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.dgt.base_url') ?? 'https://petete.tributos.hacienda.gob.es';

    $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $dateTo = $options['date_to'] ?? date('Y-m-d');

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for DGT/PETETE.
    // El sistema PETETE de la DGT permite busqueda de consultas vinculantes
    // por rango de fechas. Las fechas se envian en formato DD/MM/YYYY
    // siguiendo la convencion de la administracion tributaria espanhola.
    // El endpoint de consultas acepta parametros de formulario via GET
    // incluyendo fecha_desde, fecha_hasta y tipo_consulta.
    $dateFromFormatted = date('d/m/Y', strtotime($dateFrom));
    $dateToFormatted = date('d/m/Y', strtotime($dateTo));

    $searchUrl = rtrim($baseUrl, '/') . '/consultas-702-702.html?' . http_build_query([
      'NUM_CONSULTA' => '',
      'FECHA_DESDE' => $dateFromFormatted,
      'FECHA_HASTA' => $dateToFormatted,
      'DESCRIPCION' => '',
      'TIPO_CONSULTA' => 'V',
      'ACCION' => 'buscar',
    ]);

    try {
      $response = $this->httpClient->request('GET', $searchUrl, [
        'timeout' => 60,
        'headers' => [
          'Accept' => 'text/html,application/xhtml+xml',
          'User-Agent' => 'JarabaLegalIntelligence/1.0 (legal-research-bot)',
        ],
      ]);

      $html = (string) $response->getBody();
      $resolutions = $this->parseResponse($html, $baseUrl);

      $this->logger->info('DGT spider: @count consultas vinculantes extraidas para el rango @from - @to.', [
        '@count' => count($resolutions),
        '@from' => $dateFrom,
        '@to' => $dateTo,
      ]);

      return $resolutions;
    }
    catch (GuzzleException $e) {
      $this->logger->error('DGT spider: Error HTTP al rastrear @url: @message', [
        '@url' => $searchUrl,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
    catch (\Exception $e) {
      $this->logger->error('DGT spider: Error inesperado: @message', [
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Parsea la respuesta HTML de PETETE y extrae consultas vinculantes.
   *
   * AUDIT-TODO-RESOLVED: Implemented DOM parsing for DGT/PETETE.
   * El sistema PETETE presenta resultados de consultas vinculantes en una
   * tabla HTML con columnas: Num. Consulta (referencia V-numero), Fecha,
   * Descripcion/Asunto, y enlace al texto completo. La tabla de resultados
   * se identifica por su estructura de filas con celdas que contienen
   * la referencia en formato V + 4 digitos + guion + 2 digitos de anio.
   *
   * @note Production deployment requires validation against live API responses.
   *
   * @param string $html
   *   Contenido HTML de la respuesta de PETETE.
   * @param string $baseUrl
   *   URL base del sistema PETETE para resolver URLs relativas.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada consulta.
   */
  protected function parseResponse(string $html, string $baseUrl): array {
    $resolutions = [];

    if (empty($html)) {
      return $resolutions;
    }

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for DGT/PETETE.
    // Usar DOMDocument para extraer entradas de consultas vinculantes.
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new \DOMXPath($doc);

    // AUDIT-TODO-RESOLVED: Implemented DOM parsing for DGT/PETETE.
    // Selectores XPath para la tabla de resultados de PETETE.
    // Las consultas se presentan en filas de tabla dentro de un contenedor
    // con clase 'resultados', 'consultas', o una tabla generica. Cada fila
    // contiene celdas con: referencia (V0123-24), fecha, descripcion.
    // Tambien se buscan bloques div con clase 'resultado' como fallback.
    $entries = $xpath->query(
      "//table[contains(@class, 'resultado')]//tr[position() > 1]"
      . " | //table[contains(@class, 'consulta')]//tr[position() > 1]"
      . " | //table[contains(@class, 'listado')]//tr[position() > 1]"
      . " | //div[contains(@class, 'resultado')]"
      . " | //tr[contains(@class, 'consulta')]"
    );

    // Fallback: buscar cualquier tabla con filas que contengan el patron V + numero.
    if ($entries === FALSE || $entries->length === 0) {
      $entries = $xpath->query("//table//tr[position() > 1]");
    }

    if ($entries === FALSE || $entries->length === 0) {
      $this->logger->notice('DGT spider: No se encontraron consultas vinculantes en la respuesta. Posible cambio de formato.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($entries as $entry) {
      // AUDIT-TODO-RESOLVED: Implemented DOM parsing for DGT/PETETE.
      // Extraer datos de cada fila/bloque de resultado. La estructura
      // esperada de las celdas es:
      //   td[0]: Num. Consulta (referencia V0123-24)
      //   td[1]: Fecha de emision (DD/MM/YYYY)
      //   td[2]: Descripcion/Asunto de la consulta
      //   td[3]: Tipo de impuesto (opcional)
      // Alternativamente, los datos pueden estar en divs con clases
      // semanticas: 'referencia', 'fecha', 'titulo', 'descripcion'.
      $cells = $xpath->query(".//td", $entry);
      $externalRef = '';
      $title = '';
      $dateIssued = '';
      $originalUrl = '';
      $taxType = '';

      if ($cells && $cells->length >= 3) {
        // Formato tabla: celdas td[0..N].
        $externalRef = trim($cells->item(0)->textContent);
        $dateIssued = trim($cells->item(1)->textContent);
        $title = trim($cells->item(2)->textContent);

        // Tipo de impuesto en celda 4 si existe.
        if ($cells->length > 3) {
          $taxType = trim($cells->item(3)->textContent);
        }
      }
      else {
        // Formato bloque div: buscar por clases semanticas.
        $refNode = $xpath->query(
          ".//*[contains(@class, 'referencia')]"
          . " | .//*[contains(@class, 'numero')]"
          . " | .//span[contains(@class, 'num')]",
          $entry,
        );
        if ($refNode && $refNode->length > 0) {
          $externalRef = trim($refNode->item(0)->textContent);
        }

        $titleNode = $xpath->query(
          ".//*[contains(@class, 'titulo')]"
          . " | .//*[contains(@class, 'descripcion')]"
          . " | .//*[contains(@class, 'asunto')]",
          $entry,
        );
        if ($titleNode && $titleNode->length > 0) {
          $title = trim($titleNode->item(0)->textContent);
        }

        $dateNode = $xpath->query(
          ".//*[contains(@class, 'fecha')]",
          $entry,
        );
        if ($dateNode && $dateNode->length > 0) {
          $dateIssued = trim($dateNode->item(0)->textContent);
        }
      }

      // Extraer URL del enlace al texto completo.
      $linkNode = $xpath->query(".//a/@href", $entry);
      if ($linkNode && $linkNode->length > 0) {
        $href = trim($linkNode->item(0)->nodeValue);
        // Convertir URLs relativas a absolutas.
        $originalUrl = str_starts_with($href, 'http') ? $href : rtrim($baseUrl, '/') . '/' . ltrim($href, '/');
      }

      // Normalizar fecha DD/MM/YYYY a Y-m-d.
      if (!empty($dateIssued) && preg_match('#(\d{2})/(\d{2})/(\d{4})#', $dateIssued, $dm)) {
        $dateIssued = $dm[3] . '-' . $dm[2] . '-' . $dm[1];
      }

      // Validar que la referencia sigue el patron V + numero + guion + anho.
      // Patron ampliado: V seguido de 1-5 digitos, guion, 2 digitos de anho.
      if (empty($externalRef) || !preg_match('/^V\d{1,5}-\d{2}$/', $externalRef)) {
        if (!empty($externalRef)) {
          // Intentar extraer el patron V del texto de la referencia.
          if (preg_match('/(V\d{1,5}-\d{2})/', $externalRef, $vMatch)) {
            $externalRef = $vMatch[1];
          }
          else {
            $this->logger->debug('DGT spider: Referencia con formato inesperado: @ref', [
              '@ref' => $externalRef,
            ]);
            continue;
          }
        }
        else {
          continue;
        }
      }

      // Construir titulo descriptivo si el titulo extraido esta vacio.
      if (empty($title)) {
        $title = sprintf('Consulta Vinculante %s', $externalRef);
      }

      // Anotar el tipo de impuesto en el titulo si esta disponible.
      if (!empty($taxType) && !str_contains($title, $taxType)) {
        $title .= ' (' . $taxType . ')';
      }

      $resolutions[] = [
        'source_id' => 'dgt',
        'external_ref' => $externalRef,
        'title' => $title,
        'resolution_type' => 'consulta_vinculante',
        'issuing_body' => 'DGT',
        'jurisdiction' => 'fiscal',
        'date_issued' => $dateIssued,
        'original_url' => $originalUrl,
        // Texto completo se extrae posteriormente via Apache Tika.
        'full_text' => '',
      ];
    }

    libxml_clear_errors();

    return $resolutions;
  }

}
