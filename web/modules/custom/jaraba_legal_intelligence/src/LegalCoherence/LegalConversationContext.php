<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\LegalCoherence;

use Psr\Log\LoggerInterface;

/**
 * Contexto de conversacion legal para coherencia multi-turn.
 *
 * LEGAL-COHERENCE-MULTITURN-001: Persiste por sesion de copilot.
 * Cross-turn coherence check detecta contradicciones entre turnos.
 *
 * Almacena:
 * - Afirmaciones juridicas hechas en turnos previos.
 * - Normas citadas y su interpretacion.
 * - Posiciones tomadas (competencia, jerarquia, vigencia).
 *
 * El Validator (Capa 6) consulta este contexto para detectar
 * contradicciones entre el turno actual y turnos previos.
 *
 * Persistencia: session storage via Drupal tempstore.
 * TTL: duracion de la sesion de copilot.
 */
final class LegalConversationContext {

  /**
   * Maximo de afirmaciones almacenadas por sesion.
   */
  private const MAX_ASSERTIONS = 50;

  /**
   * Maximo de turnos almacenados.
   */
  private const MAX_TURNS = 20;

  /**
   * Afirmaciones juridicas de turnos previos.
   *
   * @var array<int, array{turn: int, assertion: string, norm: string, position: string}>
   */
  protected array $assertions = [];

  /**
   * Normas citadas con interpretacion.
   *
   * @var array<string, string>
   */
  protected array $citedNorms = [];

  /**
   * Turnos de la sesion.
   *
   * @var array<int, array{query: string, output_hash: string, timestamp: int}>
   */
  protected array $turns = [];

  /**
   * Contador de turnos.
   */
  protected int $turnCount = 0;

