<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad UserCertification.
 *
 * Certificación otorgada a un usuario.
 *
 * @ContentEntityType(
 *   id = "user_certification",
 *   label = @Translation("Certificación de Usuario"),
 *   label_collection = @Translation("Certificaciones Otorgadas"),
 *   label_singular = @Translation("certificación"),
 *   label_plural = @Translation("certificaciones"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_training\UserCertificationListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_training\UserCertificationAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "user_certification",
 *   admin_permission = "grant certifications",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/user-certifications/{user_certification}",
 *     "collection" = "/admin/content/user-certifications",
 *   },
 *   field_ui_base_route = "entity.user_certification.settings",
 * )
 */
class UserCertification extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
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

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario Certificado'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['program_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Programa de Certificación'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'certification_program')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Directriz Nuclear #20: allowed_values configurables desde YAML.
        $fields['certification_status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values_function', 'jaraba_training_allowed_certification_statuses')
            ->setDefaultValue('in_progress')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['certification_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Certificación'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['expiration_date'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Expiración'))
            ->setSetting('datetime_type', 'date')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['certificate_number'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Número de Certificado'))
            ->setDescription(t('Identificador único del certificado.'))
            ->setSetting('max_length', 50)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['exam_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntuación Examen'))
            ->setDescription(t('Puntuación obtenida en el examen (0-100).'))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['territory'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Territorio Asignado'))
            ->setDescription(t('Territorio exclusivo si aplica.'))
            ->setSetting('max_length', 255)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === TRACKING DE ROYALTIES ===

        $fields['total_royalties'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Royalties Totales'))
            ->setDescription(t('Total de royalties generados.'))
            ->setDefaultValue(0)
            ->setSetting('precision', 12)
            ->setSetting('scale', 2)
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // === CAMPOS DE SISTEMA ===

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

}
