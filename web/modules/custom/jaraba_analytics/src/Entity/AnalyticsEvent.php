<?php

namespace Drupal\jaraba_analytics\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad AnalyticsEvent.
 *
 * Almacena eventos individuales para análisis de comportamiento
 * y funnel de conversión.
 *
 * @ContentEntityType(
 *   id = "analytics_event",
 *   label = @Translation("Analytics Event"),
 *   label_collection = @Translation("Analytics Events"),
 *   label_singular = @Translation("analytics event"),
 *   label_plural = @Translation("analytics events"),
 *   label_count = @PluralTranslation(
 *     singular = "@count analytics event",
 *     plural = "@count analytics events",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "analytics_event",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 * )
 */
class AnalyticsEvent extends ContentEntityBase implements ContentEntityInterface
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Tenant ID (multi-tenant isolation).
        // Usamos integer simple para compatibilidad sin depender del módulo Group.
        $fields['tenant_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Tenant ID'))
            ->setDescription(t('ID del tenant al que pertenece este evento.'))
            ->setRequired(FALSE);

        // Tipo de evento.
        $fields['event_type'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Tipo de Evento'))
            ->setDescription(t('Tipo de evento: page_view, add_to_cart, purchase, etc.'))
            ->setSettings([
                'max_length' => 50,
            ])
            ->setRequired(TRUE);

        // Datos del evento (JSON).
        $fields['event_data'] = BaseFieldDefinition::create('map')
            ->setLabel(t('Datos del Evento'))
            ->setDescription(t('Datos específicos del evento en formato JSON.'));

        // Usuario (si está logueado).
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario'))
            ->setDescription(t('Usuario autenticado (si aplica).'))
            ->setSetting('target_type', 'user');

        // Session ID.
        $fields['session_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Session ID'))
            ->setDescription(t('ID de sesión único.'))
            ->setSettings([
                'max_length' => 64,
            ])
            ->setRequired(TRUE);

        // Visitor ID (cookie).
        $fields['visitor_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Visitor ID'))
            ->setDescription(t('ID del visitante (cookie persistente).'))
            ->setSettings([
                'max_length' => 64,
            ])
            ->setRequired(TRUE);

        // Tipo de dispositivo.
        $fields['device_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Dispositivo'))
            ->setDescription(t('Tipo de dispositivo.'))
            ->setSettings([
                'allowed_values' => [
                    'desktop' => 'Desktop',
                    'mobile' => 'Mobile',
                    'tablet' => 'Tablet',
                ],
            ]);

        // Navegador.
        $fields['browser'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Navegador'))
            ->setSettings([
                'max_length' => 50,
            ]);

        // Sistema operativo.
        $fields['os'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Sistema Operativo'))
            ->setSettings([
                'max_length' => 50,
            ]);

        // Referrer.
        $fields['referrer'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Referrer'))
            ->setDescription(t('URL de origen.'))
            ->setSettings([
                'max_length' => 500,
            ]);

        // URL de la página.
        $fields['page_url'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL de Página'))
            ->setSettings([
                'max_length' => 500,
            ])
            ->setRequired(TRUE);

        // UTM Source.
        $fields['utm_source'] = BaseFieldDefinition::create('string')
            ->setLabel(t('UTM Source'))
            ->setSettings([
                'max_length' => 100,
            ]);

        // UTM Medium.
        $fields['utm_medium'] = BaseFieldDefinition::create('string')
            ->setLabel(t('UTM Medium'))
            ->setSettings([
                'max_length' => 100,
            ]);

        // UTM Campaign.
        $fields['utm_campaign'] = BaseFieldDefinition::create('string')
            ->setLabel(t('UTM Campaign'))
            ->setSettings([
                'max_length' => 100,
            ]);

        // UTM Content.
        $fields['utm_content'] = BaseFieldDefinition::create('string')
            ->setLabel(t('UTM Content'))
            ->setSettings([
                'max_length' => 100,
            ]);

        // UTM Term.
        $fields['utm_term'] = BaseFieldDefinition::create('string')
            ->setLabel(t('UTM Term'))
            ->setSettings([
                'max_length' => 100,
            ]);

        // IP hasheada (GDPR).
        $fields['ip_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('IP Hash'))
            ->setDescription(t('IP hasheada para cumplimiento GDPR.'))
            ->setSettings([
                'max_length' => 64,
            ]);

        // País (código ISO 2).
        $fields['country'] = BaseFieldDefinition::create('string')
            ->setLabel(t('País'))
            ->setSettings([
                'max_length' => 2,
            ]);

        // Región/Provincia.
        $fields['region'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Región'))
            ->setSettings([
                'max_length' => 100,
            ]);

        // Timestamp de creación.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Momento del evento.'));

        return $fields;
    }

}
