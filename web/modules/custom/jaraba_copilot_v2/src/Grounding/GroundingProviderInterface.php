<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Grounding;

/**
 * Interfaz para proveedores de grounding del copilot.
 *
 * Cada modulo vertical puede registrar un provider que busca contenido
 * relevante en sus entity types para enriquecer las respuestas del copilot.
 *
 * Sigue el patron de tagged services con CompilerPass, identico a
 * SetupWizardRegistry y DailyActionsRegistry.
 *
 * Tag: jaraba_copilot_v2.grounding_provider
 */
interface GroundingProviderInterface {

  /**
   * Clave canonica del vertical que cubre este provider.
   *
   * Debe coincidir con VERTICAL-CANONICAL-001 o 'global'.
   *
   * @return string
   *   Clave canonica (ej: 'andalucia_ei', 'formacion', 'empleabilidad').
   */
  public function getVerticalKey(): string;

  /**
   * Busca contenido relevante para los keywords dados.
   *
   * @param array<string> $keywords
   *   Keywords extraidos del mensaje del usuario (sin stopwords).
   * @param int $limit
   *   Maximo de resultados a devolver.
   *
   * @return array<int, array<string, mixed>>
   */
  public function search(array $keywords, int $limit = 3): array;

  /**
   * Prioridad del provider (mayor = se ejecuta primero).
   *
   * Providers con mayor prioridad aparecen primero en los resultados
   * del copilot. Usar 100 para promociones activas, 80 para programas
   * especificos, 50 para contenido general.
   *
   * @return int
   *   Valor de prioridad.
   */
  public function getPriority(): int;

}
