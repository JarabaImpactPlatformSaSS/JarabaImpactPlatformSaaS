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
 * Define la entidad Group Membership.
 *
 * Membresía de usuario en un grupo con rol asignado.
 *
 * @ContentEntityType(
 *   id = "group_membership",
 *   label = @Translation("Membresía de Grupo"),
 *   label_collection = @Translation("Membresías de Grupo"),
 *   label_singular = @Translation("membresía"),
 *   label_plural = @Translation("membresías"),
 *   label_count = @PluralTranslation(
 *     singular = "@count membresía",
 *     plural = "@count membresías",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_groups\GroupMembershipListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_groups\Form\GroupMembershipForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_groups\Access\GroupMembershipAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "group_membership",
 *   admin_permission = "administer collaboration groups",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/group-memberships",
 *     "delete-form" = "/admin/content/group-memberships/{group_membership}/delete",
 *   },
 *   field_ui_base_route = "entity.group_membership.settings",
 * )
 */
class GroupMembership extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Membership roles within a group.
     */
    public const ROLE_ADMIN = 'group_admin';
    public const ROLE_MODERATOR = 'group_moderator';
    public const ROLE_FACILITATOR = 'group_facilitator';
    public const ROLE_MENTOR = 'group_mentor';
    public const ROLE_MEMBER = 'group_member';
    public const ROLE_GUEST = 'group_guest';

    /**
     * Membership statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_LEFT = 'left';

    /**
     * Gets the group ID.
     */
    public function getGroupId(): int
    {
        return (int) $this->get('group_id')->target_id;
    }

    /**
     * Gets the group entity.
     */
    public function getGroup(): ?CollaborationGroup
    {
        return $this->get('group_id')->entity;
    }

    /**
     * Gets the membership role.
     */
    public function getRole(): string
    {
        return $this->get('role')->value ?? self::ROLE_MEMBER;
    }

    /**
     * Sets the membership role.
     */
    public function setRole(string $role): self
    {
        $this->set('role', $role);
        return $this;
    }

    /**
     * Gets the membership status.
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? self::STATUS_PENDING;
    }

    /**
     * Sets the membership status.
     */
    public function setStatus(string $status): self
    {
        $this->set('status', $status);
        return $this;
    }

    /**
     * Checks if membership is active.
     */
    public function isActive(): bool
    {
        return $this->getStatus() === self::STATUS_ACTIVE;
    }

    /**
     * Checks if user has admin role.
     */
    public function isAdmin(): bool
    {
        return $this->getRole() === self::ROLE_ADMIN;
    }

    /**
     * Checks if user can moderate content.
     */
    public function canModerate(): bool
    {
        return in_array($this->getRole(), [
            self::ROLE_ADMIN,
            self::ROLE_MODERATOR,
            self::ROLE_FACILITATOR,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['group_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Grupo'))
            ->setDescription(t('Grupo al que pertenece esta membresía.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'collaboration_group')
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['role'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Rol'))
            ->setDescription(t('Rol del usuario dentro del grupo.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::ROLE_ADMIN => t('Administrador'),
                self::ROLE_MODERATOR => t('Moderador'),
                self::ROLE_FACILITATOR => t('Facilitador'),
                self::ROLE_MENTOR => t('Mentor'),
                self::ROLE_MEMBER => t('Miembro'),
                self::ROLE_GUEST => t('Invitado'),
            ])
            ->setDefaultValue(self::ROLE_MEMBER)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado de la membresía.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::STATUS_PENDING => t('Pendiente'),
                self::STATUS_ACTIVE => t('Activo'),
                self::STATUS_SUSPENDED => t('Suspendido'),
                self::STATUS_LEFT => t('Dejó el grupo'),
            ])
            ->setDefaultValue(self::STATUS_PENDING)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['joined_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Ingreso'))
            ->setDescription(t('Cuándo se unió al grupo.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['invited_by'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Invitado por'))
            ->setDescription(t('Usuario que invitó a este miembro.'))
            ->setSetting('target_type', 'user')
            ->setDisplayConfigurable('view', TRUE);

        $fields['notes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Notas'))
            ->setDescription(t('Notas internas sobre el miembro.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
