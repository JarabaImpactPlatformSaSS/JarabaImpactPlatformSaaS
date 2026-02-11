<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Define la entidad AlertRule.
 *
 * Permite configurar reglas de alerta basadas en condiciones
 * que disparan notificaciones automáticas.
 *
 * @ContentEntityType(
 *   id = "alert_rule",
 *   label = @Translation("Regla de Alerta"),
 *   label_collection = @Translation("Reglas de Alerta"),
 *   label_singular = @Translation("regla de alerta"),
 *   label_plural = @Translation("reglas de alerta"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\Entity\Handler\AlertRuleListBuilder",
 *     "form" = {
 *       "add" = "Drupal\Core\Entity\ContentEntityForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "alert_rule",
 *   admin_permission = "administer alert rules",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "collection" = "/admin/config/system/alert-rules",
 *     "add-form" = "/admin/config/system/alert-rules/add",
 *     "edit-form" = "/admin/config/system/alert-rules/{alert_rule}/edit",
 *     "delete-form" = "/admin/config/system/alert-rules/{alert_rule}/delete",
 *   },
 * )
 */
class AlertRule extends ContentEntityBase
{

    use EntityChangedTrait;

    /**
     * Tipos de métricas monitoreables.
     */
    public const METRIC_MRR = 'mrr';
    public const METRIC_CHURN = 'churn';
    public const METRIC_AI_TOKENS = 'ai_tokens';
    public const METRIC_ERROR_RATE = 'error_rate';
    public const METRIC_RESPONSE_TIME = 'response_time';

    /**
     * Operadores de condición.
     */
    public const OP_GREATER = 'gt';
    public const OP_LESS = 'lt';
    public const OP_EQUALS = 'eq';
    public const OP_CHANGE_PERCENT = 'change_pct';

    /**
     * Canales de notificación.
     */
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SLACK = 'slack';
    public const CHANNEL_WEBHOOK = 'webhook';

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        // Nombre de la regla.
        $fields['name'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Nombre'))
            ->setRequired(TRUE)
            ->setSettings(['max_length' => 255])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -10,
            ]);

        // Descripción.
        $fields['description'] = BaseFieldDefinition::create('string')
            ->setLabel(t('Descripción'))
            ->setSettings(['max_length' => 500])
            ->setDisplayOptions('form', [
                'type' => 'string_textfield',
                'weight' => -5,
            ]);

        // Métrica a monitorear.
        $fields['metric'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Métrica'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::METRIC_MRR => t('MRR (Monthly Recurring Revenue)'),
                self::METRIC_CHURN => t('Churn Rate'),
                self::METRIC_AI_TOKENS => t('Tokens IA consumidos'),
                self::METRIC_ERROR_RATE => t('Tasa de errores'),
                self::METRIC_RESPONSE_TIME => t('Tiempo de respuesta'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 0,
            ]);

        // Operador de condición.
        $fields['operator'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Condición'))
            ->setRequired(TRUE)
            ->setSetting('allowed_values', [
                self::OP_GREATER => t('Mayor que'),
                self::OP_LESS => t('Menor que'),
                self::OP_EQUALS => t('Igual a'),
                self::OP_CHANGE_PERCENT => t('Cambio porcentual'),
            ])
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 5,
            ]);

        // Valor umbral.
        $fields['threshold'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Umbral'))
            ->setRequired(TRUE)
            ->setSettings([
                'precision' => 10,
                'scale' => 2,
            ])
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 10,
            ]);

        // Canales de notificación (serializado).
        $fields['channels'] = BaseFieldDefinition::create('string_long')
            ->setLabel(t('Canales'))
            ->setDescription(t('JSON con configuración de canales.'))
            ->setDisplayOptions('form', [
                'type' => 'string_textarea',
                'weight' => 15,
            ]);

        // Severidad.
        $fields['severity'] = BaseFieldDefinition::create('list_string')
            ->setLabel(t('Severidad'))
            ->setSetting('allowed_values', [
                'info' => t('Informativo'),
                'warning' => t('Advertencia'),
                'critical' => t('Crítico'),
            ])
            ->setDefaultValue('warning')
            ->setDisplayOptions('form', [
                'type' => 'options_select',
                'weight' => 20,
            ]);

        // Cooldown (minutos entre alertas).
        $fields['cooldown_minutes'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Cooldown (minutos)'))
            ->setDescription(t('Tiempo mínimo entre alertas consecutivas.'))
            ->setDefaultValue(60)
            ->setDisplayOptions('form', [
                'type' => 'number',
                'weight' => 25,
            ]);

        // Activa.
        $fields['active'] = BaseFieldDefinition::create('boolean')
            ->setLabel(t('Activa'))
            ->setDefaultValue(TRUE)
            ->setDisplayOptions('form', [
                'type' => 'boolean_checkbox',
                'weight' => 30,
            ]);

        // Última vez disparada.
        $fields['last_triggered'] = BaseFieldDefinition::create('timestamp')
            ->setLabel(t('Última Activación'));

        // Conteo de activaciones.
        $fields['trigger_count'] = BaseFieldDefinition::create('integer')
            ->setLabel(t('Veces Activada'))
            ->setDefaultValue(0);

        // Timestamps.
        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Creado'));

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(t('Actualizado'));

        return $fields;
    }

    /**
     * Obtiene el nombre.
     */
    public function getName(): string
    {
        return $this->get('name')->value ?? '';
    }

    /**
     * Verifica si está activa.
     */
    public function isActive(): bool
    {
        return (bool) $this->get('active')->value;
    }

    /**
     * Obtiene los canales como array.
     */
    public function getChannels(): array
    {
        $json = $this->get('channels')->value ?? '[]';
        return json_decode($json, TRUE) ?: [];
    }

    /**
     * Evalúa si la condición se cumple.
     *
     * @param float $currentValue
     *   Valor actual de la métrica.
     * @param float|null $previousValue
     *   Valor previo (para comparación porcentual).
     */
    public function evaluateCondition(float $currentValue, ?float $previousValue = NULL): bool
    {
        $threshold = (float) $this->get('threshold')->value;
        $operator = $this->get('operator')->value;

        switch ($operator) {
            case self::OP_GREATER:
                return $currentValue > $threshold;

            case self::OP_LESS:
                return $currentValue < $threshold;

            case self::OP_EQUALS:
                return abs($currentValue - $threshold) < 0.01;

            case self::OP_CHANGE_PERCENT:
                if ($previousValue === NULL || $previousValue == 0) {
                    return FALSE;
                }
                $changePercent = (($currentValue - $previousValue) / $previousValue) * 100;
                return abs($changePercent) >= $threshold;

            default:
                return FALSE;
        }
    }

    /**
     * Verifica si está en cooldown.
     */
    public function isInCooldown(): bool
    {
        $lastTriggered = $this->get('last_triggered')->value;
        if (!$lastTriggered) {
            return FALSE;
        }

        $cooldownMinutes = (int) $this->get('cooldown_minutes')->value;
        $cooldownSeconds = $cooldownMinutes * 60;

        return (time() - $lastTriggered) < $cooldownSeconds;
    }

}
