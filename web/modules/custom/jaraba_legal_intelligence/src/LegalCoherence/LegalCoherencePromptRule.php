<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

/**
 * Regla de coherencia juridica inyectable en system prompts.
 *
 * Patron analogo a AIIdentityRule: static apply() prepends coherence
 * instructions to the system prompt. Called by:
 * - SmartLegalCopilotAgent::buildModePrompt()
 * - LegalRagService::buildSystemPrompt()
 * - SmartBaseAgent::callAiApi() (cuando vertical = jarabalex)
 * - CopilotOrchestratorService (modos fiscal, laboral)
 * - LegalCopilotBridgeService::injectLegalContext()
 *
 * LEGAL-COHERENCE-PROMPT-001: Este prompt NO sustituye el conocimiento
 * juridico del LLM. Lo ALINEA para que respete principios estructurales
 * del ordenamiento que el LLM ya conoce pero puede violar por inercia
 * generativa (hallucination, confabulacion).
 */
final class LegalCoherencePromptRule {

  /**
   * Prompt completo de coherencia juridica.
   *
   * Inyectado en modos legales que usan tier balanced/premium.
   */
  public const COHERENCE_PROMPT = <<<'PROMPT'
## REGLAS DE COHERENCIA JURIDICA (INQUEBRANTABLES)

Debes respetar SIEMPRE los siguientes principios del ordenamiento juridico espanol y europeo. Cualquier respuesta que los viole es INCORRECTA y perjudicial para el usuario.

### R1. JERARQUIA NORMATIVA (Art. 9.3 CE)
El ordenamiento juridico espanol tiene una jerarquia estricta. Una norma de rango inferior NUNCA puede contradecir, derogar ni prevalecer sobre una de rango superior:

Derecho UE Primario (Tratados) > Derecho UE Derivado (Reglamentos, Directivas) > Constitucion Espanola > Ley Organica > Ley Ordinaria / RDL / RDLeg > Reglamento (RD, OM) > Ley Autonomica > Reglamento Autonomico > Normativa Local

NUNCA afirmes que un Real Decreto deroga una Ley, que una Orden Ministerial prevalece sobre un Real Decreto-ley, ni que una Ley Autonomica regula materia de competencia exclusiva estatal.

### R2. PRIMACIA DEL DERECHO UE
El Derecho de la Union Europea prevalece sobre CUALQUIER norma interna contraria, incluida la aplicacion de la Constitucion (TJUE: Costa v. ENEL 6/64, Simmenthal 106/77). Los Reglamentos UE tienen efecto directo. Las Directivas no transpuestas con disposiciones claras, precisas e incondicionales generan derechos invocables (Van Gend en Loos 26/62).

NUNCA afirmes que una ley espanola prevalece sobre un Reglamento UE ni que una Directiva carece de efecto por no estar transpuesta si sus disposiciones son claras y precisas.

### R3. COMPETENCIAS ESTADO vs. CCAA (Arts. 148-149 CE)
Las competencias exclusivas del Estado (Art. 149.1 CE) incluyen: legislacion penal, mercantil, laboral, procesal, civil basica, propiedad intelectual, Seguridad Social, procedimiento administrativo comun, etc. Las CCAA NO pueden legislar en estas materias salvo que el Estado les transfiera o delegue competencias (Art. 150 CE).

NUNCA atribuyas a una Comunidad Autonoma competencia para legislar sobre materia exclusiva del Estado sin especificar el titulo competencial habilitante.

### R4. RESERVA DE LEY ORGANICA (Art. 81 CE)
Las siguientes materias SOLO pueden regularse por Ley Organica (mayoria absoluta Congreso): derechos fundamentales y libertades publicas, Estatutos de Autonomia, regimen electoral general, Defensor del Pueblo, TC, LOPJ, estados de alarma/excepcion/sitio.

NUNCA afirmes que una ley ordinaria, decreto o reglamento desarrolla derechos fundamentales.

### R5. IRRETROACTIVIDAD (Art. 9.3 CE)
Las disposiciones sancionadoras no favorables y las restrictivas de derechos individuales NO tienen efecto retroactivo. La retroactividad favorable SI es posible en materia sancionadora.

### R6. VIGENCIA Y DEROGACION
Cuando cites una norma, indica siempre su estado de vigencia si lo conoces. Si una norma ha sido derogada, modificada o sustituida, mencionalo expresamente. No cites normas derogadas como si estuvieran vigentes sin advertencia.

### R7. CONSISTENCIA TRANSVERSAL
Si en la misma respuesta o en respuestas anteriores del contexto has afirmado un principio juridico, NO puedes contradecirlo. Si existen corrientes doctrinales opuestas, presentalas como tales.

### R8. HUMILDAD JURIDICA
Ante duda sobre la jerarquia, competencia o vigencia de una norma, indica la duda al usuario. Es preferible reconocer incertidumbre que afirmar algo juridicamente incorrecto. Usa formulas como: "Cabria verificar si...", "La vigencia de esta norma podria estar afectada por...".
PROMPT;

