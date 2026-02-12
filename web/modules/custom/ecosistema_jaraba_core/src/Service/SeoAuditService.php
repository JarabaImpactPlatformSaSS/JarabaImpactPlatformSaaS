<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Performs basic SEO audits on user-provided URLs.
 *
 * This service is used by the ComercioConecta lead magnet "Auditoria SEO
 * Local" to provide immediate value to anonymous visitors. It fetches a
 * URL via Guzzle, parses the HTML DOM, and evaluates key SEO factors
 * against weighted criteria to produce a score (0-100).
 *
 * Checks performed:
 * - Title tag (presence and length)
 * - Meta description (presence and length)
 * - H1 count (exactly 1 is ideal)
 * - Image alt attributes coverage
 * - HTTPS usage
 * - Mobile viewport meta tag
 * - Open Graph tags
 * - Canonical URL
 * - Schema.org structured data
 *
 * @see \Drupal\ecosistema_jaraba_core\Controller\LeadMagnetController
 */
class SeoAuditService {

  use StringTranslationTrait;

  /**
   * Maximum allowed response time in seconds for URL fetch.
   */
  protected const FETCH_TIMEOUT = 15;

  /**
   * Maximum body size to process (2 MB).
   */
  protected const MAX_BODY_SIZE = 2097152;

  /**
   * Ideal title tag length range.
   */
  protected const TITLE_MIN_LENGTH = 30;
  protected const TITLE_MAX_LENGTH = 60;

  /**
   * Ideal meta description length range.
   */
  protected const META_DESC_MIN_LENGTH = 120;
  protected const META_DESC_MAX_LENGTH = 160;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a SeoAuditService.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The Guzzle HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger channel for ecosistema_jaraba_core.
   */
  public function __construct(
    ClientInterface $httpClient,
    LoggerInterface $logger,
  ) {
    $this->httpClient = $httpClient;
    $this->logger = $logger;
  }

