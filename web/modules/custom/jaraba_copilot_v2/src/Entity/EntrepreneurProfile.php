<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Entrepreneur Profile entity.
 *
 * Perfil del emprendedor con DIME, carril asignado, bloqueos detectados
 * y progreso en el programa.
 *
 * @ContentEntityType(
 *   id = "entrepreneur_profile",
 *   label = @Translation("Perfil de Emprendedor"),
 *   label_collection = @Translation("Perfiles de Emprendedores"),
 *   label_singular = @Translation("perfil de emprendedor"),
 *   label_plural = @Translation("perfiles de emprendedores"),
 *   label_count = @PluralTranslation(
 *     singular = "@count perfil",
 *     plural = "@count perfiles",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "entrepreneur_profile",
 *   admin_permission = "administer entrepreneur profiles",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/entrepreneur-profiles",
 *     "add-form" = "/admin/content/entrepreneur-profiles/add",
 *     "canonical" = "/admin/content/entrepreneur-profiles/{entrepreneur_profile}",
 *     "edit-form" = "/admin/content/entrepreneur-profiles/{entrepreneur_profile}/edit",
 *     "delete-form" = "/admin/content/entrepreneur-profiles/{entrepreneur_profile}/delete",
 *   },
 *   field_ui_base_route = "entity.entrepreneur_profile.settings",
 * )
 */
class EntrepreneurProfile extends ContentEntityBase implements EntrepreneurProfileInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    public function label()
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getCarril(): string
    {
        return $this->get('carril')->value ?? 'IMPULSO';
    }

    /**
     * {@inheritdoc}
     */
    public function getDimeScore(): int
    {
        return (int) $this->get('dime_score')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentProgramWeek(): int
    {
        $startDate = $this->get('program_start_date')->value;
        if (!$startDate) {
            return 0;
        }

        $start = new \DateTime($startDate);
        $now = new \DateTime();
        $diff = $now->diff($start);
        $weeks = (int) floor($diff->days / 7);

        return min($weeks, 12);
    }

    /**
     * {@inheritdoc}
     */
    public function getPhase(): string
    {
        return $this->get('phase')->value ?? 'INVENTARIO';
    }

    /**
     * {@inheritdoc}
     */
    public function getImpactPoints(): int
    {
        return (int) $this->get('impact_points')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function addImpactPoints(int $points): self
    {
        $current = $this->getImpactPoints();
        $this->set('impact_points', $current + $points);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDetectedBlockages(): array
    {
        $value = $this->get('detected_blockages')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre del emprendedor.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['carril'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Carril'))
            ->setDescription(t('Carril asignado según puntuación DIME.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'IMPULSO' => t('Carril Impulso (0-9 pts)'),
                'ACELERA' => t('Carril Acelera (10-20 pts)'),
            ])
            ->setDefaultValue('IMPULSO')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Puntuaciones DIME
        $fields['dime_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntuación DIME Total'))
            ->setDescription(t('Puntuación total del diagnóstico DIME (0-20).'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['dime_digital'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('DIME - Digital'))
            ->setDescription(t('Puntuación del bloque Digital (0-4).'))
            ->setDefaultValue(0);

        $fields['dime_idea'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('DIME - Idea'))
            ->setDescription(t('Puntuación del bloque Idea (0-6).'))
            ->setDefaultValue(0);

        $fields['dime_mercado'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('DIME - Mercado'))
            ->setDescription(t('Puntuación del bloque Mercado (0-4).'))
            ->setDefaultValue(0);

        $fields['dime_emocional'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('DIME - Emocional'))
            ->setDescription(t('Puntuación del bloque Emocional (0-6).'))
            ->setDefaultValue(0);

        $fields['detected_blockages'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Bloqueos Detectados'))
            ->setDescription(t('JSON array de bloqueos emocionales detectados.'));

        $fields['phase'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Fase Actual'))
            ->setDescription(t('Fase actual del programa.'))
            ->setSetting('allowed_values', [
                'INVENTARIO' => t('Inventario (Semanas 1-3)'),
                'VALIDACION' => t('Validación (Semanas 4-6)'),
                'MVP' => t('MVP (Semanas 7-9)'),
                'TRACCION' => t('Tracción (Semanas 10-12)'),
            ])
            ->setDefaultValue('INVENTARIO')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['program_start_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha Inicio del Programa'))
            ->setDescription(t('Fecha en que el emprendedor inició el programa.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['impact_points'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntos de Impacto'))
            ->setDescription(t('Puntos acumulados por actividades completadas.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['sector'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Sector'))
            ->setDescription(t('Sector de negocio del emprendedor.'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE);

        $fields['idea_description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción de la Idea'))
            ->setDescription(t('Descripción breve de la idea de negocio.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['nivel_tecnico'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Nivel Técnico'))
            ->setDescription(t('Nivel técnico del emprendedor (1-5).'))
            ->setDefaultValue(1);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Programa'))
            ->setDescription(t('Programa asociado (Andalucía +ei, etc).'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación del perfil.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

}
