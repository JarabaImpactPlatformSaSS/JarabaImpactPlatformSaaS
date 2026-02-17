<?php

declare(strict_types=1);

namespace Drupal\jaraba_servicios_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de logica de resenas de profesionales.
 *
 * Estructura: Gestiona la creacion, moderacion y consulta de resenas
 *   de servicios profesionales, asi como el recalculo de ratings.
 *
 * Logica: Centraliza el ciclo de vida de las resenas: envio por el
 *   cliente, aprobacion por admin, recalculo de average_rating
 *   y total_reviews en ProviderProfile. Verifica que el usuario
 *   tenga una reserva completada antes de permitir la resena.
 */
class ReviewService {

  /**
   * El entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * El usuario actual.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * El logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger;
  }

  /**
   * Envia una nueva resena para un profesional.
   *
   * Crea una entidad ReviewServicios con estado 'pending'. La resena
   * requiere moderacion antes de ser visible publicamente.
   *
   * @param int $providerId
   *   ID del perfil profesional evaluado.
   * @param int $bookingId
   *   ID de la reserva asociada.
   * @param int $rating
   *   Puntuacion de 1 a 5.
   * @param string $title
   *   Titulo breve de la resena.
   * @param string $comment
   *   Texto completo de la resena.
   *
   * @return object|null
   *   La entidad ReviewServicios creada o NULL si falla.
   */
  public function submitReview(int $providerId, int $bookingId, int $rating, string $title, string $comment): ?object {
    try {
      // Validar rating en rango 1-5.
      if ($rating < 1 || $rating > 5) {
        $this->logger->warning('Rating fuera de rango: @rating', ['@rating' => $rating]);
        return NULL;
      }

      // Verificar que el usuario puede dejar resena.
      $userId = (int) $this->currentUser->id();
      if (!$this->canUserReview($userId, $providerId)) {
        $this->logger->warning('Usuario @uid no puede dejar resena para profesional @pid.', [
          '@uid' => $userId,
          '@pid' => $providerId,
        ]);
        return NULL;
      }

      $storage = $this->entityTypeManager->getStorage('review_servicios');
      $review = $storage->create([
        'provider_id' => $providerId,
        'booking_id' => $bookingId,
        'reviewer_uid' => $userId,
        'rating' => $rating,
        'title' => $title,
        'comment' => $comment,
        'status' => 'pending',
      ]);
      $review->save();

      $this->logger->info('Resena #@id creada por usuario @uid para profesional @pid.', [
        '@id' => $review->id(),
        '@uid' => $userId,
        '@pid' => $providerId,
      ]);

      return $review;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al crear resena: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Aprueba una resena y recalcula el rating del profesional.
   *
   * Cambia el estado de la resena a 'approved' y dispara el recalculo
   * de average_rating y total_reviews en el ProviderProfile asociado.
   *
   * @param int $reviewId
   *   ID de la resena a aprobar.
   *
   * @return bool
   *   TRUE si se aprobo correctamente, FALSE en caso contrario.
   */
  public function approveReview(int $reviewId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('review_servicios');
      $review = $storage->load($reviewId);
      if (!$review) {
        $this->logger->warning('Resena #@id no encontrada.', ['@id' => $reviewId]);
        return FALSE;
      }

      $review->set('status', 'approved');
      $review->save();

      // Recalcular rating del profesional.
      $providerId = (int) $review->get('provider_id')->target_id;
      $this->recalculateAverageRating($providerId);

      $this->logger->info('Resena #@id aprobada. Rating de profesional @pid recalculado.', [
        '@id' => $reviewId,
        '@pid' => $providerId,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error al aprobar resena #@id: @error', [
        '@id' => $reviewId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene las resenas aprobadas de un profesional.
   *
   * @param int $providerId
   *   ID del perfil profesional.
   * @param int $limit
   *   Limite de resultados.
   * @param int $offset
   *   Desplazamiento para paginacion.
   *
   * @return array
   *   Array de entidades ReviewServicios aprobadas.
   */
  public function getProviderReviews(int $providerId, int $limit = 10, int $offset = 0): array {
    $storage = $this->entityTypeManager->getStorage('review_servicios');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('provider_id', $providerId)
      ->condition('status', 'approved')
      ->sort('created', 'DESC')
      ->range($offset, $limit)
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Recalcula average_rating y total_reviews en el ProviderProfile.
   *
   * Consulta todas las resenas aprobadas del profesional, calcula
   * la media y actualiza los campos desnormalizados.
   *
   * @param int $providerId
   *   ID del perfil profesional.
   */
  public function recalculateAverageRating(int $providerId): void {
    try {
      $reviewStorage = $this->entityTypeManager->getStorage('review_servicios');

      // Obtener todas las resenas aprobadas del profesional.
      $ids = $reviewStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('provider_id', $providerId)
        ->condition('status', 'approved')
        ->execute();

      $totalReviews = count($ids);
      $averageRating = 0.0;

      if ($totalReviews > 0) {
        $reviews = $reviewStorage->loadMultiple($ids);
        $sum = 0;
        foreach ($reviews as $review) {
          $sum += (int) $review->get('rating')->value;
        }
        $averageRating = round($sum / $totalReviews, 2);
      }

      // Actualizar ProviderProfile.
      $providerStorage = $this->entityTypeManager->getStorage('provider_profile');
      $provider = $providerStorage->load($providerId);
      if ($provider) {
        $provider->set('average_rating', $averageRating);
        $provider->set('total_reviews', $totalReviews);
        $provider->save();

        $this->logger->info('Rating de profesional @pid recalculado: @avg (@total resenas).', [
          '@pid' => $providerId,
          '@avg' => $averageRating,
          '@total' => $totalReviews,
        ]);
      }
      else {
        $this->logger->warning('Profesional @pid no encontrado para recalculo de rating.', [
          '@pid' => $providerId,
        ]);
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Error recalculando rating de profesional @pid: @error', [
        '@pid' => $providerId,
        '@error' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Verifica si un usuario puede dejar resena para un profesional.
   *
   * Un usuario puede dejar resena si tiene al menos una reserva
   * completada con el profesional y no ha dejado ya una resena
   * para esa misma reserva.
   *
   * @param int $userId
   *   ID del usuario.
   * @param int $providerId
   *   ID del perfil profesional.
   *
   * @return bool
   *   TRUE si el usuario puede dejar resena, FALSE en caso contrario.
   */
  public function canUserReview(int $userId, int $providerId): bool {
    try {
      // Buscar reservas completadas del usuario con este profesional.
      $bookingStorage = $this->entityTypeManager->getStorage('booking');
      $completedBookingIds = $bookingStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $userId)
        ->condition('provider_id', $providerId)
        ->condition('status', 'completed')
        ->execute();

      if (empty($completedBookingIds)) {
        return FALSE;
      }

      // Verificar que no haya dejado resena para todas las reservas completadas.
      $reviewStorage = $this->entityTypeManager->getStorage('review_servicios');
      $existingReviewIds = $reviewStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('reviewer_uid', $userId)
        ->condition('provider_id', $providerId)
        ->condition('booking_id', $completedBookingIds, 'IN')
        ->execute();

      // Si hay reservas completadas sin resena, puede dejar resena.
      return count($existingReviewIds) < count($completedBookingIds);
    }
    catch (\Exception $e) {
      $this->logger->error('Error verificando si usuario @uid puede dejar resena: @error', [
        '@uid' => $userId,
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

}
