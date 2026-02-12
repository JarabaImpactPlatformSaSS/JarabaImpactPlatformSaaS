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
 * Define la entidad ProductDocument.
 *
 * Documento asociado a un producto o al productor en general,
 * con control de acceso por nivel, tipo de partner, y validez temporal.
 * Soporta generación automática de fichas técnicas.
 *
 * @ContentEntityType(
 *   id = "product_document",
 *   label = @Translation("Documento de Producto"),
 *   label_collection = @Translation("Documentos de Producto"),
 *   label_singular = @Translation("documento de producto"),
 *   label_plural = @Translation("documentos de producto"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_agroconecta_core\Entity\ProductDocumentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_agroconecta_core\Form\ProductDocumentForm",
 *       "add" = "Drupal\jaraba_agroconecta_core\Form\ProductDocumentForm",
 *       "edit" = "Drupal\jaraba_agroconecta_core\Form\ProductDocumentForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_agroconecta_core\Entity\ProductDocumentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "product_document",
 *   admin_permission = "administer agroconecta",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.product_document.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "title",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/agro-documents/{product_document}",
 *     "add-form" = "/admin/content/agro-documents/add",
 *     "edit-form" = "/admin/content/agro-documents/{product_document}/edit",
 *     "delete-form" = "/admin/content/agro-documents/{product_document}/delete",
 *     "collection" = "/admin/content/agro-documents",
 *   },
 * )
 */
class ProductDocument extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
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

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['producer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Productor'))
            ->setDescription(t('Perfil de productor propietario del documento.'))
            ->setSetting('target_type', 'taxonomy_term')
            ->setSetting('handler_settings', ['target_bundles' => ['tenants' => 'tenants']])
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'entity_reference_autocomplete', 'weight' => -10])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['product_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Producto asociado'))
            ->setDescription(t('ID del producto agro asociado. Vacío = documento general del productor.'))
            ->setDisplayOptions('form', ['type' => 'number', 'weight' => -9])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['title'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Título'))
            ->setDescription(t('Título descriptivo del documento.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -8])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['document_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de documento'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'ficha_tecnica' => t('Ficha técnica'),
                'analitica' => t('Analítica'),
                'certificacion' => t('Certificación'),
                'marketing' => t('Marketing'),
                'especificacion' => t('Especificación'),
                'catalogo' => t('Catálogo'),
                'otro' => t('Otro'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -7])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['file_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Archivo'))
            ->setDescription(t('Archivo adjunto del documento.'))
            ->setSetting('target_type', 'file')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', ['type' => 'file_generic', 'weight' => -6])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_auto_generated'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Generado automáticamente'))
            ->setDescription(t('Indica si el documento fue generado desde datos del producto.'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => -5])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['min_access_level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Nivel mínimo de acceso'))
            ->setRequired(TRUE)
            ->setDefaultValue('basico')
            ->setSetting('allowed_values', [
                'basico' => t('Básico'),
                'verificado' => t('Verificado'),
                'premium' => t('Premium'),
            ])
            ->setDisplayOptions('form', ['type' => 'options_select', 'weight' => -4])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['allowed_partner_types'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Tipos de partner permitidos'))
            ->setDescription(t('JSON con tipos de partner con acceso. Null = todos.'))
            ->setDisplayOptions('form', ['type' => 'string_textarea', 'weight' => -3])
            ->setDisplayConfigurable('form', TRUE);

        $fields['version'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Versión'))
            ->setDefaultValue('1.0')
            ->setSetting('max_length', 16)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => -2])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['valid_from'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Válido desde'))
            ->setDescription(t('Fecha de inicio de validez del documento.'))
            ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => -1])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['valid_until'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Válido hasta'))
            ->setDescription(t('Fecha de fin de validez (certificaciones).'))
            ->setDisplayOptions('form', ['type' => 'datetime_default', 'weight' => 0])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['language_code'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Idioma'))
            ->setDefaultValue('es')
            ->setSetting('max_length', 5)
            ->setDisplayOptions('form', ['type' => 'string_textfield', 'weight' => 1])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['download_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Descargas totales'))
            ->setDefaultValue(0)
            ->setDisplayConfigurable('view', TRUE);

        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', ['type' => 'boolean_checkbox', 'weight' => 2])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')->setLabel(t('Creado'));
        $fields['changed'] = BaseFieldDefinition::create('changed')->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene el título del documento.
     */
    public function getTitle(): string
    {
        return $this->get('title')->value ?? '';
    }

    /**
     * Obtiene el tipo de documento.
     */
    public function getDocumentType(): string
    {
        return $this->get('document_type')->value ?? '';
    }

    /**
     * Obtiene el nivel mínimo de acceso requerido.
     */
    public function getMinAccessLevel(): string
    {
        return $this->get('min_access_level')->value ?? 'basico';
    }

    /**
     * Obtiene el ID del productor.
     */
    public function getProducerId(): ?int
    {
        return $this->get('producer_id')->target_id ? (int) $this->get('producer_id')->target_id : NULL;
    }

    /**
     * Obtiene el ID del producto asociado.
     */
    public function getProductId(): ?int
    {
        $value = $this->get('product_id')->value;
        return $value ? (int) $value : NULL;
    }

    /**
     * Indica si es un documento generado automáticamente.
     */
    public function isAutoGenerated(): bool
    {
        return (bool) $this->get('is_auto_generated')->value;
    }

    /**
     * Indica si el documento está activo.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }

    /**
     * Obtiene la versión del documento.
     */
    public function getVersion(): string
    {
        return $this->get('version')->value ?? '1.0';
    }

    /**
     * Obtiene los tipos de partner permitidos como array.
     */
    public function getAllowedPartnerTypes(): ?array
    {
        $value = $this->get('allowed_partner_types')->value;
        return $value ? json_decode($value, TRUE) : NULL;
    }

    /**
     * Obtiene el conteo de descargas.
     */
    public function getDownloadCount(): int
    {
        return (int) ($this->get('download_count')->value ?? 0);
    }

}
