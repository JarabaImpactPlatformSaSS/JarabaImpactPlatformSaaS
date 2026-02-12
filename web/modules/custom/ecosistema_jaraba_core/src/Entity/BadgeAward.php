<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad BadgeAward (Otorgamiento de Insignia).
 *
 * Registra cada instancia de otorgamiento de una insignia a un usuario.
 * Un usuario no puede recibir la misma insignia dos veces (el servicio
 * BadgeAwardService controla duplicados).
 *
 * @ContentEntityType(
 *   id = "badge_award",
 *   label = @Translation("Otorgamiento de Insignia"),
 *   label_collection = @Translation("Otorgamientos de Insignias"),
 *   label_singular = @Translation("otorgamiento de insignia"),
 *   label_plural = @Translation("otorgamientos de insignias"),
 *   handlers = {
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "badge_award",
 *   admin_permission = "administer tenants",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/config/badge-awards",
 *     "canonical" = "/admin/config/badge-awards/{badge_award}",
 *     "delete-form" = "/admin/config/badge-awards/{badge_award}/delete",
 *   },
 * )
 */
class BadgeAward extends ContentEntityBase
{

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Referencia a la insignia otorgada.
        $fields['badge_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Insignia'))
            ->setDescription(t('La insignia que fue otorgada.'))
            ->setSetting('target_type', 'badge')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => 0,
            ]);

        // Referencia al usuario que recibió la insignia.
        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario'))
            ->setDescription(t('El usuario al que se le otorgó la insignia.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => 1,
            ]);

        // Referencia al tenant (grupo).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant en el contexto del cual se otorgó la insignia.'))
            ->setSetting('target_type', 'group')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
                'weight' => 2,
            ]);

        // Motivo del otorgamiento.
        $fields['awarded_reason'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Motivo'))
            ->setDescription(t('Razón por la que se otorgó la insignia.'))
            ->setSettings([
                'max_length' => 255,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
                'weight' => 3,
            ]);

        // Fecha de otorgamiento.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Otorgada'))
            ->setDescription(t('Fecha en que se otorgó la insignia.'));

        return $fields;
    }

    /**
     * Obtiene la entidad Badge asociada.
     */
    public function getBadge(): ?Badge
    {
        $entity = $this->get('badge_id')->entity;
        return $entity instanceof Badge ? $entity : NULL;
    }

    /**
     * Obtiene el ID de la insignia.
     */
    public function getBadgeId(): int
    {
        return (int) ($this->get('badge_id')->target_id ?? 0);
    }

    /**
     * Obtiene el ID del usuario.
     */
    public function getUserId(): int
    {
        return (int) ($this->get('user_id')->target_id ?? 0);
    }

    /**
     * Obtiene el ID del tenant.
     */
    public function getTenantId(): ?int
    {
        $value = $this->get('tenant_id')->target_id;
        return $value !== NULL ? (int) $value : NULL;
    }

    /**
     * Obtiene el motivo del otorgamiento.
     */
    public function getAwardedReason(): string
    {
        return $this->get('awarded_reason')->value ?? '';
    }

}