  /**
   * Prompt compacto para fast tier o contextos con ventana limitada.
   */
  public const COHERENCE_PROMPT_SHORT = <<<'PROMPT'
## COHERENCIA JURIDICA (OBLIGATORIA)
1. JERARQUIA: DUE > CE > LO > Ley > RD > Ley CCAA > Local. Inferior NO deroga superior.
2. PRIMACIA UE: Derecho UE prevalece sobre derecho interno (Costa v. ENEL).
3. COMPETENCIAS: Art. 149.1 CE = exclusivas Estado. CCAA NO legislan en ellas.
4. LO: DDFF, Estatutos, LOREG = solo Ley Organica.
5. IRRETROACTIVIDAD: Sanciones desfavorables NO retroactivas (Art. 9.3 CE).
6. VIGENCIA: Indica siempre si la norma esta vigente, derogada o modificada.
7. CONSISTENCIA: No te contradigas. Duda = "cabria verificar".
PROMPT;

  /**
   * Aplica la regla de coherencia juridica al system prompt.
   *
   * @param string $systemPrompt
   *   El prompt original del agente.
   * @param bool $short
   *   TRUE para version compacta (fast tier).
   *
   * @return string
   *   El prompt con la regla de coherencia prepended.
   */
  public static function apply(string $systemPrompt, bool $short = FALSE): string {
    $rule = $short ? self::COHERENCE_PROMPT_SHORT : self::COHERENCE_PROMPT;
    return $rule . "\n\n" . $systemPrompt;
  }

  /**
   * Aplica regla con contexto territorial.
   *
   * @param string $systemPrompt
   *   Prompt original.
   * @param string $territory
   *   CCAA del tenant (ej: 'Andalucia', 'Cataluna').
   * @param bool $short
   *   TRUE para version compacta.
   *
   * @return string
   *   Prompt enriquecido con contexto territorial.
   */
  public static function applyWithTerritory(
    string $systemPrompt,
    string $territory = '',
    bool $short = FALSE,
  ): string {
    $rule = $short ? self::COHERENCE_PROMPT_SHORT : self::COHERENCE_PROMPT;

    if ($territory) {
      $territoryNote = sprintf(
        "\n\n### R9. CONTEXTO TERRITORIAL\nEl usuario opera en %s. Prioriza la normativa autonomica de %s sobre la de otras CCAA. NUNCA apliques normativa autonomica de una Comunidad Autonoma a otra distinta.",
        $territory,
        $territory,
      );

      $foralRegime = LegalCoherenceKnowledgeBase::getForalRegime('sucesiones', $territory);
      if ($foralRegime) {
        $territoryNote .= sprintf(
          " NOTA FORAL: %s tiene Derecho Civil propio (%s). En materias de %s, la normativa foral prevalece sobre el Codigo Civil estatal.",
          $territory,
          $foralRegime['corpus'],
          implode(', ', $foralRegime['materias']),
        );
      }

      $rule .= $territoryNote;
    }

    return $rule . "\n\n" . $systemPrompt;
  }

  /**
   * Determina si una accion requiere inyeccion de coherencia.
   *
   * @param string $action
   *   La accion del agente.
   * @param string $vertical
   *   El vertical activo.
   *
   * @return bool
   *   TRUE si la accion requiere coherencia juridica.
   */
  public static function requiresCoherence(string $action, string $vertical = ''): bool {
    if ($vertical === 'jarabalex') {
      return TRUE;
    }

    $legalActions = [
      'legal_search', 'legal_analysis', 'legal_alerts', 'legal_citations',
      'legal_eu', 'case_assistant', 'document_drafter', 'legal_document_draft',
      'contract_generation', 'fiscal', 'laboral',
    ];

    return in_array($action, $legalActions, TRUE);
  }

  /**
   * Determina si debe usarse la version compacta.
   */
  public static function useShortVersion(string $action): bool {
    $fastActions = ['faq', 'legal_alerts', 'legal_citations'];
    return in_array($action, $fastActions, TRUE);
  }

}
