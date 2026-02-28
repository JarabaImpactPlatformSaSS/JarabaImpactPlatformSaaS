<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Drupal\jaraba_legal_knowledge\Service\LegalDisclaimerService;
use Psr\Log\LoggerInterface;

/**
 * Enforcement de disclaimer legal obligatorio en outputs de IA.
 *
 * Capa 8 del LCIS (Legal Coherence Intelligence System).
 * Post-procesamiento final que garantiza:
 * 1. Toda respuesta legal contiene disclaimer.
 * 2. El disclaimer NO puede ser eliminado por configuracion de tenant.
 * 3. El Legal Coherence Score se anade si es < umbral.
 *
 * EU AI Act Art. 50: obligacion de transparencia — el usuario
 * DEBE saber que la respuesta es generada por IA y que no
 * constituye asesoramiento juridico profesional.
 *
 * LEGAL-COHERENCE-FAILOPEN-001: Fail-open con fallback.
 * Si LegalDisclaimerService no esta disponible, usa disclaimer
 * fallback hardcoded. El disclaimer SIEMPRE se anade.
 *
 * @see LegalDisclaimerService (servicio existente con 3 niveles)
 */
final class LegalDisclaimerEnforcementService {

  /**
   * Score threshold para mostrar advertencia visible al usuario.
   */
  private const SCORE_WARNING_THRESHOLD = 70;

  /**
   * Disclaimer fallback si LegalDisclaimerService no esta disponible.
   *
   * Este texto es el ultimo recurso. En produccion, siempre se
   * obtiene del LegalDisclaimerService que permite personalizacion
   * (sin eliminacion).
   */
  private const FALLBACK_DISCLAIMER = 'Esta informacion tiene caracter orientativo y no constituye asesoramiento juridico profesional. Para su caso concreto, consulte con un abogado o profesional cualificado.';

  /**
   * Marcadores de disclaimer existente (para evitar duplicados).
   */
  private const DISCLAIMER_MARKERS = [
    'no constituye asesoramiento',
    'caracter orientativo',
    'consulte con un abogado',
    'consulte con un profesional',
    'no sustituye el criterio profesional',
    'informacion orientativa',
  ];

  public function __construct(
    protected readonly ?LegalDisclaimerService $disclaimerService,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Garantiza que el output contiene disclaimer legal.
   *
   * @param string $output
   *   Respuesta del agente.
   * @param array $coherenceResult
   *   Resultado de las capas 6+7 (score, violations, warnings).
   *   Usado para mostrar Legal Coherence Score al usuario si < umbral.
   * @param array $context
   *   Contexto: intent, action, tenant_id.
   *
   * @return string
   *   Output con disclaimer garantizado.
   */
  public function enforce(string $output, array $coherenceResult = [], array $context = []): string {
    // 1. Obtener disclaimer del servicio existente o fallback.
    $disclaimer = $this->getDisclaimer();

    // 2. Verificar si ya contiene disclaimer (evitar duplicados).
    if ($this->containsDisclaimer($output)) {
      return $this->appendCoherenceScore($output, $coherenceResult);
    }

    // 3. Inyectar disclaimer.
    $output = $this->appendDisclaimer($output, $disclaimer);

    // 4. Anadir Legal Coherence Score si esta por debajo del umbral.
    $output = $this->appendCoherenceScore($output, $coherenceResult);

    return $output;
  }

  /**
   * Obtiene el disclaimer del servicio existente.
   *
   * Intenta obtener del LegalDisclaimerService (con nivel
   * configurable por tenant). Si falla, usa fallback.
   */
  protected function getDisclaimer(): string {
    if ($this->disclaimerService) {
      try {
        $disclaimer = $this->disclaimerService->getDisclaimer();
        if (!empty($disclaimer)) {
          return $disclaimer;
        }
      }
      catch (\Throwable) {
        // Fallback.
      }
    }
    return self::FALLBACK_DISCLAIMER;
  }

  /**
   * Verifica si el output ya contiene un disclaimer.
   *
   * Busca marcadores textuales comunes para evitar duplicados.
   */
  protected function containsDisclaimer(string $output): bool {
    $outputLower = mb_strtolower($output);
    foreach (self::DISCLAIMER_MARKERS as $marker) {
      if (str_contains($outputLower, $marker)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Anade disclaimer al final del output.
   */
  protected function appendDisclaimer(string $output, string $disclaimer): string {
    return $output . "\n\n---\n*" . $disclaimer . "*";
  }

  /**
   * Anade Legal Coherence Score visible si esta por debajo del umbral.
   *
   * Transparencia: cuando el score es bajo, el usuario ve el
   * indice de confianza para tomar decisiones informadas.
   */
  protected function appendCoherenceScore(string $output, array $coherenceResult): string {
    if (empty($coherenceResult)) {
      return $output;
    }

    $score = $coherenceResult['score'] ?? NULL;
    if ($score === NULL) {
      return $output;
    }

    // Convertir 0.0-1.0 a 0-100.
    $score100 = (int) round($score * 100);

    if ($score100 < self::SCORE_WARNING_THRESHOLD) {
      $output .= sprintf(
        "\n\n*Indice de confianza juridica: %d/100. Se recomienda verificar con fuentes oficiales.*",
        $score100,
      );
    }

    return $output;
  }

}
