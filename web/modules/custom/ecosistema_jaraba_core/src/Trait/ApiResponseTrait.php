<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Trait;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * AUDIT-CONS-008: Envelope JSON estándar para APIs REST.
 *
 * Proporciona métodos helper para que todos los controllers devuelvan
 * respuestas JSON con formato unificado:
 *
 * Éxito:   {"success": true, "data": {...}, "meta": {...}}
 * Error:   {"success": false, "error": {"code": "...", "message": "..."}}
 * Paginado: {"success": true, "data": [...], "meta": {"pagination": {...}}}
 *
 * Uso:
 * @code
 * use Drupal\ecosistema_jaraba_core\Trait\ApiResponseTrait;
 *
 * class MiController extends ControllerBase {
 *   use ApiResponseTrait;
 *
 *   public function endpoint() {
 *     return $this->apiSuccess(['items' => $items]);
 *     return $this->apiError('Not found', 'NOT_FOUND', 404);
 *     return $this->apiPaginated($items, $total, $limit, $offset);
 *   }
 * }
 * @endcode
 */
trait ApiResponseTrait {

  /**
   * Devuelve una respuesta JSON de éxito.
   *
   * @param mixed $data
   *   Los datos de la respuesta.
   * @param array $meta
   *   Metadatos opcionales (timestamps, versión, rate limit, etc.).
   * @param int $statusCode
   *   Código HTTP (200, 201, etc.).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON estandarizada.
   */
  protected function apiSuccess(mixed $data = NULL, array $meta = [], int $statusCode = 200): JsonResponse {
    $response = [
      'success' => TRUE,
      'data' => $data,
    ];

    if (!empty($meta)) {
      $response['meta'] = $meta;
    }

    return new JsonResponse($response, $statusCode);
  }

  /**
   * Devuelve una respuesta JSON de error.
   *
   * @param string $message
   *   Mensaje legible para el usuario.
   * @param string $code
   *   Código de error máquina (UNAUTHORIZED, NOT_FOUND, etc.).
   * @param int $statusCode
   *   Código HTTP (400, 401, 403, 404, 429, 500).
   * @param array $details
   *   Detalles adicionales opcionales (campo inválido, sugerencias, etc.).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON estandarizada.
   */
  protected function apiError(string $message, string $code = 'INTERNAL_ERROR', int $statusCode = 500, array $details = []): JsonResponse {
    $error = [
      'code' => $code,
      'message' => $message,
    ];

    if (!empty($details)) {
      $error['details'] = $details;
    }

    return new JsonResponse([
      'success' => FALSE,
      'error' => $error,
    ], $statusCode);
  }

  /**
   * Devuelve una respuesta JSON paginada.
   *
   * @param array $items
   *   Los ítems de la página actual.
   * @param int $total
   *   Total de ítems disponibles.
   * @param int $limit
   *   Tamaño de página.
   * @param int $offset
   *   Desplazamiento desde el inicio.
   * @param int $statusCode
   *   Código HTTP (200 por defecto).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con paginación.
   */
  protected function apiPaginated(array $items, int $total, int $limit = 20, int $offset = 0, int $statusCode = 200): JsonResponse {
    return new JsonResponse([
      'success' => TRUE,
      'data' => $items,
      'meta' => [
        'pagination' => [
          'total' => $total,
          'limit' => $limit,
          'offset' => $offset,
          'has_more' => ($offset + $limit) < $total,
        ],
      ],
    ], $statusCode);
  }

}
