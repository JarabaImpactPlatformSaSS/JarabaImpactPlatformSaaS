<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_legal_intelligence\Service\LegalAlertService;
use Drupal\jaraba_legal_intelligence\Service\LegalCitationService;
use Drupal\jaraba_legal_intelligence\Service\LegalSearchService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador del dashboard profesional del Legal Intelligence Hub.
 *
 * ESTRUCTURA:
 * Controlador frontend que renderiza el dashboard personalizado del profesional
 * juridico. Muestra tres secciones: resoluciones guardadas en favoritos
 * (bookmarks), alertas inteligentes activas y busquedas recientes. La pagina
 * se renderiza como Zero-Region con el template legal-professional-dashboard
 * y carga la libreria legal.dashboard para interactividad JS.
 *
 * LOGICA:
 * El metodo dashboard() obtiene el ID del usuario actual, consulta entity
 * storage para cargar sus bookmarks (legal_bookmark filtrado por user_id)
 * y las resoluciones asociadas (legal_resolution por resolution_id de cada
 * bookmark). Tambien carga las alertas activas (legal_alert filtradas por
 * user_id e is_active=TRUE). Las busquedas recientes se proporcionan como
 * placeholder vacio para implementacion futura. Los datos se pasan al
 * template Twig con cache por usuario para invalidacion granular.
 *
 * RELACIONES:
 * - LegalDashboardController -> EntityTypeManagerInterface: consulta y carga
 *   entidades legal_bookmark y legal_resolution del profesional actual.
 * - LegalDashboardController -> LegalSearchService: reservado para carga futura
 *   de busquedas recientes del usuario.
 * - LegalDashboardController -> LegalAlertService: carga alertas activas del
 *   usuario para mostrar en la seccion de alertas del dashboard.
 * - LegalDashboardController -> LegalCitationService: reservado para operaciones
 *   de favoritos y citas desde el dashboard.
 * - LegalDashboardController -> LoggerInterface: registra errores en el canal
 *   jaraba_legal_intelligence.
 * - LegalDashboardController <- jaraba_legal.dashboard (ruta): pagina frontend
 *   con permiso 'view legal resolutions'.
 */
class LegalDashboardController extends ControllerBase {

  /**
   * Construye una nueva instancia del controlador del dashboard profesional.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad para consultas sobre bookmarks, alertas y
   *   resoluciones del profesional actual.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalSearchService $searchService
   *   Servicio de busqueda semantica para carga de busquedas recientes.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalAlertService $alertService
   *   Servicio de alertas inteligentes para listar alertas activas.
   * @param \Drupal\jaraba_legal_intelligence\Service\LegalCitationService $citationService
   *   Servicio de citas y favoritos para operaciones de bookmark.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    protected LegalSearchService $searchService,
    protected LegalAlertService $alertService,
    protected LegalCitationService $citationService,
    protected LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('jaraba_legal_intelligence.search'),
      $container->get('jaraba_legal_intelligence.alerts'),
      $container->get('jaraba_legal_intelligence.citations'),
      $container->get('logger.channel.jaraba_legal_intelligence'),
    );
  }

  /**
   * Renderiza el dashboard profesional del Legal Intelligence Hub.
   *
   * Carga favoritos del usuario con sus resoluciones asociadas, alertas
   * activas y prepara un placeholder para busquedas recientes. Pasa todos
   * los datos al template legal-professional-dashboard con cache tags
   * por usuario para invalidacion granular.
   *
   * @return array
   *   Render array de Drupal con el theme 'legal_professional_dashboard'.
   */
  public function dashboard(): array {
    $userId = (int) $this->currentUser()->id();

    $bookmarks = $this->loadUserBookmarks($userId);
    $alerts = $this->loadUserActiveAlerts($userId);
    $searches = [];

    return [
      '#theme' => 'legal_professional_dashboard',
      '#bookmarks' => $bookmarks,
      '#alerts' => $alerts,
      '#searches' => $searches,
      '#attached' => [
        'library' => [
          'jaraba_legal_intelligence/legal.dashboard',
        ],
      ],
      '#cache' => [
        'tags' => ['user:' . $userId],
        'contexts' => ['user'],
      ],
    ];
  }

