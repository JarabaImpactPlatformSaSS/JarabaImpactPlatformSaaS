<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface para la entidad EcaFlowDefinition.
 *
 * Define el contrato para el registro centralizado de flujos ECA
 * (Event-Condition-Action) del ecosistema.
 */
interface EcaFlowDefinitionInterface extends ConfigEntityInterface
{

    /**
     * Obtiene la descripcion del flujo ECA.
     *
     * @return string
     *   La descripcion.
     */
    public function getDescription(): string;

    /**
     * Establece la descripcion del flujo ECA.
     *
     * @param string $description
     *   La descripcion.
     *
     * @return $this
     */
    public function setDescription(string $description): EcaFlowDefinitionInterface;

    /**
     * Obtiene el dominio del flujo (USR, ORD, FIN, TEN, AI, WH, MKT, LMS, JOB, BIZ).
     *
     * @return string
     *   El codigo de dominio.
     */
    public function getDomain(): string;

    /**
     * Establece el dominio del flujo.
     *
     * @param string $domain
     *   El codigo de dominio.
     *
     * @return $this
     */
    public function setDomain(string $domain): EcaFlowDefinitionInterface;

    /**
     * Obtiene el evento trigger que dispara este flujo.
     *
     * @return string
     *   El evento trigger (ej: hook_user_insert, commerce_order_complete).
     */
    public function getTriggerEvent(): string;

    /**
     * Establece el evento trigger.
     *
     * @param string $trigger_event
     *   El evento trigger.
     *
     * @return $this
     */
    public function setTriggerEvent(string $trigger_event): EcaFlowDefinitionInterface;

    /**
     * Obtiene el modulo que implementa este flujo.
     *
     * @return string
     *   El nombre del modulo (ej: ecosistema_jaraba_core).
     */
    public function getModule(): string;

    /**
     * Establece el modulo que implementa este flujo.
     *
     * @param string $module
     *   El nombre del modulo.
     *
     * @return $this
     */
    public function setModule(string $module): EcaFlowDefinitionInterface;

    /**
     * Obtiene la funcion hook que ejecuta el flujo.
     *
     * @return string
     *   El nombre de la funcion hook.
     */
    public function getHookFunction(): string;

    /**
     * Establece la funcion hook.
     *
     * @param string $hook_function
     *   El nombre de la funcion hook.
     *
     * @return $this
     */
    public function setHookFunction(string $hook_function): EcaFlowDefinitionInterface;

    /**
     * Obtiene la referencia al documento de especificacion.
     *
     * @return string
     *   La referencia (ej: 06_Core, 13_FOC).
     */
    public function getSpecReference(): string;

    /**
     * Establece la referencia al documento de especificacion.
     *
     * @param string $spec_reference
     *   La referencia.
     *
     * @return $this
     */
    public function setSpecReference(string $spec_reference): EcaFlowDefinitionInterface;

    /**
     * Obtiene el estado de implementacion del flujo.
     *
     * @return string
     *   El estado: implemented, partial, pending, deprecated.
     */
    public function getImplementationStatus(): string;

    /**
     * Establece el estado de implementacion.
     *
     * @param string $implementation_status
     *   El estado de implementacion.
     *
     * @return $this
     */
    public function setImplementationStatus(string $implementation_status): EcaFlowDefinitionInterface;

    /**
     * Obtiene el peso para ordenacion.
     *
     * @return int
     *   El peso.
     */
    public function getWeight(): int;

    /**
     * Establece el peso para ordenacion.
     *
     * @param int $weight
     *   El peso.
     *
     * @return $this
     */
    public function setWeight(int $weight): EcaFlowDefinitionInterface;

}
