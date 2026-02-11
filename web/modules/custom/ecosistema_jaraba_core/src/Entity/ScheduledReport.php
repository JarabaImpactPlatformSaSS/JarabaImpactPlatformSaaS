<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad ScheduledReport.
 *
 * Permite programar reportes automáticos que se envían
 * periódicamente por email o webhook.
 *
 * @ContentEntityType(
 *   id = "scheduled_report",
 *   label = @Translation("Reporte Programado"),
 *   label_collection = @Translation("Reportes Programados"),
 *   label_singular = @Translation("reporte programado"),
 *   label_plural = @Translation("reportes programados"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\Entity\Handler\ScheduledReportListBuilder",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "scheduled_report",
 *   admin_permission = "administer scheduled reports",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/config/system/scheduled-reports",
 *     "add-form" = "/admin/config/system/scheduled-reports/add",
 *     "edit-form" = "/admin/config/system/scheduled-reports/{scheduled_report}/edit",
 *     "delete-form" = "/admin/config/system/scheduled-reports/{scheduled_report}/delete",
 *   },
 * )
 */
class ScheduledReport extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * Tipos de reporte disponibles.
     */
    public const TYPE_FINANCIAL = 'financial';
    public const TYPE_TENANT_HEALTH = 'tenant_health';
    public const TYPE_AI_USAGE = 'ai_usage';
    public const TYPE_SECURITY = 'security';
    public const TYPE_CUSTOM = 'custom';

    /**
     * Frecuencias disponibles.
     */
    public const FREQ_DAILY = 'daily';
    public const FREQ_WEEKLY = 'weekly';
    public const FREQ_MONTHLY = 'monthly';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Nombre del reporte.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ])
            ->setDisplayOptions('view', [
                'label' => 'hidden',
                'type' => 'string',
            ]);

        // Tipo de reporte.
        $fields['report_type'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Tipo de Reporte'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::TYPE_FINANCIAL => t('Resumen Financiero (MRR, Churn)'),
                self::TYPE_TENANT_HEALTH => t('Salud de Tenants'),
                self::TYPE_AI_USAGE => t('Uso de IA'),
                self::TYPE_SECURITY => t('Auditoría de Seguridad'),
                self::TYPE_CUSTOM => t('Reporte Personalizado'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ]);

        // Frecuencia.
        $fields['frequency'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Frecuencia'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::FREQ_DAILY => t('Diario'),
                self::FREQ_WEEKLY => t('Semanal'),
                self::FREQ_MONTHLY => t('Mensual'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 5,
            ]);

        // Hora de envío (0-23).
        $fields['send_hour'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Hora de Envío'))
            ->setDescription(t('Hora del día (0-23) para enviar el reporte.'))
            ->setSetting('min', 0)
            ->setSetting('max', 23)
            ->setDefaultValue(8)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 10,
            ]);

        // Destinatarios (emails separados por coma).
        $fields['recipients'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Destinatarios'))
            ->setDescription(t('Emails separados por coma.'))
            ->setRequired(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 15,
            ]);

        // Formato de salida.
        $fields['format'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Formato'))
            ->setSetting('allowed_values', [
                'html' => t('HTML (Email)'),
                'pdf' => t('PDF adjunto'),
                'csv' => t('CSV adjunto'),
            ])
            ->setDefaultValue('html')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ]);

        // Webhook URL (opcional, alternativa a email).
        $fields['webhook_url'] = BaseFieldDefinition::create('uri')
            ->setLabel(t('Webhook URL'))
            ->setDescription(t('URL para enviar el reporte como JSON (opcional).'))
            ->setDisplayOptions('form', [
                'type' => 'uri',
                'weight' => 25,
            ]);

        // Activo/Pausado.
        $fields['active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activo'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 30,
            ]);

        // Última ejecución.
        $fields['last_run'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Última Ejecución'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
            ]);

        // Próxima ejecución.
        $fields['next_run'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Próxima Ejecución'))
            ->setDisplayOptions('view', [
                'label' => 'inline',
                'type' => 'timestamp',
            ]);

        // Timestamps estándar.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Actualizado'));

        return $fields;
    }

    /**
     * Obtiene el nombre del reporte.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Verifica si está activo.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('active')->value;
    }

    /**
     * Obtiene los destinatarios como array.
     */
    public function getRecipients(): array
    {
        $recipients = $this->get('recipients')->value ?? '';
        return array_filter(array_map('trim', explode(',', $recipients)));
    }

    /**
     * Calcula la próxima ejecución basada en frecuencia.
     */
    public function calculateNextRun(): int
    {
        $frequency = $this->get('frequency')->value;
        $hour = (int) $this->get('send_hour')->value;

        $now = new \DateTime();
        $next = new \DateTime();
        $next->setTime($hour, 0, 0);

        // Si ya pasó la hora hoy, empezar mañana.
        if ($now > $next) {
            $next->modify('+1 day');
        }

        switch ($frequency) {
            case self::FREQ_WEEKLY:
                // Siguiente lunes.
                if ($next->format('N') != 1) {
                    $next->modify('next monday');
                }
                break;

            case self::FREQ_MONTHLY:
                // Primer día del siguiente mes.
                $next->modify('first day of next month');
                break;
        }

        return $next->getTimestamp();
    }

}
