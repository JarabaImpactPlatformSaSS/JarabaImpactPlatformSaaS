<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad SiteRedirect para gestión de redirects.
 *
 * Permite crear redirects 301/302/307 con tracking de hits y expiración.
 *
 * @ContentEntityType(
 *   id = "site_redirect",
 *   label = @Translation("Redirect"),
 *   label_collection = @Translation("Redirects"),
 *   label_singular = @Translation("redirect"),
 *   label_plural = @Translation("redirects"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\jaraba_site_builder\SiteRedirectListBuilder",
 *     "form" = {
 *       "default" = "Drupal\jaraba_site_builder\Form\SiteRedirectForm",
 *       "add" = "Drupal\jaraba_site_builder\Form\SiteRedirectForm",
 *       "edit" = "Drupal\jaraba_site_builder\Form\SiteRedirectForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_site_builder\SiteRedirectAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "site_redirect",
 *   admin_permission = "manage site redirects",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "source_path",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/site-builder/redirects/{site_redirect}",
 *     "add-form" = "/admin/structure/site-builder/redirects/add",
 *     "edit-form" = "/admin/structure/site-builder/redirects/{site_redirect}/edit",
 *     "delete-form" = "/admin/structure/site-builder/redirects/{site_redirect}/delete",
 *   },
 * )
 */
class SiteRedirect extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Tenant (Group) al que pertenece este redirect.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant al que pertenece este redirect.'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => -100,
            ]);

        // URL de origen (sin dominio).
        $fields['source_path'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL de Origen'))
            ->setDescription(t('Ruta que debe redirigir (ej: /old-url).'))
            ->setRequired(TRUE)
            ->setSettings([
                'max_length' => 500,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // URL de destino.
        $fields['destination_path'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL de Destino'))
            ->setDescription(t('Ruta o URL completa de destino (ej: /new-url o https://...).'))
            ->setRequired(TRUE)
            ->setSettings([
                'max_length' => 500,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Tipo de redirect.
        $fields['redirect_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Redirect'))
            ->setDescription(t('Código HTTP de la redirección.'))
            ->setDefaultValue('301')
            ->setSettings([
                'allowed_values' => [
                    '301' => t('301 - Permanente'),
                    '302' => t('302 - Temporal'),
                    '307' => t('307 - Temporal (preserva método)'),
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Razón o nota.
        $fields['reason'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Razón'))
            ->setDescription(t('Motivo del redirect (ej: página renombrada, migración).'))
            ->setSettings([
                'max_length' => 255,
            ])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 10,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Contador de hits.
        $fields['hit_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Contador de Hits'))
            ->setDescription(t('Número de veces que se ha usado este redirect.'))
            ->setDefaultValue(0)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
                'weight' => 20,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Último hit.
        $fields['last_hit'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Último Uso'))
            ->setDescription(t('Fecha del último uso de este redirect.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
                'weight' => 21,
            ])
            ->setDisplayConfigurable('view', TRUE);

        // Estado activo.
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDescription(t('Si el redirect está activo.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 30,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Fecha de expiración.
        $fields['expires_at'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Expira'))
            ->setDescription(t('Fecha en que el redirect expira y deja de funcionar.'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_timestamp',
                'weight' => 31,
            ])
            ->setDisplayConfigurable('form', TRUE);

        // Creado automáticamente.
        $fields['is_auto_generated'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Generado Automáticamente'))
            ->setDescription(t('Indica si fue creado automáticamente por cambio de URL.'))
            ->setDefaultValue(FALSE)
            ->setDisplayConfigurable('view', TRUE);

        // --- Campos de sistema ---

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'))
            ->setDescription(t('Fecha de creación.'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'))
            ->setDescription(t('Fecha de última modificación.'));

        return $fields;
    }

    /**
     * Registra un hit en este redirect.
     */
    public function recordHit(): self
    {
        $currentCount = (int) $this->get('hit_count')->value;
        $this->set('hit_count', $currentCount + 1);
        $this->set('last_hit', \Drupal::time()->getRequestTime());
        return $this;
    }

    /**
     * Verifica si el redirect está activo.
     */
    public function isActive(): bool
    {
        if (!(bool) $this->get('is_active')->value) {
            return FALSE;
        }

        // Verificar expiración.
        $expires = $this->get('expires_at')->value;
        if ($expires && $expires < \Drupal::time()->getRequestTime()) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Obtiene el código HTTP del redirect.
     */
    public function getRedirectCode(): int
    {
        return (int) ($this->get('redirect_type')->value ?? 301);
    }

}
