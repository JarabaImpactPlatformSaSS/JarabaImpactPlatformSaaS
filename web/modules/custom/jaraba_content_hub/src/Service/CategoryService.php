<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar entidades ContentCategory.
 *
 * PROPÓSITO:
 * Centraliza la lógica de negocio para categorías del Content Hub.
 * Proporciona métodos reutilizables para consultas, opciones de formularios
 * y métricas de artículos por categoría.
 *
 * ESPECIFICACIÓN: Doc 128 - Platform_AI_Content_Hub_v2
 */
class CategoryService
{

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un CategoryService.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        LoggerInterface $logger,
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
    }

    /**
     * Obtiene todas las categorías ordenadas.
     *
     * Ordena primero por peso (weight) y luego alfabéticamente por nombre.
     * Útil para listados y menús de navegación.
     *
     * @return array
     *   Array de entidades ContentCategory.
     */
    public function getAllCategories(): array
    {
        $storage = $this->entityTypeManager->getStorage('content_category');
        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->sort('weight', 'ASC')
            ->sort('name', 'ASC')
            ->execute();

        return $storage->loadMultiple($ids);
    }

    /**
     * Obtiene una categoría por su slug.
     *
     * @param string $slug
     *   El slug único de la categoría (URL-friendly).
     *
     * @return \Drupal\jaraba_content_hub\Entity\ContentCategory|null
     *   La entidad categoría o NULL si no existe.
     */
    public function getBySlug(string $slug)
    {
        $storage = $this->entityTypeManager->getStorage('content_category');
        $entities = $storage->loadByProperties(['slug' => $slug]);
        return $entities ? reset($entities) : NULL;
    }

    /**
     * Obtiene categorías como opciones para campos select.
     *
     * Genera un array asociativo id => nombre para usar en formularios
     * con elementos #type 'select' o 'radios'.
     *
     * @return array
     *   Array asociativo [id => nombre].
     */
    public function getCategoriesAsOptions(): array
    {
        $options = [];
        foreach ($this->getAllCategories() as $category) {
            $options[$category->id()] = $category->getName();
        }
        return $options;
    }

    /**
     * Obtiene el conteo de artículos por categoría en una sola consulta.
     *
     * Resuelve el problema N+1 de llamar getArticleCount() por cada categoría.
     * Ejecuta un solo GROUP BY en vez de N queries individuales.
     *
     * @return array
     *   Array asociativo [category_id => count].
     */
    public function getArticleCountsByCategory(): array
    {
        $connection = \Drupal::database();
        $result = $connection->query(
            "SELECT ca.category, COUNT(*) as cnt
             FROM {content_article_field_data} ca
             WHERE ca.status = :status AND ca.category IS NOT NULL
             GROUP BY ca.category",
            [':status' => 'published']
        );

        $counts = [];
        foreach ($result as $row) {
            $counts[(int) $row->category] = (int) $row->cnt;
        }
        return $counts;
    }

    /**
     * Obtiene el conteo de artículos publicados en una categoría.
     *
     * Solo cuenta artículos con status 'published'.
     * Útil para mostrar métricas en dashboards y listados.
     *
     * @param int $categoryId
     *   El ID de la categoría.
     *
     * @return int
     *   Número de artículos publicados en la categoría.
     */
    public function getArticleCount(int $categoryId): int
    {
        return (int) $this->entityTypeManager
            ->getStorage('content_article')
            ->getQuery()
            ->condition('category', $categoryId)
            ->condition('status', 'published')
            ->accessCheck(TRUE)
            ->count()
            ->execute();
    }

    /**
     * Actualiza el campo posts_count de todas las categorias.
     *
     * Recalcula el conteo de articulos publicados por categoria
     * y actualiza el campo cache posts_count en cada entidad.
     * Diseniado para ejecutarse desde cron o manualmente.
     *
     * @return int
     *   Numero de categorias actualizadas.
     */
    public function updatePostsCount(): int
    {
        $counts = $this->getArticleCountsByCategory();
        $categories = $this->getAllCategories();
        $updated = 0;

        foreach ($categories as $category) {
            $categoryId = (int) $category->id();
            $newCount = $counts[$categoryId] ?? 0;
            $currentCount = (int) ($category->get('posts_count')->value ?? 0);

            if ($newCount !== $currentCount) {
                $category->set('posts_count', $newCount);
                try {
                    $category->save();
                    $updated++;
                }
                catch (\Exception $e) {
                    $this->logger->error('Error updating posts_count for category @id: @error', [
                        '@id' => $categoryId,
                        '@error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $updated;
    }

    /**
     * Obtiene las categorias activas con al menos un articulo publicado.
     *
     * Util para filtros publicos donde categorias vacias no deben mostrarse.
     *
     * @return array
     *   Array de entidades ContentCategory activas con articulos.
     */
    public function getActiveCategories(): array
    {
        $counts = $this->getArticleCountsByCategory();
        $categories = $this->getAllCategories();

        return array_filter($categories, function ($category) use ($counts) {
            // Si tiene campo is_active, verificar.
            if ($category->hasField('is_active') && !$category->get('is_active')->value) {
                return FALSE;
            }
            // Solo incluir si tiene al menos 1 articulo.
            return ($counts[(int) $category->id()] ?? 0) > 0;
        });
    }

}
