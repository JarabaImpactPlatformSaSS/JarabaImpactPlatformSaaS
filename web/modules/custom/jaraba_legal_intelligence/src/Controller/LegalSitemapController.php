<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador de sitemap XML para resoluciones legales publicas.
 *
 * ESTRUCTURA:
 * Controlador dedicado a la generacion del fichero sitemap.xml que expone
 * todas las resoluciones legales indexadas (nlp_status = completed) del
 * Legal Intelligence Hub. Genera un documento XML conforme al protocolo
 * sitemaps.org 0.9 con URL, fecha de modificacion, frecuencia de cambio
 * y prioridad calculada por importance_level. La ruta es publica (sin
 * autenticacion) para que los crawlers de buscadores puedan descubrir
 * las paginas SEO de cada resolucion (/legal/{source_slug}/{seo_slug}).
 *
 * LOGICA:
 * sitemap() consulta las entidades legal_resolution con nlp_status = completed,
 * ordenadas por date_issued DESC y limitadas a 50 000 (limite estandar del
 * protocolo sitemap). Para cada resolucion con seo_slug no vacio construye
 * la URL publica, calcula la prioridad segun importance_level (0.9/0.7/0.5/0.3)
 * y establece changefreq = monthly (los documentos legales rara vez cambian).
 * La fecha lastmod se toma del campo changed de la entidad (timestamp UNIX).
 * Devuelve una Response Symfony con Content-Type application/xml y cache
 * publica de 1 hora (max-age=3600).
 *
 * RELACIONES:
 * - LegalSitemapController -> EntityTypeManagerInterface: consulta de entidades
 *   legal_resolution por nlp_status con orden y limite.
 * - LegalSitemapController -> ConfigFactoryInterface: inyectado para posibles
 *   configuraciones futuras (base URL override, limite personalizado, etc.).
 * - LegalSitemapController <- jaraba_legal.sitemap: ruta /legal/sitemap.xml.
 * - LegalSitemapController -> LegalResolutionController::publicSummary: las URLs
 *   generadas apuntan a la ruta publica SEO gestionada por ese controlador.
 */
class LegalSitemapController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Limite maximo de URLs por sitemap segun el protocolo sitemaps.org.
   */
  private const SITEMAP_LIMIT = 50000;

  /**
   * Construye una nueva instancia de LegalSitemapController.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para consultar resoluciones legales.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para posibles ajustes del sitemap.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * Genera el sitemap XML de resoluciones legales publicas.
   *
   * Consulta todas las resoluciones con nlp_status = completed, construye
   * la URL publica SEO de cada una y genera un documento XML conforme al
   * protocolo sitemaps.org 0.9. Solo incluye resoluciones con seo_slug
   * no vacio (las que no tienen slug no tienen pagina publica accesible).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto Request para obtener el scheme y host base de las URLs.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response con Content-Type application/xml y cache publica de 1 hora.
   */
  public function sitemap(Request $request): Response {
    $storage = $this->entityTypeManager->getStorage('legal_resolution');

    // Consultar resoluciones indexadas (nlp_status = completed),
    // ordenadas por fecha de publicacion descendente.
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('nlp_status', 'completed')
      ->sort('date_issued', 'DESC')
      ->range(0, self::SITEMAP_LIMIT);

    $ids = $query->execute();

    // Construir las entradas del sitemap.
    $urls = [];
    if (!empty($ids)) {
      $entities = $storage->loadMultiple($ids);

      foreach ($entities as $entity) {
        $seoSlug = $entity->get('seo_slug')->value ?? '';
        if ($seoSlug === '') {
          continue;
        }

        $sourceId = $entity->get('source_id')->value ?? '';
        $sourceSlug = str_replace('_', '-', $sourceId);

        $loc = $request->getSchemeAndHttpHost() . '/legal/' . $sourceSlug . '/' . $seoSlug;

        // Calcular prioridad segun importance_level.
        $importanceLevel = (int) ($entity->get('importance_level')->value ?? 1);
        $priority = $this->calculatePriority($importanceLevel);

        // Fecha de ultima modificacion desde el campo changed (timestamp UNIX).
        $changed = $entity->get('changed')->value ?? '';
        $lastmod = $changed !== '' ? date('Y-m-d', (int) $changed) : date('Y-m-d');

        $urls[] = [
          'loc' => $loc,
          'lastmod' => $lastmod,
          'changefreq' => 'monthly',
          'priority' => $priority,
        ];
      }
    }

    // Generar el XML.
    $xml = $this->buildSitemapXml($urls);

    $response = new Response($xml, 200, [
      'Content-Type' => 'application/xml; charset=utf-8',
      'Cache-Control' => 'max-age=3600, public',
    ]);

    return $response;
  }

  /**
   * Calcula la prioridad del sitemap segun el nivel de importancia.
   *
   * Mapeo:
   * - importance_level >= 4: 0.9 (alta relevancia juridica).
   * - importance_level = 3: 0.7 (relevancia media).
   * - importance_level = 2: 0.5 (relevancia baja).
   * - importance_level <= 1: 0.3 (relevancia minima).
   *
   * @param int $importanceLevel
   *   Nivel de importancia de la resolucion (1-5).
   *
   * @return string
   *   Valor de prioridad como string con un decimal (0.3 - 0.9).
   */
  private function calculatePriority(int $importanceLevel): string {
    if ($importanceLevel >= 4) {
      return '0.9';
    }
    if ($importanceLevel === 3) {
      return '0.7';
    }
    if ($importanceLevel === 2) {
      return '0.5';
    }

    return '0.3';
  }

  /**
   * Construye el documento XML del sitemap a partir de un array de URLs.
   *
   * Genera XML conforme al esquema sitemaps.org 0.9 con declaracion XML,
   * namespace y elementos <url> con loc, lastmod, changefreq y priority.
   * Usa htmlspecialchars() en loc para escapar caracteres especiales en URLs.
   *
   * @param array $urls
   *   Array de arrays con claves: loc, lastmod, changefreq, priority.
   *
   * @return string
   *   Documento XML completo como string.
   */
  private function buildSitemapXml(array $urls): string {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    foreach ($urls as $url) {
      $xml .= '  <url>' . "\n";
      $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
      $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
      $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
      $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
      $xml .= '  </url>' . "\n";
    }

    $xml .= '</urlset>' . "\n";

    return $xml;
  }

}
