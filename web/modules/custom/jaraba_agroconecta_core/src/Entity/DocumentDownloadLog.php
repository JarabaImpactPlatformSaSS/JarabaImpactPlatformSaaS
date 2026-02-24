<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad DocumentDownloadLog.
 *
 * Registro inmutable de auditoría para cada descarga de documento.
 * No tiene formularios de edición — se crea exclusivamente desde
 * el servicio PartnerDocumentService::logDownload().
 *
 * @ContentEntityType(
 *   id = "document_download_log",
 *   label = @Translation("Registro de Descarga"),
 *   label_collection = @Translation("Registros de Descarga"),
 *   label_singular = @Translation("registro de descarga"),
 *   label_plural = @Translation("registros de descarga"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\DocumentDownloadLogListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\DocumentDownloadLogAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "document_download_log",
 *   admin_permission = "administer agroconecta",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-download-logs/{document_download_log}",
 *     "collection" = "/admin/content/agro-download-logs",
 *   },
 *   field_ui_base_route = "entity.document_download_log.settings",
 * )
 */
class DocumentDownloadLog extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE);

        $fields['document_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Documento'))
            ->setDescription(t('Documento que fue descargado.'))
            ->setSetting('target_type', 'product_document')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['relationship_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Partner'))
            ->setDescription(t('Relación partner que realizó la descarga.'))
            ->setSetting('target_type', 'partner_relationship')
            ->setRequired(TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['downloaded_at'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de descarga'))
            ->setDescription(t('Momento exacto de la descarga.'));

        $fields['ip_address'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Dirección IP'))
            ->setSetting('max_length', 45)
            ->setDisplayConfigurable('view', TRUE);

        $fields['user_agent'] = BaseFieldDefinition::create('string')
            ->setLabel(t('User agent'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('view', TRUE);

        return $fields;
    }

    /**
     * Obtiene el ID del documento descargado.
     */
    public function getDocumentId(): ?int
    {
        return $this->get('document_id')->target_id ? (int) $this->get('document_id')->target_id : NULL;
    }

    /**
     * Obtiene el ID de la relación partner.
     */
    public function getRelationshipId(): ?int
    {
        return $this->get('relationship_id')->target_id ? (int) $this->get('relationship_id')->target_id : NULL;
    }

}
