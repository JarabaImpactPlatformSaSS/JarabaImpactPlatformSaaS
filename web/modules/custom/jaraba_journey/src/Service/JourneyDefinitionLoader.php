<?php

declare(strict_types=1);

namespace Drupal\jaraba_journey\Service;

use Drupal\jaraba_journey\JourneyDefinition\AgroConectaJourneyDefinition;
use Drupal\jaraba_journey\JourneyDefinition\AndaluciaEiJourneyDefinition;
use Drupal\jaraba_journey\JourneyDefinition\CertificacionJourneyDefinition;
use Drupal\jaraba_journey\JourneyDefinition\ComercioConectaJourneyDefinition;
use Drupal\jaraba_journey\JourneyDefinition\EmpleabilidadJourneyDefinition;
use Drupal\jaraba_journey\JourneyDefinition\EmprendimientoJourneyDefinition;
use Drupal\jaraba_journey\JourneyDefinition\ServiciosConectaJourneyDefinition;

/**
 * Servicio para cargar definiciones de journey por vertical.
 *
 * Centraliza el acceso a las definiciones de todos los avatares.
 */
class JourneyDefinitionLoader
{

    /**
     * Mapeo de verticales a clases de definición.
     * 
     * 7 verticales completos con 19 avatares según Doc 103.
     */
    const VERTICAL_DEFINITIONS = [
        'agroconecta' => AgroConectaJourneyDefinition::class,
        'comercioconecta' => ComercioConectaJourneyDefinition::class,
        'serviciosconecta' => ServiciosConectaJourneyDefinition::class,
        'empleabilidad' => EmpleabilidadJourneyDefinition::class,
        'emprendimiento' => EmprendimientoJourneyDefinition::class,
        'andalucia_ei' => AndaluciaEiJourneyDefinition::class,
        'certificacion' => CertificacionJourneyDefinition::class,
    ];

    /**
     * Mapeo de avatar a vertical.
     */
    const AVATAR_VERTICAL_MAP = [
        // AgroConecta
        'productor' => 'agroconecta',
        'comprador_b2b' => 'agroconecta',
        'consumidor' => 'agroconecta',
        // Empleabilidad
        'job_seeker' => 'empleabilidad',
        'employer' => 'empleabilidad',
        'orientador' => 'empleabilidad',
        // Emprendimiento
        'emprendedor' => 'emprendimiento',
        'mentor' => 'emprendimiento',
        'gestor_programa' => 'emprendimiento',
        // ComercioConecta
        'comerciante' => 'comercioconecta',
        'comprador_local' => 'comercioconecta',
        // ServiciosConecta
        'profesional' => 'serviciosconecta',
        'cliente_servicios' => 'serviciosconecta',
        // Andalucía +ei
        'beneficiario_ei' => 'andalucia_ei',
        'tecnico_sto' => 'andalucia_ei',
        'admin_ei' => 'andalucia_ei',
        // Certificación
        'estudiante' => 'certificacion',
        'formador' => 'certificacion',
        'admin_lms' => 'certificacion',
    ];

    /**
     * Obtiene la definición de journey para un avatar.
     */
    public function getJourneyDefinition(string $avatar): ?array
    {
        $vertical = self::AVATAR_VERTICAL_MAP[$avatar] ?? NULL;

        if (!$vertical) {
            return NULL;
        }

        $definitionClass = self::VERTICAL_DEFINITIONS[$vertical] ?? NULL;

        if (!$definitionClass || !class_exists($definitionClass)) {
            return NULL;
        }

        return $definitionClass::getJourneyDefinition($avatar);
    }

    /**
     * Obtiene todos los avatares de una vertical.
     */
    public function getAvatarsForVertical(string $vertical): array
    {
        $definitionClass = self::VERTICAL_DEFINITIONS[$vertical] ?? NULL;

        if (!$definitionClass || !class_exists($definitionClass)) {
            return [];
        }

        return $definitionClass::getAvatars();
    }

    /**
     * Obtiene los steps para un estado específico de un avatar.
     */
    public function getStepsForState(string $avatar, string $state): array
    {
        $definition = $this->getJourneyDefinition($avatar);

        if (!$definition) {
            return [];
        }

        return $definition['states'][$state]['steps'] ?? [];
    }

    /**
     * Obtiene el evento de transición para un estado.
     */
    public function getTransitionEvent(string $avatar, string $state): ?string
    {
        $definition = $this->getJourneyDefinition($avatar);

        if (!$definition) {
            return NULL;
        }

        return $definition['states'][$state]['transition_event'] ?? NULL;
    }

    /**
     * Obtiene los triggers para un estado.
     */
    public function getTriggersForState(string $avatar, string $state): array
    {
        $definition = $this->getJourneyDefinition($avatar);

        if (!$definition) {
            return [];
        }

        return $definition['states'][$state]['triggers'] ?? [];
    }

    /**
     * Obtiene las ofertas cross-sell para un avatar.
     */
    public function getCrossSellOffers(string $avatar): array
    {
        $definition = $this->getJourneyDefinition($avatar);

        if (!$definition) {
            return [];
        }

        return $definition['cross_sell'] ?? [];
    }

    /**
     * Obtiene el KPI target para un avatar.
     */
    public function getKpiTarget(string $avatar): ?string
    {
        $definition = $this->getJourneyDefinition($avatar);

        if (!$definition) {
            return NULL;
        }

        return $definition['kpi_target'] ?? NULL;
    }

    /**
     * Verifica si un avatar tiene integración con Copilot.
     */
    public function hasCopilotIntegration(string $avatar): bool
    {
        $definition = $this->getJourneyDefinition($avatar);

        if (!$definition) {
            return FALSE;
        }

        return $definition['copilot_integration'] ?? FALSE;
    }

    /**
     * Obtiene el modo de Copilot para un step específico.
     */
    public function getCopilotModeForStep(string $avatar, string $state, int $step): ?string
    {
        $steps = $this->getStepsForState($avatar, $state);

        if (!isset($steps[$step])) {
            return NULL;
        }

        return $steps[$step]['copilot_mode'] ?? NULL;
    }

    /**
     * Obtiene todas las verticales disponibles.
     */
    public function getAvailableVerticals(): array
    {
        return array_keys(self::VERTICAL_DEFINITIONS);
    }

    /**
     * Obtiene todos los avatares disponibles.
     */
    public function getAllAvatars(): array
    {
        return array_keys(self::AVATAR_VERTICAL_MAP);
    }

}
