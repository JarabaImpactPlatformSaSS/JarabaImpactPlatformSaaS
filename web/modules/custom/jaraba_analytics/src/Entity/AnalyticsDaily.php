<?php

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad AnalyticsDaily.
 *
 * Métricas agregadas diarias precalculadas para dashboards rápidos.
 * Se genera mediante cron a las 02:00 UTC del día anterior.
 *
 * @ContentEntityType(
 *   id = "analytics_daily",
 *   label = @Translation("Analytics Daily"),
 *   label_collection = @Translation("Analytics Daily Metrics"),
 *   label_singular = @Translation("daily metric"),
 *   label_plural = @Translation("daily metrics"),
 *   label_count = @PluralTranslation(
 *     singular = "@count daily metric",
 *     plural = "@count daily metrics",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "analytics_daily",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class AnalyticsDaily extends ContentEntityBase implements ContentEntityInterface
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Tenant ID (multi-tenant isolation).
        $fields['tenant_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tenant ID'))
            ->setDescription(t('ID del tenant al que pertenecen estas métricas.'))
            ->setRequired(FALSE);

        // Fecha del agregado.
        $fields['date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha'))
            ->setDescription(t('Fecha del agregado.'))
            ->setSetting('datetime_type', 'date')
            ->setRequired(TRUE);

        // Total páginas vistas.
        $fields['page_views'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Páginas Vistas'))
            ->setDefaultValue(0);

        // Visitantes únicos.
        $fields['unique_visitors'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Visitantes Únicos'))
            ->setDefaultValue(0);

        // Sesiones totales.
        $fields['sessions'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Sesiones'))
            ->setDefaultValue(0);

        // Nuevos registros.
        $fields['new_users'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Nuevos Usuarios'))
            ->setDefaultValue(0);

        // Ingresos del día.
        $fields['total_revenue'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Ingresos Totales'))
            ->setSettings([
                'precision' => 12,
                'scale' => 2,
            ])
            ->setDefaultValue(0);

        // Número de pedidos.
        $fields['orders_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Pedidos'))
            ->setDefaultValue(0);

        // Ticket medio (computado).
        $fields['avg_order_value'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Ticket Medio'))
            ->setSettings([
                'precision' => 10,
                'scale' => 2,
            ])
            ->setDefaultValue(0);

        // Tasa de conversión (computado).
        $fields['conversion_rate'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Tasa de Conversión'))
            ->setSettings([
                'precision' => 5,
                'scale' => 4,
            ])
            ->setDefaultValue(0);

        // Tasa de rebote (computado).
        $fields['bounce_rate'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Tasa de Rebote'))
            ->setSettings([
                'precision' => 5,
                'scale' => 4,
            ])
            ->setDefaultValue(0);

        // Duración media de sesión (segundos).
        $fields['avg_session_duration'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duración Media Sesión'))
            ->setDescription(t('Duración media en segundos.'))
            ->setDefaultValue(0);

        // Top 10 páginas (JSON).
        $fields['top_pages'] = BaseFieldDefinition::create('map')
            ->setLabel(t('Top Páginas'))
            ->setDescription(t('Top 10 páginas más visitadas.'));

        // Top 10 referrers (JSON).
        $fields['top_referrers'] = BaseFieldDefinition::create('map')
            ->setLabel(t('Top Referrers'))
            ->setDescription(t('Top 10 fuentes de tráfico.'));

        // Distribución por dispositivo (JSON).
        $fields['device_breakdown'] = BaseFieldDefinition::create('map')
            ->setLabel(t('Distribución Dispositivos'))
            ->setDescription(t('Porcentaje por tipo de dispositivo.'));

        // Distribución geográfica (JSON).
        $fields['geo_breakdown'] = BaseFieldDefinition::create('map')
            ->setLabel(t('Distribución Geográfica'))
            ->setDescription(t('Porcentaje por ubicación.'));

        // Timestamp de creación.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        return $fields;
    }

}
