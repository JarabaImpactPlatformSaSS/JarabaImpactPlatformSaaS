<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Provee system prompts especificos por fase para el copilot de Andalucia +ei.
 *
 * Cada fase del programa PIIL CV 2025 tiene un comportamiento diferente
 * del copilot: exploratorio (orientacion), didactico (modulo 0),
 * mentor (modulos 1-3), productivo (modulo 4), operativo (modulo 5)
 * y autonomo (acompanamiento post-programa).
 *
 * Los prompts se almacenan en config para permitir ajustes desde admin
 * sin tocar codigo. Config: jaraba_andalucia_ei.copilot_phase_prompts.
 */
class CopilotPhaseConfigService {

  /**
   * Nombre de la configuracion de prompts por fase.
   */
  private const CONFIG_NAME = 'jaraba_andalucia_ei.copilot_phase_prompts';

  /**
   * Fases validas del programa con su comportamiento por defecto.
   */
  private const PHASE_BEHAVIORS = [
    'orientacion' => 'exploratory',
    'modulo_0' => 'didactic',
    'modulo_1_3' => 'mentor',
    'modulo_4' => 'productive',
    'modulo_5' => 'operative',
    'acompanamiento' => 'autonomous',
  ];

  /**
   * Constructs a CopilotPhaseConfigService.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Obtiene el system prompt para una fase del programa.
   *
   * @param string $estadoPrograma
   *   Clave de la fase (orientacion, modulo_0, modulo_1_3, etc.).
   *
   * @return string
   *   System prompt configurado para la fase. Cadena vacia si no existe.
   */
  public function getSystemPromptForPhase(string $estadoPrograma): string {
    try {
      if (!isset(self::PHASE_BEHAVIORS[$estadoPrograma])) {
        $this->logger->warning('Fase no reconocida solicitada al copilot: @fase', [
          '@fase' => $estadoPrograma,
        ]);
        return '';
      }

      $config = $this->configFactory->get(self::CONFIG_NAME);
      $phaseConfig = $config->get($estadoPrograma);

      if (is_array($phaseConfig) && isset($phaseConfig['system_prompt']) && $phaseConfig['system_prompt'] !== '') {
        return (string) $phaseConfig['system_prompt'];
      }

      $this->logger->warning('System prompt no configurado para fase @fase', [
        '@fase' => $estadoPrograma,
      ]);
      return '';
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo system prompt para fase @fase: @msg', [
        '@fase' => $estadoPrograma,
        '@msg' => $e->getMessage(),
      ]);
      return '';
    }
  }

  /**
   * Devuelve las fases disponibles del programa.
   *
   * @return string[]
   *   Array con las claves de fase validas.
   */
  public function getAvailablePhases(): array {
    return array_keys(self::PHASE_BEHAVIORS);
  }

  /**
   * Obtiene el tipo de comportamiento del copilot para una fase.
   *
   * @param string $estadoPrograma
   *   Clave de la fase.
   *
   * @return string
   *   Tipo de comportamiento (exploratory, didactic, mentor, productive,
   *   operative, autonomous). Cadena vacia si la fase no es valida.
   */
  public function getBehaviorForPhase(string $estadoPrograma): string {
    try {
      if (!isset(self::PHASE_BEHAVIORS[$estadoPrograma])) {
        return '';
      }

      $config = $this->configFactory->get(self::CONFIG_NAME);
      $phaseConfig = $config->get($estadoPrograma);

      if (is_array($phaseConfig) && isset($phaseConfig['behavior']) && $phaseConfig['behavior'] !== '') {
        return (string) $phaseConfig['behavior'];
      }

      // Fallback al valor por defecto de la constante.
      return self::PHASE_BEHAVIORS[$estadoPrograma];
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo behavior para fase @fase: @msg', [
        '@fase' => $estadoPrograma,
        '@msg' => $e->getMessage(),
      ]);
      return self::PHASE_BEHAVIORS[$estadoPrograma];
    }
  }

}
