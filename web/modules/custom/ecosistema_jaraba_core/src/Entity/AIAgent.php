<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuración AIAgent.
 *
 * Un AIAgent representa un agente de inteligencia artificial disponible
 * para las verticales. Permite gestión zero-code desde admin.
 *
 * @ConfigEntityType(
 *   id = "ai_agent",
 *   label = @Translation("AI Agent"),
 *   label_collection = @Translation("Agentes IA"),
 *   label_singular = @Translation("agente IA"),
 *   label_plural = @Translation("agentes IA"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\AIAgentListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\AIAgentForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\AIAgentForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "ai_agent",
 *   admin_permission = "administer ai agents",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "service_id",
 *     "icon",
 *     "color",
 *     "weight",
 *     "status",
 *     "autonomy_level",
 *     "requires_approval",
 *     "max_daily_auto_actions",
 *     "allowed_actions",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/ai-agents",
 *     "add-form" = "/admin/structure/ai-agents/add",
 *     "edit-form" = "/admin/structure/ai-agents/{ai_agent}/edit",
 *     "delete-form" = "/admin/structure/ai-agents/{ai_agent}/delete",
 *   },
 * )
 */
class AIAgent extends ConfigEntityBase implements AIAgentInterface
{

    /**
     * El ID del agente (machine name).
     *
     * @var string
     */
    protected $id;

    /**
     * El nombre visible del agente.
     *
     * @var string
     */
    protected $label;

    /**
     * Descripción del agente.
     *
     * @var string
     */
    protected $description = '';

    /**
     * ID del servicio Drupal que implementa el agente.
     *
     * @var string
     */
    protected $service_id = '';

    /**
     * Nombre del icono.
     *
     * @var string
     */
    protected $icon = '';

    /**
     * Color para UI (hex).
     *
     * @var string
     */
    protected $color = '#1a73e8';

    /**
     * Peso para ordenación.
     *
     * @var int
     */
    protected $weight = 0;

    // =========================================================================
    // AUTONOMY LEVELS (Q1 2027)
    // 0 = Suggest: Solo sugiere, usuario ejecuta manualmente
    // 1 = Confirm: Propone acción, espera confirmación
    // 2 = Auto: Ejecuta automáticamente, notifica después
    // 3 = Silent: Ejecuta sin notificar (para low-risk)
    // =========================================================================

    /**
     * Nivel de autonomía del agente.
     *
     * @var int
     */
    protected $autonomy_level = 1;

    /**
     * Indica si requiere aprobación por defecto.
     *
     * @var bool
     */
    protected $requires_approval = TRUE;

    /**
     * Máximo de acciones automáticas por día.
     *
     * @var int
     */
    protected $max_daily_auto_actions = 10;

    /**
     * Lista de acciones permitidas (JSON).
     *
     * @var string
     */
    protected $allowed_actions = '';

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->description ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription(string $description): AIAgentInterface
    {
        $this->description = $description;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getServiceId(): string
    {
        return $this->service_id ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setServiceId(string $serviceId): AIAgentInterface
    {
        $this->service_id = $serviceId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIcon(): string
    {
        return $this->icon ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setIcon(string $icon): AIAgentInterface
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getColor(): string
    {
        return $this->color ?? '#1a73e8';
    }

    /**
     * {@inheritdoc}
     */
    public function setColor(string $color): AIAgentInterface
    {
        $this->color = $color;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWeight(): int
    {
        return $this->weight ?? 0;
    }

    /**
     * {@inheritdoc}
     */
    public function setWeight(int $weight): AIAgentInterface
    {
        $this->weight = $weight;
        return $this;
    }

    // =========================================================================
    // AUTONOMY METHODS
    // =========================================================================

    /**
     * Obtiene el nivel de autonomía.
     *
     * @return int
     *   El nivel (0-3).
     */
    public function getAutonomyLevel(): int
    {
        return $this->autonomy_level ?? 1;
    }

    /**
     * Establece el nivel de autonomía.
     *
     * @param int $level
     *   El nivel (0-3).
     *
     * @return $this
     */
    public function setAutonomyLevel(int $level): AIAgentInterface
    {
        $this->autonomy_level = max(0, min(3, $level));
        return $this;
    }

    /**
     * Indica si requiere aprobación.
     *
     * @return bool
     */
    public function requiresApproval(): bool
    {
        return $this->requires_approval ?? TRUE;
    }

    /**
     * Establece si requiere aprobación.
     *
     * @param bool $requires
     *
     * @return $this
     */
    public function setRequiresApproval(bool $requires): AIAgentInterface
    {
        $this->requires_approval = $requires;
        return $this;
    }

    /**
     * Obtiene el máximo de acciones automáticas diarias.
     *
     * @return int
     */
    public function getMaxDailyAutoActions(): int
    {
        return $this->max_daily_auto_actions ?? 10;
    }

    /**
     * Establece el máximo de acciones automáticas diarias.
     *
     * @param int $max
     *
     * @return $this
     */
    public function setMaxDailyAutoActions(int $max): AIAgentInterface
    {
        $this->max_daily_auto_actions = $max;
        return $this;
    }

    /**
     * Obtiene las acciones permitidas.
     *
     * @return array
     */
    public function getAllowedActions(): array
    {
        if (empty($this->allowed_actions)) {
            return [];
        }
        return json_decode($this->allowed_actions, TRUE) ?? [];
    }

    /**
     * Establece las acciones permitidas.
     *
     * @param array $actions
     *
     * @return $this
     */
    public function setAllowedActions(array $actions): AIAgentInterface
    {
        $this->allowed_actions = json_encode($actions);
        return $this;
    }

    /**
     * Obtiene el nombre del nivel de autonomía.
     *
     * @return string
     */
    public function getAutonomyLevelName(): string
    {
        $levels = [
            0 => 'Suggest',
            1 => 'Confirm',
            2 => 'Auto',
            3 => 'Silent',
        ];
        return $levels[$this->getAutonomyLevel()] ?? 'Unknown';
    }

    /**
     * Verifica si el agente puede ejecutar automáticamente.
     *
     * @return bool
     */
    public function canAutoExecute(): bool
    {
        return $this->getAutonomyLevel() >= 2;
    }

}
