<?php

declare(strict_types=1);

namespace Drupal\jaraba_social\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad SocialAccount para cuentas de redes sociales.
 *
 * PROPÓSITO:
 * Almacena conexiones a plataformas sociales (Facebook, Instagram,
 * LinkedIn, Twitter/X, TikTok) con tokens de acceso seguros.
 *
 * MULTI-TENANT:
 * Cada cuenta está asociada a un tenant_id específico.
 *
 * @ContentEntityType(
 *   id = "social_account",
 *   label = @Translation("Cuenta Social"),
 *   label_collection = @Translation("Cuentas Sociales"),
 *   label_singular = @Translation("cuenta social"),
 *   label_plural = @Translation("cuentas sociales"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "social_account",
 *   admin_permission = "administer social accounts",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/social-accounts/{social_account}",
 *     "add-form" = "/admin/content/social-accounts/add",
 *     "edit-form" = "/admin/content/social-accounts/{social_account}/edit",
 *     "delete-form" = "/admin/content/social-accounts/{social_account}/delete",
 *     "collection" = "/admin/content/social-accounts",
 *   },
 *   field_ui_base_route = "entity.social_account.settings",
 * )
 */
class SocialAccount extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * Plataformas soportadas.
     */
    public const PLATFORM_FACEBOOK = 'facebook';
    public const PLATFORM_INSTAGRAM = 'instagram';
    public const PLATFORM_LINKEDIN = 'linkedin';
    public const PLATFORM_TWITTER = 'twitter';
    public const PLATFORM_TIKTOK = 'tiktok';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setDescription(t('Nombre descriptivo de la cuenta.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['platform'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Plataforma'))
            ->setDescription(t('Red social conectada.'))
            ->setRequired(TRUE)
            ->setSettings([
                'allowed_values' => [
                    self::PLATFORM_FACEBOOK => 'Facebook',
                    self::PLATFORM_INSTAGRAM => 'Instagram',
                    self::PLATFORM_LINKEDIN => 'LinkedIn',
                    self::PLATFORM_TWITTER => 'Twitter/X',
                    self::PLATFORM_TIKTOK => 'TikTok',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE)
            ->setDisplayConfigurable('view', TRUE);

        $fields['account_id'] = BaseFieldDefinition::create('string')
            ->setLabel(t('ID de Cuenta'))
            ->setDescription(t('ID externo de la cuenta en la plataforma.'))
            ->setSettings(['max_length' => 255]);

        $fields['access_token'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Access Token'))
            ->setDescription(t('Token de acceso OAuth.'));

        $fields['refresh_token'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Refresh Token'))
            ->setDescription(t('Token de refresco OAuth.'));

        $fields['token_expires'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Token Expires'))
            ->setDescription(t('Timestamp de expiración del token.'));

        $fields['status'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDescription(t('Indica si la cuenta está activa.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 10,
            ]);

        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant propietario de la cuenta.'))
            ->setSetting('target_type', 'group');

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Getters.
     */
    public function getPlatform(): string
    {
        return $this->get('platform')->value ?? '';
    }

    public function getAccessToken(): string
    {
        return $this->get('access_token')->value ?? '';
    }

    public function isActive(): bool
    {
        return (bool) $this->get('status')->value;
    }

    public function isTokenExpired(): bool
    {
        $expires = $this->get('token_expires')->value;
        return $expires && $expires < time();
    }

}
