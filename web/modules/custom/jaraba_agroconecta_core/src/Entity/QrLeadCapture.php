<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad QrLeadCapture.
 *
 * Lead capturado desde la landing de un QR. Registra datos de contacto
 * para nurturing y opcionalmente asigna un código de descuento.
 *
 * @ContentEntityType(
 *   id = "qr_lead_capture",
 *   label = @Translation("Lead QR Agro"),
 *   label_collection = @Translation("Leads QR Agro"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\QrLeadCaptureListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\QrLeadCaptureAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "qr_lead_capture",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.qr_lead_capture.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "email",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-qr-leads/{qr_lead_capture}",
 *     "delete-form" = "/admin/content/agro-qr-leads/{qr_lead_capture}/delete",
 *     "collection" = "/admin/content/agro-qr-leads",
 *   },
 * )
 */
class QrLeadCapture extends ContentEntityBase implements EntityChangedInterface
{

    use EntityChangedTrait;

    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE);

        $fields['qr_code_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Código QR'))
            ->setSetting('target_type', 'qr_code_agro')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['scan_event_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Evento escaneo'))
            ->setSetting('target_type', 'qr_scan_event');

        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email'))
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setSetting('max_length', 128)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['phone'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Teléfono'))
            ->setSetting('max_length', 32)
            ->setDisplayConfigurable('form', TRUE);

        $fields['source'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Origen'))
            ->setDescription(t('De dónde vino (ej: feria_alimentaria_2026, packaging_producto).'))
            ->setSetting('max_length', 128)
            ->setDisplayConfigurable('view', TRUE);

        $fields['discount_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Código descuento'))
            ->setDescription(t('Cupón de descuento asignado automáticamente.'))
            ->setSetting('max_length', 32)
            ->setDisplayConfigurable('view', TRUE);

        $fields['consent_given'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Consentimiento'))
            ->setDefaultValue(FALSE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['metadata'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Metadatos'))
            ->setDescription(t('JSON con datos adicionales del lead.'));

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

}
