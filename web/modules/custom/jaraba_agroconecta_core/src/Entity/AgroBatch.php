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
 * Define la entidad AgroBatch.
 *
 * Lote de producción con cadena de custodia inmutable.
 * Un lote agrupa una producción específica (ej: cosecha de aceitunas
 * de la parcela X, fecha Y) y contiene la secuencia de TraceEvents
 * que documentan su historia desde siembra hasta venta.
 *
 * @ContentEntityType(
 *   id = "agro_batch",
 *   label = @Translation("Lote Agro"),
 *   label_collection = @Translation("Lotes Agro"),
 *   label_singular = @Translation("lote agro"),
 *   label_plural = @Translation("lotes agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\AgroBatchListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\AgroBatchForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\AgroBatchForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\AgroBatchForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\AgroBatchAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agro_batch",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.agro_batch.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "batch_code",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-batches/{agro_batch}",
 *     "add-form" = "/admin/content/agro-batches/add",
 *     "edit-form" = "/admin/content/agro-batches/{agro_batch}/edit",
 *     "delete-form" = "/admin/content/agro-batches/{agro_batch}/delete",
 *     "collection" = "/admin/content/agro-batches",
 *   },
 * )
 */
class AgroBatch extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
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
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['batch_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Código de Lote'))
            ->setDescription(t('Identificador único (ej: LOT-2026-AOVE-001).'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -10])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['product_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Producto'))
            ->setDescription(t('Producto agroalimentario de este lote.'))
            ->setSetting('target_type', 'product_agro')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -9])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Productor'))
            ->setSetting('target_type', 'producer_profile')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -8])
            ->setDisplayConfigurable('form', TRUE);

        $fields['origin'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Origen'))
            ->setDescription(t('Ubicación o parcela de origen (ej: Finca La Esperanza, Jaén).'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -7])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['variety'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Variedad'))
            ->setDescription(t('Variedad del producto (ej: Picual, Hojiblanca).'))
            ->setSetting('max_length', 128)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -6])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['harvest_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de cosecha'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => -5])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['quantity'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Cantidad'))
            ->setDescription(t('Cantidad producida (en kg o litros).'))
            ->setSetting('precision', 10)
            ->setSetting('scale', 2)
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => -4])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['unit'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Unidad'))
            ->setSetting('allowed_values', [
                'kg' => t('Kilogramos'),
                'l' => t('Litros'),
                'units' => t('Unidades'),
            ])
            ->setDefaultValue('kg')
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -3])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['certifications'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Certificaciones'))
            ->setDescription(t('JSON array de IDs de certificaciones aplicables al lote.'))
            ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => -2, 'settings' => ['rows' => 2]])
            ->setDisplayConfigurable('form', TRUE);

        $fields['chain_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hash de cadena'))
            ->setDescription(t('SHA-256 del último evento de la cadena para verificación de integridad.'))
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('view', TRUE);

        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setSetting('allowed_values', [
                'active' => t('Activo'),
                'sealed' => t('Sellado'),
                'archived' => t('Archivado'),
            ])
            ->setDefaultValue('active')
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => 0])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getBatchCode(): string
    {
        return $this->get('batch_code')->value ?? '';
    }
    public function getChainHash(): string
    {
        return $this->get('chain_hash')->value ?? '';
    }
    public function getStatus(): string
    {
        return $this->get('status')->value ?? 'active';
    }
    public function isSealed(): bool
    {
        return $this->getStatus() === 'sealed';
    }
}
