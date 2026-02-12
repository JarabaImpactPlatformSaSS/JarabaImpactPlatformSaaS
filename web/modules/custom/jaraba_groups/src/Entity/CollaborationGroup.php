<?php

declare(strict_types=1);

namespace Drupal\jaraba_groups\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Collaboration Group.
 *
 * Grupo de colaboración para emprendedores según Doc 34.
 *
 * SPEC: 34_Emprendimiento_Collaboration_Groups_v1
 *
 * @ContentEntityType(
 *   id = "collaboration_group",
 *   label = @Translation("Grupo de Colaboración"),
 *   label_collection = @Translation("Grupos de Colaboración"),
 *   label_singular = @Translation("grupo"),
 *   label_plural = @Translation("grupos"),
 *   label_count = @PluralTranslation(
 *     singular = "@count grupo",
 *     plural = "@count grupos",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_groups\CollaborationGroupListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_groups\Form\CollaborationGroupForm",
 *       "add" = "Drupal\jaraba_groups\Form\CollaborationGroupForm",
 *       "edit" = "Drupal\jaraba_groups\Form\CollaborationGroupForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_groups\Access\CollaborationGroupAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "collaboration_group",
 *   admin_permission = "administer collaboration groups",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/groups",
 *     "add-form" = "/admin/content/groups/add",
 *     "canonical" = "/admin/content/groups/{collaboration_group}",
 *     "edit-form" = "/admin/content/groups/{collaboration_group}/edit",
 *     "delete-form" = "/admin/content/groups/{collaboration_group}/delete",
 *   },
 *   field_ui_base_route = "entity.collaboration_group.settings",
 * )
 */
class CollaborationGroup extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Group types.
     */
    public const TYPE_PROGRAM_COHORT = 'program_cohort';
    public const TYPE_SECTOR_GROUP = 'sector_group';
    public const TYPE_TERRITORIAL_GROUP = 'territorial_group';
    public const TYPE_INTEREST_GROUP = 'interest_group';
    public const TYPE_MASTERMIND = 'mastermind';

    /**
     * Visibility options.
     */
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_MEMBERS_ONLY = 'members_only';
    public const VISIBILITY_SECRET = 'secret';

    /**
     * Join policy options.
     */
    public const JOIN_OPEN = 'open';
    public const JOIN_APPROVAL = 'approval';
    public const JOIN_INVITATION = 'invitation';

    /**
     * Gets the group name.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Gets the group type.
     */
    public function getGroupType(): string
    {
        return $this->get('group_type')->value ?? self::TYPE_INTEREST_GROUP;
    }

    /**
     * Gets the visibility setting.
     */
    public function getVisibility(): string
    {
        return $this->get('visibility')->value ?? self::VISIBILITY_PUBLIC;
    }

    /**
     * Gets the join policy.
     */
    public function getJoinPolicy(): string
    {
        return $this->get('join_policy')->value ?? self::JOIN_OPEN;
    }

    /**
     * Gets the tenant ID.
     */
    public function getTenantId(): ?int
    {
        $value = $this->get('tenant_id')->target_id;
        return $value ? (int) $value : NULL;
    }

    /**
     * Gets the sector (for sector groups).
     */
    public function getSector(): ?string
    {
        return $this->get('sector')->value;
    }

    /**
     * Gets the territory (for territorial groups).
     */
    public function getTerritory(): ?string
    {
        return $this->get('territory')->value;
    }

    /**
     * Gets the maximum members allowed.
     */
    public function getMaxMembers(): ?int
    {
        $value = $this->get('max_members')->value;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * Checks if group is featured.
     */
    public function isFeatured(): bool
    {
        return (bool) $this->get('is_featured')->value;
    }

    /**
     * Gets the activity score.
     */
    public function getActivityScore(): int
    {
        return (int) $this->get('activity_score')->value;
    }

    /**
     * Increments activity score.
     */
    public function incrementActivityScore(int $points = 1): self
    {
        $this->set('activity_score', $this->getActivityScore() + $points);
        return $this;
    }

    /**
     * Gets the member count (computed from memberships).
     */
    public function getMemberCount(): int
    {
        $storage = \Drupal::entityTypeManager()->getStorage('group_membership');
        return (int) $storage->getQuery()
            ->condition('group_id', $this->id())
            ->condition('status', 'active')
            ->accessCheck(FALSE)
            ->count()
            ->execute();
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Grupo'))
            ->setDescription(t('Nombre visible del grupo.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción del propósito del grupo.'))
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['group_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Grupo'))
            ->setDescription(t('Categoría del grupo.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_PROGRAM_COHORT => t('Cohorte de Programa'),
                self::TYPE_SECTOR_GROUP => t('Grupo Sectorial'),
                self::TYPE_TERRITORIAL_GROUP => t('Grupo Territorial'),
                self::TYPE_INTEREST_GROUP => t('Grupo de Interés'),
                self::TYPE_MASTERMIND => t('Mastermind'),
            ])
            ->setDefaultValue(self::TYPE_INTEREST_GROUP)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Imagen de Portada'))
            ->setDescription(t('Imagen representativa del grupo.'))
            ->setSetting('file_extensions', 'png jpg jpeg webp')
            ->setSetting('alt_field', TRUE)
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Organización a la que pertenece el grupo.'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['sector'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Sector'))
            ->setDescription(t('Sector para grupos sectoriales.'))
            ->setSetting('allowed_values', [
                'comercio' => t('Comercio'),
                'servicios' => t('Servicios'),
                'hosteleria' => t('Hostelería'),
                'agro' => t('Agroalimentario'),
                'tech' => t('Tecnología'),
                'industria' => t('Industria'),
                'otros' => t('Otros'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['territory'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Territorio'))
            ->setDescription(t('Comarca o provincia para grupos territoriales.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['program_edition_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Edición de Programa'))
            ->setDescription(t('Para cohortes, la edición del programa asociada.'))
            ->setSetting('target_type', 'node')
            ->setDisplayConfigurable('form', TRUE);

        $fields['visibility'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Visibilidad'))
            ->setDescription(t('Quién puede ver el grupo.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::VISIBILITY_PUBLIC => t('Público'),
                self::VISIBILITY_MEMBERS_ONLY => t('Solo miembros'),
                self::VISIBILITY_SECRET => t('Secreto'),
            ])
            ->setDefaultValue(self::VISIBILITY_PUBLIC)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['join_policy'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Política de Ingreso'))
            ->setDescription(t('Cómo se unen nuevos miembros.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::JOIN_OPEN => t('Abierto'),
                self::JOIN_APPROVAL => t('Requiere aprobación'),
                self::JOIN_INVITATION => t('Solo por invitación'),
            ])
            ->setDefaultValue(self::JOIN_OPEN)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['max_members'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Máximo de Miembros'))
            ->setDescription(t('Límite de miembros (dejar vacío para ilimitado).'))
            ->setSetting('min', 1)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 12,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['start_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Inicio'))
            ->setDescription(t('Cuándo comienza la actividad del grupo.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 13,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['end_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Fin'))
            ->setDescription(t('Para grupos temporales, cuándo termina.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 14,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacado'))
            ->setDescription(t('Mostrar en listados destacados.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['activity_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntuación de Actividad'))
            ->setDescription(t('Calculado automáticamente según la actividad del grupo.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado del grupo.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'active' => t('Activo'),
                'archived' => t('Archivado'),
                'suspended' => t('Suspendido'),
            ])
            ->setDefaultValue('active')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
