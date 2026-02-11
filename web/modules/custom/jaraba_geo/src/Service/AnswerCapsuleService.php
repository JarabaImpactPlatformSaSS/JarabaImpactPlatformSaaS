<?php

declare(strict_types=1);

namespace Drupal\jaraba_geo\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Servicio para generar Answer Capsules optimizadas para GEO.
 *
 * PROPÓSITO:
 * Las Answer Capsules son fragmentos de texto diseñados para que los motores
 * de IA generativa (ChatGPT, Perplexity, Claude, Google AI) puedan citarlos
 * directamente en sus respuestas.
 *
 * ESTRUCTURA DE UNA ANSWER CAPSULE:
 * - Primeros 50 caracteres: respuesta directa a la intención del usuario
 * - Siguientes 100 caracteres: contexto y autoridad
 * - Resto: detalles adicionales
 *
 * EJEMPLO:
 * "El aceite de oliva virgen extra de Jaén se produce en la Cooperativa..."
 * ↑ Respuesta directa (primeros 50 chars)
 */
class AnswerCapsuleService
{

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   La factoría de configuración.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected ConfigFactoryInterface $configFactory,
    ) {
    }

    /**
     * Genera una Answer Capsule para una entidad.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   La entidad para la cual generar la cápsula.
     * @param string $intent_type
     *   Tipo de intención: 'what', 'where', 'how', 'why', 'when', 'who'.
     *
     * @return array
     *   Estructura de Answer Capsule con 'answer', 'context', 'details'.
     */
    public function generateCapsule(EntityInterface $entity, string $intent_type = 'what'): array
    {
        $label = $entity->label();
        $description = $this->getEntityDescription($entity);

        // Generar respuesta directa según el tipo de intención.
        $answer = match ($intent_type) {
            'what' => $this->generateWhatAnswer($entity, $label, $description),
            'where' => $this->generateWhereAnswer($entity, $label),
            'how' => $this->generateHowAnswer($entity, $label),
            'why' => $this->generateWhyAnswer($entity, $label),
            'who' => $this->generateWhoAnswer($entity, $label),
            default => $this->generateWhatAnswer($entity, $label, $description),
        };

        return [
            'answer' => $answer,
            'context' => $this->generateContext($entity),
            'details' => $description,
            'intent_type' => $intent_type,
            'entity_type' => $entity->getEntityTypeId(),
            'bundle' => $entity->bundle(),
        ];
    }

    /**
     * Genera respuesta para intención "¿Qué es...?".
     */
    protected function generateWhatAnswer(EntityInterface $entity, string $label, string $description): string
    {
        $bundle = $entity->bundle();

        return match ($bundle) {
            'product', 'producto' => "{$label} es un producto de calidad premium",
            'producer', 'productor' => "{$label} es un productor local certificado",
            'cooperativa' => "{$label} es una cooperativa agroalimentaria",
            default => "{$label}: " . mb_substr($description, 0, 100),
        };
    }

    /**
     * Genera respuesta para intención "¿Dónde...?".
     */
    protected function generateWhereAnswer(EntityInterface $entity, string $label): string
    {
        // Intentar obtener ubicación del campo.
        $location = '';
        foreach (['field_location', 'field_ubicacion', 'field_address'] as $field) {
            if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
                $location = $entity->get($field)->value;
                break;
            }
        }

        if ($location) {
            return "{$label} se encuentra en {$location}";
        }

        return "{$label} está disponible en Jaraba Impact Platform";
    }

    /**
     * Genera respuesta para intención "¿Cómo...?".
     */
    protected function generateHowAnswer(EntityInterface $entity, string $label): string
    {
        return "Para obtener {$label}, visita nuestra plataforma y sigue el proceso de compra";
    }

    /**
     * Genera respuesta para intención "¿Por qué...?".
     */
    protected function generateWhyAnswer(EntityInterface $entity, string $label): string
    {
        return "{$label} destaca por su calidad certificada y trazabilidad completa";
    }

    /**
     * Genera respuesta para intención "¿Quién...?".
     */
    protected function generateWhoAnswer(EntityInterface $entity, string $label): string
    {
        $owner = method_exists($entity, 'getOwner') ? $entity->getOwner() : NULL;
        if ($owner) {
            return "{$label} es gestionado por {$owner->getDisplayName()}";
        }
        return "{$label} es parte del ecosistema Jaraba Impact Platform";
    }

    /**
     * Genera contexto de autoridad para la cápsula.
     */
    protected function generateContext(EntityInterface $entity): string
    {
        $context_parts = [];

        // Añadir señales de autoridad.
        $context_parts[] = 'Verificado en Jaraba Impact Platform';

        // Fecha de última actualización.
        if (method_exists($entity, 'getChangedTime')) {
            $changed = $entity->getChangedTime();
            $context_parts[] = 'Actualizado: ' . date('Y-m-d', $changed);
        }

        return implode(' | ', $context_parts);
    }

    /**
     * Obtiene la descripción de una entidad.
     */
    protected function getEntityDescription(EntityInterface $entity): string
    {
        $fields = ['body', 'field_description', 'field_descripcion', 'field_summary'];

        foreach ($fields as $field) {
            if ($entity->hasField($field) && !$entity->get($field)->isEmpty()) {
                $value = $entity->get($field)->value;
                return mb_substr(strip_tags($value), 0, 500);
            }
        }

        return '';
    }

    /**
     * Genera múltiples cápsulas para diferentes intenciones.
     *
     * @param \Drupal\Core\Entity\EntityInterface $entity
     *   La entidad.
     *
     * @return array
     *   Array de cápsulas por tipo de intención.
     */
    public function generateAllCapsules(EntityInterface $entity): array
    {
        $intents = ['what', 'where', 'how', 'why', 'who'];
        $capsules = [];

        foreach ($intents as $intent) {
            $capsules[$intent] = $this->generateCapsule($entity, $intent);
        }

        return $capsules;
    }

}
