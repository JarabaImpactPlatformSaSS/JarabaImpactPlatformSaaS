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
   * @todo Refinar el parseo HTML una vez se valide con el formato real
   *   de respuesta del sistema PETETE. La estructura actual es un scaffold.
   */
  public function crawl(array $options = []): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.sources');
    $baseUrl = $config->get('sources.dgt.base_url') ?? 'https://petete.tributos.hacienda.gob.es';

    $dateFrom = $options['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
    $dateTo = $options['date_to'] ?? date('Y-m-d');

    // TODO: Ajustar endpoint y parametros al formato exacto de PETETE.
    // El sistema PETETE permite busqueda por rango de fechas.
    $searchUrl = $baseUrl . '/consultas' . '?' . http_build_query([
      'fecha_desde' => $dateFrom,
      'fecha_hasta' => $dateTo,
      'tipo' => 'vinculante',
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
      $resolutions = $this->parseResponse($html);

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
   * TODO: Esta implementacion es un scaffold. El parseo real depende del
   * formato exacto del HTML devuelto por el sistema PETETE. Refinar una
   * vez se analice la estructura DOM de la pagina de resultados de
   * consultas vinculantes.
   *
   * @param string $html
   *   Contenido HTML de la respuesta de PETETE.
   *
   * @return array
   *   Array de arrays asociativos con los datos crudos de cada consulta.
   */
  protected function parseResponse(string $html): array {
    $resolutions = [];

    if (empty($html)) {
      return $resolutions;
    }

    // TODO: Implementar parseo real del HTML de PETETE.
    // Scaffold: usar DOMDocument para extraer entradas de consultas.
    libxml_use_internal_errors(TRUE);
    $doc = new \DOMDocument();
    $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
    $xpath = new \DOMXPath($doc);

    // TODO: Ajustar selectores XPath al formato real de PETETE.
    // Las consultas vinculantes se presentan en filas de tabla o bloques div.
    $entries = $xpath->query("//tr[contains(@class, 'consulta')] | //div[contains(@class, 'resultado')]");

    if ($entries === FALSE || $entries->length === 0) {
      $this->logger->notice('DGT spider: No se encontraron consultas vinculantes en la respuesta. Posible cambio de formato.');
      libxml_clear_errors();
      return $resolutions;
    }

    foreach ($entries as $entry) {
      // TODO: Extraer datos reales de cada nodo DOM.
      // La referencia DGT sigue el patron V0123-24.
      $refNode = $xpath->query(".//td[1] | .//*[contains(@class, 'referencia')]", $entry);
      $externalRef = $refNode && $refNode->length > 0
        ? trim($refNode->item(0)->textContent)
        : '';

      $titleNode = $xpath->query(".//td[2] | .//*[contains(@class, 'titulo')]", $entry);
      $title = $titleNode && $titleNode->length > 0
        ? trim($titleNode->item(0)->textContent)
        : '';

      $dateNode = $xpath->query(".//td[3] | .//*[contains(@class, 'fecha')]", $entry);
      $dateIssued = $dateNode && $dateNode->length > 0
        ? trim($dateNode->item(0)->textContent)
        : '';

      $linkNode = $xpath->query(".//a/@href", $entry);
      $originalUrl = $linkNode && $linkNode->length > 0
        ? $baseUrl = $this->configFactory->get('jaraba_legal_intelligence.sources')->get('sources.dgt.base_url') . trim($linkNode->item(0)->nodeValue)
        : '';

      // Validar que la referencia sigue el patron V + numero + guion + anho.
      if (empty($externalRef) || !preg_match('/^V\d+-\d{2}$/', $externalRef)) {
        if (!empty($externalRef)) {
          $this->logger->debug('DGT spider: Referencia con formato inesperado: @ref', [
            '@ref' => $externalRef,
          ]);
        }
        continue;
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
