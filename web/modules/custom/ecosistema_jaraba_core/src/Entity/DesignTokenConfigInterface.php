<?php

namespace Drupal\ecosistema_jaraba_core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interfaz para la entidad de configuración DesignTokenConfig.
 *
 * Define los métodos de acceso a los Design Tokens configurables
 * por tenant/vertical, siguiendo el patrón Federated Design Tokens v2.1.
 */
interface DesignTokenConfigInterface extends ConfigEntityInterface
{

    /**
     * Obtiene la descripción de la configuración de tokens.
     *
     * @return string
     *   La descripción.
     */
    public function getDescription(): string;

    /**
     * Establece la descripción.
     *
     * @param string $description
     *   La descripción.
     *
     * @return $this
     */
    public function setDescription(string $description): DesignTokenConfigInterface;

    /**
     * Obtiene el scope de esta configuración (platform, vertical, plan, tenant).
     *
     * Corresponde a la cascada de 4 niveles:
     * Platform → Vertical → Plan → Tenant.
     *
     * @return string
     *   El scope.
     */
    public function getScope(): string;

    /**
     * Establece el scope.
     *
     * @param string $scope
     *   El scope (platform, vertical, plan, tenant).
     *
     * @return $this
     */
    public function setScope(string $scope): DesignTokenConfigInterface;

    /**
     * Obtiene el ID de la vertical asociada (si scope != platform).
     *
     * @return string
     *   El ID de la vertical o cadena vacía.
     */
    public function getVerticalId(): string;

    /**
     * Establece el ID de la vertical.
     *
     * @param string $verticalId
     *   El ID de la vertical.
     *
     * @return $this
     */
    public function setVerticalId(string $verticalId): DesignTokenConfigInterface;

    /**
     * Obtiene el ID del plan SaaS asociado (si scope = plan o tenant).
     *
     * @return string
     *   El ID del plan o cadena vacía.
     */
    public function getPlanId(): string;

    /**
     * Establece el ID del plan SaaS.
     *
     * @param string $planId
     *   El ID del plan.
     *
     * @return $this
     */
    public function setPlanId(string $planId): DesignTokenConfigInterface;

    /**
     * Obtiene el ID del tenant asociado (si scope = tenant).
     *
     * @return string
     *   El ID del tenant o cadena vacía.
     */
    public function getTenantId(): string;

    /**
     * Establece el ID del tenant.
     *
     * @param string $tenantId
     *   El ID del tenant.
     *
     * @return $this
     */
    public function setTenantId(string $tenantId): DesignTokenConfigInterface;

    /**
     * Obtiene el ID del preset de estilo aplicado.
     *
     * @return string
     *   El ID del preset aplicado o cadena vacía.
     */
    public function getPresetId(): string;

    /**
     * Establece el ID del preset de estilo.
     *
     * @param string $presetId
     *   El ID del preset.
     *
     * @return $this
     */
    public function setPresetId(string $presetId): DesignTokenConfigInterface;

    /**
     * Obtiene los tokens de color como array asociativo.
     *
     * @return array
     *   Array de tokens ['primary' => '#hex', ...].
     */
    public function getColorTokens(): array;

    /**
     * Establece los tokens de color.
     *
     * @param array $tokens
     *   Array de tokens de color.
     *
     * @return $this
     */
    public function setColorTokens(array $tokens): DesignTokenConfigInterface;

    /**
     * Obtiene los tokens de tipografía.
     *
     * @return array
     *   Array de tokens de tipografía.
     */
    public function getTypographyTokens(): array;

    /**
     * Establece los tokens de tipografía.
     *
     * @param array $tokens
     *   Array de tokens de tipografía.
     *
     * @return $this
     */
    public function setTypographyTokens(array $tokens): DesignTokenConfigInterface;

    /**
     * Obtiene los tokens de espaciado y layout.
     *
     * @return array
     *   Array de tokens de spacing.
     */
    public function getSpacingTokens(): array;

    /**
     * Establece los tokens de espaciado.
     *
     * @param array $tokens
     *   Array de tokens de spacing.
     *
     * @return $this
     */
    public function setSpacingTokens(array $tokens): DesignTokenConfigInterface;

    /**
     * Obtiene los tokens de efectos visuales (glassmorphism, shadows, etc.).
     *
     * @return array
     *   Array de tokens de efectos.
     */
    public function getEffectTokens(): array;

    /**
     * Establece los tokens de efectos.
     *
     * @param array $tokens
     *   Array de tokens de efectos.
     *
     * @return $this
     */
    public function setEffectTokens(array $tokens): DesignTokenConfigInterface;

    /**
     * Obtiene las variantes de componentes seleccionadas.
     *
     * @return array
     *   Array de variantes ['header' => 'minimal', 'card' => 'product', ...].
     */
    public function getComponentVariants(): array;

    /**
     * Establece las variantes de componentes.
     *
     * @param array $variants
     *   Array de variantes de componentes.
     *
     * @return $this
     */
    public function setComponentVariants(array $variants): DesignTokenConfigInterface;

    /**
     * Genera las CSS Custom Properties para inyección en :root.
     *
     * Aplica la cascada de tokens: los valores de este scope sobrescriben
     * los del scope padre según Platform → Vertical → Plan → Tenant.
     *
     * @return string
     *   Bloque CSS con custom properties para inyectar en <style>.
     */
    public function generateCssCustomProperties(): string;

}
