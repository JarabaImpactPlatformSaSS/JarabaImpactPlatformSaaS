<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Define la entidad Lista de Email.
 *
 * PROPÓSITO:
 * Representa una lista de suscriptores para segmentación y envío.
 * Permite organizar audiencias y asignar secuencias de bienvenida.
 *
 * TIPOS DE LISTA:
 * - static: Lista manual con suscriptores agregados individualmente
 * - dynamic: Lista basada en query que se actualiza automáticamente
 * - segment: Segmento derivado de condiciones específicas
 *
 * CARACTERÍSTICAS:
 * - Double opt-in configurable por lista
 * - Secuencia de bienvenida automática
 * - Contador de suscriptores desnormalizado para rendimiento
 * - Soporte multi-tenant
 *
 * ESPECIFICACIÓN: Doc 139 - Email_Marketing_Technical_Guide
 *
 * @ContentEntityType(
 *   id = "email_list",
 *   label = @Translation("Lista de Email"),
 *   label_collection = @Translation("Listas de Email"),
 *   label_singular = @Translation("lista de email"),
 *   label_plural = @Translation("listas de email"),
 *   label_count = @PluralTranslation(
 *     singular = "@count lista de email",
 *     plural = "@count listas de email",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_email\EmailListListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_email\Form\EmailListForm",
 *       "edit" = "Drupal\jaraba_email\Form\EmailListForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_email\EmailAccessControlHandler",
 *   },
 *   base_table = "email_list",
 *   admin_permission = "administer email lists",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/jaraba/email/lists/{email_list}",
 *     "add-form" = "/admin/jaraba/email/lists/add",
 *     "edit-form" = "/admin/jaraba/email/lists/{email_list}/edit",
 *     "delete-form" = "/admin/jaraba/email/lists/{email_list}/delete",
 *     "collection" = "/admin/jaraba/email/lists",
 *   },
 *   field_ui_base_route = "entity.email_list.settings",
 * )
 */
class EmailList extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     *
     * Define los campos base para la entidad EmailList.
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Nombre de la lista.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre de la Lista'))
            ->setDescription(t('El nombre de la lista de email.'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ])
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
                'weight' => 0,
            ]);

        // Descripción de la lista.
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDescription(t('Una descripción de la lista.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ]);

        // Tipo de lista: static, dynamic, segment.
        $fields['type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo'))
            ->setDescription(t('El tipo de lista.'))
            ->setRequired(TRUE)
            ->setDefaultValue('static')
            ->setSettings([
                'allowed_values' => [
                    'static' => 'Estática',
                    'dynamic' => 'Dinámica',
                    'segment' => 'Segmento',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ]);

        // Query JSON para listas dinámicas.
        $fields['segment_query'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Query del Segmento'))
            ->setDescription(t('Query JSON para listas dinámicas.'));

        // Configuración de double opt-in.
        $fields['double_optin'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Double Opt-in'))
            ->setDescription(t('Requiere confirmación por email para nuevos suscriptores.'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 3,
            ]);

        // Secuencia de bienvenida automática.
        $fields['welcome_sequence_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Secuencia de Bienvenida'))
            ->setDescription(t('Secuencia para inscribir nuevos suscriptores.'))
            ->setSetting('target_type', 'email_sequence')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 4,
            ]);

        // Contador desnormalizado de suscriptores.
        $fields['subscriber_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Número de Suscriptores'))
            ->setDescription(t('Contador desnormalizado de suscriptores.'))
            ->setDefaultValue(0);

        // Estado activo/inactivo.
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 5,
            ]);

        // Referencia al tenant.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('El tenant al que pertenece esta lista.'))
            ->setSetting('target_type', 'group');

        // Timestamps automáticos.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creada'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificada'));

        return $fields;
    }

    /**
     * Obtiene el nombre de la lista.
     *
     * @return string
     *   El nombre de la lista.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Obtiene el número de suscriptores.
     *
     * @return int
     *   El contador de suscriptores.
     */
    public function getSubscriberCount(): int
    {
        return (int) $this->get('subscriber_count')->value;
    }

    /**
     * Incrementa el contador de suscriptores.
     *
     * Llamar después de agregar un nuevo suscriptor.
     */
    public function incrementSubscriberCount(): void
    {
        $this->set('subscriber_count', $this->getSubscriberCount() + 1);
    }

    /**
     * Decrementa el contador de suscriptores.
     *
     * Llamar después de eliminar un suscriptor.
     * No permite valores negativos.
     */
    public function decrementSubscriberCount(): void
    {
        $count = $this->getSubscriberCount();
        $this->set('subscriber_count', max(0, $count - 1));
    }

}
