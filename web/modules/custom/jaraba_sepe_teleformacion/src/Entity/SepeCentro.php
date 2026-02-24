<?php

declare(strict_types=1);

namespace Drupal\jaraba_sepe_teleformacion\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the SepeCentro entity.
 *
 * Centro de formación acreditado/inscrito ante el SEPE.
 *
 * @ContentEntityType(
 *   id = "sepe_centro",
 *   label = @Translation("Centro SEPE"),
 *   label_collection = @Translation("Centros SEPE"),
 *   label_singular = @Translation("centro SEPE"),
 *   label_plural = @Translation("centros SEPE"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_sepe_teleformacion\SepeCentroListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "sepe_centro",
 *   admin_permission = "administer sepe teleformacion",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "razon_social",
 *   },
 *   links = {
 *     "collection" = "/admin/content/sepe-centros",
 *     "add-form" = "/admin/content/sepe-centros/add",
 *     "canonical" = "/admin/content/sepe-centros/{sepe_centro}",
 *     "edit-form" = "/admin/content/sepe-centros/{sepe_centro}/edit",
 *     "delete-form" = "/admin/content/sepe-centros/{sepe_centro}/delete",
 *   },
 *   field_ui_base_route = "entity.sepe_centro.settings",
 * )
 */
class SepeCentro extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['cif'] = BaseFieldDefinition::create('string')
            ->setLabel(t('CIF/NIF'))
            ->setDescription(t('CIF o NIF de la entidad de formación.'))
            ->setRequired(TRUE)
            ->addConstraint('UniqueField')
            ->setSetting('max_length', 9)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['razon_social'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Razón Social'))
            ->setDescription(t('Nombre o razón social de la entidad.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 100)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['codigo_sepe'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Código SEPE'))
            ->setDescription(t('Código asignado por el SEPE.'))
            ->setSetting('max_length', 20)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['tipo_registro'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Registro'))
            ->setDescription(t('Tipo de homologación ante el SEPE.'))
            ->setSetting('allowed_values', [
                'inscripcion' => t('Inscripción (especialidades no-CP)'),
                'acreditacion' => t('Acreditación (Certificados de Profesionalidad)'),
            ])
            ->setDefaultValue('inscripcion')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['direccion'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Dirección'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 200)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['codigo_postal'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Código Postal'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 5)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE);

        $fields['municipio'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Municipio'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 100)
            ->setDisplayConfigurable('form', TRUE);

        $fields['provincia'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Provincia'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 50)
            ->setDisplayConfigurable('form', TRUE);

        $fields['telefono'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Teléfono'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 15)
            ->setDisplayConfigurable('form', TRUE);

        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email'))
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['url_plataforma'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('URL Plataforma'))
            ->setDescription(t('URL de la plataforma de teleformación.'))
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['url_seguimiento'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('URL Servicio SOAP'))
            ->setDescription(t('Endpoint del Web Service SOAP para seguimiento.'))
            ->setRequired(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDefaultValue(TRUE)
            ->setDisplayConfigurable('form', TRUE);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayConfigurable('form', TRUE);

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
