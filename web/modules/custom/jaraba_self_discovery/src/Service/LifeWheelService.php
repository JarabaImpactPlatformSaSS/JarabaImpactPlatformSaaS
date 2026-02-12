<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio dedicado para consultas de Rueda de la Vida.
 */
class LifeWheelService
{

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El usuario actual.
     */
    protected AccountInterface $currentUser;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountInterface $current_user,
        LoggerInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->logger = $logger;
    }

    /**
     * Obtiene la ultima evaluacion de un usuario.
     *
     * @param int|null $uid
     *   ID del usuario. Si es NULL, usa el usuario actual.
     *
     * @return \Drupal\jaraba_self_discovery\Entity\LifeWheelAssessment|null
     *   La ultima evaluacion o NULL.
     */
    public function getLatestAssessment(?int $uid = NULL)
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        try {
            $storage = $this->entityTypeManager->getStorage('life_wheel_assessment');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $uid)
                ->sort('created', 'DESC')
                ->range(0, 1)
                ->execute();

            if (empty($ids)) {
                return NULL;
            }

            return $storage->load(reset($ids));
        }
        catch (\Exception $e) {
            $this->logger->error('LifeWheelService::getLatestAssessment error: @error', [
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Obtiene la puntuacion promedio del usuario.
     */
    public function getAverageScore(?int $uid = NULL): float
    {
        $assessment = $this->getLatestAssessment($uid);
        return $assessment ? $assessment->getAverageScore() : 0.0;
    }

    /**
     * Obtiene las areas con puntuacion mas baja.
     */
    public function getLowestAreas(?int $uid = NULL, int $count = 2): array
    {
        $assessment = $this->getLatestAssessment($uid);
        if (!$assessment) {
            return [];
        }

        $scores = $assessment->getAllScores();
        asort($scores);
        return array_slice($scores, 0, $count, TRUE);
    }

    /**
     * Obtiene las areas con puntuacion mas alta.
     */
    public function getHighestAreas(?int $uid = NULL, int $count = 2): array
    {
        $assessment = $this->getLatestAssessment($uid);
        if (!$assessment) {
            return [];
        }

        $scores = $assessment->getAllScores();
        arsort($scores);
        return array_slice($scores, 0, $count, TRUE);
    }

    /**
     * Compara la ultima evaluacion con la penultima para ver tendencia.
     */
    public function getTrend(?int $uid = NULL): array
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        try {
            $storage = $this->entityTypeManager->getStorage('life_wheel_assessment');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $uid)
                ->sort('created', 'DESC')
                ->range(0, 2)
                ->execute();

            if (count($ids) < 2) {
                return ['trend' => 'no_data', 'diff' => 0];
            }

            $entities = $storage->loadMultiple($ids);
            $entities = array_values($entities);

            $currentAvg = $entities[0]->getAverageScore();
            $previousAvg = $entities[1]->getAverageScore();
            $diff = round($currentAvg - $previousAvg, 1);

            return [
                'trend' => $diff > 0 ? 'improving' : ($diff < 0 ? 'declining' : 'stable'),
                'diff' => $diff,
                'current' => $currentAvg,
                'previous' => $previousAvg,
            ];
        }
        catch (\Exception $e) {
            $this->logger->error('LifeWheelService::getTrend error: @error', [
                '@error' => $e->getMessage(),
            ]);
            return ['trend' => 'error', 'diff' => 0];
        }
    }

    /**
     * Obtiene el historial de evaluaciones.
     */
    public function getHistorical(?int $uid = NULL, int $limit = 10): array
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        try {
            $storage = $this->entityTypeManager->getStorage('life_wheel_assessment');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $uid)
                ->sort('created', 'DESC')
                ->range(0, $limit)
                ->execute();

            if (empty($ids)) {
                return [];
            }

            $entities = $storage->loadMultiple($ids);
            $history = [];

            foreach ($entities as $entity) {
                $history[] = [
                    'id' => $entity->id(),
                    'scores' => $entity->getAllScores(),
                    'average' => $entity->getAverageScore(),
                    'created' => $entity->get('created')->value,
                ];
            }

            return $history;
        }
        catch (\Exception $e) {
            $this->logger->error('LifeWheelService::getHistorical error: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

}
