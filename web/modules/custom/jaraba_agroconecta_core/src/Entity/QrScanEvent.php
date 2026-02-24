<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad QrScanEvent.
 *
 * Evento de escaneo de QR con geolocalización, user agent,
 * y datos de referrer para analytics de campañas phygital.
 *
 * @ContentEntityType(
 *   id = "qr_scan_event",
 *   label = @Translation("Escaneo QR Agro"),
 *   label_collection = @Translation("Escaneos QR Agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\QrScanEventListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\QrScanEventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "qr_scan_event",
 *   admin_permission = "administer agroconecta",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/agro-qr-scans",
 *     "canonical" = "/admin/content/agro-qr-scans/{qr_scan_event}",
 *   },
 *   field_ui_base_route = "entity.qr_scan_event.settings",
 * )
 */
class QrScanEvent extends ContentEntityBase
{

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['qr_code_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Código QR'))
            ->setSetting('target_type', 'qr_code_agro')
            ->setRequired(TRUE);

        $fields['scan_timestamp'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha escaneo'));

        $fields['ip_address'] = BaseFieldDefinition::create('string')
            ->setLabel(t('IP'))
            ->setSetting('max_length', 45);

        $fields['user_agent'] = BaseFieldDefinition::create('string')
            ->setLabel(t('User Agent'))
            ->setSetting('max_length', 512);

        $fields['country'] = BaseFieldDefinition::create('string')
            ->setLabel(t('País'))
            ->setSetting('max_length', 2);

        $fields['city'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Ciudad'))
            ->setSetting('max_length', 128);

        $fields['latitude'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Latitud'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 7);

        $fields['longitude'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Longitud'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 7);

        $fields['device_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Dispositivo'))
            ->setSetting('allowed_values', [
                'mobile' => t('Móvil'),
                'tablet' => t('Tablet'),
                'desktop' => t('Desktop'),
                'unknown' => t('Desconocido'),
            ])
            ->setDefaultValue('unknown');

        $fields['referrer'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Referrer'))
            ->setSetting('max_length', 512);

        $fields['is_unique'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Es único'))
            ->setDescription(t('Primera vez que esta IP/dispositivo escanea este QR.'))
            ->setDefaultValue(TRUE);

        $fields['converted'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Convertido'))
            ->setDefaultValue(FALSE);

        return $fields;
    }

}
