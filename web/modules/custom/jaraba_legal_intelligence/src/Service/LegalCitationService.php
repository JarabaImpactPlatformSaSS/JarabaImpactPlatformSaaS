<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_legal_intelligence\Entity\LegalResolution;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestion de citas legales y favoritos del Legal Intelligence Hub.
 *
 * ESTRUCTURA:
 * Servicio que orquesta la generacion de citas formateadas para resoluciones
 * juridicas, la gestion de favoritos (bookmarks) de profesionales y la
 * vinculacion de resoluciones con expedientes del Buzon de Confianza.
 * Proporciona ocho operaciones principales: generacion de cita en 4 formatos
 * (formal, resumida, bibliografica, nota al pie), creacion y eliminacion de
 * favoritos, listado de favoritos del usuario, construccion del grafo de citas
 * para visualizacion D3.js, vinculacion de resoluciones a expedientes
 * (attachToExpediente), listado de referencias de un expediente
 * (getExpedienteReferences) y desvinculacion (detachFromExpediente).
 *
 * LOGICA:
 * La generacion de citas delega en LegalResolution::formatCitation() que ya
 * implementa los 4 formatos de cita con articulo determinado correcto en
 * espanol. Los favoritos se gestionan como entidades legal_bookmark con
 * unicidad logica por resolution_id + user_id: si ya existe un favorito para
 * esa combinacion, se retorna el existente sin duplicar. El grafo de citas
 * consulta la tabla legal_citation_graph (poblada por la etapa 9 del pipeline
 * NLP) y construye arrays de nodos y aristas para renderizado D3.js.
 * La vinculacion a expedientes crea entidades legal_citation con el texto
 * de cita generado por LegalResolution::formatCitation() en el formato
 * solicitado, vinculando resolucion + expediente + usuario. Se garantiza
 * unicidad logica por resolution_id + expediente_id + citation_format.
 *
 * RELACIONES:
 * - LegalCitationService -> EntityTypeManagerInterface: carga y crea entidades
 *   LegalResolution, LegalBookmark y LegalCitation.
 * - LegalCitationService -> TenantContextService: obtiene el contexto del
 *   tenant actual para logging y aislamiento multi-tenant.
 * - LegalCitationService -> LoggerInterface: registra errores y operaciones
 *   criticas en el canal jaraba_legal_intelligence.
 * - LegalCitationService -> legal_citation_graph (tabla): consulta directa a
 *   MariaDB para obtener el grafo de citas entre resoluciones.
 * - LegalCitationService <- LegalResolutionController: invocado desde endpoints
 *   REST para generacion de citas, gestion de favoritos y grafo.
 * - LegalCitationService <- LegalCopilotBridgeService: invocado para insercion
 *   de citas en expedientes desde el Copilot (FASE 6).
 * - LegalCitationService <- LegalResolution::formatCitation(): delega la
 *   generacion del texto de cita a la entidad.
 */
class LegalCitationService {

  /**
   * Formatos de cita validos soportados por el sistema.
   *
   * Mapean a los 4 estilos de cita implementados en
   * LegalResolution::formatCitation(): formal (con ratio decidendi),
   * resumida, bibliografica y nota al pie.
   *
   * @var string[]
   */
  private const VALID_FORMATS = [
    'formal',
    'resumida',
    'bibliografica',
    'nota_al_pie',
  ];

