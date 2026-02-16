<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_intelligence\Entity\LegalResolution;
use Drupal\jaraba_legal_intelligence\Service\LegalCitationService;
use Drupal\jaraba_legal_intelligence\Service\LegalSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador de paginas de detalle y endpoints API de resoluciones legales.
 *
 * ESTRUCTURA:
 * Controlador principal del frontend del Legal Intelligence Hub para todo lo
 * relacionado con resoluciones individuales. Gestiona seis rutas: detalle
 * autenticado con texto completo, insercion de citas en expedientes (slide-panel
 * AJAX), resoluciones similares, pagina publica SEO (abstract + metadatos),
 * endpoint API REST de detalle y endpoint API de bookmark (favoritos).
 * Las rutas frontend devuelven render arrays con temas Twig dedicados;
 * las rutas API devuelven JsonResponse.
 *
 * LOGICA:
 * view() carga la resolucion por source_id + external_ref (clave de negocio)
 * y enriquece la pagina con el grafo de citas y resoluciones similares.
 * cite() genera texto de cita en 4 formatos (formal, resumida, bibliografica,
 * nota al pie) para insertar en escritos juridicos via slide-panel.
 * publicSummary() expone una pagina SEO sin autenticacion con abstract y
 * metadatos (sin texto completo). apiBookmark() permite POST/DELETE para
 * gestionar favoritos del profesional actual.
 *
 * RELACIONES:
 * - LegalResolutionController -> LegalSearchService: busqueda de similares.
 * - LegalResolutionController -> LegalCitationService: generacion de citas,
 *   grafo de citas y gestion de bookmarks.
 * - LegalResolutionController -> EntityTypeManagerInterface: carga de entidades
 *   LegalResolution y LegalBookmark.
 * - LegalResolutionController <- jaraba_legal.resolution: ruta detalle.
 * - LegalResolutionController <- jaraba_legal.cite: ruta insercion de citas.
 * - LegalResolutionController <- jaraba_legal.similar: ruta similares.
 * - LegalResolutionController <- jaraba_legal.public_summary: ruta SEO publica.
 * - LegalResolutionController <- jaraba_legal.api.resolution: API detalle.
 * - LegalResolutionController <- jaraba_legal.api.bookmark: API favoritos.
 */
