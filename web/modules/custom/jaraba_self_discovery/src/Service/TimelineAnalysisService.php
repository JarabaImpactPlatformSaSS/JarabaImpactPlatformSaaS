<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de analisis y agregacion de eventos del Timeline.
 */
class TimelineAnalysisService
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
     * Obtiene todos los eventos del timeline de un usuario.
     */
    public function getAllEvents(?int $uid = NULL): array
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        try {
            $storage = $this->entityTypeManager->getStorage('life_timeline');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $uid)
                ->sort('event_date', 'ASC')
                ->execute();

            if (empty($ids)) {
                return [];
            }

            $entities = $storage->loadMultiple($ids);
            $events = [];

            foreach ($entities as $entity) {
                $events[] = [
                    'id' => $entity->id(),
                    'title' => $entity->get('title')->value,
                    'date' => $entity->get('event_date')->value,
                    'type' => $entity->get('event_type')->value,
                    'category' => $entity->get('category')->value,
                    'description' => $entity->get('description')->value ?? '',
                    'satisfaction_factors' => $entity->getSatisfactionFactors(),
                    'skills' => $entity->getSkills(),
                    'values' => $entity->getValuesDiscovered(),
                    'learnings' => $entity->get('learnings')->value ?? '',
                    'patterns' => $entity->get('patterns')->value ?? '',
                ];
            }

            return $events;
        }
        catch (\Exception $e) {
            $this->logger->error('TimelineAnalysisService::getAllEvents error: @error', [
                '@error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Identifica patrones recurrentes en los eventos.
     */
    public function getIdentifiedPatterns(?int $uid = NULL): array
    {
        $events = $this->getAllEvents($uid);
        if (empty($events)) {
            return [];
        }

        $factorFrequency = [];
        $skillFrequency = [];
        $valueFrequency = [];

        foreach ($events as $event) {
            foreach ($event['satisfaction_factors'] as $factor) {
                $factorFrequency[$factor] = ($factorFrequency[$factor] ?? 0) + 1;
            }
            foreach ($event['skills'] as $skill) {
                $skillFrequency[$skill] = ($skillFrequency[$skill] ?? 0) + 1;
            }
            foreach ($event['values'] as $value) {
                $valueFrequency[$value] = ($valueFrequency[$value] ?? 0) + 1;
            }
        }

        arsort($factorFrequency);
        arsort($skillFrequency);
        arsort($valueFrequency);

        // Patrones son elementos que aparecen en mas de 1 evento.
        return [
            'recurring_factors' => array_filter($factorFrequency, fn($count) => $count > 1),
            'recurring_skills' => array_filter($skillFrequency, fn($count) => $count > 1),
            'recurring_values' => array_filter($valueFrequency, fn($count) => $count > 1),
            'total_events' => count($events),
        ];
    }

    /**
     * Obtiene los factores de satisfaccion mas frecuentes.
     */
    public function getTopSatisfactionFactors(?int $uid = NULL, int $count = 5): array
    {
        $events = $this->getAllEvents($uid);
        $frequency = [];

        foreach ($events as $event) {
            foreach ($event['satisfaction_factors'] as $factor) {
                $frequency[$factor] = ($frequency[$factor] ?? 0) + 1;
            }
        }

        arsort($frequency);
        return array_slice($frequency, 0, $count, TRUE);
    }

    /**
     * Obtiene las habilidades mas mencionadas.
     */
    public function getTopSkills(?int $uid = NULL, int $count = 5): array
    {
        $events = $this->getAllEvents($uid);
        $frequency = [];

        foreach ($events as $event) {
            foreach ($event['skills'] as $skill) {
                $frequency[$skill] = ($frequency[$skill] ?? 0) + 1;
            }
        }

        arsort($frequency);
        return array_slice(array_keys($frequency), 0, $count);
    }

    /**
     * Obtiene los valores mas descubiertos.
     */
    public function getTopValues(?int $uid = NULL, int $count = 5): array
    {
        $events = $this->getAllEvents($uid);
        $frequency = [];

        foreach ($events as $event) {
            foreach ($event['values'] as $value) {
                $frequency[$value] = ($frequency[$value] ?? 0) + 1;
            }
        }

        arsort($frequency);
        return array_slice(array_keys($frequency), 0, $count);
    }

    /**
     * Filtra eventos por tipo.
     */
    public function getEventsByType(?int $uid = NULL, string $type = 'high_moment'): array
    {
        $events = $this->getAllEvents($uid);
        return array_filter($events, fn($event) => $event['type'] === $type);
    }

}
