<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for managing pack catalog publication.
 *
 * Handles publishing, unpublishing, and slug generation for
 * PackServicioEi entities in the digital catalog.
 *
 * TENANT-001: Queries scoped via participante (already tenant-scoped).
 */
class CatalogoPacksService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Publishes a pack and generates its catalog URL slug.
   *
   * @param int $packId
   *   The pack_servicio_ei entity ID.
   *
   * @return bool
   *   TRUE if successfully published, FALSE otherwise.
   */
  public function publicarPack(int $packId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('pack_servicio_ei');
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
      $entity = $storage->load($packId);

      if ($entity === NULL) {
        $this->logger->warning('Pack @id not found for publicarPack.', [
          '@id' => $packId,
        ]);
        return FALSE;
      }

      if (!$entity->hasField('publicado')) {
        return FALSE;
      }

      $entity->set('publicado', TRUE);

      // Generate slug if not already set.
      if ($entity->hasField('url_catalogo')) {
        $currentSlug = $entity->get('url_catalogo')->isEmpty()
          ? ''
          : (string) $entity->get('url_catalogo')->value;

        if ($currentSlug === '') {
          $participanteId = $entity->hasField('participante_id') && !$entity->get('participante_id')->isEmpty()
            ? (int) $entity->get('participante_id')->target_id
            : 0;
          $packTipo = $entity->hasField('pack_tipo') && !$entity->get('pack_tipo')->isEmpty()
            ? (string) $entity->get('pack_tipo')->value
            : 'pack';

          $slug = $this->generarSlug($participanteId, $packTipo);
          $entity->set('url_catalogo', $slug);
        }
      }

      $entity->save();

      $this->logger->info('Pack @id published.', ['@id' => $packId]);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error publishing pack @id: @message', [
        '@id' => $packId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Unpublishes a pack from the catalog.
   *
   * @param int $packId
   *   The pack_servicio_ei entity ID.
   *
   * @return bool
   *   TRUE if successfully unpublished, FALSE otherwise.
   */
  public function despublicarPack(int $packId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('pack_servicio_ei');
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
      $entity = $storage->load($packId);

      if ($entity === NULL) {
        $this->logger->warning('Pack @id not found for despublicarPack.', [
          '@id' => $packId,
        ]);
        return FALSE;
      }

      if (!$entity->hasField('publicado')) {
        return FALSE;
      }

      $entity->set('publicado', FALSE);
      $entity->save();

      $this->logger->info('Pack @id unpublished.', ['@id' => $packId]);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error unpublishing pack @id: @message', [
        '@id' => $packId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Returns all published packs for a participant.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   *
   * @return array<int, array<string, mixed>>
   *   Published packs data keyed by entity ID.
   */
  public function getPacksPublicados(int $participanteId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('pack_servicio_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('publicado', TRUE)
        ->execute();

      if (count($ids) === 0) {
        return [];
      }

      /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities */
      $entities = $storage->loadMultiple($ids);
      $packs = [];

      foreach ($entities as $entity) {
        $packs[(int) $entity->id()] = [
          'id' => (int) $entity->id(),
          'titulo' => $entity->hasField('titulo') && !$entity->get('titulo')->isEmpty()
            ? (string) $entity->get('titulo')->value
            : '',
          'pack_tipo' => $entity->hasField('pack_tipo') && !$entity->get('pack_tipo')->isEmpty()
            ? (string) $entity->get('pack_tipo')->value
            : '',
          'url_catalogo' => $entity->hasField('url_catalogo') && !$entity->get('url_catalogo')->isEmpty()
            ? (string) $entity->get('url_catalogo')->value
            : '',
          'precio' => $entity->hasField('precio') && !$entity->get('precio')->isEmpty()
            ? (string) $entity->get('precio')->value
            : '0',
        ];
      }

      return $packs;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading published packs for participante @id: @message', [
        '@id' => $participanteId,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Loads a pack by its catalog URL slug.
   *
   * @param string $slug
   *   The url_catalogo slug.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The pack entity, or NULL if not found.
   */
  public function getPackPorSlug(string $slug): ?ContentEntityInterface {
    if ($slug === '') {
      return NULL;
    }

    try {
      $storage = $this->entityTypeManager->getStorage('pack_servicio_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('url_catalogo', $slug)
        ->condition('publicado', TRUE)
        ->range(0, 1)
        ->execute();

      if (count($ids) === 0) {
        return NULL;
      }

      $id = (int) reset($ids);
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
      $entity = $storage->load($id);

      return $entity;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error loading pack by slug "@slug": @message', [
        '@slug' => $slug,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Generates a URL-safe slug for a pack.
   *
   * @param int $participanteId
   *   The programa_participante_ei entity ID.
   * @param string $packTipo
   *   The pack type machine name.
   *
   * @return string
   *   A URL-safe slug.
   */
  public function generarSlug(int $participanteId, string $packTipo): string {
    // Normalize pack type to URL-safe string.
    $cleanTipo = preg_replace('/[^a-z0-9\-]/', '-', strtolower($packTipo));
    if ($cleanTipo === NULL || $cleanTipo === '') {
      $cleanTipo = 'pack';
    }
    $cleanTipo = trim((string) preg_replace('/-+/', '-', $cleanTipo), '-');
    if ($cleanTipo === '') {
      $cleanTipo = 'pack';
    }

    $baseSlug = $cleanTipo . '-' . $participanteId;

    // Ensure uniqueness by checking existing slugs.
    try {
      $storage = $this->entityTypeManager->getStorage('pack_servicio_ei');
      $slug = $baseSlug;
      $counter = 1;

      while (TRUE) {
        $existingIds = $storage->getQuery()
          ->accessCheck(FALSE)
          ->condition('url_catalogo', $slug)
          ->count()
          ->execute();

        if ((int) $existingIds === 0) {
          break;
        }

        $counter++;
        $slug = $baseSlug . '-' . $counter;

        // Safety valve to prevent infinite loop.
        if ($counter > 100) {
          $slug = $baseSlug . '-' . time();
          break;
        }
      }

      return $slug;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error generating slug for participante @id: @message', [
        '@id' => $participanteId,
        '@message' => $e->getMessage(),
      ]);
      // Fallback: use timestamp for uniqueness.
      return $baseSlug . '-' . time();
    }
  }

}
