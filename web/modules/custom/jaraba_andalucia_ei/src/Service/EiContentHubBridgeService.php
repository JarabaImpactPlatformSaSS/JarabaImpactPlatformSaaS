<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Puente entre Andalucía +ei y jaraba_content_hub.
 *
 * Módulo 4 (Marketing Digital) y Pack 1/5 (Contenido Digital / Community
 * Manager). Patrón OPTIONAL-CROSSMODULE-001: dependencia @? en services.yml.
 */
class EiContentHubBridgeService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $contentHubService = NULL,
  ) {}

  /**
   * Comprueba si el servicio de Content Hub está disponible.
   */
  public function isAvailable(): bool {
    return $this->contentHubService !== NULL;
  }

  /**
   * Obtiene el calendario editorial de un participante.
   *
   * Consulta ContentArticle entities vinculadas al participante para construir
   * una vista de calendario con fechas de publicación planificadas y reales.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array<int, array{id: int, titulo: string, estado: string, fecha_programada: string|null, fecha_publicacion: string|null, canal: string}>
   *   Lista de entradas del calendario editorial, vacía si no hay datos o
   *   el servicio no está disponible.
   */
  public function getCalendarioEditorial(int $participanteId): array {
    if (!$this->contentHubService) {
      return [];
    }

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return [];
      }

      $uid = $participante->getOwnerId();
      if (!$uid) {
        return [];
      }

      // Delegar al content hub para obtener artículos del usuario.
      if (method_exists($this->contentHubService, 'getCalendarioEditorialByAuthor')) {
        $resultado = $this->contentHubService->getCalendarioEditorialByAuthor($uid);
        return is_array($resultado) ? $resultado : [];
      }

      // Fallback: consultar ContentArticle directamente.
      $storage = $this->entityTypeManager->getStorage('content_article');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->sort('created', 'DESC')
        ->range(0, 50)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $articles = $storage->loadMultiple($ids);
      $calendario = [];

      foreach ($articles as $article) {
        $calendario[] = [
          'id' => (int) $article->id(),
          'titulo' => $article->label() ?? '',
          'estado' => $article->isPublished() ? 'publicado' : 'borrador',
          'fecha_programada' => $article->hasField('scheduled_date') && !$article->get('scheduled_date')->isEmpty()
            ? $article->get('scheduled_date')->value
            : NULL,
          'fecha_publicacion' => $article->isPublished()
            ? date('Y-m-d', (int) $article->getCreatedTime())
            : NULL,
          'canal' => $article->hasField('canal') && !$article->get('canal')->isEmpty()
            ? (string) $article->get('canal')->value
            : 'web',
        ];
      }

      return $calendario;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo calendario editorial para participante @pid: @msg', [
        '@pid' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene el contenido publicado de un participante.
   *
   * Devuelve estadísticas y listado de contenido ya publicado para el
   * dashboard del participante y los informes de progreso del orientador.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array{total: int, publicados: int, borradores: int, items: array}
   *   Resumen del contenido publicado, con valores por defecto seguros.
   */
  public function getContenidoPublicado(int $participanteId): array {
    $default = ['total' => 0, 'publicados' => 0, 'borradores' => 0, 'items' => []];

    if (!$this->contentHubService) {
      return $default;
    }

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return $default;
      }

      $uid = $participante->getOwnerId();
      if (!$uid) {
        return $default;
      }

      // Delegar si el servicio soporta el método.
      if (method_exists($this->contentHubService, 'getContenidoByAuthor')) {
        $resultado = $this->contentHubService->getContenidoByAuthor($uid);
        return is_array($resultado) ? $resultado : $default;
      }

      // Fallback: consulta directa.
      $storage = $this->entityTypeManager->getStorage('content_article');
      $allIds = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->execute();

      if (empty($allIds)) {
        return $default;
      }

      $articles = $storage->loadMultiple($allIds);
      $publicados = 0;
      $borradores = 0;
      $items = [];

      foreach ($articles as $article) {
        $isPublished = $article->isPublished();
        if ($isPublished) {
          $publicados++;
        }
        else {
          $borradores++;
        }

        $items[] = [
          'id' => (int) $article->id(),
          'titulo' => $article->label() ?? '',
          'estado' => $isPublished ? 'publicado' : 'borrador',
          'fecha' => date('Y-m-d', (int) $article->getCreatedTime()),
        ];
      }

      return [
        'total' => count($articles),
        'publicados' => $publicados,
        'borradores' => $borradores,
        'items' => array_slice($items, 0, 20),
      ];
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo contenido publicado para participante @pid: @msg', [
        '@pid' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return $default;
    }
  }

}
