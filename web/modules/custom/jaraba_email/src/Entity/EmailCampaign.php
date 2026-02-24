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
 * Define la entidad Campaña de Email.
 *
 * PROPÓSITO:
 * Representa una campaña de email marketing con toda su configuración,
 * contenido y métricas de rendimiento.
 *
 * TIPOS DE CAMPAÑA:
 * - regular: Campaña estándar de envío único
 * - ab_test: Campaña con variantes A/B
 * - automated: Campaña automatizada por triggers
 * - newsletter: Newsletter periódico con artículos
 *
 * ESTADOS:
 * - draft: Borrador en edición
 * - scheduled: Programada para envío futuro
 * - sending: En proceso de envío
 * - sent: Enviada completamente
 * - paused: Pausada temporalmente
 * - cancelled: Cancelada
 *
 * MÉTRICAS:
 * - total_recipients, total_sent, total_delivered
 * - total_opens, unique_opens (para calcular open rate)
 * - total_clicks, unique_clicks (para calcular CTR)
 * - bounces, complaints, unsubscribes
 *
 * INTEGRACIÓN CONTENT HUB:
 * Campo article_ids para incluir artículos en newsletters.
 *
 * ESPECIFICACIÓN: Doc 139 - Email_Marketing_Technical_Guide
 *
 * @ContentEntityType(
 *   id = "email_campaign",
 *   label = @Translation("Campaña de Email"),
 *   label_collection = @Translation("Campañas de Email"),
 *   label_singular = @Translation("campaña"),
 *   label_plural = @Translation("campañas"),
 *   label_count = @PluralTranslation(
 *     singular = "@count campaña",
 *     plural = "@count campañas",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_email\EmailCampaignListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_email\Form\EmailCampaignForm",
 *       "edit" = "Drupal\jaraba_email\Form\EmailCampaignForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_email\EmailAccessControlHandler",
 *   },
 *   base_table = "email_campaign",
 *   admin_permission = "administer email campaigns",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "owner" = "created_by",
 *   },
 *   links = {
 *     "canonical" = "/admin/jaraba/email/campaigns/{email_campaign}",
 *     "add-form" = "/admin/jaraba/email/campaigns/add",
 *     "edit-form" = "/admin/jaraba/email/campaigns/{email_campaign}/edit",
 *     "delete-form" = "/admin/jaraba/email/campaigns/{email_campaign}/delete",
 *     "collection" = "/admin/jaraba/email/campaigns",
 *   },
 *   field_ui_base_route = "entity.email_campaign.settings",
 * )
 */
class EmailCampaign extends ContentEntityBase implements EntityOwnerInterface
{

    use EntityChangedTrait;
    use EntityOwnerTrait;