  /**
   * Construye una nueva instancia de LegalCitationService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para cargar y crear entidades
   *   LegalResolution y LegalBookmark.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService $tenantContext
   *   Servicio de contexto de tenant para aislamiento multi-tenant.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TenantContextService $tenantContext,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Genera una cita formateada para una resolucion juridica.
   *
   * Carga la entidad LegalResolution indicada y delega la generacion
   * del texto de cita a LegalResolution::formatCitation(), que implementa
   * los 4 formatos de cita con articulo determinado correcto en espanol.
   * Valida que el formato solicitado sea uno de los 4 formatos soportados
   * antes de proceder.
   *
   * @param int $resolutionId
   *   ID de la entidad LegalResolution para la que se genera la cita.
   * @param string $format
   *   Formato de cita solicitado: 'formal', 'resumida', 'bibliografica'
   *   o 'nota_al_pie'.
   *
   * @return array
   *   Array asociativo con claves:
   *   - success: bool — TRUE si la cita se genero correctamente.
   *   - citation: string — Texto de la cita formateada (vacio si error).
   *   - format: string — Formato utilizado.
   *   - resolution_id: int — ID de la resolucion.
   *   - error: string|null — Mensaje de error si aplica.
   */
  public function generateCitation(int $resolutionId, string $format): array {
    // Validar formato solicitado.
    if (!in_array($format, self::VALID_FORMATS, TRUE)) {
      $this->logger->warning('Citations: Formato de cita invalido: @format. Formatos validos: @valid', [
        '@format' => $format,
        '@valid' => implode(', ', self::VALID_FORMATS),
      ]);
      return [
        'success' => FALSE,
        'citation' => '',
        'format' => $format,
        'resolution_id' => $resolutionId,
        'error' => sprintf(
          'Formato de cita invalido: %s. Formatos validos: %s.',
          $format,
          implode(', ', self::VALID_FORMATS)
        ),
      ];
    }

    try {
      $storage = $this->entityTypeManager->getStorage('legal_resolution');
      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution|null $entity */
      $entity = $storage->load($resolutionId);

      if (!$entity) {
        $this->logger->warning('Citations: Resolucion no encontrada: @id', [
          '@id' => $resolutionId,
        ]);
        return [
          'success' => FALSE,
          'citation' => '',
          'format' => $format,
          'resolution_id' => $resolutionId,
          'error' => sprintf('Resolucion con ID %d no encontrada.', $resolutionId),
        ];
      }

      // Delegar generacion de texto al metodo de la entidad.
      $citationText = $entity->formatCitation($format);

      $this->logger->info('Citations: Cita generada para resolucion @id en formato @format', [
        '@id' => $resolutionId,
        '@format' => $format,
      ]);

      return [
        'success' => TRUE,
        'citation' => $citationText,
        'format' => $format,
        'resolution_id' => $resolutionId,
        'error' => NULL,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Citations: Error generando cita para resolucion @id: @msg', [
        '@id' => $resolutionId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'citation' => '',
        'format' => $format,
        'resolution_id' => $resolutionId,
        'error' => 'Error interno al generar la cita.',
      ];
    }
  }

  /**
   * Crea un favorito (bookmark) de una resolucion para un usuario.
   *
   * Verifica si ya existe un favorito para la combinacion resolution_id +
   * user_id. Si existe, retorna el ID del favorito existente sin duplicar.
   * Si no existe, crea una nueva entidad legal_bookmark vinculando la
   * resolucion con el usuario. La unicidad logica se garantiza mediante
   * consulta previa al entity storage.
   *
   * @param int $resolutionId
   *   ID de la entidad LegalResolution a guardar como favorito.
   * @param int $userId
   *   ID del usuario (uid) que guarda el favorito.
   *
   * @return array
   *   Array asociativo con claves:
   *   - success: bool — TRUE si la operacion fue exitosa.
   *   - bookmark_id: int — ID de la entidad LegalBookmark (nueva o existente).
   *   - created: bool — TRUE si se creo un nuevo favorito, FALSE si ya existia.
   *   - error: string|null — Mensaje de error si aplica.
   */
  public function createBookmark(int $resolutionId, int $userId): array {
    try {
      $bookmarkStorage = $this->entityTypeManager->getStorage('legal_bookmark');

      // Verificar si ya existe un favorito para esta combinacion.
      $existing = $bookmarkStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('resolution_id', $resolutionId)
        ->condition('user_id', $userId)
        ->range(0, 1)
        ->execute();

      if (!empty($existing)) {
        $bookmarkId = (int) reset($existing);
        $this->logger->info('Citations: Bookmark existente retornado: @bid para resolucion @rid, usuario @uid', [
          '@bid' => $bookmarkId,
          '@rid' => $resolutionId,
          '@uid' => $userId,
        ]);
        return [
          'success' => TRUE,
          'bookmark_id' => $bookmarkId,
          'created' => FALSE,
          'error' => NULL,
        ];
      }

      // Verificar que la resolucion existe antes de crear el bookmark.
      $resolutionStorage = $this->entityTypeManager->getStorage('legal_resolution');
      $resolution = $resolutionStorage->load($resolutionId);

      if (!$resolution) {
        $this->logger->warning('Citations: No se puede crear bookmark: resolucion @id no encontrada', [
          '@id' => $resolutionId,
        ]);
        return [
          'success' => FALSE,
          'bookmark_id' => 0,
          'created' => FALSE,
          'error' => sprintf('Resolucion con ID %d no encontrada.', $resolutionId),
        ];
      }

      // Crear nuevo bookmark.
      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalBookmark $bookmark */
      $bookmark = $bookmarkStorage->create([
        'resolution_id' => $resolutionId,
        'user_id' => $userId,
        'uid' => $userId,
      ]);
      $bookmark->save();

      $bookmarkId = (int) $bookmark->id();

      $this->logger->info('Citations: Bookmark creado: @bid para resolucion @rid, usuario @uid', [
        '@bid' => $bookmarkId,
        '@rid' => $resolutionId,
        '@uid' => $userId,
      ]);

      return [
        'success' => TRUE,
        'bookmark_id' => $bookmarkId,
        'created' => TRUE,
        'error' => NULL,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Citations: Error creando bookmark para resolucion @rid, usuario @uid: @msg', [
        '@rid' => $resolutionId,
        '@uid' => $userId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'bookmark_id' => 0,
        'created' => FALSE,
        'error' => 'Error interno al crear el favorito.',
      ];
    }
  }

