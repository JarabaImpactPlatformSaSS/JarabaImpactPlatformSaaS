<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Service;

use Drupal\Core\Session\AccountInterface;

/**
 * Servicio de contexto de Self-Discovery para integración con Copilot IA.
 *
 * Agrega toda la información de autodescubrimiento del usuario para
 * proporcionar contexto completo al Copilot.
 *
 * Refactorizado para delegar a servicios especializados manteniendo
 * compatibilidad retroactiva con user.data.
 */
class SelfDiscoveryContextService
{

    /**
     * El usuario actual.
     */
    protected AccountInterface $currentUser;

    /**
     * User data service.
     */
    protected $userData;

    /**
     * Entity type manager.
     */
    protected $entityTypeManager;

    /**
     * LifeWheel service.
     */
    protected ?LifeWheelService $lifeWheelService;

    /**
     * Timeline analysis service.
     */
    protected ?TimelineAnalysisService $timelineService;

    /**
     * RIASEC service.
     */
    protected ?RiasecService $riasecService;

    /**
     * Strength analysis service.
     */
    protected ?StrengthAnalysisService $strengthService;

    /**
     * Constructor.
     */
    public function __construct(
        AccountInterface $current_user,
        $user_data,
        $entity_type_manager,
        ?LifeWheelService $life_wheel_service = NULL,
        ?TimelineAnalysisService $timeline_service = NULL,
        ?RiasecService $riasec_service = NULL,
        ?StrengthAnalysisService $strength_service = NULL
    ) {
        $this->currentUser = $current_user;
        $this->userData = $user_data;
        $this->entityTypeManager = $entity_type_manager;
        $this->lifeWheelService = $life_wheel_service;
        $this->timelineService = $timeline_service;
        $this->riasecService = $riasec_service;
        $this->strengthService = $strength_service;
    }

