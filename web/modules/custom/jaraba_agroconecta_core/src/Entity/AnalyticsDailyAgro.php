<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad AnalyticsDailyAgro.
 *
 * Métricas agregadas diarias del marketplace.
 * Se genera por cron job nocturno para cada tenant,
 * alimentando el dashboard operativo y las alertas.
 *
 * @ContentEntityType(
 *   id = "analytics_daily_agro",
 *   label = @Translation("Métricas Diarias Agro"),
 *   label_collection = @Translation("Métricas Diarias Agro"),
 *   label_singular = @Translation("métrica diaria agro"),
 *   label_plural = @Translation("métricas diarias agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\AnalyticsDailyAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\AnalyticsDailyAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "analytics_daily_agro",
 *   admin_permission = "administer agroconecta",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agro-analytics-daily",
 *     "canonical" = "/admin/content/agro-analytics-daily/{analytics_daily_agro}",
 *   },
 * )
 */
class AnalyticsDailyAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE);

        $fields['date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha'))
            ->setSetting('datetime_type', 'date')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // KPIs principales.
        $fields['gmv'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('GMV'))
            ->setDescription(t('Gross Merchandise Value — ventas brutas del día (€).'))
            ->setSetting('precision', 12)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        $fields['orders_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Pedidos'))
            ->setDefaultValue(0);

        $fields['orders_completed'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Pedidos completados'))
            ->setDefaultValue(0);

        $fields['orders_cancelled'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Pedidos cancelados'))
            ->setDefaultValue(0);

        $fields['aov'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('AOV'))
            ->setDescription(t('Average Order Value (€).'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        $fields['unique_buyers'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Compradores únicos'))
            ->setDefaultValue(0);

        $fields['new_users'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Nuevos usuarios'))
            ->setDefaultValue(0);

        $fields['active_producers'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Productores activos'))
            ->setDefaultValue(0);

        $fields['products_sold'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Productos vendidos'))
            ->setDefaultValue(0);

        $fields['conversion_rate'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Tasa de conversión'))
            ->setDescription(t('% de visitantes que completaron compra.'))
            ->setSetting('precision', 5)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        $fields['reviews_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Reseñas'))
            ->setDefaultValue(0);

        $fields['avg_rating'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Rating medio'))
            ->setSetting('precision', 3)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        $fields['shipping_revenue'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Ingresos envío'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        $fields['promotions_used'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Promociones usadas'))
            ->setDefaultValue(0);

        $fields['discount_total'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Descuentos aplicados'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDefaultValue(0);

        $fields['qr_scans'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Escaneos QR'))
            ->setDefaultValue(0);

        $fields['qr_leads'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Leads QR'))
            ->setDefaultValue(0);

        // Metadatos extra en JSON.
        $fields['extra_metrics'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Métricas extra'))
            ->setDescription(t('JSON con métricas adicionales (top products, categories, etc).'));

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getGmv(): float { return (float) ($this->get('gmv')->value ?? 0); }
    public function getOrdersCount(): int { return (int) ($this->get('orders_count')->value ?? 0); }
    public function getAov(): float { return (float) ($this->get('aov')->value ?? 0); }
    public function getConversionRate(): float { return (float) ($this->get('conversion_rate')->value ?? 0); }
}