  /**
   * Performs a full SEO audit on the given URL.
   *
   * Fetches the URL, parses the HTML, runs all SEO checks, and returns
   * a structured results array with an overall score, individual check
   * results, and actionable recommendations.
   *
   * @param string $url
   *   The URL to audit. Must be a valid, publicly accessible URL.
   *
   * @return array
   *   Structured results with keys:
   *   - 'url': (string) The audited URL.
   *   - 'score': (int) Overall score 0-100.
   *   - 'grade': (string) Letter grade (A/B/C/D/F).
   *   - 'checks': (array) Individual check results, each containing:
   *     - 'name': (string) Check identifier.
   *     - 'label': (string) Human-readable label.
   *     - 'status': (string) 'pass', 'warning', or 'fail'.
   *     - 'message': (string) Descriptive message.
   *     - 'weight': (int) Weight in the scoring algorithm.
   *   - 'recommendations': (array) Prioritized list of improvement strings.
   *   - 'fetched_at': (string) ISO 8601 timestamp of when the audit ran.
   *   - 'error': (string|null) Error message if the audit failed entirely.
   */
  public function audit(string $url): array {
    $result = [
      'url' => $url,
      'score' => 0,
      'grade' => 'F',
      'checks' => [],
      'recommendations' => [],
      'fetched_at' => date('c'),
      'error' => NULL,
    ];

    try {
      // Validate the URL.
      $url = $this->validateAndNormalizeUrl($url);

      // Fetch the HTML content.
      $html = $this->fetchUrl($url);

      // Parse the DOM.
      $dom = $this->parseHtml($html);

      // Run all checks.
      $checks = [];
      $checks[] = $this->checkHttps($url);
      $checks[] = $this->checkTitle($dom);
      $checks[] = $this->checkMetaDescription($dom);
      $checks[] = $this->checkH1($dom);
      $checks[] = $this->checkImageAlt($dom);
      $checks[] = $this->checkViewport($dom);
      $checks[] = $this->checkOpenGraph($dom);
      $checks[] = $this->checkCanonical($dom);
      $checks[] = $this->checkSchemaOrg($dom, $html);

      $result['checks'] = $checks;

      // Calculate the overall score.
      $result['score'] = $this->calculateScore($checks);
      $result['grade'] = $this->scoreToGrade($result['score']);

      // Build recommendations from failed/warning checks.
      $result['recommendations'] = $this->buildRecommendations($checks);

      $this->logger->info('SEO audit completed for @url: score @score (@grade)', [
        '@url' => $url,
        '@score' => $result['score'],
        '@grade' => $result['grade'],
      ]);
    }
    catch (\InvalidArgumentException $e) {
      $result['error'] = $e->getMessage();
      $this->logger->warning('SEO audit validation error for URL: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
    catch (GuzzleException $e) {
      $result['error'] = (string) $this->t('No se pudo acceder a la URL proporcionada. Verifica que sea accesible publicamente.');
      $this->logger->warning('SEO audit fetch error for @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
    }
    catch (\Throwable $e) {
      $result['error'] = (string) $this->t('Error interno al analizar la URL. Por favor, intentalo de nuevo.');
      $this->logger->error('SEO audit unexpected error for @url: @message', [
        '@url' => $url,
        '@message' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Validates and normalizes the URL for auditing.
   *
   * @param string $url
   *   The raw URL input.
   *
   * @return string
   *   The validated and normalized URL.
   *
   * @throws \InvalidArgumentException
   *   If the URL is invalid or uses a disallowed scheme.
   */
  protected function validateAndNormalizeUrl(string $url): string {
    $url = trim($url);

    // Add scheme if missing.
    if (!preg_match('#^https?://#i', $url)) {
      $url = 'https://' . $url;
    }

    $parsed = parse_url($url);
    if ($parsed === FALSE || empty($parsed['host'])) {
      throw new \InvalidArgumentException(
        (string) $this->t('La URL proporcionada no es valida.')
      );
    }

    // Block private/local IPs to prevent SSRF.
    $host = $parsed['host'];
    $ip = gethostbyname($host);
    if ($ip !== $host && $this->isPrivateIp($ip)) {
      throw new \InvalidArgumentException(
        (string) $this->t('No se permiten URLs que apunten a direcciones IP privadas.')
      );
    }

    // Only allow http/https.
    $scheme = strtolower($parsed['scheme'] ?? 'https');
    if (!in_array($scheme, ['http', 'https'], TRUE)) {
      throw new \InvalidArgumentException(
        (string) $this->t('Solo se permiten URLs con protocolo HTTP o HTTPS.')
      );
    }

    return $url;
  }

  /**
   * Checks if an IP address is in a private range (SSRF protection).
   *
   * @param string $ip
   *   The IP address to check.
   *
   * @return bool
   *   TRUE if the IP is private or reserved.
   */
  protected function isPrivateIp(string $ip): bool {
    return filter_var(
      $ip,
      FILTER_VALIDATE_IP,
      FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
    ) === FALSE;
  }

  /**
   * Fetches the HTML content from a URL.
   *
   * @param string $url
   *   The URL to fetch.
   *
   * @return string
   *   The HTML body content.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *   If the HTTP request fails.
   * @throws \RuntimeException
   *   If the response is too large or not HTML.
   */
  protected function fetchUrl(string $url): string {
    $response = $this->httpClient->request('GET', $url, [
      'timeout' => self::FETCH_TIMEOUT,
      'connect_timeout' => 10,
      'headers' => [
        'User-Agent' => 'JarabaSeoAudit/1.0 (+https://ecosistemajaraba.org)',
        'Accept' => 'text/html,application/xhtml+xml',
        'Accept-Language' => 'es-ES,es;q=0.9',
      ],
      'allow_redirects' => [
        'max' => 5,
        'track_redirects' => TRUE,
      ],
      'verify' => TRUE,
      'http_errors' => TRUE,
    ]);

    $contentType = $response->getHeaderLine('Content-Type');
    if ($contentType && !str_contains(strtolower($contentType), 'text/html') && !str_contains(strtolower($contentType), 'application/xhtml')) {
      throw new \RuntimeException('Response is not HTML: ' . $contentType);
    }

    $body = $response->getBody()->getContents();
    if (strlen($body) > self::MAX_BODY_SIZE) {
      $body = substr($body, 0, self::MAX_BODY_SIZE);
    }

    return $body;
  }

  /**
   * Parses HTML content into a DOMDocument.
   *
   * @param string $html
   *   The HTML string to parse.
   *
   * @return \DOMDocument
   *   The parsed DOM document.
   */
  protected function parseHtml(string $html): \DOMDocument {
    $dom = new \DOMDocument();

    // Suppress warnings for malformed HTML.
    $previousState = libxml_use_internal_errors(TRUE);
    $dom->loadHTML(
      '<?xml encoding="UTF-8"?>' . $html,
      LIBXML_NOWARNING | LIBXML_NOERROR,
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previousState);

    return $dom;
  }

  /**
   * Checks whether the URL uses HTTPS.
   *
   * @param string $url
   *   The URL being audited.
   *
   * @return array
   *   The check result array.
   */
  protected function checkHttps(string $url): array {
    $isHttps = str_starts_with(strtolower($url), 'https://');

    return [
      'name' => 'https',
      'label' => (string) $this->t('Conexion HTTPS segura'),
      'status' => $isHttps ? 'pass' : 'fail',
      'message' => $isHttps
        ? (string) $this->t('Tu sitio usa HTTPS. Los datos de tus visitantes estan protegidos.')
        : (string) $this->t('Tu sitio no usa HTTPS. Los motores de busqueda penalizan sitios sin SSL.'),
      'weight' => 15,
    ];
  }

  /**
   * Checks the title tag presence and optimal length.
   *
   * @param \DOMDocument $dom
   *   The parsed DOM.
   *
   * @return array
   *   The check result array.
   */
  protected function checkTitle(\DOMDocument $dom): array {
    $titles = $dom->getElementsByTagName('title');
    $title = $titles->length > 0 ? trim($titles->item(0)->textContent) : '';
    $length = mb_strlen($title);

    if (empty($title)) {
      return [
        'name' => 'title',
        'label' => (string) $this->t('Etiqueta de titulo'),
        'status' => 'fail',
        'message' => (string) $this->t('No se encontro etiqueta &lt;title&gt;. Es fundamental para el posicionamiento en Google.'),
        'weight' => 15,
      ];
    }

    if ($length < self::TITLE_MIN_LENGTH) {
      return [
        'name' => 'title',
        'label' => (string) $this->t('Etiqueta de titulo'),
        'status' => 'warning',
        'message' => (string) $this->t('El titulo tiene @length caracteres. Se recomienda entre @min y @max para mejor visibilidad en Google.', [
          '@length' => $length,
          '@min' => self::TITLE_MIN_LENGTH,
          '@max' => self::TITLE_MAX_LENGTH,
        ]),
        'weight' => 15,
      ];
    }

    if ($length > self::TITLE_MAX_LENGTH) {
      return [
        'name' => 'title',
        'label' => (string) $this->t('Etiqueta de titulo'),
        'status' => 'warning',
        'message' => (string) $this->t('El titulo tiene @length caracteres. Google puede cortarlo. Se recomienda maximo @max caracteres.', [
          '@length' => $length,
          '@max' => self::TITLE_MAX_LENGTH,
        ]),
        'weight' => 15,
      ];
    }

    return [
      'name' => 'title',
      'label' => (string) $this->t('Etiqueta de titulo'),
      'status' => 'pass',
      'message' => (string) $this->t('Titulo correcto: "@title" (@length caracteres).', [
        '@title' => mb_substr($title, 0, 50) . ($length > 50 ? '...' : ''),
        '@length' => $length,
      ]),
      'weight' => 15,
    ];
  }

  /**
   * Checks the meta description tag presence and length.
   *
   * @param \DOMDocument $dom
   *   The parsed DOM.
   *
   * @return array
   *   The check result array.
   */
  protected function checkMetaDescription(\DOMDocument $dom): array {
    $description = $this->getMetaContent($dom, 'description');
    $length = mb_strlen($description);

    if (empty($description)) {
      return [
        'name' => 'meta_description',
        'label' => (string) $this->t('Meta descripcion'),
        'status' => 'fail',
        'message' => (string) $this->t('No se encontro meta descripcion. Google mostrara un extracto aleatorio de tu pagina.'),
        'weight' => 12,
      ];
    }

    if ($length < self::META_DESC_MIN_LENGTH) {
      return [
        'name' => 'meta_description',
        'label' => (string) $this->t('Meta descripcion'),
        'status' => 'warning',
        'message' => (string) $this->t('La meta descripcion tiene @length caracteres. Se recomienda entre @min y @max para mayor impacto.', [
          '@length' => $length,
          '@min' => self::META_DESC_MIN_LENGTH,
          '@max' => self::META_DESC_MAX_LENGTH,
        ]),
        'weight' => 12,
      ];
    }

    if ($length > self::META_DESC_MAX_LENGTH) {
      return [
        'name' => 'meta_description',
        'label' => (string) $this->t('Meta descripcion'),
        'status' => 'warning',
        'message' => (string) $this->t('La meta descripcion tiene @length caracteres. Google la cortara. Maximo recomendado: @max.', [
          '@length' => $length,
          '@max' => self::META_DESC_MAX_LENGTH,
        ]),
        'weight' => 12,
      ];
    }

    return [
      'name' => 'meta_description',
      'label' => (string) $this->t('Meta descripcion'),
      'status' => 'pass',
      'message' => (string) $this->t('Meta descripcion correcta (@length caracteres).', [
        '@length' => $length,
      ]),
      'weight' => 12,
    ];
  }

  /**
   * Checks H1 tag count (should be exactly 1).
   *
   * @param \DOMDocument $dom
   *   The parsed DOM.
   *
   * @return array
   *   The check result array.
   */
  protected function checkH1(\DOMDocument $dom): array {
    $h1s = $dom->getElementsByTagName('h1');
    $count = $h1s->length;

    if ($count === 0) {
      return [
        'name' => 'h1',
        'label' => (string) $this->t('Encabezado H1'),
        'status' => 'fail',
        'message' => (string) $this->t('No se encontro ningun encabezado H1. Cada pagina necesita exactamente un H1 con la palabra clave principal.'),
        'weight' => 12,
      ];
    }

    if ($count > 1) {
      return [
        'name' => 'h1',
        'label' => (string) $this->t('Encabezado H1'),
        'status' => 'warning',
        'message' => (string) $this->t('Se encontraron @count encabezados H1. Lo ideal es tener exactamente uno por pagina.', [
          '@count' => $count,
        ]),
        'weight' => 12,
      ];
    }

    $h1Text = trim($h1s->item(0)->textContent);
    return [
      'name' => 'h1',
      'label' => (string) $this->t('Encabezado H1'),
      'status' => 'pass',
      'message' => (string) $this->t('Un encabezado H1 encontrado: "@text".', [
        '@text' => mb_substr($h1Text, 0, 60) . (mb_strlen($h1Text) > 60 ? '...' : ''),
      ]),
      'weight' => 12,
    ];
  }

  /**
   * Checks image alt text coverage.
   *
   * @param \DOMDocument $dom
   *   The parsed DOM.
   *
   * @return array
   *   The check result array.
   */
  protected function checkImageAlt(\DOMDocument $dom): array {
    $images = $dom->getElementsByTagName('img');
    $totalImages = $images->length;

    if ($totalImages === 0) {
      return [
        'name' => 'image_alt',
        'label' => (string) $this->t('Texto alternativo de imagenes'),
        'status' => 'warning',
        'message' => (string) $this->t('No se encontraron imagenes. Las imagenes con texto alternativo mejoran el SEO y la accesibilidad.'),
        'weight' => 10,
      ];
    }

    $withAlt = 0;
    for ($i = 0; $i < $totalImages; $i++) {
      $img = $images->item($i);
      $alt = $img->getAttribute('alt');
      if (!empty(trim($alt))) {
        $withAlt++;
      }
    }

    $percentage = (int) round(($withAlt / $totalImages) * 100);

    if ($percentage === 100) {
      return [
        'name' => 'image_alt',
        'label' => (string) $this->t('Texto alternativo de imagenes'),
        'status' => 'pass',
        'message' => (string) $this->t('Todas las imagenes (@count) tienen texto alternativo.', [
          '@count' => $totalImages,
        ]),
        'weight' => 10,
      ];
    }

    $missing = $totalImages - $withAlt;

    if ($percentage >= 70) {
      return [
        'name' => 'image_alt',
        'label' => (string) $this->t('Texto alternativo de imagenes'),
        'status' => 'warning',
        'message' => (string) $this->t('@missing de @total imagenes no tienen texto alternativo (@pct% con alt).', [
          '@missing' => $missing,
          '@total' => $totalImages,
          '@pct' => $percentage,
        ]),
        'weight' => 10,
      ];
    }

    return [
      'name' => 'image_alt',
      'label' => (string) $this->t('Texto alternativo de imagenes'),
      'status' => 'fail',
      'message' => (string) $this->t('@missing de @total imagenes no tienen texto alternativo. Solo @pct% lo incluyen.', [
        '@missing' => $missing,
        '@total' => $totalImages,
        '@pct' => $percentage,
      ]),
      'weight' => 10,
    ];
  }

  /**
   * Checks for the mobile viewport meta tag.
   *
   * @param \DOMDocument $dom
   *   The parsed DOM.
   *
   * @return array
   *   The check result array.
   */
  protected function checkViewport(\DOMDocument $dom): array {
    $viewport = $this->getMetaContent($dom, 'viewport');

    if (empty($viewport)) {
      return [
        'name' => 'viewport',
        'label' => (string) $this->t('Meta viewport (movil)'),
        'status' => 'fail',
        'message' => (string) $this->t('No se encontro la etiqueta meta viewport. Tu web puede no verse bien en moviles, lo que afecta al posicionamiento.'),
        'weight' => 12,
      ];
    }

    $hasWidth = str_contains(strtolower($viewport), 'width=device-width');

    if (!$hasWidth) {
      return [
        'name' => 'viewport',
        'label' => (string) $this->t('Meta viewport (movil)'),
        'status' => 'warning',
        'message' => (string) $this->t('Meta viewport encontrado pero puede no estar configurado correctamente. Se recomienda "width=device-width, initial-scale=1".'),
        'weight' => 12,
      ];
    }

    return [
      'name' => 'viewport',
      'label' => (string) $this->t('Meta viewport (movil)'),
      'status' => 'pass',
      'message' => (string) $this->t('Meta viewport configurado correctamente para dispositivos moviles.'),
      'weight' => 12,
    ];
  }

  /**
   * Checks for Open Graph meta tags.
   *
   * @param \DOMDocument $dom
   *   The parsed DOM.
   *
   * @return array
   *   The check result array.
   */
  protected function checkOpenGraph(\DOMDocument $dom): array {
    $requiredOg = ['og:title', 'og:description', 'og:image', 'og:url'];
    $found = [];

    $metas = $dom->getElementsByTagName('meta');
    for ($i = 0; $i < $metas->length; $i++) {
      $meta = $metas->item($i);
      $property = $meta->getAttribute('property');
      if (in_array($property, $requiredOg, TRUE) && !empty($meta->getAttribute('content'))) {
        $found[] = $property;
      }
    }

    $foundCount = count($found);
    $totalRequired = count($requiredOg);

    if ($foundCount === 0) {
      return [
        'name' => 'open_graph',
        'label' => (string) $this->t('Open Graph (redes sociales)'),
        'status' => 'fail',
        'message' => (string) $this->t('No se encontraron etiquetas Open Graph. Cuando compartan tu web en redes sociales, no se mostrara imagen ni descripcion.'),
        'weight' => 8,
      ];
    }

    if ($foundCount < $totalRequired) {
      $missing = array_diff($requiredOg, $found);
      return [
        'name' => 'open_graph',
        'label' => (string) $this->t('Open Graph (redes sociales)'),
        'status' => 'warning',
        'message' => (string) $this->t('Se encontraron @found de @total etiquetas Open Graph. Faltan: @missing.', [
          '@found' => $foundCount,
          '@total' => $totalRequired,
          '@missing' => implode(', ', $missing),
        ]),
        'weight' => 8,
      ];
    }

    return [
      'name' => 'open_graph',
      'label' => (string) $this->t('Open Graph (redes sociales)'),
      'status' => 'pass',
      'message' => (string) $this->t('Todas las etiquetas Open Graph basicas estan presentes. Tu web se vera bien al compartirla.'),
      'weight' => 8,
    ];
  }

  /**
   * Checks for a canonical URL tag.
   *
   * @param \DOMDocument $dom
   *   The parsed DOM.
   *
   * @return array
   *   The check result array.
   */
  protected function checkCanonical(\DOMDocument $dom): array {
    $links = $dom->getElementsByTagName('link');
    $canonical = NULL;

    for ($i = 0; $i < $links->length; $i++) {
      $link = $links->item($i);
      if (strtolower($link->getAttribute('rel')) === 'canonical') {
        $canonical = $link->getAttribute('href');
        break;
      }
    }

    if (empty($canonical)) {
      return [
        'name' => 'canonical',
        'label' => (string) $this->t('URL canonica'),
        'status' => 'warning',
        'message' => (string) $this->t('No se encontro etiqueta de URL canonica. Esto puede causar problemas de contenido duplicado.'),
        'weight' => 8,
      ];
    }

    return [
      'name' => 'canonical',
      'label' => (string) $this->t('URL canonica'),
      'status' => 'pass',
      'message' => (string) $this->t('URL canonica configurada: @url', [
        '@url' => mb_substr($canonical, 0, 80) . (mb_strlen($canonical) > 80 ? '...' : ''),
      ]),
      'weight' => 8,
    ];
  }

  /**
   * Checks for Schema.org structured data.
   *
   * @param \DOMDocument $dom
   *   The parsed DOM.
   * @param string $html
   *   The raw HTML content (for JSON-LD detection).
   *
   * @return array
   *   The check result array.
   */
  protected function checkSchemaOrg(\DOMDocument $dom, string $html): array {
    $hasJsonLd = FALSE;
    $hasMicrodata = FALSE;

    // Check for JSON-LD scripts.
    $scripts = $dom->getElementsByTagName('script');
    for ($i = 0; $i < $scripts->length; $i++) {
      $script = $scripts->item($i);
      if (strtolower($script->getAttribute('type')) === 'application/ld+json') {
        $content = trim($script->textContent);
        if (!empty($content)) {
          $decoded = json_decode($content, TRUE);
          if ($decoded !== NULL && (isset($decoded['@type']) || isset($decoded['@graph']))) {
            $hasJsonLd = TRUE;
            break;
          }
        }
      }
    }

    // Check for Microdata attributes.
    if (!$hasJsonLd) {
      $hasMicrodata = str_contains($html, 'itemscope') && str_contains($html, 'itemtype');
    }

    if (!$hasJsonLd && !$hasMicrodata) {
      return [
        'name' => 'schema_org',
        'label' => (string) $this->t('Datos estructurados (Schema.org)'),
        'status' => 'fail',
        'message' => (string) $this->t('No se encontraron datos estructurados. Anade Schema.org para obtener resultados enriquecidos en Google (estrellas, precios, horarios).'),
        'weight' => 8,
      ];
    }

    $format = $hasJsonLd ? 'JSON-LD' : 'Microdata';
    return [
      'name' => 'schema_org',
      'label' => (string) $this->t('Datos estructurados (Schema.org)'),
      'status' => 'pass',
      'message' => (string) $this->t('Se encontraron datos estructurados (@format). Esto mejora la visibilidad en Google.', [
        '@format' => $format,
      ]),
      'weight' => 8,
    ];
  }

  /**
   * Calculates the overall score from individual checks.
   *
   * The score is calculated as a weighted average:
   * - 'pass' = 100% of weight
   * - 'warning' = 50% of weight
   * - 'fail' = 0% of weight
   *
   * @param array $checks
   *   Array of individual check results.
   *
   * @return int
   *   The overall score from 0 to 100.
   */
  protected function calculateScore(array $checks): int {
    $totalWeight = 0;
    $earnedScore = 0;

    foreach ($checks as $check) {
      $weight = $check['weight'] ?? 0;
      $totalWeight += $weight;

      switch ($check['status']) {
        case 'pass':
          $earnedScore += $weight;
          break;

        case 'warning':
          $earnedScore += (int) round($weight * 0.5);
          break;

        case 'fail':
        default:
          // No points earned.
          break;
      }
    }

    if ($totalWeight === 0) {
      return 0;
    }

    return (int) round(($earnedScore / $totalWeight) * 100);
  }

  /**
   * Converts a numeric score to a letter grade.
   *
   * @param int $score
   *   Score from 0-100.
   *
   * @return string
   *   Letter grade: A (90-100), B (75-89), C (60-74), D (40-59), F (0-39).
   */
  protected function scoreToGrade(int $score): string {
    if ($score >= 90) {
      return 'A';
    }
    if ($score >= 75) {
      return 'B';
    }
    if ($score >= 60) {
      return 'C';
    }
    if ($score >= 40) {
      return 'D';
    }

    return 'F';
  }

  /**
   * Builds prioritized recommendations from check results.
   *
   * Recommendations are ordered by priority: failures first (by weight
   * descending), then warnings (by weight descending).
   *
   * @param array $checks
   *   Array of individual check results.
   *
   * @return array
   *   Ordered array of recommendation strings.
   */
  protected function buildRecommendations(array $checks): array {
    $recommendations = [];

    // Collect failures and warnings with their weights.
    $failures = [];
    $warnings = [];

    foreach ($checks as $check) {
      if ($check['status'] === 'fail') {
        $failures[] = $check;
      }
      elseif ($check['status'] === 'warning') {
        $warnings[] = $check;
      }
    }

    // Sort by weight descending (highest priority first).
    usort($failures, fn($a, $b) => ($b['weight'] ?? 0) - ($a['weight'] ?? 0));
    usort($warnings, fn($a, $b) => ($b['weight'] ?? 0) - ($a['weight'] ?? 0));

    foreach ($failures as $check) {
      $recommendations[] = $this->getRecommendation($check['name'], 'fail');
    }

    foreach ($warnings as $check) {
      $recommendations[] = $this->getRecommendation($check['name'], 'warning');
    }

    return array_filter($recommendations);
  }

  /**
   * Returns a specific recommendation based on check name and status.
   *
   * @param string $checkName
   *   The check identifier.
   * @param string $status
   *   The check status ('fail' or 'warning').
   *
   * @return string
   *   A human-readable recommendation string.
   */
  protected function getRecommendation(string $checkName, string $status): string {
    $recommendations = [
      'https' => [
        'fail' => (string) $this->t('CRITICO: Instala un certificado SSL para habilitar HTTPS. Muchos proveedores de hosting lo ofrecen gratis con Let\'s Encrypt.'),
      ],
      'title' => [
        'fail' => (string) $this->t('CRITICO: Anade una etiqueta &lt;title&gt; unica y descriptiva que incluya tu palabra clave principal.'),
        'warning' => (string) $this->t('Optimiza la longitud del titulo a entre 30 y 60 caracteres para mejor visibilidad en resultados de busqueda.'),
      ],
      'meta_description' => [
        'fail' => (string) $this->t('IMPORTANTE: Anade una meta descripcion que resuma el contenido de la pagina e incluya una llamada a la accion.'),
        'warning' => (string) $this->t('Ajusta la meta descripcion a entre 120 y 160 caracteres para que Google la muestre completa.'),
      ],
      'h1' => [
        'fail' => (string) $this->t('IMPORTANTE: Anade exactamente un encabezado H1 por pagina con tu palabra clave principal.'),
        'warning' => (string) $this->t('Reduce a un solo encabezado H1 por pagina. Usa H2-H6 para el resto de secciones.'),
      ],
      'image_alt' => [
        'fail' => (string) $this->t('Anade texto alternativo descriptivo a tus imagenes. Mejora SEO y hace tu web accesible.'),
        'warning' => (string) $this->t('Algunas imagenes no tienen texto alternativo. Revisalas y anade descripciones relevantes.'),
      ],
      'viewport' => [
        'fail' => (string) $this->t('CRITICO: Anade &lt;meta name="viewport" content="width=device-width, initial-scale=1"&gt; para compatibilidad movil.'),
        'warning' => (string) $this->t('Revisa la configuracion de viewport. El valor recomendado es "width=device-width, initial-scale=1".'),
      ],
      'open_graph' => [
        'fail' => (string) $this->t('Anade etiquetas Open Graph (og:title, og:description, og:image, og:url) para que tu web se vea bien al compartirla en redes sociales.'),
        'warning' => (string) $this->t('Completa las etiquetas Open Graph que faltan para una mejor presentacion en redes sociales.'),
      ],
      'canonical' => [
        'warning' => (string) $this->t('Anade una etiqueta &lt;link rel="canonical"&gt; para evitar problemas de contenido duplicado.'),
      ],
      'schema_org' => [
        'fail' => (string) $this->t('Anade datos estructurados Schema.org (JSON-LD recomendado) para obtener resultados enriquecidos en Google. Para comercios locales usa LocalBusiness.'),
      ],
    ];

    return $recommendations[$checkName][$status] ?? '';
  }

  /**
   * Extracts the content attribute from a meta tag by name.
   *
   * @param \DOMDocument $dom
   *   The parsed DOM.
   * @param string $name
   *   The meta tag name attribute value.
   *
   * @return string
   *   The meta content value, or empty string if not found.
   */
  protected function getMetaContent(\DOMDocument $dom, string $name): string {
    $metas = $dom->getElementsByTagName('meta');
    for ($i = 0; $i < $metas->length; $i++) {
      $meta = $metas->item($i);
      if (strtolower($meta->getAttribute('name')) === strtolower($name)) {
        return trim($meta->getAttribute('content'));
      }
    }

    return '';
  }

}
