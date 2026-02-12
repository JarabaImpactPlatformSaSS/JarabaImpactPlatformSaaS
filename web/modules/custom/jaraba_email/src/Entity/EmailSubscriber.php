<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Define la entidad Suscriptor de Email.
 *
 * PROPÓSITO:
 * Representa un suscriptor de email con su información de contacto,
 * preferencias, historial de interacción y métricas de engagement.
 *
 * ESTADOS:
 * - pending: Pendiente de confirmación (double opt-in)
 * - subscribed: Activo y recibiendo emails
 * - unsubscribed: Dado de baja voluntariamente
 * - bounced: Email rebotado (hard bounce)
 * - complained: Marcó como spam
 *
 * FUENTES DE SUSCRIPCIÓN:
 * - form: Formulario web
 * - import: Importación masiva
 * - api: API REST
 * - manual: Agregado manualmente
 * - lead_magnet: Lead magnet/descargable
 *
 * ENGAGEMENT SCORE:
 * Puntuación 0-100 que refleja la actividad del suscriptor.
 * - Se incrementa +2 por cada apertura
 * - Se incrementa +5 por cada clic
 * - Decae con el tiempo si no hay actividad
 *
 * GDPR:
 * Campos específicos para cumplimiento GDPR:
 * - gdpr_consent: Si dio consentimiento
 * - gdpr_consent_at: Fecha del consentimiento
 * - ip_address: IP desde donde se suscribió
 *
 * ESPECIFICACIÓN: Doc 139 - Email_Marketing_Technical_Guide
 *
 * @ContentEntityType(
 *   id = "email_subscriber",
 *   label = @Translation("Suscriptor de Email"),
 *   label_collection = @Translation("Suscriptores de Email"),
 *   label_singular = @Translation("suscriptor"),
 *   label_plural = @Translation("suscriptores"),
 *   label_count = @PluralTranslation(
 *     singular = "@count suscriptor",
 *     plural = "@count suscriptores",
 *   ),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "list_builder" = "Drupal\jaraba_email\EmailSubscriberListBuilder",
 *     "form" = {
 *       "add" = "Drupal\jaraba_email\Form\EmailSubscriberForm",
 *       "edit" = "Drupal\jaraba_email\Form\EmailSubscriberForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\jaraba_email\EmailAccessControlHandler",
 *   },
 *   base_table = "email_subscriber",
 *   admin_permission = "administer email subscribers",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "email",
 *   },
 *   links = {
 *     "canonical" = "/admin/jaraba/email/subscribers/{email_subscriber}",
 *     "add-form" = "/admin/jaraba/email/subscribers/add",
 *     "edit-form" = "/admin/jaraba/email/subscribers/{email_subscriber}/edit",
 *     "delete-form" = "/admin/jaraba/email/subscribers/{email_subscriber}/delete",
 *     "collection" = "/admin/jaraba/email/subscribers",
 *   },
 * )
 */
