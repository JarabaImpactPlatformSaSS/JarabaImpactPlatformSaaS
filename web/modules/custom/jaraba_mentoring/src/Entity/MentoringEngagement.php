<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Mentoring Engagement.
 *
 * Representa una relación activa entre mentor y emprendedor.
 * Se crea al comprar un paquete y gestiona las sesiones disponibles.
 *
 * SPEC: 31_Emprendimiento_Mentoring_Core_v1
 *
 * @ContentEntityType(
 *   id = "mentoring_engagement",
 *   label = @Translation("Engagement de Mentoría"),
 *   label_collection = @Translation("Engagements de Mentoría"),
 *   label_singular = @Translation("engagement de mentoría"),
 *   label_plural = @Translation("engagements de mentoría"),
 *   label_count = @PluralTranslation(
 *     singular = "@count engagement",
 *     plural = "@count engagements",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_mentoring\MentoringEngagementListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_mentoring\Form\MentoringEngagementForm",
 *       "edit" = "Drupal\jaraba_mentoring\Form\MentoringEngagementForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_mentoring\MentoringEngagementAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "mentoring_engagement",
 *   admin_permission = "manage engagements",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "id",
 *     "owner" = "mentee_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/mentoring-engagements",
 *     "add-form" = "/admin/content/mentoring-engagements/add",
 *     "canonical" = "/admin/content/mentoring-engagement/{mentoring_engagement}",
 *     "edit-form" = "/admin/content/mentoring-engagement/{mentoring_engagement}/edit",
 *     "delete-form" = "/admin/content/mentoring-engagement/{mentoring_engagement}/delete",
 *   },
 *   field_ui_base_route = "entity.mentoring_engagement.settings",
 * )
 */
class MentoringEngagement extends ContentEntityBase implements EntityOwnerInterface
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

        // === Relaciones ===
        $fields['mentor_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Mentor'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'mentor_profile')
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayConfigurable('view', TRUE);

        $fields['mentee_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Emprendedor'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayConfigurable('view', TRUE);

        $fields['package_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Paquete'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'mentoring_package')
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayConfigurable('view', TRUE);

        // === Pago ===
        $fields['order_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID de Orden'))
            ->setDescription(t('Referencia a commerce_order o ID de transacción.'))
            ->setSetting('max_length', 64);

        $fields['payment_intent_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Payment Intent'))
            ->setSetting('max_length', 64);

        $fields['amount_paid'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Importe Pagado (€)'))
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('view', TRUE);

        // === Sesiones ===
        $fields['sessions_total'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Sesiones Totales'))
            ->setRequired(TRUE)
            ->setDefaultValue(1)
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayConfigurable('view', TRUE);

        $fields['sessions_used'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Sesiones Usadas'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 6])
            ->setDisplayConfigurable('view', TRUE);

        $fields['sessions_remaining'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Sesiones Restantes'))
            ->setDefaultValue(1)
            ->setDisplayOptions('view', ['weight' => 7])
            ->setDisplayConfigurable('view', TRUE);

        // === Fechas ===
        $fields['start_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Inicio'))
            ->setRequired(TRUE)
            ->setDisplayOptions('view', ['weight' => 10])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['expiry_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Expiración'))
            ->setDisplayOptions('view', ['weight' => 11])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 11,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Vinculación con Diagnóstico ===
        $fields['business_diagnostic_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Diagnóstico Asociado'))
            ->setSetting('target_type', 'business_diagnostic')
            ->setDisplayConfigurable('view', TRUE);

        // === Estado ===
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'pending' => 'Pendiente de Pago',
                'active' => 'Activo',
                'paused' => 'Pausado',
                'completed' => 'Completado',
                'expired' => 'Expirado',
                'cancelled' => 'Cancelado',
            ])
            ->setDefaultValue('pending')
            ->setDisplayOptions('view', ['weight' => 15])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Notas internas ===
        $fields['goals'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Objetivos'))
            ->setDescription(t('Objetivos del emprendedor para esta mentoría.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // === Timestamps ===
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Gets remaining sessions count.
     */
    public function getSessionsRemaining(): int
    {
        return (int) ($this->get('sessions_remaining')->value ?? 0);
    }

    /**
     * Checks if the engagement is active.
     */
    public function isActive(): bool
    {
        return $this->get('status')->value === 'active';
    }

    /**
     * Decrements remaining sessions.
     */
    public function useSession(): self
    {
        $remaining = $this->getSessionsRemaining();
        if ($remaining > 0) {
            $this->set('sessions_remaining', $remaining - 1);
            $this->set('sessions_used', ((int) $this->get('sessions_used')->value) + 1);
        }
        return $this;
    }

}
