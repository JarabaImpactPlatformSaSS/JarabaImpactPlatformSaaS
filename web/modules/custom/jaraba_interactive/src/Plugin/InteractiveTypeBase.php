<?php

declare(strict_types=1);

namespace Drupal\jaraba_interactive\Plugin;

use Drupal\Component\Plugin\PluginBase;

/**
 * Clase base para plugins de tipo interactivo.
 *
 * Proporciona implementaciones por defecto para métodos comunes.
 */
abstract class InteractiveTypeBase extends PluginBase implements InteractiveTypeInterface
{

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return (string) $this->pluginDefinition['label'];
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return (string) ($this->pluginDefinition['description'] ?? '');
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): array
    {
        $icon = $this->pluginDefinition['icon'] ?? 'ui/question';
        $parts = explode('/', $icon);
        return [
            'category' => $parts[0] ?? 'ui',
            'name' => $parts[1] ?? 'question',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validate(array $data): array
    {
        $errors = [];
        $schema = $this->getSchema();

        // Validación básica de campos requeridos.
        foreach ($schema as $field => $definition) {
            if (!empty($definition['required']) && empty($data[$field])) {
                $errors[$field] = t('El campo @field es requerido.', ['@field' => $field]);
            }
        }

        return $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getXapiVerbs(): array
    {
        return ['attempted', 'completed'];
    }

    /**
     * Calcula el porcentaje de puntuación.
     *
     * @param float $score
     *   La puntuación obtenida.
     * @param float $maxScore
     *   La puntuación máxima.
     *
     * @return float
     *   El porcentaje (0-100).
     */
    protected function calculatePercentage(float $score, float $maxScore): float
    {
        if ($maxScore === 0.0) {
            return 0.0;
        }
        return round(($score / $maxScore) * 100, 2);
    }

    /**
     * Determina si el usuario aprobó según el umbral.
     *
     * @param float $percentage
     *   El porcentaje obtenido.
     * @param float $threshold
     *   El umbral de aprobación (default 70).
     *
     * @return bool
     *   TRUE si aprobó.
     */
    protected function determinePassed(float $percentage, float $threshold = 70.0): bool
    {
        return $percentage >= $threshold;
    }

}
