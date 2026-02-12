<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserDataInterface;

/**
 * Servicio de persistencia del estado del Onboarding Tour.
 *
 * Gestiona si un usuario ha completado el tour del Canvas Editor
 * usando el servicio user.data de Drupal para persistencia cruzada
 * entre dispositivos y sesiones.
 *
 * @see docs/planificacion/20260209-Plan_Mejoras_Page_Site_Builder_v3.md §3
 */
class OnboardingStateService
{

    /**
     * Nombre del módulo para user.data.
     */
    const MODULE_NAME = 'jaraba_page_builder';

    /**
     * Clave del estado del tour en user.data.
     */
    const TOUR_KEY = 'onboarding_tour_completed';

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\user\UserDataInterface $userData
     *   Servicio de datos de usuario de Drupal.
     * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
     *   Proxy del usuario actual.
     */
    public function __construct(
        protected UserDataInterface $userData,
        protected AccountProxyInterface $currentUser,
    ) {
    }

    /**
     * Verifica si el usuario actual ha completado el tour.
     *
     * @return bool
     *   TRUE si el tour fue completado.
     */
    public function hasCompletedTour(): bool
    {
        $uid = (int) $this->currentUser->id();
        if ($uid === 0) {
            return FALSE;
        }

        $value = $this->userData->get(
            self::MODULE_NAME,
            $uid,
            self::TOUR_KEY
        );

        return !empty($value);
    }

    /**
     * Marca el tour como completado para el usuario actual.
     */
    public function markTourCompleted(): void
    {
        $uid = (int) $this->currentUser->id();
        if ($uid === 0) {
            return;
        }

        $this->userData->set(
            self::MODULE_NAME,
            $uid,
            self::TOUR_KEY,
            \Drupal::time()->getRequestTime()
        );
    }

    /**
     * Resetea el estado del tour para el usuario actual.
     *
     * Útil para permitir relanzar el tour desde la configuración.
     */
    public function resetTour(): void
    {
        $uid = (int) $this->currentUser->id();
        if ($uid === 0) {
            return;
        }

        $this->userData->delete(
            self::MODULE_NAME,
            $uid,
            self::TOUR_KEY
        );
    }

    /**
     * Verifica si un usuario específico ha completado el tour.
     *
     * @param int $uid
     *   ID del usuario.
     *
     * @return bool
     *   TRUE si el tour fue completado por ese usuario.
     */
    public function hasUserCompletedTour(int $uid): bool
    {
        if ($uid === 0) {
            return FALSE;
        }

        $value = $this->userData->get(
            self::MODULE_NAME,
            $uid,
            self::TOUR_KEY
        );

        return !empty($value);
    }

}
