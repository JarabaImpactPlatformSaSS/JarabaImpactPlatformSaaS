<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Badge (Insignia).
 *
 * Sistema de gamificación para reconocer logros de usuarios
 * en la plataforma. Las insignias se otorgan automáticamente
 * según criterios configurables o manualmente por administradores.
 *
 * Categorías soportadas: onboarding, engagement, achievement, milestone, community.
 * Tipos de criterio: event_count, first_action, streak, manual.
 *
 * @ContentEntityType(
 *   id = "badge",
 *   label = @Translation("Insignia"),
 *   label_collection = @Translation("Insignias"),
 *   label_singular = @Translation("insignia"),
 *   label_plural = @Translation("insignias"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\BadgeListBuilder",
 *     "form" = {
 *       "default" = "Drupal\ecosistema_jaraba_core\Form\BadgeForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "badge",
 *   admin_permission = "administer tenants",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/badges",
 *     "canonical" = "/admin/config/badges/{badge}",
 *     "add-form" = "/admin/config/badges/add",
 *     "edit-form" = "/admin/config/badges/{badge}/edit",
 *     "delete-form" = "/admin/config/badges/{badge}/delete",
 *   },
 *   field_ui_base_route = "entity.badge.settings",
 * )
 */
class Badge extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Nombre de la insignia.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre visible de la insignia.'))
            ->setRequired(TRUE)
            ->setSettings([
                'max_length' => 128,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => 0,
            ]);

        // Descripción de la insignia.
        $fields['description'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada de la insignia y cómo obtenerla.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 1,
            ])
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'basic_string',
                'weight' => 1,
            ]);

        // Icono (nombre de clase CSS o emoji).
        $fields['icon'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Icono'))
            ->setDescription(t('Nombre del icono CSS o emoji para representar la insignia.'))
            ->setSettings([
                'max_length' => 64,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 2,
            ]);

        // Categoría de la insignia.
        $fields['category'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Categoría'))
            ->setDescription(t('Categoría para agrupar las insignias.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'onboarding' => t('Onboarding'),
                'engagement' => t('Engagement'),
                'achievement' => t('Logro'),
                'milestone' => t('Hito'),
                'community' => t('Comunidad'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 3,
            ]);

        // Tipo de criterio para otorgamiento automático.
        $fields['criteria_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Criterio'))
            ->setDescription(t('Mecanismo de evaluación para otorgar la insignia automáticamente.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'event_count' => t('Conteo de eventos'),
                'first_action' => t('Primera acción'),
                'streak' => t('Racha consecutiva'),
                'manual' => t('Otorgamiento manual'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 4,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 4,
            ]);

        // Configuración del criterio (JSON).
        $fields['criteria_config'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Configuración de Criterio'))
            ->setDescription(t('JSON con configuración del criterio de otorgamiento. Ej: {"event": "login", "count": 10} o {"event": "profile_complete"}.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 5,
            ])
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'basic_string',
                'weight' => 5,
            ]);

        // Puntos asociados a la insignia.
        $fields['points'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntos'))
            ->setDescription(t('Puntos que otorga esta insignia al usuario.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 6,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 6,
            ]);

        // Estado activo/inactivo.
        $fields['active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDescription(t('Solo las insignias activas pueden ser otorgadas.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 7,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'boolean',
                'weight' => 7,
            ]);

        // Fecha de creación.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creada'))
            ->setDescription(t('Fecha de creación de la insignia.'));

        // Fecha de última modificación.
        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificada'))
            ->setDescription(t('Fecha de última modificación de la insignia.'));

        return $fields;
    }

    /**
     * Obtiene el nombre de la insignia.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Obtiene la descripción de la insignia.
     */
    public function getDescription(): string
    {
        return $this->get('description')->value ?? '';
    }

    /**
     * Obtiene el icono de la insignia.
     */
    public function getIcon(): string
    {
        return $this->get('icon')->value ?? '';
    }

    /**
     * Obtiene la categoría de la insignia.
     */
    public function getCategory(): string
    {
        return $this->get('category')->value ?? '';
    }

    /**
     * Obtiene el tipo de criterio.
     */
    public function getCriteriaType(): string
    {
        return $this->get('criteria_type')->value ?? '';
    }

    /**
     * Obtiene la configuración del criterio como array.
     */
    public function getCriteriaConfig(): array
    {
        $json = $this->get('criteria_config')->value ?? '';
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene los puntos de la insignia.
     */
    public function getPoints(): int
    {
        return (int) ($this->get('points')->value ?? 0);
    }

    /**
     * Verifica si la insignia está activa.
     */
    public function isActive(): bool
    {
        return (bool) ($this->get('active')->value ?? TRUE);
    }

}
