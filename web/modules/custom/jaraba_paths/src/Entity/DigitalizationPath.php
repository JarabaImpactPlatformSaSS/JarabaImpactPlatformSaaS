<?php

declare(strict_types=1);

namespace Drupal\jaraba_paths\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad DigitalizationPath.
 *
 * Representa un itinerario de digitalización completo.
 * Equivalente a Course en jaraba_lms pero adaptado para el
 * Método Jaraba de transformación digital empresarial.
 *
 * SPEC: 28_Emprendimiento_Digitalization_Paths_v1
 *
 * @ContentEntityType(
 *   id = "digitalization_path",
 *   label = @Translation("Itinerario de Digitalización"),
 *   label_collection = @Translation("Itinerarios de Digitalización"),
 *   label_singular = @Translation("itinerario"),
 *   label_plural = @Translation("itinerarios"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_paths\DigitalizationPathListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_paths\Form\DigitalizationPathForm",
 *       "add" = "Drupal\jaraba_paths\Form\DigitalizationPathForm",
 *       "edit" = "Drupal\jaraba_paths\Form\DigitalizationPathForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_paths\DigitalizationPathAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "digitalization_path",
 *   admin_permission = "administer digitalization paths",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "author",
 *   },
 *   links = {
 *     "collection" = "/admin/content/paths",
 *     "add-form" = "/admin/content/paths/add",
 *     "canonical" = "/path/{digitalization_path}",
 *     "edit-form" = "/admin/content/path/{digitalization_path}/edit",
 *     "delete-form" = "/admin/content/path/{digitalization_path}/delete",
 *   },
 *   field_ui_base_route = "entity.digitalization_path.settings",
 * )
 */
class DigitalizationPath extends ContentEntityBase implements DigitalizationPathInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // === Información básica ===
        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Nombre del itinerario de digitalización.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', ['weight' => 0])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada del itinerario.'))
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', ['weight' => 1])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['short_description'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Descripción Corta'))
            ->setDescription(t('Resumen breve para listados y cards.'))
            ->setSetting('max_length', 300)
            ->setDisplayOptions('form', ['weight' => 2])
            ->setDisplayConfigurable('form', TRUE);

        // === Targeting ===
        $fields['target_sector'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Sector Objetivo'))
            ->setDescription(t('Sector de negocio para el que está diseñado.'))
            ->setSetting('allowed_values', [
                'comercio' => 'Comercio Local',
                'servicios' => 'Servicios Profesionales',
                'agro' => 'Agroalimentario',
                'hosteleria' => 'Hostelería y Turismo',
                'industria' => 'Industria',
                'tech' => 'Tecnología',
                'general' => 'General (Todos)',
            ])
            ->setDefaultValue('general')
            ->setDisplayOptions('form', ['weight' => 3])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['target_maturity_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Nivel de Madurez Objetivo'))
            ->setDescription(t('Nivel de madurez digital inicial recomendado.'))
            ->setSetting('allowed_values', [
                'analogico' => 'Analógico (0-20)',
                'basico' => 'Básico (21-40)',
                'conectado' => 'Conectado (41-60)',
                'digitalizado' => 'Digitalizado (61-80)',
                'inteligente' => 'Inteligente (81-100)',
            ])
            ->setDisplayOptions('form', ['weight' => 4])
            ->setDisplayConfigurable('form', TRUE);

        $fields['target_business_size'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tamaño de Negocio'))
            ->setDescription(t('Tamaño de negocio recomendado.'))
            ->setSetting('allowed_values', [
                'solo' => 'Autónomo/Solo',
                'micro' => 'Microempresa (1-9)',
                'pequena' => 'Pequeña (10-49)',
                'all' => 'Todos los tamaños',
            ])
            ->setDefaultValue('all')
            ->setDisplayOptions('form', ['weight' => 5])
            ->setDisplayConfigurable('form', TRUE);

        // === Métricas del itinerario ===
        $fields['estimated_weeks'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duración Estimada (semanas)'))
            ->setDescription(t('Duración estimada para completar el itinerario.'))
            ->setDefaultValue(12)
            ->setDisplayOptions('view', ['weight' => 10])
            ->setDisplayOptions('form', ['weight' => 10])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['expected_roi_percent'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('ROI Esperado (%)'))
            ->setDescription(t('Retorno de inversión esperado para marketing.'))
            ->setDefaultValue(150)
            ->setDisplayOptions('form', ['weight' => 11])
            ->setDisplayConfigurable('form', TRUE);

        $fields['difficulty_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Nivel de Dificultad'))
            ->setSetting('allowed_values', [
                'beginner' => 'Principiante',
                'intermediate' => 'Intermedio',
                'advanced' => 'Avanzado',
            ])
            ->setDefaultValue('beginner')
            ->setDisplayOptions('form', ['weight' => 12])
            ->setDisplayConfigurable('form', TRUE);

        $fields['total_steps'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total de Pasos'))
            ->setDescription(t('Número total de pasos (calculado).'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['total_quick_wins'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Quick Wins'))
            ->setDescription(t('Número de quick wins disponibles.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        // === Imagen y presentación ===
        $fields['image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Imagen de Portada'))
            ->setDescription(t('Imagen representativa del itinerario.'))
            ->setDisplayOptions('view', ['weight' => -1])
            ->setDisplayOptions('form', ['weight' => 15])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Multi-tenancy ===
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayOptions('form', ['weight' => 20])
            ->setDisplayConfigurable('form', TRUE);

        // === Estado ===
        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Publicado'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', ['weight' => 25])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacado'))
            ->setDescription(t('Mostrar en sección destacados.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', ['weight' => 26])
            ->setDisplayConfigurable('form', TRUE);

        // === Timestamps ===
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    // === Getters ===

    /**
     * {@inheritdoc}
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetSector(): string
    {
        return $this->get('target_sector')->value ?? 'general';
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetMaturityLevel(): ?string
    {
        return $this->get('target_maturity_level')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getEstimatedWeeks(): int
    {
        return (int) ($this->get('estimated_weeks')->value ?? 12);
    }

    /**
     * {@inheritdoc}
     */
    public function isPublished(): bool
    {
        return (bool) ($this->get('status')->value ?? TRUE);
    }

    /**
     * {@inheritdoc}
     */
    public function isFeatured(): bool
    {
        return (bool) ($this->get('is_featured')->value ?? FALSE);
    }

}