    /**
     * Obtiene el contexto completo de Self-Discovery para un usuario.
     *
     * @param int|null $uid
     *   ID del usuario. Si es NULL, usa el usuario actual.
     *
     * @return array
     *   Contexto estructurado para el Copilot.
     */
    public function getFullContext(?int $uid = NULL): array
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        return [
            'life_wheel' => $this->getLifeWheelContext($uid),
            'timeline' => $this->getTimelineContext($uid),
            'riasec' => $this->getRiasecContext($uid),
            'strengths' => $this->getStrengthsContext($uid),
            'summary' => $this->generateSummary($uid),
        ];
    }

    /**
     * Obtiene el contexto de Rueda de la Vida.
     */
    protected function getLifeWheelContext(int $uid): array
    {
        if ($this->lifeWheelService) {
            $assessment = $this->lifeWheelService->getLatestAssessment($uid);
            if (!$assessment) {
                return ['completed' => FALSE];
            }

            $scores = $assessment->getAllScores();
            $lowest = $this->lifeWheelService->getLowestAreas($uid, 2);

            return [
                'completed' => TRUE,
                'scores' => $scores,
                'average' => round(array_sum($scores) / count($scores), 1),
                'lowest_areas' => array_keys($lowest),
                'lowest_values' => $lowest,
            ];
        }

        // Fallback directo a entity query (retrocompatibilidad).
        try {
            $storage = $this->entityTypeManager->getStorage('life_wheel_assessment');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $uid)
                ->sort('created', 'DESC')
                ->range(0, 1)
                ->execute();

            if (empty($ids)) {
                return ['completed' => FALSE];
            }

            $assessment = $storage->load(reset($ids));
            $scores = $assessment->getAllScores();

            asort($scores);
            $lowest = array_slice($scores, 0, 2, TRUE);

            return [
                'completed' => TRUE,
                'scores' => $scores,
                'average' => round(array_sum($scores) / count($scores), 1),
                'lowest_areas' => array_keys($lowest),
                'lowest_values' => $lowest,
            ];
        } catch (\Exception $e) {
            return ['completed' => FALSE, 'error' => $e->getMessage()];
        }
    }

    /**
     * Obtiene el contexto del Timeline.
     */
    protected function getTimelineContext(int $uid): array
    {
        if ($this->timelineService) {
            $events = $this->timelineService->getAllEvents($uid);
            if (empty($events)) {
                return ['completed' => FALSE, 'events_count' => 0];
            }

            $highMoments = count(array_filter($events, fn($e) => $e['type'] === 'high_moment'));
            $lowMoments = count(array_filter($events, fn($e) => $e['type'] === 'low_moment'));

            return [
                'completed' => TRUE,
                'events_count' => count($events),
                'events' => $events,
                'high_moments' => $highMoments,
                'low_moments' => $lowMoments,
                'top_skills' => $this->timelineService->getTopSkills($uid),
                'top_values' => $this->timelineService->getTopValues($uid),
            ];
        }

        // Fallback directo (retrocompatibilidad).
        try {
            $storage = $this->entityTypeManager->getStorage('life_timeline');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $uid)
                ->sort('event_date', 'ASC')
                ->execute();

            if (empty($ids)) {
                return ['completed' => FALSE, 'events_count' => 0];
            }

            $entities = $storage->loadMultiple($ids);
            $events = [];
            $highMoments = 0;
            $lowMoments = 0;
            $allSkills = [];
            $allValues = [];

            foreach ($entities as $entity) {
                $type = $entity->get('event_type')->value;
                if ($type === 'high_moment') {
                    $highMoments++;
                }
                elseif ($type === 'low_moment') {
                    $lowMoments++;
                }

                $skills = $entity->getSkills();
                $values = $entity->getValuesDiscovered();
                $allSkills = array_merge($allSkills, $skills);
                $allValues = array_merge($allValues, $values);

                $events[] = [
                    'title' => $entity->get('title')->value,
                    'date' => $entity->get('event_date')->value,
                    'type' => $type,
                    'category' => $entity->get('category')->value,
                    'learnings' => $entity->get('learnings')->value ?? '',
                ];
            }

            $skillFrequency = array_count_values($allSkills);
            arsort($skillFrequency);
            $valueFrequency = array_count_values($allValues);
            arsort($valueFrequency);

            return [
                'completed' => TRUE,
                'events_count' => count($events),
                'events' => $events,
                'high_moments' => $highMoments,
                'low_moments' => $lowMoments,
                'top_skills' => array_slice(array_keys($skillFrequency), 0, 5),
                'top_values' => array_slice(array_keys($valueFrequency), 0, 5),
            ];
        } catch (\Exception $e) {
            return ['completed' => FALSE, 'events_count' => 0];
        }
    }

    /**
     * Obtiene el contexto RIASEC.
     */
    protected function getRiasecContext(int $uid): array
    {
        if ($this->riasecService) {
            $code = $this->riasecService->getCode($uid);
            if (!$code) {
                return ['completed' => FALSE];
            }

            return [
                'completed' => TRUE,
                'code' => $code,
                'scores' => $this->riasecService->getScores($uid),
                'primary_type' => $code[0] ?? '',
                'primary_description' => $this->getTypeDescription($code[0] ?? ''),
                'profile_description' => $this->riasecService->getProfileDescription($uid),
            ];
        }

        // Fallback a user.data (retrocompatibilidad).
        $code = $this->userData->get('jaraba_self_discovery', $uid, 'riasec_code');
        $scores = $this->userData->get('jaraba_self_discovery', $uid, 'riasec_scores');
        $completed = $this->userData->get('jaraba_self_discovery', $uid, 'riasec_completed');

        if (!$code) {
            return ['completed' => FALSE];
        }

        $typeDescriptions = [
            'R' => 'Realista - practico, tecnico',
            'I' => 'Investigador - analitico, cientifico',
            'A' => 'Artistico - creativo, expresivo',
            'S' => 'Social - colaborador, empatico',
            'E' => 'Emprendedor - lider, persuasivo',
            'C' => 'Convencional - organizado, estructurado',
        ];

        $letters = str_split($code);
        $descriptions = array_map(fn($l) => $typeDescriptions[$l] ?? $l, $letters);

        return [
            'completed' => TRUE,
            'code' => $code,
            'scores' => $scores,
            'primary_type' => $letters[0] ?? '',
            'primary_description' => $typeDescriptions[$letters[0]] ?? '',
            'profile_description' => implode(' + ', $descriptions),
            'completed_at' => $completed,
        ];
    }

    /**
     * Obtiene el contexto de Fortalezas.
     */
    protected function getStrengthsContext(int $uid): array
    {
        if ($this->strengthService) {
            $top5 = $this->strengthService->getTop5($uid);
            if (empty($top5)) {
                return ['completed' => FALSE];
            }

            return [
                'completed' => TRUE,
                'top5' => $top5,
                'top_strength' => !empty($top5) ? reset($top5) : NULL,
            ];
        }

        // Fallback a user.data (retrocompatibilidad).
        $top5 = $this->userData->get('jaraba_self_discovery', $uid, 'strengths_top5');
        $completed = $this->userData->get('jaraba_self_discovery', $uid, 'strengths_completed');

        if (!$top5) {
            return ['completed' => FALSE];
        }

        return [
            'completed' => TRUE,
            'top5' => $top5,
            'top_strength' => !empty($top5) ? reset($top5) : NULL,
            'completed_at' => $completed,
        ];
    }

    /**
     * Genera un resumen textual para el Copilot.
     */
    protected function generateSummary(int $uid): string
    {
        $context = [
            'life_wheel' => $this->getLifeWheelContext($uid),
            'riasec' => $this->getRiasecContext($uid),
            'strengths' => $this->getStrengthsContext($uid),
        ];

        $parts = [];

        if ($context['life_wheel']['completed']) {
            $lowest = implode(' y ', $context['life_wheel']['lowest_areas'] ?? []);
            $parts[] = "Sus areas mas bajas en Rueda de Vida son: {$lowest}.";
        }

        if ($context['riasec']['completed']) {
            $parts[] = "Perfil RIASEC: {$context['riasec']['profile_description']}.";
        }

        if ($context['strengths']['completed']) {
            $top = $context['strengths']['top_strength']['name'] ?? '';
            $parts[] = "Su fortaleza principal es: {$top}.";
        }

        return implode(' ', $parts) ?: 'El usuario aun no ha completado herramientas de autodescubrimiento.';
    }

    /**
     * Genera el prompt de contexto para el Copilot.
     */
    public function getCopilotContextPrompt(?int $uid = NULL): string
    {
        $context = $this->getFullContext($uid);

        return <<<PROMPT
CONTEXTO DE AUTODESCUBRIMIENTO DEL USUARIO:

{$context['summary']}

Usa esta información para dar orientación personalizada sobre su carrera profesional.
PROMPT;
    }

    /**
     * Obtiene la descripcion de un tipo RIASEC.
     */
    protected function getTypeDescription(string $letter): string
    {
        $descriptions = [
            'R' => 'Realista - practico, tecnico',
            'I' => 'Investigador - analitico, cientifico',
            'A' => 'Artistico - creativo, expresivo',
            'S' => 'Social - colaborador, empatico',
            'E' => 'Emprendedor - lider, persuasivo',
            'C' => 'Convencional - organizado, estructurado',
        ];

        return $descriptions[$letter] ?? '';
    }

}
