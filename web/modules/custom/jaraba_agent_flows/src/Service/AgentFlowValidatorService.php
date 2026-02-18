<?php

declare(strict_types=1);

namespace Drupal\jaraba_agent_flows\Service;

use Psr\Log\LoggerInterface;

/**
 * Servicio de validacion de definiciones de flujos de agentes IA.
 *
 * PROPOSITO:
 * Valida que la configuracion JSON de un flujo sea estructuralmente
 * correcta y contenga todos los campos requeridos para cada paso.
 *
 * USO:
 * @code
 * $errors = $this->validatorService->validateFlowConfig($config);
 * if (!empty($errors)) {
 *   // Mostrar errores al usuario.
 * }
 * @endcode
 */
class AgentFlowValidatorService {

  /**
   * Tipos de paso validos.
   */
  protected const VALID_STEP_TYPES = [
    'generate',
    'validate',
    'transform',
    'publish',
    'condition',
    'wait',
    'notify',
    'api_call',
  ];

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Valida la configuracion de un flujo de agente.
   *
   * Verifica estructura, pasos requeridos, tipos validos y
   * conexiones entre pasos.
   *
   * @param array $config
   *   Array con la configuracion del flujo (decodificada de JSON).
   *
   * @return array
   *   Array de errores encontrados. Vacio si la configuracion es valida.
   */
  public function validateFlowConfig(array $config): array {
    $errors = [];

    // Verificar que exista la clave 'steps'.
    if (!isset($config['steps'])) {
      $errors[] = 'La configuracion debe contener una clave "steps".';
      $this->logErrors($errors);
      return $errors;
    }

    if (!is_array($config['steps'])) {
      $errors[] = 'La clave "steps" debe ser un array.';
      $this->logErrors($errors);
      return $errors;
    }

    if (empty($config['steps'])) {
      $errors[] = 'El flujo debe contener al menos un paso.';
      $this->logErrors($errors);
      return $errors;
    }

    $stepNames = [];

    foreach ($config['steps'] as $index => $step) {
      $stepPrefix = sprintf('Paso %d', $index + 1);

      // Verificar que el paso sea un array.
      if (!is_array($step)) {
        $errors[] = sprintf('%s: debe ser un objeto/array.', $stepPrefix);
        continue;
      }

      // Validar nombre del paso.
      if (empty($step['name'])) {
        $errors[] = sprintf('%s: falta el campo "name".', $stepPrefix);
      }
      else {
        // Verificar nombres duplicados.
        if (in_array($step['name'], $stepNames, TRUE)) {
          $errors[] = sprintf('%s: nombre duplicado "%s".', $stepPrefix, $step['name']);
        }
        $stepNames[] = $step['name'];
      }

      // Validar tipo de paso.
      if (empty($step['type'])) {
        $errors[] = sprintf('%s: falta el campo "type".', $stepPrefix);
      }
      elseif (!in_array($step['type'], self::VALID_STEP_TYPES, TRUE)) {
        $errors[] = sprintf(
          '%s: tipo "%s" no valido. Tipos permitidos: %s.',
          $stepPrefix,
          $step['type'],
          implode(', ', self::VALID_STEP_TYPES),
        );
      }

      // Validar parametros especificos por tipo.
      $typeErrors = $this->validateStepParams($step, $stepPrefix);
      $errors = array_merge($errors, $typeErrors);
    }

    // Validar configuracion de settings globales si existen.
    if (isset($config['settings'])) {
      if (!is_array($config['settings'])) {
        $errors[] = 'La clave "settings" debe ser un objeto/array.';
      }
      else {
        if (isset($config['settings']['timeout']) && !is_numeric($config['settings']['timeout'])) {
          $errors[] = 'settings.timeout debe ser un valor numerico.';
        }
        if (isset($config['settings']['max_retries']) && !is_numeric($config['settings']['max_retries'])) {
          $errors[] = 'settings.max_retries debe ser un valor numerico.';
        }
      }
    }

    if (!empty($errors)) {
      $this->logErrors($errors);
    }

    return $errors;
  }

  /**
   * Registra los errores de validacion en el logger.
   *
   * @param array $errors
   *   Los errores encontrados.
   */
  protected function logErrors(array $errors): void {
    if (empty($errors)) {
      return;
    }

    $this->logger->notice('Validacion de flujo fallida con @count errores.', [
      '@count' => count($errors),
    ]);
  }

  /**
   * Valida parametros especificos segun el tipo de paso.
   *
   * @param array $step
   *   Definicion del paso.
   * @param string $prefix
   *   Prefijo para mensajes de error.
   *
   * @return array
   *   Array de errores encontrados.
   */
  protected function validateStepParams(array $step, string $prefix): array {
    $errors = [];
    $type = $step['type'] ?? '';
    $params = $step['params'] ?? [];

    switch ($type) {
      case 'generate':
        if (empty($params['prompt']) && empty($params['template'])) {
          $errors[] = sprintf('%s (generate): requiere "prompt" o "template" en params.', $prefix);
        }
        break;

      case 'api_call':
        if (empty($params['url'])) {
          $errors[] = sprintf('%s (api_call): requiere "url" en params.', $prefix);
        }
        if (empty($params['method'])) {
          $errors[] = sprintf('%s (api_call): requiere "method" en params.', $prefix);
        }
        break;

      case 'condition':
        if (empty($params['expression'])) {
          $errors[] = sprintf('%s (condition): requiere "expression" en params.', $prefix);
        }
        break;

      case 'wait':
        if (!isset($params['seconds']) || !is_numeric($params['seconds'])) {
          $errors[] = sprintf('%s (wait): requiere "seconds" (numerico) en params.', $prefix);
        }
        break;

      case 'publish':
        if (empty($params['target'])) {
          $errors[] = sprintf('%s (publish): requiere "target" en params.', $prefix);
        }
        break;
    }

    return $errors;
  }

}
