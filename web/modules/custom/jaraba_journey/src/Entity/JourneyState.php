<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the JourneyState entity.
 *
 * Almacena el estado del journey de cada usuario según Doc 103.
 * Implementa los 7 estados: Discovery, Activation, Engagement,
 * Conversion, Retention, Expansion, Advocacy, At-Risk.
 *
 * @ContentEntityType(
 *   id = "journey_state",
 *   label = @Translation("Journey State"),
 *   label_collection = @Translation("Journey States"),
 *   label_singular = @Translation("journey state"),
 *   label_plural = @Translation("journey states"),
 *   label_count = @PluralTranslation(
 *     singular = "@count journey state",
 *     plural = "@count journey states",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "journey_state",
 *   admin_permission = "administer journey states",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "collection" = "/admin/content/journey-states",
 *     "canonical" = "/admin/content/journey-states/{journey_state}",
 *     "edit-form" = "/admin/content/journey-states/{journey_state}/edit",
 *     "delete-form" = "/admin/content/journey-states/{journey_state}/delete",
 *   },
 * )
 */
class JourneyState extends ContentEntityBase implements JourneyStateInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Los 7 estados de journey según Doc 103.
     */
    const STATES = [
        'discovery' => 'Discovery',
        'activation' => 'Activation',
        'engagement' => 'Engagement',
        'conversion' => 'Conversion',
        'retention' => 'Retention',
        'expansion' => 'Expansion',
        'advocacy' => 'Advocacy',
        'at_risk' => 'At-Risk',
    ];

    /**
     * Los 19 avatares por vertical.
     */
    const AVATARS = [
        // AgroConecta
        'productor' => 'Productor Agrícola',
        'comprador_b2b' => 'Comprador B2B',
        'consumidor' => 'Consumidor Final',
        // ComercioConecta
        'comerciante' => 'Comerciante Local',
        'comprador_local' => 'Comprador Local',
        // ServiciosConecta
        'profesional' => 'Profesional',
        'cliente_servicios' => 'Cliente',
        // Empleabilidad
        'job_seeker' => 'Job Seeker',
        'employer' => 'Employer',
        'orientador' => 'Orientador',
        // Emprendimiento
        'emprendedor' => 'Emprendedor',
        'mentor' => 'Mentor',
        'gestor_programa' => 'Gestor de Programa',
        // Andalucía +ei
        'beneficiario_ei' => 'Beneficiario +ei',
        'tecnico_sto' => 'Técnico STO',
        'admin_ei' => 'Admin Programa',
        // Certificación
        'estudiante' => 'Estudiante',
        'formador' => 'Formador',
        'admin_lms' => 'Admin LMS',
    ];

    /**
     * {@inheritdoc}
     */
    public function label(): ?string
    {
        $avatar = self::AVATARS[$this->getAvatarType()] ?? $this->getAvatarType();
        $state = self::STATES[$this->getJourneyState()] ?? $this->getJourneyState();
        return "{$avatar} - {$state}";
    }

    /**
     * {@inheritdoc}
     */
    public function getAvatarType(): string
    {
        return $this->get('avatar_type')->value ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getJourneyState(): string
    {
        return $this->get('journey_state')->value ?? 'discovery';
    }

    /**
     * {@inheritdoc}
     */
    public function setJourneyState(string $state): self
    {
        $this->set('journey_state', $state);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentStep(): int
    {
        return (int) ($this->get('current_step')->value ?? 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompletedSteps(): array
    {
        $value = $this->get('completed_steps')->value ?? '[]';
        return json_decode($value, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        $value = $this->get('context_data')->value ?? '{}';
        return json_decode($value, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(array $context): self
    {
        $this->set('context_data', json_encode($context));
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingTriggers(): array
    {
        $value = $this->get('pending_triggers')->value ?? '[]';
        return json_decode($value, TRUE) ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Usuario propietario
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The user whose journey state this is.'))
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de avatar (19 posibles)
        $fields['avatar_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Avatar Type'))
            ->setDescription(t('The user avatar/role in the platform.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', self::AVATARS)
            ->setDefaultValue('emprendedor')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado del journey (7 + At-Risk)
        $fields['journey_state'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Journey State'))
            ->setDescription(t('Current state in the user journey.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', self::STATES)
            ->setDefaultValue('discovery')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 1,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Paso actual dentro del estado
        $fields['current_step'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Current Step'))
            ->setDescription(t('Current step number within the journey state.'))
            ->setDefaultValue(1)
            ->setSetting('min', 1)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Pasos completados (JSON array)
        $fields['completed_steps'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Completed Steps'))
            ->setDescription(t('JSON array of completed step numbers.'))
            ->setDefaultValue('[]');

        // Contexto del usuario (JSON)
        $fields['context_data'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Context Data'))
            ->setDescription(t('JSON object with user context: last_action, time_in_state, interactions, risk_score.'))
            ->setDefaultValue('{}');

        // Triggers pendientes (JSON array)
        $fields['pending_triggers'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Pending Triggers'))
            ->setDescription(t('JSON array of pending IA intervention triggers.'))
            ->setDefaultValue('[]');

        // Vertical asociada
        $fields['vertical'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Vertical'))
            ->setDescription(t('The vertical this journey belongs to.'))
            ->setSetting('allowed_values', [
                'agroconecta' => 'AgroConecta',
                'comercioconecta' => 'ComercioConecta',
                'serviciosconecta' => 'ServiciosConecta',
                'empleabilidad' => 'Empleabilidad',
                'emprendimiento' => 'Emprendimiento',
                'andalucia_ei' => 'Andalucía +ei',
                'certificacion' => 'Certificación',
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Timestamps
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time that the journey state was created.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Changed'))
            ->setDescription(t('The time that the journey state was last edited.'));

        // Última transición de estado
        $fields['last_transition'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Last Transition'))
            ->setDescription(t('When the user last changed journey state.'));

        return $fields;
    }

}
