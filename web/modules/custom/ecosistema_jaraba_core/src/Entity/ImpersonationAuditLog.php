<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad ImpersonationAuditLog.
 *
 * Registra todos los eventos de impersonación de usuarios
 * para auditoría de seguridad y compliance.
 *
 * @ContentEntityType(
 *   id = "impersonation_audit_log",
 *   label = @Translation("Registro de Impersonación"),
 *   label_collection = @Translation("Registros de Impersonación"),
 *   label_singular = @Translation("registro de impersonación"),
 *   label_plural = @Translation("registros de impersonación"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "impersonation_audit_log",
 *   admin_permission = "administer impersonation",
 *   links = {
 *     "collection" = "/admin/content/impersonation-audit-log",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   field_ui_base_route = "entity.impersonation_audit_log.settings",
 * )
 */
class ImpersonationAuditLog extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // ID del administrador que inicia la impersonación.
        $fields['admin_uid'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Administrador'))
            ->setDescription(t('Usuario administrador que inició la impersonación.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
            ]);

        // ID del usuario objetivo (tenant).
        $fields['target_uid'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Usuario Objetivo'))
            ->setDescription(t('Usuario como el cual se está impersonando.'))
            ->setSetting('target_type', 'user')
            ->setRequired(TRUE)
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
            ]);

        // Tenant relacionado.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('Tenant al que pertenece el usuario objetivo.'))
            ->setSetting('target_type', 'tenant')
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'entity_reference_label',
            ]);

        // Tipo de evento.
        $fields['event_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Evento'))
            ->setDescription(t('Inicio o fin de la sesión de impersonación.'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                'start' => t('Inicio de sesión'),
                'end' => t('Fin de sesión'),
                'timeout' => t('Timeout automático'),
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'list_default',
            ]);

        // Timestamp del evento.
        $fields['event_time'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Fecha del Evento'))
            ->setDescription(t('Momento en que ocurrió el evento.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
                'settings' => [
                    'date_format' => 'medium',
                ],
            ]);

        // Duración de la sesión (solo para eventos 'end' o 'timeout').
        $fields['session_duration'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Duración (segundos)'))
            ->setDescription(t('Duración total de la sesión de impersonación.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'number_integer',
            ]);

        // IP del administrador.
        $fields['admin_ip'] = BaseFieldDefinition::create('string')
            ->setLabel(t('IP Administrador'))
            ->setDescription(t('Dirección IP desde la que se inició la impersonación.'))
            ->setSettings([
                'max_length' => 45,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
            ]);

        // User agent.
        $fields['user_agent'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('User Agent'))
            ->setDescription(t('Navegador desde el que se inició la impersonación.'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'basic_string',
            ]);

        // Razón proporcionada (opcional).
        $fields['reason'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Razón'))
            ->setDescription(t('Motivo de la impersonación (opcional).'))
            ->setSettings([
                'max_length' => 255,
            ])
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'string',
            ]);

        return $fields;
    }

    /**
     * Obtiene el administrador que inició la impersonación.
     */
    public function getAdmin(): ?object
    {
        return $this->get('admin_uid')->entity;
    }

    /**
     * Obtiene el usuario objetivo.
     */
    public function getTargetUser(): ?object
    {
        return $this->get('target_uid')->entity;
    }

    /**
     * Obtiene el tipo de evento.
     */
    public function getEventType(): string
    {
        return $this->get('event_type')->value ?? '';
    }

    /**
     * Obtiene la duración de la sesión en formato legible.
     */
    public function getFormattedDuration(): string
    {
        $seconds = (int) $this->get('session_duration')->value;
        if ($seconds < 60) {
            return $seconds . ' ' . t('segundos');
        }
        $minutes = floor($seconds / 60);
        return $minutes . ' ' . t('minutos');
    }

}
