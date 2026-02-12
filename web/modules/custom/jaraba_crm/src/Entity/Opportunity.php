<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Oportunidad del CRM.
 *
 * Representa una oportunidad de venta en el pipeline comercial.
 * Las etapas se configuran desde YAML (Directriz #20).
 *
 * @ContentEntityType(
 *   id = "crm_opportunity",
 *   label = @Translation("Oportunidad"),
 *   label_collection = @Translation("Oportunidades"),
 *   label_singular = @Translation("oportunidad"),
 *   label_plural = @Translation("oportunidades"),
 *   handlers = {
 *     "list_builder" = "Drupal\jaraba_crm\OpportunityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_crm\Form\OpportunityForm",
 *       "add" = "Drupal\jaraba_crm\Form\OpportunityForm",
 *       "edit" = "Drupal\jaraba_crm\Form\OpportunityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_crm\OpportunityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "crm_opportunity",
 *   admin_permission = "administer crm entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/opportunities/{crm_opportunity}",
 *     "add-form" = "/admin/content/opportunities/add",
 *     "edit-form" = "/admin/content/opportunities/{crm_opportunity}/edit",
 *     "delete-form" = "/admin/content/opportunities/{crm_opportunity}/delete",
 *     "collection" = "/admin/content/opportunities",
 *   },
 *   field_ui_base_route = "entity.crm_opportunity.settings",
 * )
 */
class Opportunity extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     */
    /**
     * Valores BANT que cuentan como "maximo nivel" para el score.
     */
    private const BANT_MAX_VALUES = [
        'bant_budget' => 'approved',
        'bant_authority' => 'champion',
        'bant_need' => 'critical',
        'bant_timeline' => 'immediate',
    ];

    /**
     * {@inheritdoc}
     */
    public function preSave(EntityStorageInterface $storage): void
    {
        parent::preSave($storage);
        if (!$this->getOwnerId()) {
            $this->setOwnerId(\Drupal::currentUser()->id());
        }
        $this->set('bant_score', $this->computeBantScore());
    }

    /**
     * Computa el score BANT (0-4) contando criterios en nivel maximo.
     */
    public function computeBantScore(): int
    {
        $score = 0;
        foreach (self::BANT_MAX_VALUES as $field => $maxValue) {
            if ($this->hasField($field) && $this->get($field)->value === $maxValue) {
                $score++;
            }
        }
        return $score;
    }

    /**
     * Obtiene la etapa actual del pipeline.
     */
    public function getStage(): string
    {
        return $this->get('stage')->value ?? 'lead';
    }

    /**
     * Obtiene el score BANT.
     */
    public function getBantScore(): int
    {
        return (int) ($this->get('bant_score')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Nombre descriptivo de la oportunidad.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['contact_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Contacto'))
            ->setDescription(t('Contacto principal de la oportunidad.'))
            ->setSetting('target_type', 'crm_contact')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'entity_reference_label',
                'weight' => 0,
            ])
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['value'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Valor'))
            ->setDescription(t('Valor estimado de la oportunidad en euros.'))
            ->setSetting('precision', 12)
            ->setSetting('scale', 2)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'number_decimal',
                'weight' => 1,
                'settings' => [
                    'thousand_separator' => '.',
                    'decimal_separator' => ',',
                    'prefix_suffix' => TRUE,
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['stage'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Etapa'))
            ->setDescription(t('Etapa actual en el pipeline de ventas.'))
            ->setRequired(TRUE)
            ->setDefaultValue('lead')
            ->setSetting('allowed_values_function', 'jaraba_crm_get_opportunity_stage_values')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'list_default',
                'weight' => 2,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['probability'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Probabilidad'))
            ->setDescription(t('Probabilidad de cierre (0-100%).'))
            ->setDefaultValue(50)
            ->setSetting('min', 0)
            ->setSetting('max', 100)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'number_integer',
                'weight' => 3,
                'settings' => [
                    'suffix' => '%',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['expected_close'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha esperada de cierre'))
            ->setDescription(t('Fecha estimada para cerrar la oportunidad.'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'datetime_default',
                'weight' => 4,
            ])
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // BANT Qualification (Doc 186 §3).
        $fields['bant_budget'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('BANT: Presupuesto'))
            ->setDescription(t('Estado del presupuesto del prospecto.'))
            ->setDefaultValue('none')
            ->setSetting('allowed_values_function', 'jaraba_crm_get_bant_budget_values')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'list_default',
                'weight' => 5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['bant_authority'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('BANT: Autoridad'))
            ->setDescription(t('Nivel de autoridad del contacto principal.'))
            ->setDefaultValue('user')
            ->setSetting('allowed_values_function', 'jaraba_crm_get_bant_authority_values')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'list_default',
                'weight' => 6,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['bant_need'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('BANT: Necesidad'))
            ->setDescription(t('Nivel de necesidad identificada.'))
            ->setDefaultValue('none')
            ->setSetting('allowed_values_function', 'jaraba_crm_get_bant_need_values')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'list_default',
                'weight' => 7,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['bant_timeline'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('BANT: Timeline'))
            ->setDescription(t('Plazo estimado para la decisión de compra.'))
            ->setDefaultValue('none')
            ->setSetting('allowed_values_function', 'jaraba_crm_get_bant_timeline_values')
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'list_default',
                'weight' => 8,
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['bant_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('BANT Score'))
            ->setDescription(t('Puntuación BANT computada (0-4).'))
            ->setDefaultValue(0)
            ->setSetting('min', 0)
            ->setSetting('max', 4)
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'number_integer',
                'weight' => 9,
            ])
            ->setDisplayConfigurable('form', FALSE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['notes'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Notas'))
            ->setDescription(t('Notas sobre la oportunidad.'))
            ->setDisplayOptions('view', [
                'label' => 'above',
                'type' => 'text_default',
                'weight' => 10,
            ])
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'group')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 15,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Última modificación'));

        return $fields;
    }

}
