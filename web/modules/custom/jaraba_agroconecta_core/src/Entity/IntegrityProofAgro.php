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
 * Define la entidad IntegrityProofAgro.
 *
 * Prueba de integridad que ancla periódicamente el hash de la cadena
 * de trazabilidad a un registro externo (timestamp authority, blockchain,
 * o simplemente un log firmado). Permite demostrar que la cadena no fue
 * manipulada retroactivamente.
 *
 * @ContentEntityType(
 *   id = "integrity_proof_agro",
 *   label = @Translation("Prueba Integridad Agro"),
 *   label_collection = @Translation("Pruebas Integridad Agro"),
 *   label_singular = @Translation("prueba de integridad agro"),
 *   label_plural = @Translation("pruebas de integridad agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\IntegrityProofAgroListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\IntegrityProofAgroForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\IntegrityProofAgroForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\IntegrityProofAgroForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\IntegrityProofAgroAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "integrity_proof_agro",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.integrity_proof_agro.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "proof_hash",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-integrity-proofs/{integrity_proof_agro}",
 *     "add-form" = "/admin/content/agro-integrity-proofs/add",
 *     "edit-form" = "/admin/content/agro-integrity-proofs/{integrity_proof_agro}/edit",
 *     "delete-form" = "/admin/content/agro-integrity-proofs/{integrity_proof_agro}/delete",
 *     "collection" = "/admin/content/agro-integrity-proofs",
 *   },
 * )
 */
class IntegrityProofAgro extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        $fields['batch_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Lote'))
            ->setSetting('target_type', 'agro_batch')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -10])
            ->setDisplayConfigurable('form', TRUE);

        $fields['proof_hash'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Hash de prueba'))
            ->setDescription(t('SHA-256 del root hash de la cadena en el momento del anclaje.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['anchor_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de anclaje'))
            ->setSetting('allowed_values', [
                'internal' => t('Log interno firmado'),
                'tsa' => t('Timestamp Authority (RFC 3161)'),
                'blockchain' => t('Blockchain'),
            ])
            ->setDefaultValue('internal')
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -8])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['anchor_reference'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Referencia externa'))
            ->setDescription(t('Transaction hash, TSA token ID, o referencia del servicio externo.'))
            ->setSetting('max_length', 256)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -7])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['event_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Eventos cubiertos'))
            ->setDescription(t('Número de TraceEvents incluidos hasta este punto.'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['proof_timestamp'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de prueba'))
            ->setSetting('datetime_type', 'datetime')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => -6])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['signature'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Firma digital'))
            ->setDescription(t('Firma Ed25519 del proof_hash para no-repudio.'))
            ->setDisplayConfigurable('view', TRUE);

        $fields['verification_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado verificación'))
            ->setSetting('allowed_values', [
                'pending' => t('Pendiente'),
                'verified' => t('Verificado'),
                'failed' => t('Fallido'),
            ])
            ->setDefaultValue('pending')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    public function getProofHash(): string
    {
        return $this->get('proof_hash')->value ?? '';
    }
    public function getAnchorType(): string
    {
        return $this->get('anchor_type')->value ?? 'internal';
    }
    public function isVerified(): bool
    {
        return $this->get('verification_status')->value === 'verified';
    }
}
