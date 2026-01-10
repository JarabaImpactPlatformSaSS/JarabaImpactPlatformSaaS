<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad de contenido Plan de Suscripción SaaS.
 *
 * Un Plan define los límites y características disponibles para un Tenant.
 *
 * @ContentEntityType(
 *   id = "saas_plan",
 *   label = @Translation("Plan SaaS"),
 *   label_collection = @Translation("Planes SaaS"),
 *   label_singular = @Translation("plan SaaS"),
 *   label_plural = @Translation("planes SaaS"),
 *   label_count = @PluralTranslation(
 *     singular = "@count plan SaaS",
 *     plural = "@count planes SaaS",
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\SaasPlanListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\ecosistema_jaraba_core\Form\SaasPlanForm",
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\SaasPlanForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\SaasPlanForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\ecosistema_jaraba_core\SaasPlanAccessControlHandler",
 *   },
 *   base_table = "saas_plan",
 *   data_table = "saas_plan_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer saas plans",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/saas-plan",
 *     "add-form" = "/admin/structure/saas-plan/add",
 *     "canonical" = "/admin/structure/saas-plan/{saas_plan}",
 *     "edit-form" = "/admin/structure/saas-plan/{saas_plan}/edit",
 *     "delete-form" = "/admin/structure/saas-plan/{saas_plan}/delete",
 *   },
 *   field_ui_base_route = "entity.saas_plan.collection",
 * )
 */
class SaasPlan extends ContentEntityBase implements SaasPlanInterface
{

    use EntityChangedTrait;

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
    public function getVertical(): ?VerticalInterface
    {
        $vertical = $this->get('vertical')->entity;
        return $vertical instanceof VerticalInterface ? $vertical : NULL;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceMonthly(): float
    {
        return (float) ($this->get('price_monthly')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getPriceYearly(): float
    {
        return (float) ($this->get('price_yearly')->value ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function getLimits(): array
    {
        $limits = $this->get('limits')->value;
        if (is_string($limits)) {
            return json_decode($limits, TRUE) ?? [];
        }
        return $limits ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit(string $key, $default = 0)
    {
        $limits = $this->getLimits();
        return $limits[$key] ?? $default;
    }

    /**
     * {@inheritdoc}
     */
    public function getFeatures(): array
    {
        $features = $this->get('features')->getValue();
        return array_column($features, 'value');
    }

    /**
     * {@inheritdoc}
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->getFeatures(), TRUE);
    }

    /**
     * {@inheritdoc}
     */
    public function getStripePriceId(): ?string
    {
        return $this->get('stripe_price_id')->value;
    }

    /**
     * {@inheritdoc}
     */
    public function isFree(): bool
    {
        return $this->getPriceMonthly() <= 0;
    }

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Nombre del plan.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre del plan (ej: Profesional).'))
            ->setRequired(TRUE)
            ->setTranslatable(TRUE)
            ->setSetting('max_length', 100)
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => -5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Vertical asociada (opcional - NULL = todas).
        $fields['vertical'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Vertical'))
            ->setDescription(t('Vertical a la que aplica este plan. Vacío = todas.'))
            ->setSetting('target_type', 'vertical')
            ->setSetting('handler', 'default')
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

        // Precio mensual.
        $fields['price_monthly'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Precio Mensual (€)'))
            ->setDescription(t('Precio mensual en euros.'))
            ->setRequired(TRUE)
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 5,
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Precio anual.
        $fields['price_yearly'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Precio Anual (€)'))
            ->setDescription(t('Precio anual en euros (con descuento).'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_decimal',
                'weight' => 6,
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Features incluidas.
        $fields['features'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Features Incluidas'))
            ->setDescription(t('Funcionalidades incluidas en el plan.'))
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setSetting('allowed_values', [
                'trazabilidad_basica' => 'Trazabilidad básica',
                'trazabilidad_avanzada' => 'Trazabilidad avanzada',
                'agentes_ia_limitados' => 'Agentes IA (limitados)',
                'agentes_ia_completos' => 'Agentes IA (completos)',
                'firma_digital' => 'Firma digital',
                'webhooks' => 'Webhooks',
                'api_access' => 'Acceso API',
                'soporte_email' => 'Soporte por email',
                'soporte_chat' => 'Soporte por chat',
                'soporte_telefono' => 'Soporte telefónico',
                'soporte_dedicado' => 'Soporte dedicado',
                'analiticas_basicas' => 'Analíticas básicas',
                'analiticas_avanzadas' => 'Analíticas avanzadas',
                'dominio_personalizado' => 'Dominio personalizado',
                'marca_blanca' => 'Marca blanca',
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_buttons',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Límites (JSON).
        $fields['limits'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Límites'))
            ->setDescription(t('JSON con límites: {productores: 50, storage_gb: 25, ai_queries: 100}'))
            ->setDefaultValue('{"productores": 10, "storage_gb": 5, "ai_queries": 0}')
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 15,
                'settings' => [
                    'rows' => 5,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Stripe Price ID.
        $fields['stripe_price_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Stripe Price ID'))
            ->setDescription(t('ID del precio en Stripe (ej: price_1234567890).'))
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Orden de visualización.
        $fields['weight'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Orden'))
            ->setDescription(t('Orden de visualización en la lista de planes.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 25,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Estado activo.
        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDescription(t('Indica si el plan está disponible para nuevos clientes.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 30,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de creación'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Fecha de modificación'));

        return $fields;
    }

}
