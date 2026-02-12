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
 * Define la entidad ShippingZoneAgro.
 *
 * Representa una zona geográfica de envío con sus provincias/regiones
 * asociadas. Cada zona agrupa un conjunto de destinos que comparten
 * las mismas opciones de envío y tarifas.
 *
 * @ContentEntityType(
 *   id = "shipping_zone_agro",
 *   label = @Translation("Zona de Envío Agro"),
 *   label_collection = @Translation("Zonas de Envío Agro"),
 *   label_singular = @Translation("zona de envío agro"),
 *   label_plural = @Translation("zonas de envío agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\ShippingZoneAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\ShippingZoneAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\ShippingZoneAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\ShippingZoneAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\ShippingZoneAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "shipping_zone_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.shipping_zone_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-shipping-zones/{shipping_zone_agro}",
 *     "add-form" = "/admin/content/agro-shipping-zones/add",
 *     "edit-form" = "/admin/content/agro-shipping-zones/{shipping_zone_agro}/edit",
 *     "delete-form" = "/admin/content/agro-shipping-zones/{shipping_zone_agro}/delete",
 *     "collection" = "/admin/content/agro-shipping-zones",
 *   },
 * )
 */
class ShippingZoneAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
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

        // Tenant ID.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Marketplace propietario de esta zona.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // Nombre de la zona.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre descriptivo (ej: Andalucía, Península, Baleares, Canarias).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // País (código ISO 3166-1 alpha-2).
        $fields['country'] = BaseFieldDefinition::create('string')
            ->setLabel(t('País'))
            ->setDescription(t('Código de país ISO (ej: ES, PT, FR).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 2)
            ->setDefaultValue('ES')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Provincias/regiones (JSON array de códigos).
        $fields['regions'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Regiones/Provincias'))
            ->setDescription(t('JSON array de códigos postales o provincias (ej: ["SE","CO","MA","GR"]). Vacío = país entero.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -8,
                'settings' => ['rows' => 3],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Códigos postales (rangos o JSON).
        $fields['postal_codes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Códigos postales'))
            ->setDescription(t('JSON array de rangos (ej: ["41000-41999","14000-14999"]). Vacío = todos los CP de las regiones.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -7,
                'settings' => ['rows' => 3],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', FALSE);

        // ¿Activa?
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDescription(t('Solo las zonas activas aceptan envíos.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos de sistema.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene las regiones como array.
     */
    public function getRegions(): array
    {
        $json = $this->get('regions')->value;
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene los códigos postales como array.
     */
    public function getPostalCodes(): array
    {
        $json = $this->get('postal_codes')->value;
        if (empty($json)) {
            return [];
        }
        $decoded = json_decode($json, TRUE);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Indica si la zona está activa.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }

    /**
     * Obtiene el código de país.
     */
    public function getCountry(): string
    {
        return $this->get('country')->value ?? 'ES';
    }

    /**
     * Comprueba si un código postal pertenece a esta zona.
     */
    public function matchesPostalCode(string $postalCode, string $country = 'ES'): bool
    {
        // Verificar país.
        if (strtoupper($country) !== strtoupper($this->getCountry())) {
            return FALSE;
        }

        // Si no hay restricciones de CP ni región, acepta todo el país.
        $regions = $this->getRegions();
        $postalRanges = $this->getPostalCodes();

        if (empty($regions) && empty($postalRanges)) {
            return TRUE;
        }

        // Verificar rangos de CP.
        if (!empty($postalRanges)) {
            $cp = (int) $postalCode;
            foreach ($postalRanges as $range) {
                if (str_contains($range, '-')) {
                    [$start, $end] = explode('-', $range);
                    if ($cp >= (int) $start && $cp <= (int) $end) {
                        return TRUE;
                    }
                } elseif ($postalCode === $range) {
                    return TRUE;
                }
            }
            return FALSE;
        }

        // Verificar por prefijo de provincia (España: 2 primeros dígitos del CP).
        if (!empty($regions)) {
            $prefix = substr($postalCode, 0, 2);
            return in_array($prefix, $regions);
        }

        return FALSE;
    }

}