class LegalResolutionController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Construye una nueva instancia de LegalResolutionController.
   *
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalSearchService $searchService
   *   Servicio de busqueda semantica para resoluciones similares.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalCitationService $citationService
   *   Servicio de citas para generar texto de cita, grafo y bookmarks.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para cargar resoluciones y bookmarks.
   */
  public function __construct(
    protected LegalSearchService $searchService,
    protected LegalCitationService $citationService,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_legal_intelligence.search'),
      $container->get('jaraba_legal_intelligence.citations'),
      $container->get('entity_type.manager'),
    );
  }

  // ===========================================================================
  // PAGINAS FRONTEND: Detalle, cita, similares y pagina publica SEO.
  // ===========================================================================

  /**
   * Pagina de detalle de una resolucion legal autenticada.
   *
   * Carga la resolucion por source_id + external_ref (clave de negocio),
   * enriquece la pagina con el grafo de citas inter-resoluciones y con
   * resoluciones similares encontradas via busqueda vectorial en Qdrant.
   * Muestra texto completo, ratio decidendi, metadatos y campos UE.
   *
   * @param string $source_id
   *   Identificador de la fuente de datos (cendoj, boe, dgt, teac, tjue, etc.).
   * @param string $external_ref
   *   Referencia oficial unica (V0123-24, STS 1234/2024, C-415/11, etc.).
   *
   * @return array
   *   Render array con tema legal_resolution_detail.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Si no se encuentra la resolucion con esa clave de negocio.
   */
  public function view(string $source_id, string $external_ref): array {
    $entity = $this->loadResolutionByBusinessKey($source_id, $external_ref);
    if (!$entity) {
      throw new NotFoundHttpException();
    }

    $resolutionId = (int) $entity->id();

    // Obtener grafo de citas y resoluciones similares.
    $citationGraph = $this->citationService->getCitationGraph($resolutionId);
    $similar = $this->searchService->findSimilar($resolutionId, 5);

    return [
      '#theme' => 'legal_resolution_detail',
      '#resolution' => $this->entityToArray($entity),
      '#similar' => $similar,
      '#citations_graph' => $citationGraph,
      '#cache' => [
        'tags' => $entity->getCacheTags(),
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * Pagina de insercion de cita en expediente (slide-panel AJAX).
   *
   * Genera el texto de cita en el formato solicitado y lo presenta en un
   * slide-panel para copiar e insertar en escritos juridicos. Soporta
   * peticiones AJAX (devuelve render array parcial) y peticiones normales.
   *
   * @param string $resolution_id
   *   ID de la entidad LegalResolution.
   * @param string $format
   *   Formato de cita: formal, resumida, bibliografica o nota_al_pie.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto Request para detectar AJAX.
   *
   * @return array
   *   Render array con tema legal_citation_insert.
   */
  public function cite(string $resolution_id, string $format, Request $request): array {
    $citation = $this->citationService->generateCitation((int) $resolution_id, $format);

    // Cargar la entidad para pasar al template.
    $storage = $this->entityTypeManager->getStorage('legal_resolution');
    $entity = $storage->load($resolution_id);
    $resolution = $entity ? $this->entityToArray($entity) : [];

    $build = [
      '#theme' => 'legal_citation_insert',
      '#resolution' => $resolution,
      '#format' => $format,
      '#citation_text' => $citation['citation'] ?? '',
      '#expedientes' => [],
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];

    // Para peticiones AJAX, devolver render array parcial sin wrappers.
    if ($request->isXmlHttpRequest() || $request->query->get('ajax') === '1') {
      $build['#theme_wrappers'] = [];
    }

    return $build;
  }

  /**
   * Pagina de resoluciones similares.
   *
   * Muestra las 10 resoluciones mas similares a la resolucion dada,
   * calculadas por proximidad vectorial en Qdrant. Usa el mismo tema
   * de resultados de busqueda que la pagina principal de busqueda.
   *
   * @param string $resolution_id
   *   ID de la entidad LegalResolution.
   *
   * @return array
   *   Render array con tema legal_search_results.
   */
  public function similar(string $resolution_id): array {
    $results = $this->searchService->findSimilar((int) $resolution_id, 10);

    return [
      '#theme' => 'legal_search_results',
      '#results' => $results,
      '#query' => '',
      '#total' => count($results),
      '#cache' => [
        'contexts' => ['user.permissions'],
      ],
    ];
  }

  /**
   * Pagina publica SEO de una resolucion (sin autenticacion).
   *
   * Expone abstract y metadatos de la resolucion sin texto completo, optimizada
   * para indexacion por buscadores. Convierte el source_slug (con guiones) a
   * source_id (con underscores) y busca por seo_slug. Inyecta meta tags
   * Open Graph y Twitter Card via #attached.html_head.
   *
   * @param string $source_slug
   *   Slug de la fuente con guiones (ej: consulta-vinculante-dgt).
   * @param string $seo_slug
   *   Slug SEO de la resolucion (ej: sentencia-ts-1234-2024-desahucio).
   *
   * @return array
   *   Render array minimo con abstract y metadatos + meta tags SEO.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Si no se encuentra la resolucion con ese slug.
   */
  public function publicSummary(string $source_slug, string $seo_slug): array {
    // Convertir slug con guiones a source_id con underscores.
    $source_id = str_replace('-', '_', $source_slug);

    $entity = $this->loadResolutionBySeoSlug($source_id, $seo_slug);
    if (!$entity) {
      throw new NotFoundHttpException();
    }

    $title = $entity->get('title')->value ?? '';
    $abstract = $entity->get('abstract_ai')->value ?? '';
    $description = mb_substr(strip_tags($abstract), 0, 160);

    $sourceId = $entity->get('source_id')->value ?? '';
    $externalRef = $entity->get('external_ref')->value ?? '';
    $issuingBody = $entity->get('issuing_body')->value ?? '';
    $dateIssued = $entity->get('date_issued')->value ?? '';
    $statusLegal = $entity->get('status_legal')->value ?? 'vigente';

    // Schema.org JSON-LD: Legislation structured data para indexacion SEO.
    $jsonLd = [
      '@context' => 'https://schema.org',
      '@type' => 'Legislation',
      'name' => $title,
      'description' => $description,
      'legislationIdentifier' => $externalRef,
      'legislationDate' => $dateIssued,
      'legislationLegalForce' => $this->mapLegalForceStatus($statusLegal),
      'legislationJurisdiction' => [
        '@type' => 'AdministrativeArea',
        'name' => $entity->isEuSource() ? 'European Union' : 'Spain',
      ],
      'author' => [
        '@type' => 'GovernmentOrganization',
        'name' => $this->resolveAuthorName($sourceId, $issuingBody),
      ],
      'inLanguage' => $entity->get('language_original')->value ?? 'es',
    ];

    // Campos opcionales UE.
    $celex = $entity->get('celex_number')->value ?? '';
    if ($celex !== '') {
      $jsonLd['identifier'] = $celex;
    }
    $ecli = $entity->get('ecli')->value ?? '';
    if ($ecli !== '') {
      $jsonLd['legislationIdentifier'] = $ecli;
    }

    $jsonLdScript = json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    return [
      '#theme' => 'legal_resolution_detail',
      '#resolution' => [
        'id' => (int) $entity->id(),
        'title' => $title,
        'source_id' => $sourceId,
        'external_ref' => $externalRef,
        'resolution_type' => $entity->get('resolution_type')->value ?? '',
        'issuing_body' => $issuingBody,
        'jurisdiction' => $entity->get('jurisdiction')->value ?? '',
        'date_issued' => $dateIssued,
        'status_legal' => $statusLegal,
        'abstract_ai' => $abstract,
        'key_holdings' => $entity->get('key_holdings')->value ?? '',
        'topics' => $entity->getTopics(),
        'cited_legislation' => $entity->getCitedLegislation(),
        'original_url' => $entity->get('original_url')->value ?? '',
        'importance_level' => (int) ($entity->get('importance_level')->value ?? 3),
        'celex_number' => $celex,
        'ecli' => $ecli,
        'case_number' => $entity->get('case_number')->value ?? '',
        'procedure_type' => $entity->get('procedure_type')->value ?? '',
        'respondent_state' => $entity->get('respondent_state')->value ?? '',
        'impact_spain' => $entity->get('impact_spain')->value ?? '',
        'language_original' => $entity->get('language_original')->value ?? 'es',
        'advocate_general' => $entity->get('advocate_general')->value ?? '',
      ],
      '#similar' => [],
      '#citations_graph' => [],
      '#attached' => [
        'html_head' => [
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'name' => 'description',
                'content' => $description,
              ],
            ],
            'legal_meta_description',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:title',
                'content' => $title,
              ],
            ],
            'legal_og_title',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:description',
                'content' => $description,
              ],
            ],
            'legal_og_description',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'property' => 'og:type',
                'content' => 'article',
              ],
            ],
            'legal_og_type',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'name' => 'twitter:card',
                'content' => 'summary',
              ],
            ],
            'legal_twitter_card',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'name' => 'twitter:title',
                'content' => $title,
              ],
            ],
            'legal_twitter_title',
          ],
          [
            [
              '#tag' => 'meta',
              '#attributes' => [
                'name' => 'twitter:description',
                'content' => $description,
              ],
            ],
            'legal_twitter_description',
          ],
          // Schema.org JSON-LD structured data (Legislation).
          [
            [
              '#type' => 'html_tag',
              '#tag' => 'script',
              '#attributes' => ['type' => 'application/ld+json'],
              '#value' => $jsonLdScript,
            ],
            'legal_schema_jsonld',
          ],
        ],
      ],
      '#cache' => [
        'tags' => $entity->getCacheTags(),
        'contexts' => ['url.path'],
      ],
    ];
  }

  // ===========================================================================
  // ENDPOINTS API REST: Detalle JSON y gestion de bookmarks.
  // ===========================================================================

  /**
   * API: Devuelve una resolucion por ID en formato JSON.
   *
   * Endpoint GET /api/v1/legal/resolutions/{id}. Carga la entidad
   * LegalResolution y devuelve todos sus campos como JSON plano.
   * Devuelve 404 si la resolucion no existe.
   *
   * @param string $id
   *   ID de la entidad LegalResolution.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con todos los campos de la resolucion o error 404.
   */
  public function apiGet(string $id): JsonResponse {
    $storage = $this->entityTypeManager->getStorage('legal_resolution');
    /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution|null $entity */
    $entity = $storage->load($id);

    if (!$entity) {
      return new JsonResponse([
        'error' => 'Resolution not found.',
        'id' => (int) $id,
      ], 404);
    }

    return new JsonResponse([
      'data' => $this->entityToArray($entity),
    ]);
  }

  /**
   * API: Crea o elimina un bookmark (favorito) de una resolucion.
   *
   * Endpoint POST/DELETE /api/v1/legal/bookmark. Lee el JSON body con
   * resolution_id. POST crea un bookmark para el usuario actual;
   * DELETE lo elimina. Devuelve JSON con el resultado de la operacion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto Request con el body JSON { "resolution_id": 123 }.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con { "success": true, ... } o error.
   */
  public function apiBookmark(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE);
    $resolutionId = (int) ($body['resolution_id'] ?? 0);

    if ($resolutionId <= 0) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Missing or invalid resolution_id.',
      ], 400);
    }

    // Verificar que la resolucion existe.
    $storage = $this->entityTypeManager->getStorage('legal_resolution');
    $resolution = $storage->load($resolutionId);
    if (!$resolution) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Resolution not found.',
      ], 404);
    }

    $userId = (int) $this->currentUser()->id();
    $method = $request->getMethod();

    try {
      if ($method === 'POST') {
        return $this->createBookmark($resolutionId, $userId);
      }

      if ($method === 'DELETE') {
        return $this->deleteBookmark($resolutionId, $userId);
      }

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Method not allowed.',
      ], 405);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'An error occurred while processing the bookmark.',
      ], 500);
    }
  }

  // ===========================================================================
  // METODOS PRIVADOS: Carga de entidades y utilidades.
  // ===========================================================================

  /**
   * Carga una resolucion por su clave de negocio (source_id + external_ref).
   *
   * Busca en el entity storage por la combinacion de source_id y external_ref
   * que forma la clave de negocio unica de cada resolucion.
   *
   * @param string $source_id
   *   Identificador de la fuente de datos.
   * @param string $external_ref
   *   Referencia oficial unica.
   *
   * @return \Drupal\jaraba_legal_intelligence\Entity\LegalResolution|null
   *   La entidad si existe, NULL en caso contrario.
   */
  private function loadResolutionByBusinessKey(string $source_id, string $external_ref): ?LegalResolution {
    $storage = $this->entityTypeManager->getStorage('legal_resolution');
    $entities = $storage->loadByProperties([
      'source_id' => $source_id,
      'external_ref' => $external_ref,
    ]);

    if (empty($entities)) {
      return NULL;
    }

    /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $entity */
    $entity = reset($entities);
    return $entity;
  }

  /**
   * Carga una resolucion por source_id y seo_slug.
   *
   * Usado por la pagina publica SEO para encontrar la resolucion
   * por su slug URL-friendly en combinacion con la fuente de datos.
   *
   * @param string $source_id
   *   Identificador de la fuente de datos (con underscores).
   * @param string $seo_slug
   *   Slug SEO URL-friendly.
   *
   * @return \Drupal\jaraba_legal_intelligence\Entity\LegalResolution|null
   *   La entidad si existe, NULL en caso contrario.
   */
  private function loadResolutionBySeoSlug(string $source_id, string $seo_slug): ?LegalResolution {
    $storage = $this->entityTypeManager->getStorage('legal_resolution');
    $entities = $storage->loadByProperties([
      'source_id' => $source_id,
      'seo_slug' => $seo_slug,
    ]);

    if (empty($entities)) {
      return NULL;
    }

    /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $entity */
    $entity = reset($entities);
    return $entity;
  }

  /**
   * Convierte una entidad LegalResolution a un array completo de campos.
   *
   * Extrae todos los campos relevantes de la entidad incluyendo campos AI,
   * campos UE (doc 178A) y texto completo para uso en render arrays y API.
   *
   * @param \Drupal\jaraba_legal_intelligence\Entity\LegalResolution $entity
   *   Entidad LegalResolution completa.
   *
   * @return array
   *   Array asociativo con todos los campos de la resolucion.
   */
  private function entityToArray(LegalResolution $entity): array {
    return [
      'id' => (int) $entity->id(),
      'title' => $entity->get('title')->value ?? '',
      'source_id' => $entity->get('source_id')->value ?? '',
      'external_ref' => $entity->get('external_ref')->value ?? '',
      'resolution_type' => $entity->get('resolution_type')->value ?? '',
      'issuing_body' => $entity->get('issuing_body')->value ?? '',
      'jurisdiction' => $entity->get('jurisdiction')->value ?? '',
      'date_issued' => $entity->get('date_issued')->value ?? '',
      'status_legal' => $entity->get('status_legal')->value ?? 'vigente',
      'abstract_ai' => $entity->get('abstract_ai')->value ?? '',
      'key_holdings' => $entity->get('key_holdings')->value ?? '',
      'topics' => $entity->getTopics(),
      'cited_legislation' => $entity->getCitedLegislation(),
      'full_text' => $entity->get('full_text')->value ?? '',
      'original_url' => $entity->get('original_url')->value ?? '',
      'importance_level' => (int) ($entity->get('importance_level')->value ?? 3),
      'celex_number' => $entity->get('celex_number')->value ?? '',
      'ecli' => $entity->get('ecli')->value ?? '',
      'case_number' => $entity->get('case_number')->value ?? '',
      'procedure_type' => $entity->get('procedure_type')->value ?? '',
      'respondent_state' => $entity->get('respondent_state')->value ?? '',
      'impact_spain' => $entity->get('impact_spain')->value ?? '',
      'language_original' => $entity->get('language_original')->value ?? 'es',
      'advocate_general' => $entity->get('advocate_general')->value ?? '',
    ];
  }

  /**
   * Crea un bookmark (favorito) para el usuario actual.
   *
   * Verifica que no exista ya un bookmark para la misma combinacion
   * user_id + resolution_id antes de crear uno nuevo.
   *
   * @param int $resolutionId
   *   ID de la entidad LegalResolution.
   * @param int $userId
   *   ID del usuario actual.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con el resultado de la operacion.
   */
  private function createBookmark(int $resolutionId, int $userId): JsonResponse {
    $bookmarkStorage = $this->entityTypeManager->getStorage('legal_bookmark');

    // Verificar si ya existe un bookmark para esta combinacion.
    $existing = $bookmarkStorage->loadByProperties([
      'user_id' => $userId,
      'resolution_id' => $resolutionId,
    ]);

    if (!empty($existing)) {
      $bookmark = reset($existing);
      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Bookmark already exists.',
        'bookmark_id' => (int) $bookmark->id(),
      ]);
    }

    // Crear el bookmark.
    $bookmark = $bookmarkStorage->create([
      'user_id' => $userId,
      'resolution_id' => $resolutionId,
    ]);
    $bookmark->save();

    return new JsonResponse([
      'success' => TRUE,
      'message' => 'Bookmark created.',
      'bookmark_id' => (int) $bookmark->id(),
    ], 201);
  }

  /**
   * Elimina un bookmark (favorito) del usuario actual.
   *
   * Busca el bookmark por user_id + resolution_id y lo elimina.
   * Devuelve exito aunque el bookmark no exista (idempotente).
   *
   * @param int $resolutionId
   *   ID de la entidad LegalResolution.
   * @param int $userId
   *   ID del usuario actual.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con el resultado de la operacion.
   */
  private function deleteBookmark(int $resolutionId, int $userId): JsonResponse {
    $bookmarkStorage = $this->entityTypeManager->getStorage('legal_bookmark');

    $existing = $bookmarkStorage->loadByProperties([
      'user_id' => $userId,
      'resolution_id' => $resolutionId,
    ]);

    if (empty($existing)) {
      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Bookmark not found, nothing to delete.',
      ]);
    }

    $bookmarkStorage->delete($existing);

    return new JsonResponse([
      'success' => TRUE,
      'message' => 'Bookmark deleted.',
    ]);
  }

  // ===========================================================================
  // HELPERS SEO: Schema.org y metadatos estructurados.
  // ===========================================================================

  /**
   * Mapea el status_legal interno al valor Schema.org LegalForceStatus.
   *
   * @param string $status
   *   Estado legal interno: vigente, derogada, anulada, superada,
   *   parcialmente_derogada.
   *
   * @return string
   *   Valor Schema.org: InForce, NotInForce, PartiallyInForce.
   *
   * @see https://schema.org/LegalForceStatus
   */
  private function mapLegalForceStatus(string $status): string {
    return match ($status) {
      'vigente' => 'InForce',
      'derogada', 'anulada', 'superada' => 'NotInForce',
      'parcialmente_derogada' => 'PartiallyInForce',
      default => 'InForce',
    };
  }

  /**
   * Resuelve el nombre del autor/organismo emisor para Schema.org.
   *
   * Prioriza el campo issuing_body de la entidad. Si esta vacio,
   * usa un mapeo por source_id a la denominacion oficial del organismo.
   *
   * @param string $sourceId
   *   Identificador de la fuente (dgt, cendoj, boe, tjue, etc.).
   * @param string $issuingBody
   *   Valor del campo issuing_body de la entidad.
   *
   * @return string
   *   Nombre del organismo emisor para el campo author.name del JSON-LD.
   */
  private function resolveAuthorName(string $sourceId, string $issuingBody): string {
    if ($issuingBody !== '') {
      return $issuingBody;
    }

    return match ($sourceId) {
      'dgt' => 'Dirección General de Tributos',
      'cendoj' => 'Poder Judicial de España',
      'boe' => 'Boletín Oficial del Estado',
      'teac' => 'Tribunal Económico-Administrativo Central',
      'tjue' => 'Tribunal de Justicia de la Unión Europea',
      'eurlex' => 'Unión Europea',
      'tedh' => 'Tribunal Europeo de Derechos Humanos',
      'edpb' => 'European Data Protection Board',
      default => 'Organismo público',
    };
  }

}
