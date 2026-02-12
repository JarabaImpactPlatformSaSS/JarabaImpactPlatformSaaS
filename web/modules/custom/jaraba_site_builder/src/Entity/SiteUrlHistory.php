<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Historial de URL para auditoría.
 *
 * Registra todos los cambios de URL de páginas para:
 * - Auditoría y compliance
 * - Debugging de redirects
 * - Analytics de cambios estructurales
 *
 * @ContentEntityType(
 *   id = "site_url_history",
 *   label = @Translation("Historial de URL"),
 *   label_collection = @Translation("Historial de URLs"),
 *   label_singular = @Translation("registro de URL"),
 *   label_plural = @Translation("registros de URL"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\jaraba_site_builder\SiteRedirectAccessControlHandler",
 *   },
 *   base_table = "site_url_history",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   admin_permission = "administer site structure",
 * )
 */
class SiteUrlHistory extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Tenant (multi-tenant).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'group')
            ->setRequired(TRUE);

        // Referencia a la página.
        $fields['page_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Página'))
            ->setSetting('target_type', 'page_content')
            ->setRequired(TRUE);

        // URL anterior.
        $fields['old_path'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL Anterior'))
            ->setSettings(['max_length' => 2048])
            ->setRequired(TRUE);

        // URL nueva.
        $fields['new_path'] = BaseFieldDefinition::create('string')
            ->setLabel(t('URL Nueva'))
            ->setSettings(['max_length' => 2048])
            ->setRequired(TRUE);

        // Tipo de cambio.
        $fields['change_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Cambio'))
            ->setSettings([
                'allowed_values' => [
                    'slug_change' => 'Cambio de slug',
                    'parent_change' => 'Cambio de padre',
                    'manual' => 'Edición manual',
                    'migration' => 'Migración',
                    'bulk' => 'Cambio masivo',
                ],
            ])
            ->setDefaultValue('slug_change');

        // Usuario que realizó el cambio.
        $fields['changed_by'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario'))
            ->setSetting('target_type', 'user');

        // Timestamp del cambio.
        $fields['changed_at'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha de Cambio'));

        // ID del redirect creado (si aplica).
        $fields['redirect_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Redirect Creado'))
            ->setSetting('target_type', 'site_redirect');

        // Notas adicionales.
        $fields['notes'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Notas'));

        return $fields;
    }

    /**
     * Obtiene la página asociada.
     */
    public function getPage(): ?object
    {
        $pageId = $this->get('page_id')->target_id;
        if (!$pageId) {
            return NULL;
        }
        return \Drupal::entityTypeManager()->getStorage('page_content')->load($pageId);
    }

    /**
     * Obtiene el usuario que hizo el cambio.
     */
    public function getChangedBy(): ?object
    {
        $userId = $this->get('changed_by')->target_id;
        if (!$userId) {
            return NULL;
        }
        return \Drupal::entityTypeManager()->getStorage('user')->load($userId);
    }

}