class EmailSubscriber extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * {@inheritdoc}
     *
     * Define los campos base para la entidad EmailSubscriber.
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Email del suscriptor - campo principal y único.
        $fields['email'] = BaseFieldDefinition::create('email')
            ->setLabel(t('Email'))
            ->setDescription(t('La dirección de email del suscriptor.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'email_default',
                'weight' => 0,
            ])
            ->setDisplayOptions('view', [
                'type' => 'basic_string',
                'weight' => 0,
            ]);

        // Nombre del suscriptor.
        $fields['first_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setSettings(['max_length' => 100])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 1,
            ]);

        // Apellido del suscriptor.
        $fields['last_name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Apellido'))
            ->setSettings(['max_length' => 100])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => 2,
            ]);

        // Estado del suscriptor en el ciclo de vida.
        $fields['status'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Estado'))
            ->setRequired(TRUE)
            ->setDefaultValue('pending')
            ->setSettings([
                'allowed_values' => [
                    'pending' => 'Pendiente',
                    'subscribed' => 'Suscrito',
                    'unsubscribed' => 'Desuscrito',
                    'bounced' => 'Rebotado',
                    'complained' => 'Quejado',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 3,
            ]);

        // Fuente de la suscripción.
        $fields['source'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Fuente'))
            ->setSettings([
                'allowed_values' => [
                    'form' => 'Formulario',
                    'import' => 'Importación',
                    'api' => 'API',
                    'manual' => 'Manual',
                    'lead_magnet' => 'Lead Magnet',
                ],
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 4,
            ]);

        // Detalle de la fuente (nombre del formulario, archivo, etc.).
        $fields['source_detail'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Detalle de Fuente'))
            ->setSettings(['max_length' => 255]);

        // --- Campos GDPR ---

        // Consentimiento GDPR.
        $fields['gdpr_consent'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Consentimiento GDPR'))
            ->setDefaultValue(FALSE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 5,
            ]);

        // Fecha del consentimiento GDPR.
        $fields['gdpr_consent_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Fecha de Consentimiento GDPR'));

        // Fecha de confirmación (double opt-in).
        $fields['confirmed_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Confirmado El'));

        // Fecha de desuscripción.
        $fields['unsubscribed_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Desuscrito El'));

        // Razón de la desuscripción.
        $fields['unsubscribe_reason'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Razón de Desuscripción'))
            ->setSettings(['max_length' => 255]);

        // --- Campos personalizados y tags ---

        // Campos personalizados en formato JSON.
        $fields['custom_fields'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Campos Personalizados'))
            ->setDescription(t('Campos personalizados en JSON.'));

        // Tags del suscriptor en formato JSON.
        $fields['tags'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Tags'))
            ->setDescription(t('Array JSON de tags.'));

        // --- Métricas de engagement ---

        // Puntuación de engagement (0-100).
        $fields['engagement_score'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Puntuación de Engagement'))
            ->setDefaultValue(50)
            ->setDescription(t('Puntuación 0-100 basada en aperturas y clics.'));

        // Fecha del último email enviado.
        $fields['last_email_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Último Email Enviado'));

        // Fecha de la última apertura.
        $fields['last_open_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Última Apertura'));

        // Fecha del último clic.
        $fields['last_click_at'] = BaseFieldDefinition::create('datetime')
            ->setLabel(t('Último Clic'));

        // Contadores acumulados.
        $fields['total_emails_sent'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Emails Enviados'))
            ->setDefaultValue(0);

        $fields['total_opens'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Aperturas'))
            ->setDefaultValue(0);

        $fields['total_clicks'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Total Clics'))
            ->setDefaultValue(0);

        // --- Campos de relación ---

        // Referencia al tenant (grupo) propietario.
        $fields['tenant_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Tenant'))
            ->setSetting('target_type', 'group');

        // Listas a las que pertenece el suscriptor.
        $fields['lists'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('Listas'))
            ->setDescription(t('Listas a las que pertenece este suscriptor.'))
            ->setSetting('target_type', 'email_list')
            ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED);

        // IP de registro (para cumplimiento GDPR).
        $fields['ip_address'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Dirección IP'))
            ->setSettings(['max_length' => 45]);

        // Timestamps automáticos.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Modificado'));

        return $fields;
    }

    /**
     * Obtiene el email del suscriptor.
     *
     * @return string
     *   La dirección de email.
     */
    public function getEmail(): string
    {
        return $this->get('email')->value ?? '';
    }

    /**
     * Obtiene el nombre completo del suscriptor.
     *
     * @return string
     *   El nombre completo (nombre + apellido).
     */
    public function getFullName(): string
    {
        $first = $this->get('first_name')->value ?? '';
        $last = $this->get('last_name')->value ?? '';
        return trim("{$first} {$last}");
    }

    /**
     * Obtiene el estado del suscriptor.
     *
     * @return string
     *   El estado: pending, subscribed, unsubscribed, bounced, complained.
     */
    public function getStatus(): string
    {
        return $this->get('status')->value ?? 'pending';
    }

    /**
     * Verifica si el suscriptor está activo.
     *
     * @return bool
     *   TRUE si el estado es 'subscribed'.
     */
    public function isSubscribed(): bool
    {
        return $this->getStatus() === 'subscribed';
    }

    /**
     * Obtiene la puntuación de engagement.
     *
     * @return int
     *   Puntuación de 0 a 100.
     */
    public function getEngagementScore(): int
    {
        return (int) $this->get('engagement_score')->value;
    }

    /**
     * Registra una apertura de email.
     *
     * Incrementa el contador de aperturas, actualiza la fecha
     * de última apertura y aumenta el engagement score en +2.
     */
    public function recordOpen(): void
    {
        $this->set('total_opens', ((int) $this->get('total_opens')->value) + 1);
        $this->set('last_open_at', date('Y-m-d\TH:i:s'));
        // Incrementar engagement score (máximo 100).
        $score = min(100, $this->getEngagementScore() + 2);
        $this->set('engagement_score', $score);
    }

    /**
     * Registra un clic en un enlace.
     *
     * Incrementa el contador de clics, actualiza la fecha
     * de último clic y aumenta el engagement score en +5.
     */
    public function recordClick(): void
    {
        $this->set('total_clicks', ((int) $this->get('total_clicks')->value) + 1);
        $this->set('last_click_at', date('Y-m-d\TH:i:s'));
        // Los clics incrementan más el engagement score.
        $score = min(100, $this->getEngagementScore() + 5);
        $this->set('engagement_score', $score);
    }

}
