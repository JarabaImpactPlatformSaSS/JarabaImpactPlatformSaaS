<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * CONFIGURACIÓN DE CONOCIMIENTO DEL TENANT - TenantKnowledgeConfig
 *
 * PROPÓSITO:
 * Almacena la configuración base de conocimiento del negocio.
 * Esta es la entidad principal que agrupa toda la información
 * del tenant para personalización de IA.
 *
 * ESTRUCTURA:
 * Cada tenant tiene exactamente UNA instancia de TenantKnowledgeConfig
 * que actúa como "perfil de conocimiento" del negocio.
 *
 * LÓGICA:
 * - Se crea automáticamente cuando el tenant accede al dashboard
 * - El prompt del agente IA incluye resumen de esta config
 * - Los campos se indexan en Qdrant para retrieval semántico
 *
 * MULTI-TENANCY:
 * Campo tenant_id es obligatorio. Todas las queries filtran por tenant.
 *
 * @ContentEntityType(
 *   id = "tenant_knowledge_config",
 *   label = @Translation("Configuración de Conocimiento"),
 *   label_collection = @Translation("Configuraciones de Conocimiento"),
 *   label_singular = @Translation("configuración de conocimiento"),
 *   label_plural = @Translation("configuraciones de conocimiento"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_tenant_knowledge\Form\TenantKnowledgeConfigForm",
 *       "edit" = "Drupal\jaraba_tenant_knowledge\Form\TenantKnowledgeConfigForm",
 *     },
 *     "access" = "Drupal\jaraba_tenant_knowledge\TenantKnowledgeAccessControlHandler",
 *   },
 *   base_table = "tenant_knowledge_config",
 *   admin_permission = "administer tenant knowledge",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "business_name",
 *   },
 *   links = {
 *     "collection" = "/admin/content/tenant-knowledge-config",
 *     "edit-form" = "/knowledge/config/{tenant_knowledge_config}/edit",
 *   },
 *   field_ui_base_route = "entity.tenant_knowledge_config.settings",
 * )
 */
