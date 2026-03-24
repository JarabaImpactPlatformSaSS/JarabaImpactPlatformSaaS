<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Puente entre Andalucía +ei y jaraba_comercioconecta.
 *
 * Pack 4 (Tienda Digital): permite a participantes del programa acceder a las
 * herramientas de catálogo y tienda online de ComercioConecta.
 * Patrón OPTIONAL-CROSSMODULE-001: dependencia @? en services.yml.
 */
class EiComercioConectaBridgeService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $catalogoService = NULL,
  ) {}

  /**
   * Comprueba si el servicio de ComercioConecta está disponible.
   */
  public function isAvailable(): bool {
    return $this->catalogoService !== NULL;
  }

  /**
   * Obtiene el catálogo de productos de un participante.
   *
   * Consulta los productos creados por el participante dentro de
   * ComercioConecta para mostrar en su dashboard del programa.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array<int, array{id: int, nombre: string, precio: float, estado: string, imagen_url: string|null}>
   *   Lista de productos del catálogo, vacía si no hay datos o el servicio
   *   no está disponible.
   */
  public function getCatalogoProductos(int $participanteId): array {
    if (!$this->catalogoService) {
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

      // Delegar al catálogo service si soporta el método.
      if (method_exists($this->catalogoService, 'getProductosByOwner')) {
        $resultado = $this->catalogoService->getProductosByOwner($uid);
        return is_array($resultado) ? $resultado : [];
      }

      // Fallback: consulta directa a la entity de producto.
      $storage = $this->entityTypeManager->getStorage('comercio_producto');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->sort('created', 'DESC')
        ->range(0, 100)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $productos = $storage->loadMultiple($ids);
      $catalogo = [];

      foreach ($productos as $producto) {
        $catalogo[] = [
          'id' => (int) $producto->id(),
          'nombre' => $producto->label() ?? '',
          'precio' => $producto->hasField('precio') && !$producto->get('precio')->isEmpty()
            ? (float) $producto->get('precio')->value
            : 0.0,
          'estado' => $producto->isPublished() ? 'activo' : 'borrador',
          'imagen_url' => $producto->hasField('imagen') && !$producto->get('imagen')->isEmpty()
            ? $producto->get('imagen')->entity?->createFileUrl()
            : NULL,
        ];
      }

      return $catalogo;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo catálogo productos para participante @pid: @msg', [
        '@pid' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene la URL de la tienda online de un participante.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return string|null
   *   URL de la tienda o NULL si no existe o el servicio no está disponible.
   */
  public function getTiendaUrl(int $participanteId): ?string {
    if (!$this->catalogoService) {
      return NULL;
    }

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return NULL;
      }

      $uid = $participante->getOwnerId();
      if (!$uid) {
        return NULL;
      }

      // Delegar si el servicio soporta el método.
      if (method_exists($this->catalogoService, 'getTiendaUrlByOwner')) {
        $resultado = $this->catalogoService->getTiendaUrlByOwner($uid);
        return is_string($resultado) ? $resultado : NULL;
      }

      // Fallback: buscar tienda entity vinculada al usuario.
      $storage = $this->entityTypeManager->getStorage('comercio_tienda');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('uid', $uid)
        ->condition('status', 1)
        ->range(0, 1)
        ->execute();

      if (empty($ids)) {
        return NULL;
      }

      $tienda = $storage->load(reset($ids));
      if (!$tienda) {
        return NULL;
      }

      if ($tienda->hasField('url_publica') && !$tienda->get('url_publica')->isEmpty()) {
        return (string) $tienda->get('url_publica')->value;
      }

      // Construir URL por slug si existe.
      if ($tienda->hasField('slug') && !$tienda->get('slug')->isEmpty()) {
        return '/tienda/' . $tienda->get('slug')->value;
      }

      return NULL;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo URL tienda para participante @pid: @msg', [
        '@pid' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

}
