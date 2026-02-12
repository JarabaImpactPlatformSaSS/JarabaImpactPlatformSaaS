<?php

declare(strict_types=1);

namespace Drupal\jaraba_self_discovery\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio dedicado para datos de Fortalezas con fallback a user.data.
 */
class StrengthAnalysisService
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
     * User data service (retrocompatibilidad).
     */
    protected $userData;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Catalogo de 24 fortalezas.
     */
    protected const STRENGTHS = [
        'creativity' => ['name' => 'Creatividad', 'desc' => 'Generar ideas nuevas y originales', 'virtue' => 'Sabiduria'],
        'curiosity' => ['name' => 'Curiosidad', 'desc' => 'Interes por explorar y aprender', 'virtue' => 'Sabiduria'],
        'judgment' => ['name' => 'Criterio', 'desc' => 'Pensar de forma critica y objetiva', 'virtue' => 'Sabiduria'],
        'love_learning' => ['name' => 'Amor por aprender', 'desc' => 'Dominar nuevas habilidades', 'virtue' => 'Sabiduria'],
        'perspective' => ['name' => 'Perspectiva', 'desc' => 'Dar consejos sabios a otros', 'virtue' => 'Sabiduria'],
        'bravery' => ['name' => 'Valentia', 'desc' => 'Actuar a pesar del miedo', 'virtue' => 'Coraje'],
        'perseverance' => ['name' => 'Perseverancia', 'desc' => 'Terminar lo que se empieza', 'virtue' => 'Coraje'],
        'honesty' => ['name' => 'Honestidad', 'desc' => 'Ser genuino y autentico', 'virtue' => 'Coraje'],
        'zest' => ['name' => 'Vitalidad', 'desc' => 'Vivir con energia y entusiasmo', 'virtue' => 'Coraje'],
        'love' => ['name' => 'Amor', 'desc' => 'Valorar relaciones cercanas', 'virtue' => 'Humanidad'],
        'kindness' => ['name' => 'Amabilidad', 'desc' => 'Ayudar a los demas', 'virtue' => 'Humanidad'],
        'social_intel' => ['name' => 'Inteligencia social', 'desc' => 'Entender motivaciones de otros', 'virtue' => 'Humanidad'],
        'teamwork' => ['name' => 'Trabajo en equipo', 'desc' => 'Colaborar efectivamente', 'virtue' => 'Justicia'],
        'fairness' => ['name' => 'Equidad', 'desc' => 'Tratar a todos por igual', 'virtue' => 'Justicia'],
        'leadership' => ['name' => 'Liderazgo', 'desc' => 'Organizar e inspirar grupos', 'virtue' => 'Justicia'],
        'forgiveness' => ['name' => 'Perdon', 'desc' => 'Dejar ir el resentimiento', 'virtue' => 'Templanza'],
        'humility' => ['name' => 'Humildad', 'desc' => 'No buscar el centro de atencion', 'virtue' => 'Templanza'],
        'prudence' => ['name' => 'Prudencia', 'desc' => 'Tomar decisiones cuidadosas', 'virtue' => 'Templanza'],
        'self_control' => ['name' => 'Autocontrol', 'desc' => 'Regular impulsos y emociones', 'virtue' => 'Templanza'],
        'appreciation' => ['name' => 'Apreciacion', 'desc' => 'Notar la belleza y excelencia', 'virtue' => 'Transcendencia'],
        'gratitude' => ['name' => 'Gratitud', 'desc' => 'Ser consciente de lo bueno', 'virtue' => 'Transcendencia'],
        'hope' => ['name' => 'Esperanza', 'desc' => 'Esperar lo mejor del futuro', 'virtue' => 'Transcendencia'],
        'humor' => ['name' => 'Humor', 'desc' => 'Disfrutar la risa y alegria', 'virtue' => 'Transcendencia'],
        'spirituality' => ['name' => 'Espiritualidad', 'desc' => 'Conexion con proposito mayor', 'virtue' => 'Transcendencia'],
    ];

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountInterface $current_user,
        $user_data,
        LoggerInterface $logger
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
        $this->userData = $user_data;
        $this->logger = $logger;
    }

    /**
     * Obtiene la ultima evaluacion StrengthAssessment del usuario.
     *
     * @return \Drupal\jaraba_self_discovery\Entity\StrengthAssessment|null
     *   La ultima evaluacion o NULL.
     */
    public function getLatestAssessment(?int $uid = NULL)
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        try {
            $storage = $this->entityTypeManager->getStorage('strength_assessment');
            $ids = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('user_id', $uid)
                ->sort('created', 'DESC')
                ->range(0, 1)
                ->execute();

            if (!empty($ids)) {
                return $storage->load(reset($ids));
            }
        }
        catch (\Exception $e) {
            $this->logger->error('StrengthAnalysisService::getLatestAssessment error: @error', [
                '@error' => $e->getMessage(),
            ]);
        }

        return NULL;
    }

    /**
     * Obtiene el top 5 de fortalezas.
     */
    public function getTop5(?int $uid = NULL): array
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        // Intentar desde entity primero.
        $assessment = $this->getLatestAssessment($uid);
        if ($assessment) {
            return $assessment->getTopStrengths();
        }

        // Fallback a user.data.
        return $this->userData->get('jaraba_self_discovery', $uid, 'strengths_top5') ?: [];
    }

    /**
     * Obtiene todas las puntuaciones.
     */
    public function getAllScores(?int $uid = NULL): array
    {
        $uid = $uid ?: (int) $this->currentUser->id();

        $assessment = $this->getLatestAssessment($uid);
        if ($assessment) {
            return $assessment->getAllScores();
        }

        // Fallback a user.data (solo tiene top5).
        return $this->userData->get('jaraba_self_discovery', $uid, 'strengths_top5') ?: [];
    }

    /**
     * Obtiene la descripcion de una fortaleza por su key.
     */
    public function getStrengthDescription(string $strengthKey): array
    {
        return self::STRENGTHS[$strengthKey] ?? ['name' => $strengthKey, 'desc' => '', 'virtue' => ''];
    }

}