class TenantKnowledgeConfig extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia al tenant propietario (OBLIGATORIO).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant propietario de esta configuración.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setCardinality(1);

        // === INFORMACIÓN BÁSICA DEL NEGOCIO ===

        // Nombre comercial del negocio.
        $fields['business_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Negocio'))
            ->setDescription(t('Nombre comercial como lo conocen los clientes.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ]);

        // Descripción general del negocio.
        $fields['business_description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción del Negocio'))
            ->setDescription(t('Describe qué hace tu negocio, a quién sirve y qué te diferencia.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ]);

        // Industria/sector.
        $fields['industry'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Industria'))
            ->setDescription(t('Sector principal del negocio.'))
            ->setSettings([
                'allowed_values' => [
                    'retail' => 'Comercio/Retail',
                    'services' => 'Servicios Profesionales',
                    'hospitality' => 'Hostelería/Turismo',
                    'health' => 'Salud/Bienestar',
                    'education' => 'Educación/Formación',
                    'technology' => 'Tecnología',
                    'manufacturing' => 'Fabricación/Industrial',
                    'construction' => 'Construcción/Inmobiliaria',
                    'agriculture' => 'Agricultura/Alimentación',
                    'other' => 'Otro',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ]);

        // Sub-sector específico.
        $fields['industry_specific'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Especialidad'))
            ->setDescription(t('Sub-sector o nicho específico (ej: "Panadería artesanal", "Consultoría fiscal").'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 3,
            ]);

        // === TONO Y PERSONALIDAD ===

        // Tono de comunicación preferido.
        $fields['communication_tone'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tono de Comunicación'))
            ->setDescription(t('Cómo debe comunicarse el asistente IA con tus clientes.'))
            ->setSettings([
                'allowed_values' => [
                    'professional' => 'Profesional y Formal',
                    'friendly' => 'Amigable y Cercano',
                    'casual' => 'Casual e Informal',
                    'technical' => 'Técnico y Especializado',
                    'warm' => 'Cálido y Empático',
                ],
            ])
            ->setDefaultValue('friendly')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 4,
            ]);

        // Instrucciones personalizadas de tono.
        $fields['tone_instructions'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Instrucciones de Tono'))
            ->setDescription(t('Instrucciones adicionales sobre cómo debe hablar el asistente (frases a usar, evitar, etc.).'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 5,
            ]);

        // === INFORMACIÓN DE CONTACTO ===

        // Horario de atención.
        $fields['business_hours'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Horario de Atención'))
            ->setDescription(t('Horario en que el negocio está disponible (ej: "Lun-Vie 9:00-18:00").'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 6,
            ]);

        // Ubicación/dirección.
        $fields['location'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Ubicación'))
            ->setDescription(t('Dirección física o zona de servicio.'))
            ->setSetting('max_length', 500)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 7,
            ]);

        // Teléfono de contacto.
        $fields['contact_phone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Teléfono'))
            ->setDescription(t('Número de teléfono principal.'))
            ->setSetting('max_length', 50)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 8,
            ]);

        // Email de contacto.
        $fields['contact_email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email'))
            ->setDescription(t('Email de contacto principal.'))
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => 9,
            ]);

        // === CONFIGURACIÓN AVANZADA ===

        // Palabras clave del negocio (para SEO y retrieval).
        $fields['keywords'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Palabras Clave'))
            ->setDescription(t('Palabras clave separadas por comas que describen tu negocio.'))
            ->setSetting('max_length', 1000)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 10,
            ]);

        // Competidores a evitar mencionar.
        $fields['competitors_to_avoid'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Competidores a No Mencionar'))
            ->setDescription(t('Nombres de competidores que el asistente no debe mencionar, separados por comas.'))
            ->setSetting('max_length', 500)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 11,
            ]);

        // === ESTADO Y METADATOS ===

        // Indica si la configuración está completa.
        $fields['is_complete'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Configuración Completa'))
            ->setDescription(t('Indica si el tenant ha completado la configuración básica.'))
            ->setDefaultValue(FALSE);

        // Puntuación de completitud (0-100).
        $fields['completeness_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntuación de Completitud'))
            ->setDescription(t('Porcentaje de campos completados.'))
            ->setDefaultValue(0);

        // Hash del contenido para detectar cambios y regenerar embeddings.
        $fields['content_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hash del Contenido'))
            ->setSetting('max_length', 32);

        // ID del punto en Qdrant.
        $fields['qdrant_point_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID en Qdrant'))
            ->setSetting('max_length', 36);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de Modificación'));

        return $fields;
    }

    /**
     * Obtiene el tenant propietario.
     *
     * @return \Drupal\group\Entity\GroupInterface|null
     *   El grupo (tenant) o NULL.
     */
    public function getTenant()
    {
        return $this->get('tenant_id')->entity;
    }

    /**
     * Obtiene el nombre del negocio.
     */
    public function getBusinessName(): string
    {
        return $this->get('business_name')->value ?? '';
    }

    /**
     * Genera texto combinado para embedding.
     *
     * Combina todos los campos relevantes en un texto único
     * para generar el embedding vectorial.
     *
     * @return string
     *   Texto combinado para embedding.
     */
    public function getEmbeddingText(): string
    {
        $parts = [];

        if ($name = $this->getBusinessName()) {
            $parts[] = "Negocio: {$name}";
        }

        if ($desc = $this->get('business_description')->value) {
            $parts[] = "Descripción: {$desc}";
        }

        if ($industry = $this->get('industry')->value) {
            $allowedValues = $this->getFieldDefinition('industry')
                ->getSetting('allowed_values');
            $industryLabel = $allowedValues[$industry] ?? $industry;
            $parts[] = "Industria: {$industryLabel}";
        }

        if ($specific = $this->get('industry_specific')->value) {
            $parts[] = "Especialidad: {$specific}";
        }

        if ($tone = $this->get('communication_tone')->value) {
            $allowedValues = $this->getFieldDefinition('communication_tone')
                ->getSetting('allowed_values');
            $toneLabel = $allowedValues[$tone] ?? $tone;
            $parts[] = "Tono de comunicación: {$toneLabel}";
        }

        if ($toneInst = $this->get('tone_instructions')->value) {
            $parts[] = "Instrucciones de tono: {$toneInst}";
        }

        if ($hours = $this->get('business_hours')->value) {
            $parts[] = "Horario: {$hours}";
        }

        if ($location = $this->get('location')->value) {
            $parts[] = "Ubicación: {$location}";
        }

        if ($keywords = $this->get('keywords')->value) {
            $parts[] = "Palabras clave: {$keywords}";
        }

        return implode("\n\n", $parts);
    }

    /**
     * Calcula el score de completitud.
     *
     * @return int
     *   Porcentaje de completitud (0-100).
     */
    public function calculateCompletenessScore(): int
    {
        $requiredFields = [
            'business_name',
            'business_description',
            'industry',
        ];

        $optionalFields = [
            'industry_specific',
            'communication_tone',
            'tone_instructions',
            'business_hours',
            'location',
            'contact_phone',
            'contact_email',
            'keywords',
        ];

        $score = 0;
        $total = count($requiredFields) * 2 + count($optionalFields);

        // Campos requeridos valen doble.
        foreach ($requiredFields as $field) {
            if (!empty($this->get($field)->value)) {
                $score += 2;
            }
        }

        // Campos opcionales.
        foreach ($optionalFields as $field) {
            if (!empty($this->get($field)->value)) {
                $score += 1;
            }
        }

        return (int) round(($score / $total) * 100);
    }

}