    /**
     * {@inheritdoc}
     *
     * Define los campos base para la entidad EmailCampaign.
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);
        $fields += static::ownerBaseFieldDefinitions($entity_type);

        // Nombre de la campaña - identificador principal.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre de la Campaña'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 0,
            ]);

        // Tipo de campaña: regular, ab_test, automated, newsletter.
        $fields['type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo'))
            ->setDefaultValue('regular')
            ->setSettings([
                'allowed_values' => [
                    'regular' => 'Regular',
                    'ab_test' => 'Prueba A/B',
                    'automated' => 'Automatizada',
                    'newsletter' => 'Newsletter',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 1,
            ]);

        // Estado del ciclo de vida de la campaña.
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setDefaultValue('draft')
            ->setSettings([
                'allowed_values' => [
                    'draft' => 'Borrador',
                    'scheduled' => 'Programada',
                    'sending' => 'Enviando',
                    'sent' => 'Enviada',
                    'paused' => 'Pausada',
                    'cancelled' => 'Cancelada',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 2,
            ]);

        // Referencia a la plantilla de email.
        $fields['template_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Plantilla'))
            ->setSetting('target_type', 'email_template')
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 3,
            ]);

        // Línea de asunto del email.
        $fields['subject_line'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Línea de Asunto'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 4,
            ]);

        // Texto de vista previa (preheader).
        $fields['preview_text'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Texto de Vista Previa'))
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 5,
            ]);

        // Nombre del remitente.
        $fields['from_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre del Remitente'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 100])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 6,
            ]);

        // Email del remitente.
        $fields['from_email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email del Remitente'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => 7,
            ]);

        // Email de respuesta.
        $fields['reply_to'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Responder A'))
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => 8,
            ]);

        // Contenido HTML compilado (desde MJML).
        $fields['body_html'] = BaseFieldDefinition::create('text_long')
            ->setLabel(t('Cuerpo HTML'))
            ->setDescription(t('Contenido HTML compilado.'))
            ->setDisplayOptions('form', [
                'type' => 'text_textarea',
                'weight' => 9,
            ]);

        // Listas de destinatarios objetivo.
        $fields['list_ids'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Listas Objetivo'))
            ->setSetting('target_type', 'email_list')
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
            ->setDisplayOptions('form', [
                'type' => 'entity_reference_autocomplete',
                'weight' => 10,
            ]);

        // Fecha/hora de envío programado.
        $fields['scheduled_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Programada Para'))
            ->setDisplayOptions('form', [
                'type' => 'datetime_default',
                'weight' => 11,
            ]);

        // Fecha/hora de inicio de envío.
        $fields['sent_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Enviada El'));

        // Fecha/hora de finalización de envío.
        $fields['completed_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Completada El'));

        // --- Campos de estadísticas ---

        // Total de destinatarios al iniciar.
        $fields['total_recipients'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total de Destinatarios'))
            ->setDefaultValue(0);

        // Total de emails enviados.
        $fields['total_sent'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Enviados'))
            ->setDefaultValue(0);

        // Total de emails entregados correctamente.
        $fields['total_delivered'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Entregados'))
            ->setDefaultValue(0);

        // Total de aperturas (incluye repetidas).
        $fields['total_opens'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Aperturas'))
            ->setDefaultValue(0);

        // Aperturas únicas (un conteo por suscriptor).
        $fields['unique_opens'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Aperturas Únicas'))
            ->setDefaultValue(0);

        // Total de clics (incluye repetidos).
        $fields['total_clicks'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Clics'))
            ->setDefaultValue(0);

        // Clics únicos (un conteo por suscriptor).
        $fields['unique_clicks'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Clics Únicos'))
            ->setDefaultValue(0);

        // Rebotes (emails no entregados).
        $fields['bounces'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Rebotes'))
            ->setDefaultValue(0);

        // Quejas de spam.
        $fields['complaints'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Quejas'))
            ->setDefaultValue(0);

        // Desuscripciones desde esta campaña.
        $fields['unsubscribes'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Desuscripciones'))
            ->setDefaultValue(0);

        // --- Integración con Content Hub ---

        // Artículos destacados para newsletters.
        $fields['article_ids'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Artículos Destacados'))
            ->setDescription(t('Artículos para incluir en newsletter.'))
            ->setSetting('target_type', 'content_article')
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

        // Referencia al tenant (grupo) propietario.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'group');

        // Timestamps automáticos.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creada'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificada'));

        return $fields;
    }

    /**
     * Obtiene el nombre de la campaña.
     *
     * @return string
     *   El nombre de la campaña.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Obtiene el estado actual de la campaña.
     *
     * @return string
     *   El estado: draft, scheduled, sending, sent, paused, cancelled.
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? 'draft';
    }

    /**
     * Verifica si la campaña puede ser enviada.
     *
     * Solo se pueden enviar campañas en estado draft, scheduled o paused.
     *
     * @return bool
     *   TRUE si la campaña puede enviarse.
     */
    public function canSend(): bool
    {
        return in_array($this->getStatus(), ['draft', 'scheduled', 'paused']);
    }

    /**
     * Calcula la tasa de apertura.
     *
     * Fórmula: (unique_opens / total_delivered) * 100
     *
     * @return float
     *   El porcentaje de apertura (0-100).
     */
    public function getOpenRate(): float
    {
        $delivered = (int) $this->get('total_delivered')->value;
        if ($delivered === 0) {
            return 0.0;
        }
        return ((int) $this->get('unique_opens')->value / $delivered) * 100;
    }

    /**
     * Calcula la tasa de clics (CTR).
     *
     * Fórmula: (unique_clicks / total_delivered) * 100
     *
     * @return float
     *   El porcentaje de clics (0-100).
     */
    public function getClickRate(): float
    {
        $delivered = (int) $this->get('total_delivered')->value;
        if ($delivered === 0) {
            return 0.0;
        }
        return ((int) $this->get('unique_clicks')->value / $delivered) * 100;
    }

}
