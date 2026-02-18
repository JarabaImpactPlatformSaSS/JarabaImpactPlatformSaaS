<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_comercio_conecta\Service\FlashOfferService;
use Drupal\jaraba_comercio_conecta\Service\QrRetailService;
use Drupal\jaraba_comercio_conecta\Service\ReviewRetailService;
use Drupal\jaraba_comercio_conecta\Service\NotificationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller API para Flash Offers, QR, Reviews y Notificaciones.
 *
 * Estructura: Expone endpoints JSON para las 4 areas funcionales de F9.
 *   Cada metodo valida la entrada, delega al servicio correspondiente
 *   y devuelve una JsonResponse estandarizada.
 */
class FlashOfferApiController extends ControllerBase {

  public function __construct(
    protected FlashOfferService $flashOfferService,
    protected QrRetailService $qrRetailService,
    protected ReviewRetailService $reviewRetailService,
    protected NotificationService $notificationService,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.flash_offer'),
      $container->get('jaraba_comercio_conecta.qr_retail'),
      $container->get('jaraba_comercio_conecta.review_retail'),
      $container->get('jaraba_comercio_conecta.notification'),
    );
  }

  /**
   * Lista ofertas flash activas, opcionalmente por geolocalizacion.
   *
   * Logica: Obtiene lat, lng, radius y limit de query params.
   *   Delega a FlashOfferService::getActiveOffers() y serializa
   *   cada oferta con datos basicos para el frontend.
   */
  public function listActiveOffers(Request $request): JsonResponse {
    $lat = $request->query->get('lat') !== NULL ? (float) $request->query->get('lat') : NULL;
    $lng = $request->query->get('lng') !== NULL ? (float) $request->query->get('lng') : NULL;
    $radius = (float) ($request->query->get('radius') ?: 10);
    $limit = min(50, max(1, (int) ($request->query->get('limit') ?: 20)));

    $offers = $this->flashOfferService->getActiveOffers($lat, $lng, $radius, $limit);

    $data = [];
    foreach ($offers as $offer) {
      $data[] = [
        'id' => (int) $offer->id(),
        'title' => $offer->get('title')->value,
        'description' => $offer->get('description')->value,
        'discount_type' => $offer->get('discount_type')->value,
        'discount_value' => (float) $offer->get('discount_value')->value,
        'original_price' => (float) $offer->get('original_price')->value,
        'offer_price' => (float) $offer->get('offer_price')->value,
        'image_url' => $offer->get('image_url')->value,
        'end_time' => (int) $offer->get('end_time')->value,
        'max_claims' => (int) $offer->get('max_claims')->value,
        'current_claims' => (int) $offer->get('current_claims')->value,
        'location_lat' => (float) $offer->get('location_lat')->value,
        'location_lng' => (float) $offer->get('location_lng')->value,
      ];
    }

    return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Canjea una oferta flash para el usuario actual.
   *
   * Logica: Lee offer_id, lat, lng del body JSON. Valida que
   *   el usuario este autenticado. Delega a FlashOfferService::claimOffer().
   */
  public function claimOffer(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];
    $offer_id = (int) ($body['offer_id'] ?? 0);

    if (!$offer_id) {
      return new JsonResponse(['error' => $this->t('Campo offer_id requerido.')], 400);
    }

    $user_id = (int) $this->currentUser()->id();
    if ($user_id === 0) {
      return new JsonResponse(['error' => $this->t('Debe iniciar sesion para canjear ofertas.')], 403);
    }

    $lat = (float) ($body['lat'] ?? 0);
    $lng = (float) ($body['lng'] ?? 0);

    $claim = $this->flashOfferService->claimOffer($offer_id, $user_id, $lat, $lng);

    if (!$claim) {
      return new JsonResponse(['error' => $this->t('No se pudo canjear la oferta. Puede estar expirada, llena o ya canjeada.')], 409);
    }

    return new JsonResponse(['success' => TRUE, 'data' => $claim, 'meta' => ['timestamp' => time()]], 201);
  }

  /**
   * Redime un canje de oferta flash con el codigo.
   *
   * Logica: Lee claim_code del body JSON. Delega a
   *   FlashOfferService::redeemClaim().
   */
  public function redeemClaim(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];
    $claim_code = trim($body['claim_code'] ?? '');

    if (!$claim_code) {
      return new JsonResponse(['error' => $this->t('Campo claim_code requerido.')], 400);
    }

    $success = $this->flashOfferService->redeemClaim($claim_code);

    if (!$success) {
      return new JsonResponse(['error' => $this->t('Codigo de canje invalido o ya utilizado.')], 404);
    }

    return new JsonResponse(['success' => TRUE, 'data' => ['redeemed' => TRUE], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Estadisticas de una oferta flash.
   */
  public function offerStats(int $offer_id): JsonResponse {
    $stats = $this->flashOfferService->getOfferStats($offer_id);
    return new JsonResponse(['success' => TRUE, 'data' => $stats, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Resuelve un short_code QR y redirige/devuelve la URL destino.
   *
   * Logica: Busca el QR por short_code, registra el escaneo,
   *   devuelve la URL destino para redireccion del frontend.
   */
  public function resolveQr(string $short_code, Request $request): JsonResponse {
    $resolved = $this->qrRetailService->resolveShortCode($short_code);

    if (!$resolved) {
      return new JsonResponse(['error' => $this->t('Codigo QR no encontrado o inactivo.')], 404);
    }

    $user_id = $this->currentUser()->isAuthenticated() ? (int) $this->currentUser()->id() : NULL;
    $session_id = $request->getSession()->getId();
    $user_agent = $request->headers->get('User-Agent', '');
    $lat = $request->query->get('lat') !== NULL ? (float) $request->query->get('lat') : NULL;
    $lng = $request->query->get('lng') !== NULL ? (float) $request->query->get('lng') : NULL;

    $this->qrRetailService->recordScan(
      $resolved['qr_id'],
      $user_id,
      $session_id,
      $user_agent,
      $lat,
      $lng
    );

    return new JsonResponse(['data' => [
      'target_url' => $resolved['target_url'],
      'ab_variant' => $resolved['ab_variant'],
    ]]);
  }

  /**
   * Captura un lead desde un escaneo QR.
   */
  public function captureQrLead(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];
    $qr_code_id = (int) ($body['qr_code_id'] ?? 0);
    $scan_event_id = (int) ($body['scan_event_id'] ?? 0);

    if (!$qr_code_id || !$scan_event_id) {
      return new JsonResponse(['error' => $this->t('Campos qr_code_id y scan_event_id requeridos.')], 400);
    }

    $lead_data = array_intersect_key($body, array_flip(['name', 'email', 'phone', 'notes']));
    $success = $this->qrRetailService->captureLead($qr_code_id, $scan_event_id, $lead_data);

    if (!$success) {
      return new JsonResponse(['error' => $this->t('Error capturando lead.')], 500);
    }

    return new JsonResponse(['success' => TRUE, 'data' => ['captured' => TRUE], 'meta' => ['timestamp' => time()]], 201);
  }

  /**
   * Estadisticas de un codigo QR.
   */
  public function qrStats(int $qr_code_id): JsonResponse {
    $stats = $this->qrRetailService->getQrStats($qr_code_id);
    return new JsonResponse(['success' => TRUE, 'data' => $stats, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Lista resenas aprobadas de una entidad (producto, comercio, etc.).
   */
  public function listReviews(Request $request): JsonResponse {
    $entity_type = $request->query->get('entity_type', '');
    $entity_id = (int) $request->query->get('entity_id', 0);
    $limit = min(50, max(1, (int) ($request->query->get('limit') ?: 20)));
    $offset = max(0, (int) ($request->query->get('offset') ?: 0));

    if (!$entity_type || !$entity_id) {
      return new JsonResponse(['error' => $this->t('Parametros entity_type y entity_id requeridos.')], 400);
    }

    $reviews = $this->reviewRetailService->getEntityReviews($entity_type, $entity_id, $limit, $offset);
    $summary = $this->reviewRetailService->getReviewSummary($entity_type, $entity_id);

    $data = [];
    foreach ($reviews as $review) {
      $data[] = [
        'id' => (int) $review->id(),
        'title' => $review->get('title')->value,
        'body' => $review->get('body')->value,
        'rating' => (int) $review->get('rating')->value,
        'verified_purchase' => (bool) $review->get('verified_purchase')->value,
        'helpful_count' => (int) $review->get('helpful_count')->value,
        'merchant_response' => $review->get('merchant_response')->value,
        'created' => (int) $review->get('created')->value,
      ];
    }

    return new JsonResponse([
      'data' => $data,
      'summary' => $summary,
    ]);
  }

  /**
   * Crea una resena.
   */
  public function createReview(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];

    $required = ['title', 'body', 'rating', 'entity_type_ref', 'entity_id_ref'];
    foreach ($required as $field) {
      if (empty($body[$field])) {
        return new JsonResponse(['error' => $this->t('Campo @field requerido.', ['@field' => $field])], 400);
      }
    }

    $rating = (int) $body['rating'];
    if ($rating < 1 || $rating > 5) {
      return new JsonResponse(['error' => $this->t('La valoracion debe ser entre 1 y 5.')], 400);
    }

    $user_id = (int) $this->currentUser()->id();
    if ($user_id === 0) {
      return new JsonResponse(['error' => $this->t('Debe iniciar sesion para dejar una resena.')], 403);
    }

    $review = $this->reviewRetailService->createReview(array_merge($body, [
      'user_id' => $user_id,
    ]));

    if (!$review) {
      return new JsonResponse(['error' => $this->t('Error creando resena.')], 500);
    }

    return new JsonResponse(['success' => TRUE, 'data' => [
      'id' => (int) $review->id(),
      'status' => $review->get('status')->value,
    ], 'meta' => ['timestamp' => time()]], 201);
  }

  /**
   * Marca una resena como util.
   */
  public function markReviewHelpful(int $review_id): JsonResponse {
    $new_count = $this->reviewRetailService->markHelpful($review_id);
    return new JsonResponse(['success' => TRUE, 'data' => ['helpful_count' => $new_count], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Obtiene el historial de notificaciones del usuario actual.
   */
  public function notificationHistory(Request $request): JsonResponse {
    $user_id = (int) $this->currentUser()->id();
    if ($user_id === 0) {
      return new JsonResponse(['error' => $this->t('Debe iniciar sesion.')], 403);
    }

    $limit = min(50, max(1, (int) ($request->query->get('limit') ?: 20)));
    $notifications = $this->notificationService->getNotificationHistory($user_id, $limit);

    $data = [];
    foreach ($notifications as $log) {
      $data[] = [
        'id' => (int) $log->id(),
        'subject' => $log->get('subject')->value,
        'body' => $log->get('body')->value,
        'channel' => $log->get('channel')->value,
        'status' => $log->get('status')->value,
        'is_read' => (bool) $log->get('is_read')->value,
        'created' => (int) $log->get('created')->value,
      ];
    }

    return new JsonResponse(['success' => TRUE, 'data' => $data, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Marca una notificacion como leida.
   */
  public function markNotificationRead(int $notification_id): JsonResponse {
    $success = $this->notificationService->markAsRead($notification_id);

    if (!$success) {
      return new JsonResponse(['error' => $this->t('Notificacion no encontrada.')], 404);
    }

    return new JsonResponse(['success' => TRUE, 'data' => ['read' => TRUE], 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Obtiene preferencias de notificacion del usuario actual.
   */
  public function getPreferences(): JsonResponse {
    $user_id = (int) $this->currentUser()->id();
    if ($user_id === 0) {
      return new JsonResponse(['error' => $this->t('Debe iniciar sesion.')], 403);
    }

    $preferences = $this->notificationService->getUserPreferences($user_id);
    return new JsonResponse(['success' => TRUE, 'data' => $preferences, 'meta' => ['timestamp' => time()]]);
  }

  /**
   * Actualiza una preferencia de notificacion.
   */
  public function updatePreference(Request $request): JsonResponse {
    $user_id = (int) $this->currentUser()->id();
    if ($user_id === 0) {
      return new JsonResponse(['error' => $this->t('Debe iniciar sesion.')], 403);
    }

    $body = json_decode($request->getContent(), TRUE) ?? [];
    $channel = $body['channel'] ?? '';
    $category = $body['category'] ?? '';

    if (!$channel || !$category) {
      return new JsonResponse(['error' => $this->t('Campos channel y category requeridos.')], 400);
    }

    $enabled = (bool) ($body['enabled'] ?? TRUE);
    $this->notificationService->updatePreference($user_id, $channel, $category, $enabled);

    return new JsonResponse(['success' => TRUE, 'data' => ['updated' => TRUE], 'meta' => ['timestamp' => time()]]);
  }

}
