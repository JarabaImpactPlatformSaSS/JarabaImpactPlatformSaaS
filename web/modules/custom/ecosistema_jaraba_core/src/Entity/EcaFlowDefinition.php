<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Define la entidad de configuracion EcaFlowDefinition.
 *
 * Registro centralizado de todos los flujos automatizados (ECA) del
 * ecosistema Jaraba con IDs unicos, convencion de nomenclatura
 * estandarizada y estado de implementacion.
 *
 * Convencion de nomenclatura: ECA-{DOMINIO}-{NUMERO}
 * Dominios: USR, ORD, FIN, TEN, AI, WH, MKT, LMS, JOB, BIZ
 *
 * @ConfigEntityType(
 *   id = "eca_flow_definition",
 *   label = @Translation("ECA Flow Definition"),
 *   label_collection = @Translation("ECA Flow Registry"),
 *   label_singular = @Translation("ECA flow definition"),
 *   label_plural = @Translation("ECA flow definitions"),
 *   handlers = {
 *     "list_builder" = "Drupal\ecosistema_jaraba_core\EcaFlowDefinitionListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ecosistema_jaraba_core\Form\EcaFlowDefinitionForm",
 *       "edit" = "Drupal\ecosistema_jaraba_core\Form\EcaFlowDefinitionForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "eca_flow_definition",
 *   admin_permission = "administer eca flow definitions",
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
 *     "domain",
 *     "trigger_event",
 *     "module",
 *     "hook_function",
 *     "spec_reference",
 *     "implementation_status",
 *     "weight",
 *     "status",
 *   },
 *   links = {
 *     "collection" = "/admin/structure/eca-registry",
 *     "add-form" = "/admin/structure/eca-registry/add",
 *     "edit-form" = "/admin/structure/eca-registry/{eca_flow_definition}/edit",
 *     "delete-form" = "/admin/structure/eca-registry/{eca_flow_definition}/delete",
 *   },
 * )
 */
class EcaFlowDefinition extends ConfigEntityBase implements EcaFlowDefinitionInterface
{

    /**
     * El ID del flujo ECA (machine name, ej: eca_usr_001).
     *
     * @var string
     */
    protected $id;

    /**
     * El nombre visible del flujo ECA.
     *
     * @var string
     */
    protected $label;

    /**
     * Descripcion del flujo y sus acciones.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Dominio del flujo: USR, ORD, FIN, TEN, AI, WH, MKT, LMS, JOB, BIZ.
     *
     * @var string
     */
    protected $domain = '';

    /**
     * Evento que dispara el flujo (ej: hook_user_insert, commerce_order_complete).
     *
     * @var string
     */
    protected $trigger_event = '';

    /**
     * Modulo que implementa el flujo.
     *
     * @var string
     */
    protected $module = '';

    /**
     * Funcion hook que ejecuta el flujo.
     *
     * @var string
     */
    protected $hook_function = '';

    /**
     * Referencia al documento de especificacion.
     *
     * @var string
     */
    protected $spec_reference = '';

    /**
     * Estado de implementacion: implemented, partial, pending, deprecated.
     *
     * @var string
     */
    protected $implementation_status = 'pending';

    /**
     * Peso para ordenacion.
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
    public function setDescription(string $description): EcaFlowDefinitionInterface
    {
        $this->description = $description;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDomain(): string
    {
        return $this->domain ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setDomain(string $domain): EcaFlowDefinitionInterface
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTriggerEvent(): string
    {
        return $this->trigger_event ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setTriggerEvent(string $trigger_event): EcaFlowDefinitionInterface
    {
        $this->trigger_event = $trigger_event;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getModule(): string
    {
        return $this->module ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setModule(string $module): EcaFlowDefinitionInterface
    {
        $this->module = $module;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHookFunction(): string
    {
        return $this->hook_function ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setHookFunction(string $hook_function): EcaFlowDefinitionInterface
    {
        $this->hook_function = $hook_function;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSpecReference(): string
    {
        return $this->spec_reference ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function setSpecReference(string $spec_reference): EcaFlowDefinitionInterface
    {
        $this->spec_reference = $spec_reference;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getImplementationStatus(): string
    {
        return $this->implementation_status ?? 'pending';
    }

    /**
     * {@inheritdoc}
     */
    public function setImplementationStatus(string $implementation_status): EcaFlowDefinitionInterface
    {
        $this->implementation_status = $implementation_status;
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
    public function setWeight(int $weight): EcaFlowDefinitionInterface
    {
        $this->weight = $weight;
        return $this;
    }

}
