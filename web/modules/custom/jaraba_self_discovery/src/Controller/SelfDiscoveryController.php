<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controlador principal para las páginas de Self-Discovery.
 *
 * PROPÓSITO:
 * Renderiza las páginas frontend para el usuario:
 * - Dashboard general
 * - Rueda de la Vida
 * - Timeline
 * - Intereses RIASEC
 * - Fortalezas
 */
class SelfDiscoveryController extends ControllerBase
{

    /**
     * El usuario actual.
     *
     * @var \Drupal\Core\Session\AccountProxyInterface
     */
    protected AccountProxyInterface $account;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = parent::create($container);
        $instance->account = $container->get('current_user');
        return $instance;
    }

    /**
     * Página principal del dashboard de autodescubrimiento.
     *
     * @return array
     *   Render array.
     */
    public function dashboard(): array
    {
        // Obtener evaluaciones del usuario.
        $assessments = $this->getAssessmentStatus();

        return [
            '#theme' => 'self_discovery_dashboard',
            '#assessments' => $assessments,
            '#labels' => [
                'title' => $this->t('Autodescubrimiento'),
                'subtitle' => $this->t('Descubre tu potencial profesional'),
                'life_wheel' => $this->t('Rueda de la Vida'),
                'life_wheel_desc' => $this->t('Evalúa las 8 áreas clave de tu vida'),
                'timeline' => $this->t('Mi Línea de Vida'),
                'timeline_desc' => $this->t('Identifica momentos que te definen'),
                'interests' => $this->t('Mis Intereses'),
                'interests_desc' => $this->t('Descubre tu perfil RIASEC'),
                'strengths' => $this->t('Mis Fortalezas'),
                'strengths_desc' => $this->t('Identifica tus 5 talentos principales'),
                'start' => $this->t('Comenzar'),
                'continue' => $this->t('Continuar'),
                'completed' => $this->t('Completado'),
                'view' => $this->t('Ver resultados'),
            ],
            '#attached' => [
                'library' => ['jaraba_self_discovery/global'],
            ],
        ];
    }

    /**
     * Página de Rueda de la Vida.
     *
     * @return array
     *   Render array.
     */
    public function lifeWheel(): array
    {
        // Obtener última evaluación del usuario.
        $last_assessment = $this->getLastAssessment('life_wheel_assessment');
        $scores = $last_assessment ? $last_assessment->getAllScores() : $this->getDefaultScores();

        return [
            '#theme' => 'self_discovery_life_wheel',
            '#scores' => $scores,
            '#last_assessment' => $last_assessment,
            '#labels' => [
                'title' => $this->t('Rueda de la Vida'),
                'subtitle' => $this->t('Evalúa tu satisfacción actual en las 8 áreas de tu vida'),
                'areas' => [
                    'career' => $this->t('Trabajo/Carrera'),
                    'finance' => $this->t('Finanzas'),
                    'health' => $this->t('Salud'),
                    'family' => $this->t('Familia'),
                    'social' => $this->t('Social'),
                    'growth' => $this->t('Desarrollo Personal'),
                    'leisure' => $this->t('Ocio'),
                    'environment' => $this->t('Entorno'),
                ],
                'save' => $this->t('Guardar Evaluación'),
                'reset' => $this->t('Reiniciar'),
                'history' => $this->t('Ver historial'),
                'help' => $this->t('Mueve los sliders para indicar tu nivel de satisfacción (1-10) en cada área.'),
            ],
            '#attached' => [
                'library' => [
                    'jaraba_self_discovery/life-wheel',
                ],
                'drupalSettings' => [
                    'jarabaSelfDiscovery' => [
                        'scores' => $scores,
                        'saveUrl' => '/api/v1/self-discovery/life-wheel',
                    ],
                ],
            ],
        ];
    }

    /**
     * Página de Timeline de Vida.
     *
     * @return array
     *   Render array.
     */
    public function timeline(): array
    {
        return [
            '#theme' => 'self_discovery_timeline',
            '#events' => [],
            '#labels' => [
                'title' => $this->t('Mi Línea de Vida'),
                'subtitle' => $this->t('Identifica los momentos que te han definido'),
                'add_event' => $this->t('Añadir Evento'),
                'high_moment' => $this->t('Momento Álgido'),
                'low_moment' => $this->t('Momento Bajo'),
                'personal' => $this->t('Personal'),
                'professional' => $this->t('Profesional'),
            ],
            '#attached' => [
                'library' => ['jaraba_self_discovery/timeline'],
            ],
        ];
    }

    /**
     * Página de Intereses RIASEC.
     *
     * @return array
     *   Render array.
     */
    public function interests(): array
    {
        $form = $this->formBuilder()->getForm('Drupal\jaraba_self_discovery\Form\InterestsAssessmentForm');

        return [
            '#theme' => 'self_discovery_interests',
            '#form' => $form,
            '#labels' => [
                'title' => $this->t('Mis Intereses Vocacionales'),
                'subtitle' => $this->t('Descubre tu perfil RIASEC'),
            ],
            '#attached' => [
                'library' => ['jaraba_self_discovery/riasec'],
            ],
        ];
    }

    /**
     * Página de Fortalezas.
     *
     * @return array
     *   Render array.
     */
    public function strengths(): array
    {
        $form = $this->formBuilder()->getForm('Drupal\jaraba_self_discovery\Form\StrengthsAssessmentForm');

        return [
            '#theme' => 'self_discovery_strengths',
            '#form' => $form,
            '#labels' => [
                'title' => $this->t('Mis Fortalezas'),
                'subtitle' => $this->t('Descubre tus 5 talentos principales'),
            ],
            '#attached' => [
                'library' => ['jaraba_self_discovery/global'],
            ],
        ];
    }

    /**
     * Obtiene el estado de las evaluaciones del usuario.
     *
     * @return array
     *   Array con estado de cada herramienta.
     */
    protected function getAssessmentStatus(): array
    {
        $uid = $this->currentUser()->id();

        return [
            'life_wheel' => [
                'status' => $this->hasAssessment('life_wheel_assessment', $uid) ? 'completed' : 'pending',
                'url' => '/my-profile/self-discovery/life-wheel',
            ],
            'timeline' => [
                'status' => 'pending',
                'url' => '/my-profile/self-discovery/timeline',
            ],
            'interests' => [
                'status' => 'locked',
                'url' => '/my-profile/self-discovery/interests',
            ],
            'strengths' => [
                'status' => 'locked',
                'url' => '/my-profile/self-discovery/strengths',
            ],
        ];
    }

    /**
     * Verifica si el usuario tiene una evaluación de un tipo.
     *
     * @param string $entity_type
     *   Tipo de entidad.
     * @param int $uid
     *   ID del usuario.
     *
     * @return bool
     *   TRUE si existe al menos una evaluación.
     */
    protected function hasAssessment(string $entity_type, int|string $uid): bool
    {
        try {
            $count = $this->entityTypeManager()
                ->getStorage($entity_type)
                ->getQuery()
                ->accessCheck(TRUE)
                ->condition('user_id', $uid)
                ->count()
                ->execute();

            return $count > 0;
        } catch (\Exception $e) {
            return FALSE;
        }
    }

    /**
     * Obtiene la última evaluación del usuario.
     *
     * @param string $entity_type
     *   Tipo de entidad.
     *
     * @return \Drupal\jaraba_self_discovery\Entity\LifeWheelAssessment|null
     *   La última evaluación o NULL.
     */
    protected function getLastAssessment(string $entity_type)
    {
        try {
            $ids = $this->entityTypeManager()
                ->getStorage($entity_type)
                ->getQuery()
                ->accessCheck(TRUE)
                ->condition('user_id', $this->currentUser()->id())
                ->sort('created', 'DESC')
                ->range(0, 1)
                ->execute();

            if (!empty($ids)) {
                return $this->entityTypeManager()
                    ->getStorage($entity_type)
                    ->load(reset($ids));
            }
        } catch (\Exception $e) {
            // Log error.
        }

        return NULL;
    }

    /**
     * Retorna scores por defecto para nueva evaluación.
     *
     * @return array
     *   Scores con valor 5 (neutral).
     */
    protected function getDefaultScores(): array
    {
        return [
            'career' => 5,
            'finance' => 5,
            'health' => 5,
            'family' => 5,
            'social' => 5,
            'growth' => 5,
            'leisure' => 5,
            'environment' => 5,
        ];
    }

}
