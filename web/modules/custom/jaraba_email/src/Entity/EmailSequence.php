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
 * Define la entidad Secuencia de Email (campañas drip).
 *
 * PROPÓSITO:
 * Representa una secuencia automatizada de emails (drip campaign)
 * que se envían a suscriptores basándose en triggers y tiempos.
 *
 * CATEGORÍAS:
 * - onboarding: Serie de bienvenida para nuevos suscriptores
 * - nurture: Nutrición de leads con contenido educativo
 * - sales: Secuencia de ventas
 * - reengagement: Recuperación de usuarios inactivos
 * - post_purchase: Seguimiento post-compra
 * - custom: Secuencia personalizada
 *
 * TIPOS DE TRIGGER:
 * - list_subscription: Al suscribirse a una lista
 * - tag_added: Al agregar un tag específico
 * - event: Por evento disparado
 * - date_field: Basado en campo de fecha (cumpleaños, etc.)
 * - manual: Inscripción manual
 * - api: Vía API REST
 *
 * MÉTRICAS:
 * - total_enrolled: Total histórico de inscritos
 * - currently_enrolled: Actualmente en la secuencia
 * - completed: Que completaron toda la secuencia
 *
 * ESPECIFICACIÓN: Doc 139 - Email_Marketing_Technical_Guide
 *
 * @ContentEntityType(
 *   id = "email_sequence",
 *   label = @Translation("Secuencia de Email"),
 *   label_collection = @Translation("Secuencias de Email"),
 *   label_singular = @Translation("secuencia"),
 *   label_plural = @Translation("secuencias"),
 *   label_count = @PluralTranslation(
 *     singular = "@count secuencia",
 *     plural = "@count secuencias",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_email\EmailSequenceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_email\Form\EmailSequenceForm",
 *       "edit" = "Drupal\jaraba_email\Form\EmailSequenceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_email\EmailAccessControlHandler",
 *   },
 *   base_table = "email_sequence",
 *   admin_permission = "administer email sequences",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/admin/jaraba/email/sequences/{email_sequence}",
 *     "add-form" = "/admin/jaraba/email/sequences/add",
 *     "edit-form" = "/admin/jaraba/email/sequences/{email_sequence}/edit",
 *     "delete-form" = "/admin/jaraba/email/sequences/{email_sequence}/delete",
 *     "collection" = "/admin/jaraba/email/sequences",
 *   },
 *   field_ui_base_route = "entity.email_sequence.settings",
 * )
 */
class EmailSequence extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     *
     * Define los campos base para la entidad EmailSequence.
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Nombre de la secuencia.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre de la Secuencia'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ]);

        // Descripción de la secuencia.
        $fields['description'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Descripción'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 1,
            ]);

        // Categoría de la secuencia.
        $fields['category'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Categoría'))
            ->setSettings([
                'allowed_values' => [
                    'onboarding' => 'Onboarding',
                    'nurture' => 'Nutrición',
                    'sales' => 'Ventas',
                    'reengagement' => 'Reactivación',
                    'post_purchase' => 'Post-Compra',
                    'custom' => 'Personalizada',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ]);

        // Vertical de negocio.
        $fields['vertical'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Vertical'))
            ->setDefaultValue('all')
            ->setSettings([
                'allowed_values' => [
                    'all' => 'Todas las Verticales',
                    'empleabilidad' => 'Empleabilidad',
                    'emprendimiento' => 'Emprendimiento',
                    'agroconecta' => 'AgroConecta',
                    'comercioconecta' => 'ComercioConecta',
                    'serviciosconecta' => 'ServiciosConecta',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ]);

        // Tipo de trigger que inicia la secuencia.
        $fields['trigger_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Trigger'))
            ->setRequired(TRUE)
            ->setSettings([
                'allowed_values' => [
                    'list_subscription' => 'Suscripción a Lista',
                    'tag_added' => 'Tag Agregado',
                    'event' => 'Evento Disparado',
                    'date_field' => 'Campo de Fecha',
                    'manual' => 'Inscripción Manual',
                    'api' => 'API',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 4,
            ]);

        // Configuración del trigger en formato JSON.
        $fields['trigger_config'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Configuración del Trigger'))
            ->setDescription(t('Configuración JSON del trigger.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 5,
            ]);

        // Condiciones de entrada para inscribirse.
        $fields['entry_conditions'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Condiciones de Entrada'))
            ->setDescription(t('Condiciones JSON para inscripción.'));

        // Condiciones de salida anticipada.
        $fields['exit_conditions'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Condiciones de Salida'))
            ->setDescription(t('Condiciones JSON para salida anticipada.'));

        // Si es una secuencia de sistema (no editable).
        $fields['is_system'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Secuencia de Sistema'))
            ->setDefaultValue(FALSE);

        // Estado activo/inactivo.
        $fields['is_active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 6,
            ]);

        // --- Campos de estadísticas ---

        // Total histórico de inscritos.
        $fields['total_enrolled'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Inscritos'))
            ->setDefaultValue(0);

        // Actualmente en la secuencia.
        $fields['currently_enrolled'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Actualmente Inscritos'))
            ->setDefaultValue(0);

        // Que completaron la secuencia.
        $fields['completed'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Completados'))
            ->setDefaultValue(0);

        // Referencia al tenant (NULL = secuencia global).
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setDescription(t('NULL = secuencia global.'))
            ->setSetting('target_type', 'group');

        // Timestamps automáticos.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creada'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificada'));

        return $fields;
    }

    /**
     * Obtiene el nombre de la secuencia.
     *
     * @return string
     *   El nombre de la secuencia.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Verifica si la secuencia está activa.
     *
     * @return bool
     *   TRUE si la secuencia está activa.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('is_active')->value;
    }

    /**
     * Calcula la tasa de completitud.
     *
     * Fórmula: (completed / total_enrolled) * 100
     *
     * @return float
     *   El porcentaje de completitud (0-100).
     */
    public function getCompletionRate(): float
    {
        $total = (int) $this->get('total_enrolled')->value;
        if ($total === 0) {
            return 0.0;
        }
        return ((int) $this->get('completed')->value / $total) * 100;
    }

}
