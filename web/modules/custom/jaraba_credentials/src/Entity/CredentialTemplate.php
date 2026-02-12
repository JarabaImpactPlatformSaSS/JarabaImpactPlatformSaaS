<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad CredentialTemplate.
 *
 * Modelo de badge o certificado que se puede emitir.
 *
 * @ContentEntityType(
 *   id = "credential_template",
 *   label = @Translation("Template de Credencial"),
 *   label_collection = @Translation("Templates de Credenciales"),
 *   label_singular = @Translation("template de credencial"),
 *   label_plural = @Translation("templates de credenciales"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_credentials\CredentialTemplateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_credentials\Form\CredentialTemplateForm",
 *       "add" = "Drupal\jaraba_credentials\Form\CredentialTemplateForm",
 *       "edit" = "Drupal\jaraba_credentials\Form\CredentialTemplateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_credentials\CredentialTemplateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "credential_template",
 *   admin_permission = "manage credential templates",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.credential_template.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/credential-templates/{credential_template}",
 *     "add-form" = "/admin/content/credential-templates/add",
 *     "edit-form" = "/admin/content/credential-templates/{credential_template}/edit",
 *     "delete-form" = "/admin/content/credential-templates/{credential_template}/delete",
 *     "collection" = "/admin/content/credential-templates",
 *   },
 * )
 */
class CredentialTemplate extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Tipos de credencial disponibles.
     */
    public const TYPE_COURSE_BADGE = 'course_badge';
    public const TYPE_PATH_CERTIFICATE = 'path_certificate';
    public const TYPE_SKILL_ENDORSEMENT = 'skill_endorsement';
    public const TYPE_ACHIEVEMENT = 'achievement';
    public const TYPE_DIPLOMA = 'diploma';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Nombre del template
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre del badge o certificado.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Machine name
        $fields['machine_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre máquina'))
            ->setDescription(t('Identificador único para integración.'))
            ->setRequired(TRUE)
            ->setSetting('max_length', 64)
            ->addConstraint('UniqueField')
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Descripción
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Descripción detallada del logro que representa.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Tipo de credencial
        $fields['credential_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Credencial'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_COURSE_BADGE => t('Badge de Curso'),
                self::TYPE_PATH_CERTIFICATE => t('Certificado de Ruta'),
                self::TYPE_SKILL_ENDORSEMENT => t('Endorsement de Habilidad'),
                self::TYPE_ACHIEVEMENT => t('Logro'),
                self::TYPE_DIPLOMA => t('Diploma'),
            ])
            ->setDefaultValue(self::TYPE_COURSE_BADGE)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Imagen del badge
        $fields['image'] = BaseFieldDefinition::create('image')
            ->setLabel(t('Imagen del Badge'))
            ->setDescription(t('Imagen que representa el badge (PNG o SVG recomendado).'))
            ->setSetting('file_extensions', 'png svg jpg jpeg')
            ->setSetting('file_directory', 'credentials/badges')
            ->setDisplayOptions('form', [
                'type' => 'image_image',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Criterios (HTML)
        $fields['criteria'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Criterios'))
            ->setDescription(t('Criterios que debe cumplir el usuario para obtener este badge.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => -5,
                'settings' => [
                    'rows' => 5,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Skills certificadas (JSON array de IDs)
        $fields['skills_certified'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Skills Certificadas'))
            ->setDescription(t('JSON array de IDs de skills que certifica este badge.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -4,
                'settings' => [
                    'rows' => 3,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al programa de certificación (si aplica)
        $fields['certification_program_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Programa de Certificación'))
            ->setDescription(t('Programa de certificación al que pertenece este template.'))
            ->setSetting('target_type', 'certification_program')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -3,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al emisor
        $fields['issuer_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Emisor'))
            ->setDescription(t('Organización que emite este badge.'))
            ->setSetting('target_type', 'issuer_profile')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Período de validez en meses (0 = sin expiración)
        $fields['validity_period_months'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Período de Validez (meses)'))
            ->setDescription(t('Meses de validez. 0 = sin expiración.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => -1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al curso LMS (para emisión automática)
        // Cuando un usuario completa este curso, se emite credencial automáticamente.
        $fields['lms_course_id'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Curso LMS Asociado'))
            ->setDescription(t('ID del curso LMS. Al completarlo, se emite esta credencial automáticamente.'))
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia a contenido interactivo evaluativo (para emisión automática).
        // Cuando un usuario aprueba este examen, se emite credencial automáticamente.
        $fields['interactive_activity_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Actividad Interactiva Asociada'))
            ->setDescription(t('Contenido interactivo evaluativo. Al aprobarlo, se emite esta credencial automáticamente.'))
            ->setSetting('target_type', 'interactive_content')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 1,
                'settings' => [
                    'match_operator' => 'CONTAINS',
                    'placeholder' => t('Buscar examen o quiz...'),
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nota mínima para aprobar (porcentaje 0-100).
        // Si no se especifica, se usa la configurada en el contenido interactivo.
        $fields['passing_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Nota Mínima para Aprobar (%)'))
            ->setDescription(t('Porcentaje mínimo (0-100) para emitir credencial. Si vacío, usa la del contenido.'))
            ->setSetting('min', 0)
            ->setSetting('max', 100)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nivel del badge
        $fields['level'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Nivel'))
            ->setSetting('allowed_values', [
                'beginner' => t('Principiante'),
                'intermediate' => t('Intermedio'),
                'advanced' => t('Avanzado'),
                'expert' => t('Experto'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Activo
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDescription(t('Si está desactivado, no se pueden emitir nuevas credenciales.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Campos de sistema
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene el emisor asociado.
     *
     * @return \Drupal\jaraba_credentials\Entity\IssuerProfile|null
     *   El perfil de emisor o NULL.
     */
    public function getIssuer(): ?IssuerProfile
    {
        $issuerId = $this->get('issuer_id')->target_id ?? NULL;
        if (!$issuerId) {
            return NULL;
        }
        return IssuerProfile::load($issuerId);
    }

    /**
     * Calcula la fecha de expiración.
     *
     * @param int $issuedTimestamp
     *   El timestamp de emisión.
     *
     * @return int|null
     *   El timestamp de expiración o NULL si no expira.
     */
    public function calculateExpiration(int $issuedTimestamp): ?int
    {
        $months = (int) ($this->get('validity_period_months')->value ?? 0);
        if ($months <= 0) {
            return NULL;
        }
        return strtotime("+{$months} months", $issuedTimestamp);
    }

}
