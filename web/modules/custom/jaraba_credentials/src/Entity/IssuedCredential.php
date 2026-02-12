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
 * Define la entidad IssuedCredential.
 *
 * Credencial emitida a un usuario específico con firma Ed25519.
 *
 * @ContentEntityType(
 *   id = "issued_credential",
 *   label = @Translation("Credencial Emitida"),
 *   label_collection = @Translation("Credenciales Emitidas"),
 *   label_singular = @Translation("credencial emitida"),
 *   label_plural = @Translation("credenciales emitidas"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_credentials\IssuedCredentialListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\jaraba_credentials\Form\IssuedCredentialForm",
 *       "add" = "Drupal\jaraba_credentials\Form\IssuedCredentialForm",
 *       "edit" = "Drupal\jaraba_credentials\Form\IssuedCredentialForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_credentials\IssuedCredentialAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "issued_credential",
 *   admin_permission = "administer credentials",
 *   fieldable = TRUE,
 *   field_ui_base_route = "entity.issued_credential.settings",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "credential_id_uri",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/issued-credentials/{issued_credential}",
 *     "add-form" = "/admin/content/issued-credentials/add",
 *     "edit-form" = "/admin/content/issued-credentials/{issued_credential}/edit",
 *     "delete-form" = "/admin/content/issued-credentials/{issued_credential}/delete",
 *     "collection" = "/admin/content/issued-credentials",
 *   },
 * )
 */
class IssuedCredential extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * Estados de la credencial.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // URI identificador de la credencial (OB3 id)
        $fields['credential_id_uri'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('URI de Credencial'))
            ->setDescription(t('Identificador único URI de la credencial (OB3 id).'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'uri',
                'weight' => -10,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al template
        $fields['template_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Template'))
            ->setDescription(t('Template de credencial utilizado.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'credential_template')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -9,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al usuario receptor
        $fields['recipient_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Receptor'))
            ->setDescription(t('Usuario que recibe la credencial.'))
            ->setRequired(TRUE)
            ->setSetting('target_type', 'user')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -8,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Email del receptor (para OB3)
        $fields['recipient_email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email del Receptor'))
            ->setDescription(t('Email del usuario al momento de la emisión.'))
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => -7,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Nombre del receptor
        $fields['recipient_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Receptor'))
            ->setDescription(t('Nombre completo del usuario.'))
            ->setSetting('max_length', 255)
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -6,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de emisión
        $fields['issued_on'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Emisión'))
            ->setRequired(TRUE)
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -5,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Fecha de expiración
        $fields['expires_on'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Expiración'))
            ->setDescription(t('Vacío si no expira.'))
            ->setSetting('datetime_type', 'datetime')
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => -4,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Evidencia (JSON)
        $fields['evidence'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Evidencia'))
            ->setDescription(t('JSON con evidencia del logro.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => -3,
                'settings' => [
                    'rows' => 4,
                ],
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Estado
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::STATUS_ACTIVE => t('Activa'),
                self::STATUS_REVOKED => t('Revocada'),
                self::STATUS_EXPIRED => t('Expirada'),
                self::STATUS_SUSPENDED => t('Suspendida'),
            ])
            ->setDefaultValue(self::STATUS_ACTIVE)
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => -2,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // JSON-LD OB3 firmado
        $fields['ob3_json'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('JSON-LD OB3'))
            ->setDescription(t('Credencial completa en formato Open Badge 3.0.'))
            ->setDisplayConfigurable('form', FALSE)
            ->setDisplayConfigurable('view', TRUE);

        // Firma Ed25519
        $fields['signature'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Firma Ed25519'))
            ->setDescription(t('Firma criptográfica de la credencial.'))
            ->setDisplayConfigurable('form', FALSE)
            ->setDisplayConfigurable('view', TRUE);

        // URL de verificación
        $fields['verification_url'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('URL de Verificación'))
            ->setDescription(t('URL pública para verificar esta credencial.'))
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia al archivo PDF
        $fields['pdf_file'] = BaseFieldDefinition::create('file')
            ->setLabel(t('Archivo PDF'))
            ->setDescription(t('Certificado en formato PDF.'))
            ->setSetting('file_extensions', 'pdf')
            ->setSetting('file_directory', 'credentials/pdfs')
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        // Referencia a la certificación de origen (si aplica)
        $fields['certification_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Certificación de Usuario'))
            ->setDescription(t('UserCertification que originó esta credencial.'))
            ->setSetting('target_type', 'user_certification')
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
     * Verifica si la credencial está activa y válida.
     *
     * @return bool
     *   TRUE si está activa y no ha expirado.
     */
    public function isValid(): bool
    {
        $status = $this->get('status')->value ?? '';
        if ($status !== self::STATUS_ACTIVE) {
            return FALSE;
        }

        $expiresOn = $this->get('expires_on')->value ?? NULL;
        if ($expiresOn) {
            $expirationTimestamp = strtotime($expiresOn);
            if ($expirationTimestamp && $expirationTimestamp < time()) {
                return FALSE;
            }
        }

        return TRUE;
    }

    /**
     * Obtiene el template asociado.
     *
     * @return \Drupal\jaraba_credentials\Entity\CredentialTemplate|null
     *   El template o NULL.
     */
    public function getTemplate(): ?CredentialTemplate
    {
        $templateId = $this->get('template_id')->target_id ?? NULL;
        if (!$templateId) {
            return NULL;
        }
        return CredentialTemplate::load($templateId);
    }

    /**
     * Obtiene el JSON de evidencia decodificado.
     *
     * @return array
     *   Array con la evidencia.
     */
    public function getEvidence(): array
    {
        $json = $this->get('evidence')->value ?? '';
        if (empty($json)) {
            return [];
        }
        return json_decode($json, TRUE) ?: [];
    }

}