  /**
   * Elimina el favorito (bookmark) de una resolucion para un usuario.
   *
   * Busca la entidad legal_bookmark que vincula la resolucion con el
   * usuario y la elimina. Si no existe un favorito para esa combinacion,
   * retorna deleted=false sin error.
   *
   * @param int $resolutionId
   *   ID de la entidad LegalResolution del favorito a eliminar.
   * @param int $userId
   *   ID del usuario (uid) propietario del favorito.
   *
   * @return array
   *   Array asociativo con claves:
   *   - success: bool — TRUE si la operacion fue exitosa.
   *   - deleted: bool — TRUE si se elimino un favorito, FALSE si no existia.
   *   - error: string|null — Mensaje de error si aplica.
   */
  public function deleteBookmark(int $resolutionId, int $userId): array {
    try {
      $bookmarkStorage = $this->entityTypeManager->getStorage('legal_bookmark');

      // Buscar el bookmark para esta combinacion.
      $ids = $bookmarkStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('resolution_id', $resolutionId)
        ->condition('user_id', $userId)
        ->execute();

      if (empty($ids)) {
        $this->logger->info('Citations: No se encontro bookmark para resolucion @rid, usuario @uid', [
          '@rid' => $resolutionId,
          '@uid' => $userId,
        ]);
        return [
          'success' => TRUE,
          'deleted' => FALSE,
          'error' => NULL,
        ];
      }

      // Cargar y eliminar los bookmarks encontrados (normalmente 1).
      $bookmarks = $bookmarkStorage->loadMultiple($ids);
      $bookmarkStorage->delete($bookmarks);

      $this->logger->info('Citations: Bookmark eliminado para resolucion @rid, usuario @uid (@count eliminados)', [
        '@rid' => $resolutionId,
        '@uid' => $userId,
        '@count' => count($bookmarks),
      ]);

      return [
        'success' => TRUE,
        'deleted' => TRUE,
        'error' => NULL,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Citations: Error eliminando bookmark para resolucion @rid, usuario @uid: @msg', [
        '@rid' => $resolutionId,
        '@uid' => $userId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'deleted' => FALSE,
        'error' => 'Error interno al eliminar el favorito.',
      ];
    }
  }