  public function __construct(
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Registra un nuevo turno en la conversacion.
   *
   * @param string $query
   *   Query del usuario.
   * @param string $output
   *   Output del agente.
   * @param array $validationResult
   *   Resultado de validacion del turno (Capa 6).
   */
  public function addTurn(string $query, string $output, array $validationResult = []): void {
    $this->turnCount++;

    // Almacenar turno.
    $this->turns[] = [
      'turn' => $this->turnCount,
      'query' => mb_substr($query, 0, 500),
      'output_hash' => md5($output),
      'timestamp' => time(),
      'score' => $validationResult['score'] ?? NULL,
    ];

    // Limitar turnos almacenados.
    if (count($this->turns) > self::MAX_TURNS) {
      $this->turns = array_slice($this->turns, -self::MAX_TURNS);
    }

    // Extraer afirmaciones juridicas del output.
    $this->extractAssertions($output);
  }

  /**
   * Verifica coherencia del turno actual con turnos previos.
   *
   * @param string $currentOutput
   *   Output del turno actual.
   *
   * @return array{contradictions: array, is_coherent: bool}
   *   Resultado de la verificacion.
   */
  public function checkCrossTurnCoherence(string $currentOutput): array {
    $contradictions = [];

    if (empty($this->assertions)) {
      return ['contradictions' => [], 'is_coherent' => TRUE];
    }

    $outputLower = mb_strtolower($currentOutput);

    // Verificar contradicciones con posiciones previas.
    foreach ($this->assertions as $assertion) {
      $assertionLower = mb_strtolower($assertion['assertion']);

      // Verificar si el turno actual contradice la posicion previa.
      $contradiction = $this->detectContradiction($assertionLower, $outputLower);
      if ($contradiction) {
        $contradictions[] = [
          'previous_turn' => $assertion['turn'],
          'previous_assertion' => $assertion['assertion'],
          'previous_norm' => $assertion['norm'] ?? '',
          'contradiction_type' => $contradiction['type'],
          'description' => $contradiction['description'],
        ];
      }
    }

    $isCoherent = empty($contradictions);

    if (!$isCoherent) {
      $this->logger->info('Cross-turn coherence issues detected: @count contradiction(s) in turn @turn', [
        '@count' => count($contradictions),
        '@turn' => $this->turnCount + 1,
      ]);
    }

    return [
      'contradictions' => $contradictions,
      'is_coherent' => $isCoherent,
    ];
  }

  /**
   * Extrae afirmaciones juridicas del output para seguimiento.
   *
   * Heuristica: busca patrones de afirmacion juridica y almacena.
   */
  protected function extractAssertions(string $output): void {
    // Patrones de posiciones juridicas.
    $patterns = [
      '/(?:es|sera?)\s+competencia\s+(?:exclusiva\s+)?(?:del\s+Estado|de\s+las?\s+CCAA)/i' => 'competencia',
      '/(?:requiere|exige)\s+Ley\s+Organica/i' => 'reserva_lo',
      '/(?:prevalece|prima)\s+(?:el|la|los?)\s+(?:Derecho\s+UE|derecho\s+interno)/i' => 'primacia',
      '/(?:esta|fue|ha\s+sido)\s+(?:derogad[ao]|modificad[ao]|sustituida?)/i' => 'vigencia',
      '/no\s+tiene\s+efecto\s+retroactivo/i' => 'irretroactividad',
      '/(?:norma\s+posterior|lex\s+posterior)\s+(?:deroga|prevalece)/i' => 'lex_posterior',
    ];

    $sentences = preg_split('/[.]\s+/', $output);
    foreach ($sentences as $sentence) {
      foreach ($patterns as $pattern => $position) {
        if (preg_match($pattern, $sentence)) {
          $this->assertions[] = [
            'turn' => $this->turnCount,
            'assertion' => mb_substr(trim($sentence), 0, 300),
            'norm' => $this->extractNormReference($sentence),
            'position' => $position,
          ];

          // Limitar afirmaciones.
          if (count($this->assertions) > self::MAX_ASSERTIONS) {
            array_shift($this->assertions);
          }
          break;
        }
      }
    }
  }

  /**
   * Detecta contradiccion entre una afirmacion previa y el output actual.
   */
  protected function detectContradiction(string $previousAssertion, string $currentOutput): ?array {
    // Pares contradictorios.
    $pairs = [
      ['competencia exclusiva del estado', 'las ccaa pueden legislar', 'competencia'],
      ['no tiene efecto retroactivo', 'se aplica retroactivamente', 'retroactividad'],
      ['requiere ley organica', 'se regula por ley ordinaria', 'reserva_lo'],
      ['prevalece el derecho ue', 'la ley espanola prevalece', 'primacia_ue'],
      ['esta derogada', 'esta vigente', 'vigencia'],
      ['fue derogada', 'sigue vigente', 'vigencia'],
    ];

    foreach ($pairs as [$assertionPattern, $outputPattern, $type]) {
      if (str_contains($previousAssertion, $assertionPattern)
        && str_contains($currentOutput, $outputPattern)) {
        return [
          'type' => $type,
          'description' => sprintf(
            'En turno previo se afirmo "%s" pero ahora se afirma "%s".',
            $assertionPattern,
            $outputPattern,
          ),
        ];
      }
    }

    return NULL;
  }

  /**
   * Extrae referencia normativa de una frase.
   */
  protected function extractNormReference(string $sentence): string {
    foreach (LegalCoherenceKnowledgeBase::NORM_TYPE_PATTERNS as $pattern => $key) {
      if (preg_match($pattern, $sentence, $m)) {
        return $m[0];
      }
    }
    return '';
  }

  /**
   * Reinicia el contexto (nueva sesion).
   */
  public function reset(): void {
    $this->assertions = [];
    $this->citedNorms = [];
    $this->turns = [];
    $this->turnCount = 0;
  }

  /**
   * Obtiene el numero de turnos en la sesion.
   */
  public function getTurnCount(): int {
    return $this->turnCount;
  }

  /**
   * Obtiene las afirmaciones almacenadas.
   *
   * @return array
   *   Afirmaciones juridicas de turnos previos.
   */
  public function getAssertions(): array {
    return $this->assertions;
  }

}
