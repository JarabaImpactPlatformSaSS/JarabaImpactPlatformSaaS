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

}
