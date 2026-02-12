<?php

declare(strict_types=1);

namespace Drupal\jaraba_diagnostic\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Business Diagnostic.
 *
 * Representa un diagnóstico empresarial completo del vertical Emprendimiento.
 * Implementa evaluación multidimensional de madurez digital con scoring
 * por sector y generación automática de roadmap de digitalización.
 *
 * SPEC: 25_Emprendimiento_Business_Diagnostic_Core_v1
 *
 * @ContentEntityType(
 *   id = "business_diagnostic",
 *   label = @Translation("Diagnóstico Empresarial"),
 *   label_collection = @Translation("Diagnósticos Empresariales"),
 *   label_singular = @Translation("diagnóstico empresarial"),
 *   label_plural = @Translation("diagnósticos empresariales"),
 *   label_count = @PluralTranslation(
 *     singular = "@count diagnóstico",
 *     plural = "@count diagnósticos",
 *   ),
 *   bundle_label = @Translation("Tipo de diagnóstico"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_diagnostic\BusinessDiagnosticListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_diagnostic\Form\BusinessDiagnosticForm",
 *       "add" = "Drupal\jaraba_diagnostic\Form\BusinessDiagnosticForm",
 *       "edit" = "Drupal\jaraba_diagnostic\Form\BusinessDiagnosticForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_diagnostic\BusinessDiagnosticAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "business_diagnostic",
 *   admin_permission = "administer business diagnostics",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "business_name",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/diagnostics",
 *     "add-form" = "/admin/content/diagnostics/add",
 *     "canonical" = "/admin/content/diagnostic/{business_diagnostic}",
 *     "edit-form" = "/admin/content/diagnostic/{business_diagnostic}/edit",
 *     "delete-form" = "/admin/content/diagnostic/{business_diagnostic}/delete",
 *   },
 *   field_ui_base_route = "entity.business_diagnostic.settings",
 * )
 */
class BusinessDiagnostic extends ContentEntityBase implements BusinessDiagnosticInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function preSave(EntityStorageInterface $storage): void
    {
        parent::preSave($storage);

        // Calcular overall_score si hay respuestas
        if ($this->isNew() || $this->hasField('answers_updated')) {
            $this->recalculateScores();
        }
    }

    /**
     * Recalcula los scores del diagnóstico.
     */
    protected function recalculateScores(): void
    {
        // La lógica de scoring se delega al DiagnosticScoringService
        // que se invoca via hook_entity_presave
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // === Información del Negocio ===
        $fields['business_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Negocio'))
            ->setDescription(t('Nombre comercial o razón social.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['business_sector'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Sector'))
            ->setDescription(t('Sector de actividad del negocio.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'comercio' => 'Comercio Local',
                'servicios' => 'Servicios Profesionales',
                'agro' => 'Agroalimentario',
                'hosteleria' => 'Hostelería y Turismo',
                'industria' => 'Industria',
                'tech' => 'Tecnología',
                'otros' => 'Otros',
            ])
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['business_size'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tamaño'))
            ->setDescription(t('Tamaño del negocio.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'solo' => 'Autónomo/Solo',
                'micro' => 'Microempresa (1-9)',
                'pequena' => 'Pequeña (10-49)',
                'mediana' => 'Mediana (50-249)',
            ])
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['business_age_years'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Años de Operación'))
            ->setDescription(t('Años desde el inicio del negocio.'))
            ->setRequired(TRUE)
            ->setSetting('min', 0)
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['annual_revenue'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Facturación Anual Estimada (€)'))
            ->setDescription(t('Facturación anual aproximada en euros.'))
            ->setSetting('precision', 12)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', ['weight' => 4])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Multi-tenancy ===
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Programa o entidad asociada.'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // === Referencia a Calculadora TTV ===
        $fields['maturity_ttv_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Score Calculadora TTV'))
            ->setDescription(t('Puntuación de la calculadora de madurez digital TTV (si existe).'))
            ->setSetting('min', 0)
            ->setSetting('max', 100)
            ->setDisplayConfigurable('view', TRUE);

        // === Resultados Calculados ===
        $fields['overall_score'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Puntuación Global'))
            ->setDescription(t('Puntuación global calculada 0-100.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 10])
            ->setDisplayConfigurable('view', TRUE);

        $fields['maturity_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Nivel de Madurez'))
            ->setDescription(t('Nivel de madurez digital calculado.'))
            ->setSetting('allowed_values', [
                'analogico' => 'Analógico (0-20)',
                'basico' => 'Básico (21-40)',
                'conectado' => 'Conectado (41-60)',
                'digitalizado' => 'Digitalizado (61-80)',
                'inteligente' => 'Inteligente (81-100)',
            ])
            ->setDisplayOptions('view', ['weight' => 11])
            ->setDisplayConfigurable('view', TRUE);

        $fields['estimated_loss_annual'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Pérdida Anual Estimada (€)'))
            ->setDescription(t('Estimación de pérdidas económicas por gaps digitales.'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', ['weight' => 12])
            ->setDisplayConfigurable('view', TRUE);

        $fields['priority_gaps'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Gaps Prioritarios'))
            ->setDescription(t('JSON con gaps prioritarios detectados.'))
            ->setDisplayConfigurable('view', TRUE);

        // === Referencia a Itinerario Recomendado ===
        $fields['recommended_path_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Itinerario Recomendado'))
            ->setDescription(t('Ruta de digitalización recomendada según resultados.'))
            ->setSetting('target_type', 'digitalization_path')
            ->setDisplayOptions('view', ['weight' => 15])
            ->setDisplayConfigurable('view', TRUE);

        // === Estado ===
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado del diagnóstico.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'in_progress' => 'En Progreso',
                'completed' => 'Completado',
                'archived' => 'Archivado',
            ])
            ->setDefaultValue('in_progress')
            ->setDisplayOptions('view', ['weight' => 20])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['completed_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Fecha de Completitud'))
            ->setDescription(t('Cuándo se completó el diagnóstico.'))
            ->setDisplayConfigurable('view', TRUE);

        // === Timestamps estándar ===
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación del diagnóstico.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

    // === Getters ===

    /**
     * {@inheritdoc}
     */
    public function getBusinessName(): string
    {
        return $this->get('business_name')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getBusinessSector(): string
    {
        return $this->get('business_sector')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getOverallScore(): float
    {
        return (float) ($this->get('overall_score')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getMaturityLevel(): string
    {
        return $this->get('maturity_level')->value ?? 'analogico';
    }

    /**
     * {@inheritdoc}
     */
    public function getEstimatedLoss(): float
    {
        return (float) ($this->get('estimated_loss_annual')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function isCompleted(): bool
    {
        return $this->get('status')->value === 'completed';
    }

    // === Setters ===

    /**
     * {@inheritdoc}
     */
    public function setOverallScore(float $score): self
    {
        $this->set('overall_score', $score);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setMaturityLevel(string $level): self
    {
        $this->set('maturity_level', $level);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setEstimatedLoss(float $loss): self
    {
        $this->set('estimated_loss_annual', $loss);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function markCompleted(): self
    {
        $this->set('status', 'completed');
        $this->set('completed_at', time());
        return $this;
    }

}
