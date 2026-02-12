<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para Self-Discovery.
 *
 * PROPÓSITO:
 * Endpoints REST para guardar/actualizar evaluaciones via AJAX.
 */
class SelfDiscoveryApiController extends ControllerBase
{

    /**
     * Guarda una evaluación de Rueda de la Vida.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function saveLifeWheel(Request $request): JsonResponse
    {
        try {
            $content = json_decode($request->getContent(), TRUE);

            if (!$content || !isset($content['scores'])) {
                return new JsonResponse([
                    'success' => FALSE,
                    'message' => $this->t('Datos inválidos.'),
                ], 400);
            }

            $scores = $content['scores'];
            $uid = $this->currentUser()->id();

            // Crear nueva evaluación.
            $assessment = $this->entityTypeManager()
                ->getStorage('life_wheel_assessment')
                ->create([
                    'user_id' => $uid,
                    'score_career' => $scores['career'] ?? 5,
                    'score_finance' => $scores['finance'] ?? 5,
                    'score_health' => $scores['health'] ?? 5,
                    'score_family' => $scores['family'] ?? 5,
                    'score_social' => $scores['social'] ?? 5,
                    'score_growth' => $scores['growth'] ?? 5,
                    'score_leisure' => $scores['leisure'] ?? 5,
                    'score_environment' => $scores['environment'] ?? 5,
                    'notes' => $content['notes'] ?? '',
                ]);

            $assessment->save();

            return new JsonResponse([
                'success' => TRUE,
                'message' => $this->t('Evaluación guardada correctamente.'),
                'id' => $assessment->id(),
                'average' => $assessment->getAverageScore(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => $this->t('Error al guardar la evaluación.'),
            ], 500);
        }
    }

    /**
     * Guarda un evento del Timeline.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function saveTimelineEvent(Request $request): JsonResponse
    {
        try {
            $content = json_decode($request->getContent(), TRUE);

            if (!$content || empty($content['title'])) {
                return new JsonResponse([
                    'success' => FALSE,
                    'message' => $this->t('Datos inválidos: se requiere título.'),
                ], 400);
            }

            $uid = $this->currentUser()->id();
            $storage = $this->entityTypeManager()->getStorage('life_timeline');

            // Map event_type from JS values to entity values.
            $typeMap = [
                'high' => 'high_moment',
                'low' => 'low_moment',
                'turning' => 'turning_point',
            ];

            // If updating an existing entity.
            $entity = NULL;
            if (!empty($content['id']) && is_numeric($content['id'])) {
                $entity = $storage->load($content['id']);
                if ($entity && (int) $entity->getOwnerId() !== (int) $uid) {
                    return new JsonResponse([
                        'success' => FALSE,
                        'message' => $this->t('No tienes permiso para editar este evento.'),
                    ], 403);
                }
            }

            if (!$entity) {
                $entity = $storage->create(['user_id' => $uid]);
            }

            $eventType = $content['type'] ?? 'high';
            $entity->set('title', $content['title']);
            $entity->set('event_date', $content['date'] ?? '');
            $entity->set('event_type', $typeMap[$eventType] ?? $eventType);
            $entity->set('category', $content['category'] ?? 'personal');
            $entity->set('description', $content['description'] ?? '');
            $entity->set('satisfaction_factors', json_encode($content['factors'] ?? []));
            $entity->set('skills_used', json_encode($content['skills'] ?? []));
            $entity->set('learnings', $content['learnings'] ?? '');
            $entity->set('values_discovered', json_encode($content['values'] ?? []));
            $entity->set('patterns', $content['patterns'] ?? '');

            $entity->save();

            return new JsonResponse([
                'success' => TRUE,
                'message' => $this->t('Evento guardado correctamente.'),
                'id' => $entity->id(),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => $this->t('Error al guardar el evento.'),
            ], 500);
        }
    }

    /**
     * Elimina un evento del Timeline.
     *
     * @param int $event_id
     *   ID del evento.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON.
     */
    public function deleteTimelineEvent(int $event_id): JsonResponse
    {
        try {
            $uid = $this->currentUser()->id();
            $storage = $this->entityTypeManager()->getStorage('life_timeline');
            $entity = $storage->load($event_id);

            if (!$entity) {
                return new JsonResponse([
                    'success' => FALSE,
                    'message' => $this->t('Evento no encontrado.'),
                ], 404);
            }

            if ((int) $entity->getOwnerId() !== (int) $uid) {
                return new JsonResponse([
                    'success' => FALSE,
                    'message' => $this->t('No tienes permiso para eliminar este evento.'),
                ], 403);
            }

            $entity->delete();

            return new JsonResponse([
                'success' => TRUE,
                'message' => $this->t('Evento eliminado correctamente.'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => $this->t('Error al eliminar el evento.'),
            ], 500);
        }
    }

    /**
     * Devuelve respuesta contextual del Copilot basada en el perfil Self-Discovery.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP con la consulta del usuario.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con recomendaciones proactivas.
     */
    public function getCopilotContext(Request $request): JsonResponse
    {
        try {
            $content = json_decode($request->getContent(), TRUE);
            $query = $content['query'] ?? '';

            // Obtener contexto del usuario.
            $contextService = \Drupal::service('jaraba_self_discovery.context');
            $context = $contextService->getFullContext();

            // Detectar tipo de consulta y generar respuesta proactiva.
            $response = $this->generateProactiveResponse($query, $context);

            return new JsonResponse([
                'success' => TRUE,
                'response' => $response,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => $this->t('Error al procesar la consulta.'),
            ], 500);
        }
    }

    /**
     * Genera respuesta proactiva basada en contexto del usuario.
     */
    protected function generateProactiveResponse(string $query, array $context): array
    {
        $queryLower = mb_strtolower($query);
        $response = [
            'message' => '',
            'tips' => [],
            'actions' => [],
            'followUp' => '',
        ];

        // Detectar si pregunta sobre RIASEC/carreras.
        if (
            strpos($queryLower, 'riasec') !== FALSE ||
            strpos($queryLower, 'carrera') !== FALSE ||
            strpos($queryLower, 'profesión') !== FALSE ||
            strpos($queryLower, 'trabajo') !== FALSE
        ) {

            $riasec = $context['riasec'] ?? [];

            if (!empty($riasec['code'])) {
                $response['message'] = $this->t('<strong>Tu perfil RIASEC: @code</strong><br><br>@description', [
                    '@code' => $riasec['code'],
                    '@description' => $riasec['description'] ?? 'Perfil único',
                ]);

                // Añadir carreras sugeridas.
                $careers = $riasec['suggested_careers'] ?? [];
                if (!empty($careers)) {
                    $response['tips'][] = $this->t('<strong>Carreras recomendadas:</strong>');
                    foreach (array_slice($careers, 0, 3) as $career) {
                        $response['tips'][] = '• ' . $career;
                    }
                }

                // Fortalezas si existen.
                $strengths = $context['strengths'] ?? [];
                if (!empty($strengths['top5'])) {
                    $strengthNames = array_map(fn($s) => $s['name'], array_slice($strengths['top5'], 0, 3));
                    $response['tips'][] = $this->t('<strong>Tus fortalezas:</strong> @strengths', [
                        '@strengths' => implode(', ', $strengthNames),
                    ]);
                }

                $response['actions'] = [
                    ['label' => $this->t('Ver ofertas compatibles'), 'url' => '/jobs', 'icon' => 'briefcase'],
                    ['label' => $this->t('Explorar cursos'), 'url' => '/courses', 'icon' => 'book'],
                ];
                $response['followUp'] = $this->t('¿Quieres que te ayude a preparar tu CV para estas carreras?');
            } else {
                $response['message'] = $this->t('Aún no has completado el test RIASEC. Te recomiendo hacerlo para descubrir tus intereses profesionales.');
                $response['actions'] = [
                    ['label' => $this->t('Hacer test RIASEC'), 'url' => '/my-profile/self-discovery/interests', 'icon' => 'target'],
                ];
            }
        }
        // Detectar si pregunta sobre fortalezas.
        elseif (
            strpos($queryLower, 'fortaleza') !== FALSE ||
            strpos($queryLower, 'talento') !== FALSE
        ) {

            $strengths = $context['strengths'] ?? [];

            if (!empty($strengths['top5'])) {
                $response['message'] = $this->t('<strong>Tus 5 fortalezas principales:</strong>');
                $response['tips'] = array_map(
                    fn($s, $i) => ($i + 1) . '. ' . $s['name'] . ' - ' . $s['desc'],
                    array_values($strengths['top5']),
                    array_keys($strengths['top5'])
                );
                $response['followUp'] = $this->t('Usa estas fortalezas para destacar en entrevistas y tu CV.');
            } else {
                $response['message'] = $this->t('Descubre tus fortalezas principales con nuestro test.');
                $response['actions'] = [
                    ['label' => $this->t('Hacer test de Fortalezas'), 'url' => '/my-profile/self-discovery/strengths', 'icon' => 'star'],
                ];
            }
        }
        // Detectar si pregunta sobre áreas de mejora/rueda de la vida.
        elseif (
            strpos($queryLower, 'mejorar') !== FALSE ||
            strpos($queryLower, 'rueda') !== FALSE ||
            strpos($queryLower, 'equilibrio') !== FALSE
        ) {

            $lifeWheel = $context['life_wheel'] ?? [];

            if (!empty($lifeWheel['scores'])) {
                $lowAreas = $lifeWheel['low_areas'] ?? [];
                if (!empty($lowAreas)) {
                    $response['message'] = $this->t('<strong>Areas de mejora segun tu Rueda de la Vida:</strong>');
                    foreach ($lowAreas as $area) {
                        $response['tips'][] = $this->t('@area: @score/10 - prioridad de mejora', [
                            '@area' => ucfirst($area['name']),
                            '@score' => $area['score'],
                        ]);
                    }
                    $response['followUp'] = $this->t('¿Quieres que te sugiera acciones concretas para mejorar estas áreas?');
                } else {
                    $response['message'] = $this->t('¡Tu balance vital está equilibrado! Todas tus áreas están en buen nivel.');
                }
            } else {
                $response['message'] = $this->t('Evalúa tu equilibrio vital con la Rueda de la Vida.');
                $response['actions'] = [
                    ['label' => $this->t('Hacer Rueda de la Vida'), 'url' => '/my-profile/self-discovery/life-wheel', 'icon' => 'pie-chart'],
                ];
            }
        }
        // Respuesta genérica con contexto disponible.
        else {
            $hasData = !empty($context['riasec']) || !empty($context['strengths']) || !empty($context['life_wheel']);

            if ($hasData) {
                $response['message'] = $this->t('Basándome en tu perfil, puedo ayudarte con:');
                $response['tips'] = [
                    $this->t('Carreras compatibles con tu perfil RIASEC'),
                    $this->t('Como usar tus fortalezas en entrevistas'),
                    $this->t('Areas de mejora segun tu Rueda de la Vida'),
                ];
                $response['followUp'] = $this->t('¿Sobre qué tema te gustaría profundizar?');
            } else {
                $response['message'] = $this->t('Para darte recomendaciones personalizadas, te sugiero completar las herramientas de autodescubrimiento.');
                $response['actions'] = [
                    ['label' => $this->t('Ir a Self-Discovery'), 'url' => '/my-profile/self-discovery', 'icon' => 'search'],
                ];
            }
        }

        return $response;
    }

}

