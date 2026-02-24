<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * CORRECCIÓN DE IA - TenantAiCorrection
 *
 * PROPÓSITO:
 * Registra correcciones hechas por el tenant cuando el copiloto
 * da una respuesta incorrecta o incompleta.
 *
 * FLUJO DE FEEDBACK LOOP:
 * 1. Usuario detecta respuesta incorrecta del copiloto
 * 2. Registra la corrección con contexto
 * 3. El sistema aprende de la corrección
 * 4. Se genera una "regla" que se aplica en futuros prompts
 *
 * TIPOS DE CORRECCIÓN:
 * - factual: Información incorrecta
 * - tone: Tono inadecuado
 * - missing: Información faltante
 * - policy: Violación de política
 *
 * MULTILINGÜE (G114-3):
 * Campos de contenido traducibles (title, original_query, incorrect_response,
 * correct_response, generated_rule, related_topic).
 * Campos de organización (correction_type, priority, status, timestamps) no traducibles.
 * Integración con content_translation para UI de traducción estándar.
 *
 * @ContentEntityType(
 *   id = "tenant_ai_correction",
 *   label = @Translation("Corrección de IA"),
 *   label_collection = @Translation("Correcciones de IA"),
 *   label_singular = @Translation("corrección de IA"),
 *   label_plural = @Translation("correcciones de IA"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_tenant_knowledge\TenantAiCorrectionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_knowledge\Form\TenantAiCorrectionForm",
 *       "add" = "Drupal\jaraba_tenant_knowledge\Form\TenantAiCorrectionForm",
 *       "edit" = "Drupal\jaraba_tenant_knowledge\Form\TenantAiCorrectionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_tenant_knowledge\TenantKnowledgeAccessControlHandler",
 *   },
 *   base_table = "tenant_ai_correction",
 *   data_table = "tenant_ai_correction_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer tenant knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/content/tenant-ai-corrections",
 *     "add-form" = "/knowledge/corrections/add",
 *     "edit-form" = "/knowledge/corrections/{tenant_ai_correction}/edit",
 *     "delete-form" = "/knowledge/corrections/{tenant_ai_correction}/delete",
 *   },
 *   field_ui_base_route = "entity.tenant_ai_correction.settings",
 * )
 */
