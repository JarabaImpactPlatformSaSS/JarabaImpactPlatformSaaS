<?php

declare(strict_types=1);

namespace Drupal\jaraba_resources\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Membership Plan.
 *
 * Planes de suscripción para acceso a recursos premium.
 *
 * @ContentEntityType(
 *   id = "membership_plan",
 *   label = @Translation("Plan de Membresía"),
 *   label_collection = @Translation("Planes de Membresía"),
 *   label_singular = @Translation("plan"),
 *   label_plural = @Translation("planes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count plan",
 *     plural = "@count planes",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_resources\MembershipPlanListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_resources\Form\MembershipPlanForm",
 *       "add" = "Drupal\jaraba_resources\Form\MembershipPlanForm",
 *       "edit" = "Drupal\jaraba_resources\Form\MembershipPlanForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_resources\Access\MembershipPlanAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "membership_plan",
 *   admin_permission = "administer membership plans",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/membership-plans",
 *     "add-form" = "/admin/content/membership-plans/add",
 *     "canonical" = "/admin/content/membership-plans/{membership_plan}",
 *     "edit-form" = "/admin/content/membership-plans/{membership_plan}/edit",
 *     "delete-form" = "/admin/content/membership-plans/{membership_plan}/delete",
 *   },
 *   field_ui_base_route = "entity.membership_plan.settings",
 * )
 */
class MembershipPlan extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Plan types.
     */
    public const TYPE_FREE = 'free';
    public const TYPE_STARTER = 'starter';
    public const TYPE_PROFESSIONAL = 'professional';
    public const TYPE_ENTERPRISE = 'enterprise';

    /**
     * Billing intervals.
     */
    public const INTERVAL_MONTHLY = 'monthly';
    public const INTERVAL_QUARTERLY = 'quarterly';
    public const INTERVAL_YEARLY = 'yearly';
    public const INTERVAL_LIFETIME = 'lifetime';

    /**
     * Gets the plan name.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Gets the plan type.
     */
    public function getPlanType(): string
    {
        return $this->get('plan_type')->value ?? self::TYPE_FREE;
    }

    /**
     * Gets the price.
     */
    public function getPrice(): float
    {
        return (float) $this->get('price')->value;
    }

    /**
     * Gets the billing interval.
     */
    public function getBillingInterval(): string
    {
        return $this->get('billing_interval')->value ?? self::INTERVAL_MONTHLY;
    }

    /**
     * Gets the features as array.
     */
    public function getFeatures(): array
    {
        $value = $this->get('features')->value;
        return $value ? json_decode($value, TRUE) : [];
    }

    /**
     * Gets the kit access level.
     */
    public function getKitAccessLevel(): string
    {
        return $this->get('kit_access_level')->value ?? 'free';
    }

    /**
     * Gets max mentoring sessions per month.
     */
    public function getMaxMentoringSessions(): ?int
    {
        $value = $this->get('max_mentoring_sessions')->value;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * Gets max group memberships.
     */
    public function getMaxGroups(): ?int
    {
        $value = $this->get('max_groups')->value;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * Checks if AI features are included.
     */
    public function hasAiFeatures(): bool
    {
        return (bool) $this->get('has_ai_features')->value;
    }

    /**
     * Checks if priority support is included.
     */
    public function hasPrioritySupport(): bool
    {
        return (bool) $this->get('has_priority_support')->value;
    }

    /**
     * Gets Stripe Price ID for payment integration.
     */
    public function getStripePriceId(): ?string
    {
        return $this->get('stripe_price_id')->value;
    }

    /**
     * Checks if this is a higher tier than another plan.
     */
    public function isHigherThan(MembershipPlan $other): bool
    {
        $tiers = [
            self::TYPE_FREE => 0,
            self::TYPE_STARTER => 1,
            self::TYPE_PROFESSIONAL => 2,
            self::TYPE_ENTERPRISE => 3,
        ];

        return ($tiers[$this->getPlanType()] ?? 0) > ($tiers[$other->getPlanType()] ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Plan'))
            ->setDescription(t('Nombre comercial del plan.'))
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
            ->setDescription(t('Descripción del plan y sus beneficios.'))
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['plan_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Plan'))
            ->setDescription(t('Nivel del plan.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_FREE => t('Gratuito'),
                self::TYPE_STARTER => t('Starter'),
                self::TYPE_PROFESSIONAL => t('Professional'),
                self::TYPE_ENTERPRISE => t('Enterprise'),
            ])
            ->setDefaultValue(self::TYPE_FREE)
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['price'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Precio'))
            ->setDescription(t('Precio del plan (€).'))
            ->setRequired(TRUE)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 3])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['billing_interval'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Intervalo de Facturación'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::INTERVAL_MONTHLY => t('Mensual'),
                self::INTERVAL_QUARTERLY => t('Trimestral'),
                self::INTERVAL_YEARLY => t('Anual'),
                self::INTERVAL_LIFETIME => t('Pago único'),
            ])
            ->setDefaultValue(self::INTERVAL_MONTHLY)
            ->setDisplayOptions('view', ['weight' => 4])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['stripe_price_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Price ID'))
            ->setDescription(t('ID del precio en Stripe para pagos recurrentes.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['features'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Características'))
            ->setDescription(t('JSON con lista de características del plan.'))
            ->setDisplayConfigurable('form', TRUE);

        $fields['kit_access_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Acceso a Kits'))
            ->setDescription(t('Nivel de kits accesibles con este plan.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'free' => t('Solo gratuitos'),
                'starter' => t('Hasta Starter'),
                'professional' => t('Hasta Professional'),
                'enterprise' => t('Todos'),
            ])
            ->setDefaultValue('free')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['max_mentoring_sessions'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Sesiones de Mentoría/Mes'))
            ->setDescription(t('Máximo de sesiones incluidas por mes.'))
            ->setSetting('min', 0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['max_groups'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Grupos Máximos'))
            ->setDescription(t('Máximo de grupos a los que puede unirse.'))
            ->setSetting('min', 0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 12,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['has_ai_features'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Incluye IA'))
            ->setDescription(t('Acceso a funciones de IA (análisis de canvas, copiloto).'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['has_priority_support'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Soporte Prioritario'))
            ->setDescription(t('Acceso a soporte prioritario.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 16,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_featured'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Destacado'))
            ->setDescription(t('Resaltar en la página de precios.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 17,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['display_order'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Orden'))
            ->setDescription(t('Orden de visualización en la tabla de precios.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 18,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'active' => t('Activo'),
                'inactive' => t('Inactivo'),
                'deprecated' => t('Descontinuado'),
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
