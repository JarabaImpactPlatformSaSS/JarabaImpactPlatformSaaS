<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Service;

use Drupal\jaraba_interactive\Plugin\InteractiveTypeManager;
use Psr\Log\LoggerInterface;

/**
 * Servicio de scoring para contenido interactivo.
 *
 * Calcula puntuaciones utilizando los plugins de tipo interactivo.
 */
class Scorer
{

    /**
     * Constructor.
     *
     * @param \Drupal\jaraba_interactive\Plugin\InteractiveTypeManager $typeManager
     *   El plugin manager de tipos interactivos.
     * @param \Psr\Log\LoggerInterface $logger
     *   El logger.
     */
    public function __construct(
        protected InteractiveTypeManager $typeManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Calcula la puntuación para un contenido.
     *
     * @param string $contentType
     *   El tipo de contenido (plugin ID).
     * @param array $contentData
     *   Los datos del contenido.
     * @param array $responses
     *   Las respuestas del usuario.
     *
     * @return array
     *   Array con score, max_score, passed, details.
     */
    public function calculate(string $contentType, array $contentData, array $responses): array
    {
        try {
            /** @var \Drupal\jaraba_interactive\Plugin\InteractiveTypeInterface $plugin */
            $plugin = $this->typeManager->createInstance($contentType);
            return $plugin->calculateScore($contentData, $responses);
        } catch (\Exception $e) {
            $this->logger->error('Error calculando puntuación: @message', [
                '@message' => $e->getMessage(),
            ]);

            return [
                'score' => 0,
                'max_score' => 100,
                'passed' => FALSE,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Determina si el usuario aprobó.
     *
     * @param float $score
     *   La puntuación obtenida.
     * @param float $passingScore
     *   El umbral de aprobación.
     *
     * @return bool
     *   TRUE si aprobó.
     */
    public function hasPassed(float $score, float $passingScore = 70.0): bool
    {
        return $score >= $passingScore;
    }

}
