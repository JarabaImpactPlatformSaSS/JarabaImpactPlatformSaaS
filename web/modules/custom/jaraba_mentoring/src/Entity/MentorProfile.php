<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Mentor Profile.
 *
 * Representa el perfil de un mentor certificado en el ecosistema.
 * Extiende el usuario con información especializada para el marketplace.
 *
 * SPEC: 31_Emprendimiento_Mentoring_Core_v1
 *
 * @ContentEntityType(
 *   id = "mentor_profile",
 *   label = @Translation("Perfil de Mentor"),
 *   label_collection = @Translation("Perfiles de Mentores"),
 *   label_singular = @Translation("perfil de mentor"),
 *   label_plural = @Translation("perfiles de mentores"),
 *   label_count = @PluralTranslation(
 *     singular = "@count perfil de mentor",
 *     plural = "@count perfiles de mentores",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_mentoring\MentorProfileListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_mentoring\Form\MentorProfileForm",
 *       "add" = "Drupal\jaraba_mentoring\Form\MentorProfileForm",
 *       "edit" = "Drupal\jaraba_mentoring\Form\MentorProfileForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_mentoring\MentorProfileAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "mentor_profile",
 *   admin_permission = "administer mentor profiles",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "display_name",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/mentors",
 *     "add-form" = "/admin/content/mentors/add",
 *     "canonical" = "/admin/content/mentor/{mentor_profile}",
 *     "edit-form" = "/admin/content/mentor/{mentor_profile}/edit",
 *     "delete-form" = "/admin/content/mentor/{mentor_profile}/delete",
 *   },
 *   field_ui_base_route = "entity.mentor_profile.settings",
 * )
 */
class MentorProfile extends ContentEntityBase implements MentorProfileInterface
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

        // === Información de Perfil ===
        $fields['display_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre a Mostrar'))
            ->setDescription(t('Nombre profesional del mentor.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 0])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['headline'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Titular'))
            ->setDescription(t('Frase corta que describe al mentor (ej: "Experto en eCommerce con 15 años").'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', ['weight' => 1])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['bio'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Biografía'))
            ->setDescription(t('Descripción extendida de experiencia y trayectoria.'))
            ->setDisplayOptions('view', ['weight' => 2])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['avatar'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Foto de Perfil'))
            ->setDescription(t('Imagen profesional del mentor.'))
            ->setSettings([
                'file_directory' => 'mentors/avatars',
                'alt_field_required' => FALSE,
                'file_extensions' => 'png jpg jpeg webp',
                'max_filesize' => '2 MB',
            ])
            ->setDisplayOptions('view', ['weight' => -1])
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Especialización ===
        $fields['specializations'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Especializaciones'))
            ->setDescription(t('Áreas de expertise (separadas por coma).'))
            ->setCardinality(-1)
            ->setDisplayOptions('view', ['weight' => 5])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['sectors'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Sectores'))
            ->setDescription(t('Sectores en los que tiene experiencia.'))
            ->setCardinality(-1)
            ->setSetting('allowed_values', [
                'comercio' => 'Comercio Local',
                'servicios' => 'Servicios Profesionales',
                'agro' => 'Agroalimentario',
                'hosteleria' => 'Hostelería y Turismo',
                'industria' => 'Industria',
                'tech' => 'Tecnología',
            ])
            ->setDisplayOptions('view', ['weight' => 6])
            ->setDisplayOptions('form', [
                'type' => 'options_buttons',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['business_stages'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Fases de Negocio'))
            ->setDescription(t('Fases de emprendimiento en las que puede ayudar.'))
            ->setCardinality(-1)
            ->setSetting('allowed_values', [
                'idea' => 'Idea / Validación',
                'lanzamiento' => 'Lanzamiento',
                'crecimiento' => 'Crecimiento',
                'escalado' => 'Escalado',
                'consolidacion' => 'Consolidación',
            ])
            ->setDisplayOptions('view', ['weight' => 7])
            ->setDisplayOptions('form', [
                'type' => 'options_buttons',
                'weight' => 7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === Tarifas ===
        $fields['hourly_rate'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Tarifa por Hora (€)'))
            ->setDescription(t('Tarifa base por hora de mentoría.'))
            ->setRequired(TRUE)
            ->setSetting('precision', 8)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', ['weight' => 10])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['currency'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Moneda'))
            ->setSetting('allowed_values', [
                'EUR' => 'EUR (€)',
            ])
            ->setDefaultValue('EUR')
            ->setDisplayConfigurable('form', TRUE);

        // === Stripe Connect ===
        $fields['stripe_account_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Account ID'))
            ->setDescription(t('ID de la cuenta Connect de Stripe.'))
            ->setSetting('max_length', 64);

        $fields['stripe_onboarding_complete'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Onboarding Stripe Completado'))
            ->setDefaultValue(FALSE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['platform_fee_percent'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Comisión Plataforma (%)'))
            ->setDescription(t('Porcentaje que retiene la plataforma.'))
            ->setDefaultValue(15)
            ->setSetting('min', 10)
            ->setSetting('max', 20)
            ->setDisplayConfigurable('form', TRUE);

        // === Certificación y Reputación ===
        $fields['certification_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Nivel de Certificación'))
            ->setSetting('allowed_values', [
                'base' => 'Base',
                'certified' => 'Certificado',
                'premium' => 'Premium',
                'elite' => 'Elite',
            ])
            ->setDefaultValue('base')
            ->setDisplayOptions('view', ['weight' => 15])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['average_rating'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Rating Promedio'))
            ->setSetting('precision', 3)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayOptions('view', ['weight' => 16])
            ->setDisplayConfigurable('view', TRUE);

        $fields['total_sessions'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total de Sesiones'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['total_reviews'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total de Reviews'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        // === Disponibilidad y Estado ===
        $fields['is_available'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Disponible'))
            ->setDescription(t('Indica si está aceptando nuevos clientes.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('view', ['weight' => 20])
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'pending' => 'Pendiente de Aprobación',
                'active' => 'Activo',
                'suspended' => 'Suspendido',
                'inactive' => 'Inactivo',
            ])
            ->setDefaultValue('pending')
            ->setDisplayOptions('view', ['weight' => 21])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 21,
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
                'weight' => 25,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // === Timestamps ===
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación del perfil.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

    // === Getters ===

    /**
     * {@inheritdoc}
     */
    public function getDisplayName(): string
    {
        return $this->get('display_name')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getHeadline(): string
    {
        return $this->get('headline')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getHourlyRate(): float
    {
        return (float) ($this->get('hourly_rate')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getAverageRating(): float
    {
        return (float) ($this->get('average_rating')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function hasStripeOnboarding(): bool
    {
        return (bool) $this->get('stripe_onboarding_complete')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return (bool) $this->get('is_available')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getCertificationLevel(): string
    {
        return $this->get('certification_level')->value ?? 'base';
    }

}