  /**
   * Carga los favoritos del usuario con las resoluciones asociadas.
   *
   * Consulta el entity storage de legal_bookmark filtrado por user_id,
   * ordena por fecha de creacion descendente y para cada bookmark carga
   * la entidad legal_resolution asociada extrayendo los campos necesarios
   * para el template (titulo, fuente, referencia, fecha, estado, resumen).
   *
   * @param int $userId
   *   ID del usuario actual.
   *
   * @return array
   *   Lista de arrays asociativos con datos del bookmark y su resolucion.
   */
  private function loadUserBookmarks(int $userId): array {
    $bookmarks = [];

    try {
      $bookmarkStorage = $this->entityTypeManager->getStorage('legal_bookmark');
      $bookmarkIds = $bookmarkStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $userId)
        ->sort('created', 'DESC')
        ->execute();

      if (empty($bookmarkIds)) {
        return [];
      }

      $bookmarkEntities = $bookmarkStorage->loadMultiple($bookmarkIds);
      $resolutionStorage = $this->entityTypeManager->getStorage('legal_resolution');

      foreach ($bookmarkEntities as $bookmark) {
        $resolutionId = $bookmark->get('resolution_id')->value ?? NULL;
        if ($resolutionId === NULL) {
          continue;
        }

        $resolution = $resolutionStorage->load($resolutionId);
        if ($resolution === NULL) {
          continue;
        }

        $bookmarks[] = [
          'bookmark_id' => (int) $bookmark->id(),
          'bookmarked_at' => $bookmark->get('created')->value ?? NULL,
          'resolution' => [
            'id' => (int) $resolution->id(),
            'title' => $resolution->get('title')->value ?? '',
            'source_id' => $resolution->get('source_id')->value ?? '',
            'external_ref' => $resolution->get('external_ref')->value ?? '',
            'date_issued' => $resolution->get('date_issued')->value ?? NULL,
            'status_legal' => $resolution->get('status_legal')->value ?? 'vigente',
            'abstract_ai' => $resolution->get('abstract_ai')->value ?? '',
            'issuing_body' => $resolution->get('issuing_body')->value ?? '',
          ],
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading bookmarks for user @uid: @msg', [
        '@uid' => $userId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $bookmarks;
  }

  /**
   * Carga las alertas activas del usuario.
   *
   * Consulta el entity storage de legal_alert filtrado por user_id e
   * is_active=TRUE, ordena por fecha de creacion descendente y extrae
   * los campos necesarios para el template (tipo, query, temas, fuentes,
   * ultima activacion, fecha de creacion).
   *
   * @param int $userId
   *   ID del usuario actual.
   *
   * @return array
   *   Lista de arrays asociativos con datos de cada alerta activa.
   */
  private function loadUserActiveAlerts(int $userId): array {
    $alerts = [];

    try {
      $alertStorage = $this->entityTypeManager->getStorage('legal_alert');
      $alertIds = $alertStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('user_id', $userId)
        ->condition('is_active', TRUE)
        ->sort('created', 'DESC')
        ->execute();

      if (empty($alertIds)) {
        return [];
      }

      $alertEntities = $alertStorage->loadMultiple($alertIds);

      foreach ($alertEntities as $alert) {
        $alerts[] = [
          'id' => (int) $alert->id(),
          'alert_type' => $alert->get('alert_type')->value ?? '',
          'query_text' => $alert->get('query_text')->value ?? '',
          'source_ids' => $alert->get('source_ids')->value ?? '',
          'topics' => $alert->get('topics')->value ?? '',
          'is_active' => (bool) ($alert->get('is_active')->value ?? FALSE),
          'last_triggered' => $alert->get('last_triggered')->value ?? NULL,
          'created' => $alert->get('created')->value ?? NULL,
        ];
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error loading alerts for user @uid: @msg', [
        '@uid' => $userId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $alerts;
  }

}
