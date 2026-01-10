<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Interface para la entidad Tenant.
 *
 * Un Tenant representa una organización cliente (antes "Sede").
 */
interface TenantInterface extends ContentEntityInterface
{

    // Estados de suscripción.
    const STATUS_PENDING = 'pending';
    const STATUS_TRIAL = 'trial';
    const STATUS_ACTIVE = 'active';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Obtiene el nombre del Tenant.
     *
     * @return string
     *   El nombre comercial del tenant.
     */
    public function getName(): string;

    /**
     * Establece el nombre del Tenant.
     *
     * @param string $name
     *   El nombre del tenant.
     *
     * @return $this
     */
    public function setName(string $name): self;

    /**
     * Obtiene la Vertical a la que pertenece.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\VerticalInterface|null
     *   La vertical asociada.
     */
    public function getVertical(): ?VerticalInterface;

    /**
     * Obtiene el Plan de suscripción actual.
     *
     * @return \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface|null
     *   El plan de suscripción.
     */
    public function getSubscriptionPlan(): ?SaasPlanInterface;

    /**
     * Establece el plan de suscripción.
     *
     * @param \Drupal\ecosistema_jaraba_core\Entity\SaasPlanInterface $plan
     *   El nuevo plan.
     *
     * @return $this
     */
    public function setSubscriptionPlan(SaasPlanInterface $plan): self;

    /**
     * Obtiene el dominio del Tenant.
     *
     * @return string
     *   El dominio (ej: cooperativa-jaen.jaraba.io).
     */
    public function getDomain(): string;

    /**
     * Obtiene el usuario administrador del Tenant.
     *
     * @return \Drupal\user\UserInterface|null
     *   El usuario administrador.
     */
    public function getAdminUser(): ?UserInterface;

    /**
     * Obtiene el estado de la suscripción.
     *
     * @return string
     *   El estado (trial, active, past_due, etc.)
     */
    public function getSubscriptionStatus(): string;

    /**
     * Establece el estado de la suscripción.
     *
     * @param string $status
     *   El nuevo estado.
     *
     * @return $this
     */
    public function setSubscriptionStatus(string $status): self;

    /**
     * Verifica si el Tenant está activo.
     *
     * @return bool
     *   TRUE si el tenant puede operar normalmente.
     */
    public function isActive(): bool;

    /**
     * Verifica si está en período de prueba.
     *
     * @return bool
     *   TRUE si está en trial.
     */
    public function isOnTrial(): bool;

    /**
     * Obtiene la fecha de fin de trial.
     *
     * @return \DateTimeInterface|null
     *   La fecha o NULL si no aplica.
     */
    public function getTrialEndsAt(): ?\DateTimeInterface;

    /**
     * Obtiene las personalizaciones de tema.
     *
     * @return array
     *   Configuración de tema específica del tenant.
     */
    public function getThemeOverrides(): array;

    /**
     * Obtiene el ID de cliente en Stripe.
     *
     * @return string|null
     *   El ID de Stripe Customer.
     */
    public function getStripeCustomerId(): ?string;

    /**
     * Obtiene el ID de cuenta conectada en Stripe (franquicias).
     *
     * @return string|null
     *   El ID de Stripe Connect Account.
     */
    public function getStripeConnectId(): ?string;

    /**
     * Verifica si tiene cuenta de Stripe Connect.
     *
     * @return bool
     *   TRUE si tiene cuenta conectada.
     */
    public function hasStripeConnect(): bool;

    /**
     * Obtiene el Group de aislamiento asociado a este Tenant.
     *
     * El Group se usa para el aislamiento de contenido via Group Module.
     *
     * @return \Drupal\group\Entity\GroupInterface|null
     *   El grupo asociado o NULL si no existe.
     */
    public function getGroup(): ?\Drupal\group\Entity\GroupInterface;

    /**
     * Obtiene la entidad Domain asociada a este Tenant.
     *
     * PROPÓSITO:
     * Cada Tenant tiene asignado un dominio personalizado a través del
     * módulo Domain Access. Este método retorna la entidad Domain completa
     * para poder acceder a todas sus propiedades (hostname, scheme, etc.).
     *
     * RELACIÓN:
     * - Tenant -> Domain: Uno a uno (cada tenant tiene su dominio exclusivo)
     * - Complementa getDomain() que solo retorna el string del hostname
     *
     * @return \Drupal\domain\Entity\Domain|null
     *   La entidad Domain asociada o NULL si no tiene dominio asignado.
     *
     * @see \Drupal\domain\Entity\Domain
     * @see getDomain() Para obtener solo el hostname como string
     */
    public function getDomainEntity(): ?\Drupal\domain\Entity\Domain;

    /**
     * Establece la entidad Domain asociada a este Tenant.
     *
     * PROPÓSITO:
     * Vincula un dominio del módulo Domain Access con este Tenant.
     * Esta operación se realiza automáticamente durante el onboarding
     * pero puede ser modificada manualmente por administradores.
     *
     * FLUJO DE USO:
     * 1. TenantOnboardingService crea el Domain
     * 2. Se llama a este método para vincular
     * 3. El Tenant se guarda con la referencia
     *
     * @param \Drupal\domain\Entity\Domain $domain
     *   La entidad Domain a asociar.
     *
     * @return $this
     *   La instancia actual para encadenamiento fluido.
     */
    public function setDomainEntity(\Drupal\domain\Entity\Domain $domain): self;

}
