<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_training\Entity\TrainingProductInterface;

/**
 * Servicio para gestión de la Escalera de Valor.
 *
 * Implementa la lógica de progresión entre peldaños:
 * 0: Lead Magnet (gratis) → Captura email
 * 1: Microcurso ($19-49) → Primera compra
 * 2: Membresía ($97/mes) → Recurrente
 * 3: Mastermind ($497/mes) → Grupo premium
 * 4: Mentoring 1:1 ($1500+) → Alto ticket
 * 5: Certificación → Franquicia
 */
class LadderService
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected AccountProxyInterface $currentUser,
    ) {
    }

    /**
     * Obtiene todos los productos ordenados por peldaño.
     *
     * @return \Drupal\jaraba_training\Entity\TrainingProductInterface[]
     *   Lista de productos ordenados.
     */
    public function getFullLadder(): array
    {
        $storage = $this->entityTypeManager->getStorage('training_product');
        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 1)
            ->sort('ladder_level', 'ASC')
            ->sort('price', 'ASC')
            ->execute();

        if (empty($ids)) {
            return [];
        }

        return $storage->loadMultiple($ids);
    }

    /**
     * Obtiene productos de un peldaño específico.
     *
     * @param int $level
     *   Nivel del peldaño (0-5).
     *
     * @return \Drupal\jaraba_training\Entity\TrainingProductInterface[]
     *   Productos de ese nivel.
     */
    public function getProductsByLevel(int $level): array
    {
        $storage = $this->entityTypeManager->getStorage('training_product');
        $ids = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('status', 1)
            ->condition('ladder_level', $level)
            ->execute();

        return $storage->loadMultiple($ids);
    }

    /**
     * Determina el nivel actual del usuario en la escalera.
     *
     * @param int|null $userId
     *   ID del usuario (null = usuario actual).
     *
     * @return int
     *   Nivel máximo alcanzado (0-5).
     */
    public function getCurrentLevel(?int $userId = NULL): int
    {
        $userId = $userId ?? (int) $this->currentUser->id();

        // TODO: Integrar con sistema de compras/enrollments.
        // Por ahora devuelve 0 (Lead Magnet).
        return 0;
    }

    /**
     * Obtiene el siguiente producto recomendado para el usuario.
     *
     * @param int|null $userId
     *   ID del usuario.
     *
     * @return \Drupal\jaraba_training\Entity\TrainingProductInterface|null
     *   Producto recomendado o NULL.
     */
    public function getRecommendedProduct(?int $userId = NULL): ?TrainingProductInterface
    {
        $currentLevel = $this->getCurrentLevel($userId);
        $nextLevel = min($currentLevel + 1, 5);

        $products = $this->getProductsByLevel($nextLevel);

        // Devuelve el producto destacado o el primero disponible.
        foreach ($products as $product) {
            if ($product->get('is_featured')->value) {
                return $product;
            }
        }

        return reset($products) ?: NULL;
    }

    /**
     * Calcula el progreso del usuario en la escalera.
     *
     * @param int|null $userId
     *   ID del usuario.
     *
     * @return array
     *   Datos de progreso con nivel actual, porcentaje y siguiente paso.
     */
    public function getUserProgress(?int $userId = NULL): array
    {
        $currentLevel = $this->getCurrentLevel($userId);
        $recommended = $this->getRecommendedProduct($userId);

        return [
            'current_level' => $currentLevel,
            'max_level' => 5,
            'progress_percent' => round(($currentLevel / 5) * 100),
            'recommended_product' => $recommended,
            'levels_completed' => $currentLevel,
            'levels_remaining' => 5 - $currentLevel,
        ];
    }

}
