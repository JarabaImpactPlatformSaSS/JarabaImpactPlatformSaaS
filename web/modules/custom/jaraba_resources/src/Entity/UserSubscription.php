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
 * Define la entidad User Subscription.
 *
 * Suscripción de un usuario a un plan de membresía.
 *
 * @ContentEntityType(
 *   id = "user_subscription",
 *   label = @Translation("Suscripción de Usuario"),
 *   label_collection = @Translation("Suscripciones"),
 *   label_singular = @Translation("suscripción"),
 *   label_plural = @Translation("suscripciones"),
 *   label_count = @PluralTranslation(
 *     singular = "@count suscripción",
 *     plural = "@count suscripciones",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_resources\UserSubscriptionListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_resources\Form\UserSubscriptionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\jaraba_resources\Access\UserSubscriptionAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "user_subscription",
 *   admin_permission = "administer membership plans",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/subscriptions",
 *     "canonical" = "/admin/content/subscriptions/{user_subscription}",
 *     "delete-form" = "/admin/content/subscriptions/{user_subscription}/delete",
 *   },
 *   field_ui_base_route = "entity.user_subscription.settings",
 * )
 */
class UserSubscription extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Subscription statuses.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TRIAL = 'trial';

    /**
     * Gets the plan ID.
     */
    public function getPlanId(): int
    {
        return (int) $this->get('plan_id')->target_id;
    }

    /**
     * Gets the plan entity.
     */
    public function getPlan(): ?MembershipPlan
    {
        return $this->get('plan_id')->entity;
    }

    /**
     * Gets the subscription status.
     */
    public function getSubscriptionStatus(): string
    {
        return $this->get('subscription_status')->value ?? self::STATUS_ACTIVE;
    }

    /**
     * Checks if subscription is active.
     */
    public function isActive(): bool
    {
        return in_array($this->getSubscriptionStatus(), [
            self::STATUS_ACTIVE,
            self::STATUS_TRIAL,
        ]);
    }

    /**
     * Gets the current period end date.
     */
    public function getCurrentPeriodEnd(): ?string
    {
        return $this->get('current_period_end')->value;
    }

    /**
     * Checks if subscription has expired.
     */
    public function hasExpired(): bool
    {
        $endDate = $this->getCurrentPeriodEnd();
        if (!$endDate) {
            return FALSE;
        }
        return strtotime($endDate) < time();
    }

    /**
     * Gets the Stripe subscription ID.
     */
    public function getStripeSubscriptionId(): ?string
    {
        return $this->get('stripe_subscription_id')->value;
    }

    /**
     * Gets remaining mentoring sessions this month.
     */
    public function getRemainingMentoringSessions(): int
    {
        $plan = $this->getPlan();
        if (!$plan) {
            return 0;
        }

        $max = $plan->getMaxMentoringSessions();
        if ($max === NULL) {
            return PHP_INT_MAX; // Unlimited.
        }

        $used = (int) $this->get('mentoring_sessions_used')->value;
        return max(0, $max - $used);
    }

    /**
     * Increments used mentoring sessions.
     */
    public function useMentoringSession(): self
    {
        $used = (int) $this->get('mentoring_sessions_used')->value;
        $this->set('mentoring_sessions_used', $used + 1);
        return $this;
    }

    /**
     * Resets monthly usage counters.
     */
    public function resetMonthlyUsage(): self
    {
        $this->set('mentoring_sessions_used', 0);
        $this->set('usage_reset_at', date('Y-m-d\TH:i:s'));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['plan_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Plan'))
            ->setDescription(t('Plan de membresía suscrito.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'membership_plan')
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['subscription_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDescription(t('Estado de la suscripción.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::STATUS_ACTIVE => t('Activa'),
                self::STATUS_TRIAL => t('Período de Prueba'),
                self::STATUS_PAST_DUE => t('Pago Pendiente'),
                self::STATUS_CANCELLED => t('Cancelada'),
                self::STATUS_EXPIRED => t('Expirada'),
            ])
            ->setDefaultValue(self::STATUS_ACTIVE)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['stripe_subscription_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Subscription ID'))
            ->setDescription(t('ID de la suscripción en Stripe.'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE);

        $fields['stripe_customer_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Customer ID'))
            ->setDescription(t('ID del cliente en Stripe.'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE);

        $fields['current_period_start'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Inicio del Período'))
            ->setDescription(t('Inicio del período de facturación actual.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['current_period_end'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fin del Período'))
            ->setDescription(t('Fin del período de facturación actual.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['trial_end'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fin del Período de Prueba'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['cancelled_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Cancelación'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['mentoring_sessions_used'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Sesiones de Mentoría Usadas'))
            ->setDescription(t('Sesiones usadas este mes.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['usage_reset_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Último Reset de Uso'))
            ->setDescription(t('Cuándo se reiniciaron los contadores mensuales.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