  /**
   * Obtiene la lista de favoritos (bookmarks) de un usuario.
   *
   * Consulta las entidades legal_bookmark del usuario, carga las entidades
   * LegalResolution asociadas e hidrata los datos de cada resolucion para
   * devolver un array de resultados completo con metadatos, campos IA y
   * datos del favorito (ID del bookmark y fecha de creacion).
   *
   * @param int $userId
   *   ID del usuario (uid) cuyos favoritos se quieren listar.
   * @param int $limit
   *   Numero maximo de favoritos a devolver. Por defecto 50.
   *
   * @return array
   *   Array de resoluciones favoritas. Cada elemento contiene los campos
   *   de la resolucion mas bookmark_id y bookmark_created. Vacio si no hay
   *   favoritos o si ocurre un error.
   */
  public function getUserBookmarks(int $userId, int $limit = 50): array {
    try {
      $bookmarkStorage = $this->entityTypeManager->getStorage('legal_bookmark');

      // Consultar bookmarks del usuario, ordenados por creacion descendente.
      $ids = $bookmarkStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalBookmark[] $bookmarks */
      $bookmarks = $bookmarkStorage->loadMultiple($ids);

      // Recopilar resolution_ids para carga batch.
      $resolutionIds = [];
      foreach ($bookmarks as $bookmark) {
        $resId = $bookmark->get('resolution_id')->target_id;
        if ($resId) {
          $resolutionIds[] = (int) $resId;
        }
      }

      if (empty($resolutionIds)) {
        return [];
      }

      // Cargar resoluciones en batch.
      $resolutionStorage = $this->entityTypeManager->getStorage('legal_resolution');
      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution[] $resolutions */
      $resolutions = $resolutionStorage->loadMultiple(array_unique($resolutionIds));

      // Construir resultado combinando datos del bookmark y la resolucion.
      $results = [];
      foreach ($bookmarks as $bookmark) {
        $resId = (int) $bookmark->get('resolution_id')->target_id;
        $entity = $resolutions[$resId] ?? NULL;

        if (!$entity) {
          // La resolucion fue eliminada pero el bookmark persiste.
          continue;
        }

        $results[] = [
          'bookmark_id' => (int) $bookmark->id(),
          'bookmark_created' => $bookmark->get('created')->value ?? NULL,
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
          'importance_level' => (int) ($entity->get('importance_level')->value ?? 3),
          'is_eu' => $entity->isEuSource(),
          'original_url' => $entity->get('original_url')->value ?? '',
          'seo_slug' => $entity->get('seo_slug')->value ?? '',
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Citations: Error obteniendo bookmarks del usuario @uid: @msg', [
        '@uid' => $userId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene datos del grafo de citas para una resolucion.
   *
   * Consulta la tabla legal_citation_graph (poblada por la etapa 9 del
   * pipeline NLP) para obtener todas las relaciones de cita donde la
   * resolucion indicada participa como fuente o destino. Construye arrays
   * de nodos y aristas en formato adecuado para renderizado D3.js
   * (force-directed graph) en el frontend.
   *
   * Se usa \Drupal::database() para la consulta directa a la tabla
   * legal_citation_graph porque el servicio no tiene @database inyectado
   * como dependencia del constructor. La consulta es de solo lectura.
   *
   * @param int $resolutionId
   *   ID de la entidad LegalResolution central del grafo.
   *
   * @return array
   *   Array asociativo con claves:
   *   - nodes: array — Array de nodos para D3.js. Cada nodo contiene:
   *     - id: int — ID de la resolucion.
   *     - label: string — Titulo o referencia de la resolucion.
   *     - type: string — Tipo de resolucion (sentencia, auto, etc.).
   *     - is_central: bool — TRUE si es la resolucion consultada.
   *   - edges: array — Array de aristas para D3.js. Cada arista contiene:
   *     - source: int — ID de la resolucion fuente.
   *     - target: int — ID de la resolucion destino.
   *     - relation_type: string — Tipo de relacion (cita, aplica, distingue, etc.).
   */
  public function getCitationGraph(int $resolutionId): array {
    try {
      $database = \Drupal::database();

      // Obtener todas las relaciones donde la resolucion es fuente o destino.
      $query = $database->select('legal_citation_graph', 'g');
      $query->fields('g', [
        'source_resolution_id',
        'target_resolution_id',
        'relation_type',
        'created',
      ]);

      // Condicion OR: la resolucion puede ser fuente o destino.
      $orCondition = $query->orConditionGroup()
        ->condition('g.source_resolution_id', $resolutionId)
        ->condition('g.target_resolution_id', $resolutionId);
      $query->condition($orCondition);

      $rows = $query->execute()->fetchAll();

      if (empty($rows)) {
        return [
          'nodes' => [],
          'edges' => [],
        ];
      }

      // Recopilar todos los IDs de resoluciones involucradas.
      $allResolutionIds = [$resolutionId];
      $edges = [];

      foreach ($rows as $row) {
        $sourceId = (int) $row->source_resolution_id;
        $targetId = (int) $row->target_resolution_id;

        $allResolutionIds[] = $sourceId;
        $allResolutionIds[] = $targetId;

        $edges[] = [
          'source' => $sourceId,
          'target' => $targetId,
          'relation_type' => $row->relation_type ?? 'cita',
        ];
      }

      $allResolutionIds = array_unique($allResolutionIds);

      // Cargar entidades para obtener datos de los nodos.
      $resolutionStorage = $this->entityTypeManager->getStorage('legal_resolution');
      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution[] $entities */
      $entities = $resolutionStorage->loadMultiple($allResolutionIds);

      // Construir nodos con datos de las entidades.
      $nodes = [];
      foreach ($allResolutionIds as $id) {
        $entity = $entities[$id] ?? NULL;

        if ($entity) {
          $nodes[] = [
            'id' => (int) $entity->id(),
            'label' => $entity->get('external_ref')->value ?: ($entity->get('title')->value ?? ''),
            'type' => $entity->get('resolution_type')->value ?? '',
            'is_central' => ($id === $resolutionId),
          ];
        }
        else {
          // La entidad fue eliminada pero la relacion en el grafo persiste.
          $nodes[] = [
            'id' => $id,
            'label' => sprintf('Resolucion #%d (eliminada)', $id),
            'type' => 'unknown',
            'is_central' => ($id === $resolutionId),
          ];
        }
      }

      return [
        'nodes' => $nodes,
        'edges' => $edges,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Citations: Error obteniendo grafo de citas para resolucion @id: @msg', [
        '@id' => $resolutionId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'nodes' => [],
        'edges' => [],
      ];
    }
  }

  // ===========================================================================
  // FASE 6: Vinculacion de resoluciones a expedientes del Buzon de Confianza.
  // ===========================================================================

  /**
   * Vincula una resolucion a un expediente del Buzon de Confianza.
   *
   * Genera el texto de cita en el formato solicitado via
   * LegalResolution::formatCitation() y crea una entidad legal_citation
   * que vincula la resolucion con el expediente. Verifica unicidad logica
   * por resolution_id + expediente_id + citation_format: si ya existe una
   * cita identica, retorna la existente sin duplicar.
   *
   * @param int $resolutionId
   *   ID de la entidad LegalResolution a vincular.
   * @param int $expedienteId
   *   ID del expediente del Buzon de Confianza (doc 88).
   * @param string $format
   *   Formato de cita: 'formal', 'resumida', 'bibliografica' o 'nota_al_pie'.
   * @param int $userId
   *   ID del usuario (uid) que realiza la insercion.
   *
   * @return array
   *   Array asociativo con claves:
   *   - success: bool — TRUE si la vinculacion fue exitosa.
   *   - citation_id: int — ID de la entidad LegalCitation creada o existente.
   *   - citation_text: string — Texto de la cita generada.
   *   - created: bool — TRUE si se creo nueva, FALSE si ya existia.
   *   - error: string|null — Mensaje de error si aplica.
   */
  public function attachToExpediente(int $resolutionId, int $expedienteId, string $format, int $userId): array {
    // Validar formato.
    if (!in_array($format, self::VALID_FORMATS, TRUE)) {
      return [
        'success' => FALSE,
        'citation_id' => 0,
        'citation_text' => '',
        'created' => FALSE,
        'error' => sprintf(
          'Formato de cita invalido: %s. Formatos validos: %s.',
          $format,
          implode(', ', self::VALID_FORMATS)
        ),
      ];
    }

    try {
      $citationStorage = $this->entityTypeManager->getStorage('legal_citation');

      // Verificar unicidad logica: resolution_id + expediente_id + format.
      $existing = $citationStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('resolution_id', $resolutionId)
        ->condition('expediente_id', $expedienteId)
        ->condition('citation_format', $format)
        ->range(0, 1)
        ->execute();

      if (!empty($existing)) {
        $citationId = (int) reset($existing);
        /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalCitation $citation */
        $citation = $citationStorage->load($citationId);
        $citationText = $citation ? ($citation->get('citation_text')->value ?? '') : '';

        $this->logger->info('Citations: Cita existente retornada @cid para resolucion @rid en expediente @eid', [
          '@cid' => $citationId,
          '@rid' => $resolutionId,
          '@eid' => $expedienteId,
        ]);

        return [
          'success' => TRUE,
          'citation_id' => $citationId,
          'citation_text' => $citationText,
          'created' => FALSE,
          'error' => NULL,
        ];
      }

      // Cargar resolucion para generar texto de cita.
      $resolutionStorage = $this->entityTypeManager->getStorage('legal_resolution');
      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution|null $resolution */
      $resolution = $resolutionStorage->load($resolutionId);

      if (!$resolution) {
        $this->logger->warning('Citations: Resolucion @id no encontrada para vincular a expediente @eid', [
          '@id' => $resolutionId,
          '@eid' => $expedienteId,
        ]);
        return [
          'success' => FALSE,
          'citation_id' => 0,
          'citation_text' => '',
          'created' => FALSE,
          'error' => sprintf('Resolucion con ID %d no encontrada.', $resolutionId),
        ];
      }

      // Generar texto de cita delegando a la entidad.
      $citationText = $resolution->formatCitation($format);

      // Crear entidad LegalCitation.
      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalCitation $citation */
      $citation = $citationStorage->create([
        'resolution_id' => $resolutionId,
        'expediente_id' => $expedienteId,
        'inserted_by' => $userId,
        'citation_format' => $format,
        'citation_text' => $citationText,
        'uid' => $userId,
      ]);
      $citation->save();

      $citationId = (int) $citation->id();

      $this->logger->info('Citations: Cita @cid creada para resolucion @rid en expediente @eid (formato: @fmt)', [
        '@cid' => $citationId,
        '@rid' => $resolutionId,
        '@eid' => $expedienteId,
        '@fmt' => $format,
      ]);

      return [
        'success' => TRUE,
        'citation_id' => $citationId,
        'citation_text' => $citationText,
        'created' => TRUE,
        'error' => NULL,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Citations: Error vinculando resolucion @rid a expediente @eid: @msg', [
        '@rid' => $resolutionId,
        '@eid' => $expedienteId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'citation_id' => 0,
        'citation_text' => '',
        'created' => FALSE,
        'error' => 'Error interno al vincular la resolucion al expediente.',
      ];
    }
  }

  /**
   * Obtiene todas las resoluciones vinculadas a un expediente.
   *
   * Consulta las entidades legal_citation vinculadas al expediente indicado,
   * carga las entidades LegalResolution asociadas e hidrata los datos para
   * devolver un array completo con datos de la cita y la resolucion.
   *
   * @param int $expedienteId
   *   ID del expediente del Buzon de Confianza.
   * @param int $limit
   *   Numero maximo de referencias a devolver. Por defecto 50.
   *
   * @return array
   *   Array de referencias. Cada elemento contiene:
   *   - citation_id: int — ID de la entidad LegalCitation.
   *   - resolution_id: int — ID de la resolucion vinculada.
   *   - citation_format: string — Formato de la cita.
   *   - citation_text: string — Texto de la cita generada.
   *   - inserted_by: int — UID del usuario que inserto la cita.
   *   - created: string|null — Timestamp de creacion.
   *   - resolution: array — Datos de la resolucion (title, source_id, etc.).
   *   Vacio si no hay referencias o si ocurre un error.
   */
  public function getExpedienteReferences(int $expedienteId, int $limit = 50): array {
    try {
      $citationStorage = $this->entityTypeManager->getStorage('legal_citation');

      // Consultar citas del expediente, ordenadas por creacion descendente.
      $ids = $citationStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('expediente_id', $expedienteId)
        ->sort('created', 'DESC')
        ->range(0, $limit)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalCitation[] $citations */
      $citations = $citationStorage->loadMultiple($ids);

      // Recopilar resolution_ids para carga batch.
      $resolutionIds = [];
      foreach ($citations as $citation) {
        $resId = $citation->get('resolution_id')->target_id;
        if ($resId) {
          $resolutionIds[] = (int) $resId;
        }
      }

      // Cargar resoluciones en batch.
      $resolutions = [];
      if (!empty($resolutionIds)) {
        $resolutionStorage = $this->entityTypeManager->getStorage('legal_resolution');
        /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalResolution[] $resolutions */
        $resolutions = $resolutionStorage->loadMultiple(array_unique($resolutionIds));
      }

      // Construir resultado combinando datos de la cita y la resolucion.
      $results = [];
      foreach ($citations as $citation) {
        $resId = (int) ($citation->get('resolution_id')->target_id ?? 0);
        $entity = $resolutions[$resId] ?? NULL;

        $resolutionData = [];
        if ($entity) {
          $resolutionData = [
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
            'importance_level' => (int) ($entity->get('importance_level')->value ?? 3),
            'is_eu' => $entity->isEuSource(),
            'original_url' => $entity->get('original_url')->value ?? '',
          ];
        }

        $results[] = [
          'citation_id' => (int) $citation->id(),
          'resolution_id' => $resId,
          'citation_format' => $citation->get('citation_format')->value ?? 'formal',
          'citation_text' => $citation->get('citation_text')->value ?? '',
          'inserted_by' => (int) ($citation->get('inserted_by')->target_id ?? 0),
          'created' => $citation->get('created')->value ?? NULL,
          'resolution' => $resolutionData,
        ];
      }

      return $results;
    }
    catch (\Exception $e) {
      $this->logger->error('Citations: Error obteniendo referencias del expediente @eid: @msg', [
        '@eid' => $expedienteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Desvincula una resolucion de un expediente eliminando la cita.
   *
   * Carga la entidad legal_citation por ID, verifica que pertenezca al
   * usuario indicado (o si el usuario es admin) y la elimina. La verificacion
   * de propiedad es opcional: si $userId es 0, se omite la comprobacion
   * (para uso administrativo).
   *
   * @param int $citationId
   *   ID de la entidad LegalCitation a eliminar.
   * @param int $userId
   *   ID del usuario que solicita la desvinculacion. Si es 0, no se
   *   verifica propiedad (uso administrativo).
   *
   * @return array
   *   Array asociativo con claves:
   *   - success: bool — TRUE si la operacion fue exitosa.
   *   - deleted: bool — TRUE si se elimino la cita.
   *   - error: string|null — Mensaje de error si aplica.
   */
  public function detachFromExpediente(int $citationId, int $userId = 0): array {
    try {
      $citationStorage = $this->entityTypeManager->getStorage('legal_citation');

      /** @var \Drupal\jaraba_legal_intelligence\Entity\LegalCitation|null $citation */
      $citation = $citationStorage->load($citationId);

      if (!$citation) {
        $this->logger->info('Citations: Cita @cid no encontrada para desvincular', [
          '@cid' => $citationId,
        ]);
        return [
          'success' => TRUE,
          'deleted' => FALSE,
          'error' => NULL,
        ];
      }

      // Verificar propiedad si se proporciona userId.
      if ($userId > 0) {
        $insertedBy = (int) ($citation->get('inserted_by')->target_id ?? 0);
        if ($insertedBy !== $userId) {
          $this->logger->warning('Citations: Usuario @uid intento desvincular cita @cid de otro usuario @owner', [
            '@uid' => $userId,
            '@cid' => $citationId,
            '@owner' => $insertedBy,
          ]);
          return [
            'success' => FALSE,
            'deleted' => FALSE,
            'error' => 'No tiene permiso para eliminar esta cita.',
          ];
        }
      }

      $expedienteId = (int) ($citation->get('expediente_id')->value ?? 0);
      $resolutionId = (int) ($citation->get('resolution_id')->target_id ?? 0);

      $citation->delete();

      $this->logger->info('Citations: Cita @cid desvinculada (resolucion @rid de expediente @eid)', [
        '@cid' => $citationId,
        '@rid' => $resolutionId,
        '@eid' => $expedienteId,
      ]);

      return [
        'success' => TRUE,
        'deleted' => TRUE,
        'error' => NULL,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Citations: Error desvinculando cita @cid: @msg', [
        '@cid' => $citationId,
        '@msg' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'deleted' => FALSE,
        'error' => 'Error interno al desvincular la cita.',
      ];
    }
  }

}