class TenantAiCorrection extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * Tipos de corrección.
     */
    public const TYPE_FACTUAL = 'factual';
    public const TYPE_TONE = 'tone';
    public const TYPE_MISSING = 'missing';
    public const TYPE_POLICY = 'policy';
    public const TYPE_OTHER = 'other';

    /**
     * Estados de corrección.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REJECTED = 'rejected';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al tenant propietario.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant propietario.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // === CONTEXTO DE LA CORRECCIÓN ===

        // Título descriptivo.
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Breve descripción de la corrección.'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
                'settings' => [
                    'placeholder' => 'Ej: Precio incorrecto del producto X',
                ],
            ]);

        // Tipo de corrección.
        $fields['correction_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Corrección'))
            ->setDescription(t('¿Qué tipo de error cometió el copiloto?'))
            ->setRequired(TRUE)
            ->setSettings([
                'allowed_values' => [
                    'factual' => 'Información incorrecta',
                    'tone' => 'Tono inadecuado',
                    'missing' => 'Información faltante',
                    'policy' => 'Violación de política',
                    'other' => 'Otro',
                ],
            ])
            ->setDefaultValue('factual')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ]);

        // === RESPUESTA ORIGINAL ===

        // Pregunta/contexto original.
        $fields['original_query'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Pregunta Original'))
            ->setDescription(t('¿Qué preguntó el usuario?'))
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 2,
                'settings' => ['rows' => 3],
            ]);

        // Respuesta incorrecta del copiloto.
        $fields['incorrect_response'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Respuesta Incorrecta'))
            ->setDescription(t('¿Qué respondió el copiloto incorrectamente?'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 3,
                'settings' => ['rows' => 5],
            ]);

        // === CORRECCIÓN ===

        // Respuesta correcta.
        $fields['correct_response'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Respuesta Correcta'))
            ->setDescription(t('¿Cuál debería haber sido la respuesta?'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 4,
                'settings' => ['rows' => 5],
            ]);

        // Regla generada (para inyectar en prompts futuros).
        $fields['generated_rule'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Regla Generada'))
            ->setDescription(t('Instrucción derivada para el copiloto.'))
            ->setTranslatable(TRUE);

        // === CATEGORIZACIÓN ===

        // Tema/producto relacionado.
        $fields['related_topic'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tema Relacionado'))
            ->setDescription(t('Producto, servicio o tema afectado.'))
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 5,
            ]);

        // Prioridad.
        $fields['priority'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Prioridad'))
            ->setSettings([
                'allowed_values' => [
                    'low' => 'Baja',
                    'medium' => 'Media',
                    'high' => 'Alta',
                    'critical' => 'Crítica',
                ],
            ])
            ->setDefaultValue('medium')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 6,
            ]);

        // === ESTADO ===

        // Estado de la corrección.
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setSettings([
                'allowed_values' => [
                    'pending' => 'Pendiente de aplicar',
                    'applied' => 'Aplicada',
                    'rejected' => 'Rechazada',
                ],
            ])
            ->setDefaultValue('pending');

        // Fecha de aplicación.
        $fields['applied_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha de Aplicación'));

        // Contador de veces que esta corrección ha evitado el error.
        $fields['hit_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Veces Aplicada'))
            ->setDescription(t('Número de veces que esta corrección ha sido útil.'))
            ->setDefaultValue(0);

        // === TIMESTAMPS ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de Modificación'));

        return $fields;
    }

    /**
     * Obtiene el título.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Obtiene el tipo de corrección.
     */
    public function getCorrectionType(): string
    {
        return $this->get('correction_type')->value ?? self::TYPE_OTHER;
    }

    /**
     * Obtiene la etiqueta del tipo.
     */
    public function getCorrectionTypeLabel(): string
    {
        $type = $this->getCorrectionType();
        $allowedValues = $this->getFieldDefinition('correction_type')->getSetting('allowed_values');
        return $allowedValues[$type] ?? $type;
    }

    /**
     * Obtiene el estado.
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? self::STATUS_PENDING;
    }

    /**
     * Obtiene el tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->get('tenant_id')->target_id ? (int) $this->get('tenant_id')->target_id : NULL;
    }

    /**
     * Verifica si está aplicada.
     */
    public function isApplied(): bool
    {
        return $this->getStatus() === self::STATUS_APPLIED;
    }

    /**
     * Obtiene la regla generada.
     */
    public function getGeneratedRule(): string
    {
        return $this->get('generated_rule')->value ?? '';
    }

    /**
     * Genera la regla a partir de la corrección.
     */
    public function generateRule(): string
    {
        $type = $this->getCorrectionType();
        $incorrectResponse = $this->get('incorrect_response')->value ?? '';
        $correctResponse = $this->get('correct_response')->value ?? '';
        $topic = $this->get('related_topic')->value ?? '';

        $rule = "CORRECCIÓN";
        if (!empty($topic)) {
            $rule .= " sobre \"$topic\"";
        }
        $rule .= ":\n";
        $rule .= "- NO responder: " . substr($incorrectResponse, 0, 200) . "\n";
        $rule .= "- SÍ responder: " . substr($correctResponse, 0, 200);

        return $rule;
    }

    /**
     * Aplica la corrección (genera regla y actualiza estado).
     */
    public function apply(): void
    {
        $rule = $this->generateRule();
        $this->set('generated_rule', $rule);
        $this->set('status', self::STATUS_APPLIED);
        $this->set('applied_at', \Drupal::time()->getRequestTime());
        $this->save();
    }

    /**
     * Incrementa el contador de hits.
     */
    public function incrementHitCount(): void
    {
        $current = (int) ($this->get('hit_count')->value ?? 0);
        $this->set('hit_count', $current + 1);
        $this->save();
    }

    /**
     * Genera texto para embedding.
     */
    public function getEmbeddingText(): string
    {
        $parts = [];

        $parts[] = "Tipo: " . $this->getCorrectionTypeLabel();
        $parts[] = "Título: " . $this->getTitle();

        $topic = $this->get('related_topic')->value;
        if (!empty($topic)) {
            $parts[] = "Tema: " . $topic;
        }

        $query = $this->get('original_query')->value;
        if (!empty($query)) {
            $parts[] = "Pregunta: " . $query;
        }

        $parts[] = "Respuesta correcta: " . $this->get('correct_response')->value;

        return implode("\n", $parts);
    }

}
