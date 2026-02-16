<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de fusion y re-ranking de resultados nacionales y europeos.
 *
 * ESTRUCTURA:
 * Servicio que combina resultados de busqueda de las colecciones Qdrant
 * nacional y europea, aplicando boosts configurables para primacia UE,
 * frescura temporal e importancia de la resolucion. Tras los boosts,
 * deduplica por resolution_id y ordena por score final descendente.
 *
 * LOGICA:
 * El flujo principal es: recibir resultados crudos nacional + EU ->
 * aplicar EU primacy boost a fuentes TJUE/TEDH/eurlex/EDPB/EBA/ESMA/AG_TJUE ->
 * aplicar freshness boost a resoluciones dentro del periodo configurable ->
 * aplicar importance boost segun importance_level (1=clave, 2=media, 3=baja) ->
 * deduplicar por resolution_id conservando el score mas alto ->
 * ordenar por score final descendente -> devolver array fusionado.
 *
 * RELACIONES:
 * - LegalMergeRankService -> ConfigFactory: lee eu_primacy_boost, freshness_boost,
 *   freshness_days desde jaraba_legal_intelligence.settings.
 * - LegalMergeRankService <- LegalSearchService: invocado cuando scope='all'
 *   para fusionar resultados de ambas colecciones Qdrant.
 * - LegalMergeRankService <- LegalSearchController: puede invocarse directamente
 *   via applyBoosts() para re-rankear un conjunto de resultados individual.
 */
class LegalMergeRankService {

  /**
   * Fuentes europeas cuyos resultados reciben el boost de primacia UE.
   *
   * @var string[]
   */
  private const EU_SOURCES = ['tjue', 'eurlex', 'tedh', 'edpb', 'eba', 'esma', 'ag_tjue'];

  /**
   * Construye una nueva instancia de LegalMergeRankService.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoria de configuracion para leer boosts y thresholds.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_legal_intelligence.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Fusiona y re-rankea resultados de busqueda nacionales y europeos.
   *
   * Punto de entrada principal del servicio. Combina dos conjuntos de
   * resultados crudos, aplica boosts de primacia UE, frescura temporal
   * e importancia, deduplica por resolution_id conservando el score mas
   * alto y ordena por score final descendente.
   *
   * @param array $nationalResults
   *   Resultados de la coleccion Qdrant nacional. Cada elemento es un array
   *   con las claves: id, title, source_id, external_ref, resolution_type,
   *   issuing_body, jurisdiction, date_issued, status_legal, abstract_ai,
   *   key_holdings, topics, cited_legislation, original_url, importance_level,
   *   is_eu, celex_number, ecli, impact_spain, seo_slug, score.
   * @param array $euResults
   *   Resultados de la coleccion Qdrant europea. Misma estructura que
   *   $nationalResults.
   * @param string $scope
   *   Ambito de busqueda: 'national', 'eu' o 'all'. Determina que conjuntos
   *   de resultados se incluyen en la fusion.
   *
   * @return array
   *   Array fusionado de resultados, deduplicado por resolution_id y ordenado
   *   por score descendente. Cada resultado conserva su estructura original
   *   con el score ajustado por los boosts aplicados.
   */
  public function mergeAndRank(array $nationalResults, array $euResults, string $scope): array {
    $config = $this->configFactory->get('jaraba_legal_intelligence.settings');

    // Seleccionar resultados segun el scope.
    $merged = match ($scope) {
      'national' => $nationalResults,
      'eu' => $euResults,
      default => array_merge($nationalResults, $euResults),
    };

    if (empty($merged)) {
      return [];
    }

    // Leer configuracion de boosts.
    $euPrimacyBoost = (float) ($config->get('eu_primacy_boost') ?: 0.05);
    $freshnessBoost = (float) ($config->get('freshness_boost') ?: 0.02);
    $freshnessDays = (int) ($config->get('freshness_days') ?: 365);

    // Aplicar boosts a cada resultado.
    $boosted = [];
    foreach ($merged as $result) {
      $boosted[] = $this->applyBoostsToResult($result, $euPrimacyBoost, $freshnessBoost, $freshnessDays);
    }

    // Deduplicar por resolution_id, conservando el score mas alto.
    $deduped = $this->deduplicateByResolutionId($boosted);

    // Ordenar por score descendente.
    usort($deduped, fn(array $a, array $b) => ($b['score'] ?? 0.0) <=> ($a['score'] ?? 0.0));

    $this->logger->info('MergeRank: Fusionados @total resultados (national=@nat, eu=@eu, scope=@scope) -> @final tras dedup.', [
      '@total' => count($merged),
      '@nat' => count($nationalResults),
      '@eu' => count($euResults),
      '@scope' => $scope,
      '@final' => count($deduped),
    ]);

    return $deduped;
  }

  /**
   * Aplica todos los boosts a un conjunto de resultados individual.
   *
   * Metodo publico util cuando LegalMergeRankService se usa de forma
   * independiente para re-rankear un unico conjunto de resultados sin
   * fusion. Aplica EU primacy boost, freshness boost e importance boost.
   *
   * @param array $results
   *   Array de resultados con la estructura estandar (id, title, source_id,
   *   date_issued, importance_level, is_eu, score, etc.).
   *
   * @return array
   *   Array de resultados con scores ajustados por los boosts, ordenado
   *   por score descendente.
   */
  public function applyBoosts(array $results): array {
    if (empty($results)) {
      return [];
    }

    $config = $this->configFactory->get('jaraba_legal_intelligence.settings');
    $euPrimacyBoost = (float) ($config->get('eu_primacy_boost') ?: 0.05);
    $freshnessBoost = (float) ($config->get('freshness_boost') ?: 0.02);
    $freshnessDays = (int) ($config->get('freshness_days') ?: 365);

    $boosted = [];
    foreach ($results as $result) {
      $boosted[] = $this->applyBoostsToResult($result, $euPrimacyBoost, $freshnessBoost, $freshnessDays);
    }

    // Ordenar por score descendente.
    usort($boosted, fn(array $a, array $b) => ($b['score'] ?? 0.0) <=> ($a['score'] ?? 0.0));

    return $boosted;
  }

  // =========================================================================
  // METODOS PRIVADOS: Boosts individuales.
  // =========================================================================

  /**
   * Aplica todos los boosts configurados a un resultado individual.
   *
   * Suma los boosts aplicables (EU primacy, freshness, importance) al score
   * base del resultado. El score final se redondea a 4 decimales y se
   * acota a un maximo de 1.0.
   *
   * @param array $result
   *   Resultado individual con las claves score, source_id, is_eu,
   *   date_issued e importance_level.
   * @param float $euPrimacyBoost
   *   Boost a sumar a fuentes europeas de primacia (TJUE, TEDH, eurlex, etc.).
   * @param float $freshnessBoost
   *   Boost a sumar a resoluciones dentro del periodo de frescura.
   * @param int $freshnessDays
   *   Numero de dias que definen el periodo de frescura desde hoy.
   *
   * @return array
   *   Resultado con el score ajustado.
   */
  private function applyBoostsToResult(array $result, float $euPrimacyBoost, float $freshnessBoost, int $freshnessDays): array {
    $score = (float) ($result['score'] ?? 0.0);

    // EU primacy boost: se aplica a resultados de fuentes europeas clave.
    $score += $this->calculateEuPrimacyBoost($result, $euPrimacyBoost);

    // Freshness boost: se aplica a resoluciones recientes.
    $score += $this->calculateFreshnessBoost($result, $freshnessBoost, $freshnessDays);

    // Importance boost: se aplica segun el nivel de importancia.
    $score += $this->calculateImportanceBoost($result);

    // Acotar score a maximo 1.0 y redondear a 4 decimales.
    $result['score'] = round(min($score, 1.0), 4);

    return $result;
  }

  /**
   * Calcula el boost de primacia UE para un resultado.
   *
   * Se aplica unicamente a resultados cuyo source_id coincide con una de
   * las fuentes europeas definidas en EU_SOURCES (tjue, eurlex, tedh,
   * edpb, eba, esma, ag_tjue).
   *
   * @param array $result
   *   Resultado con las claves source_id e is_eu.
   * @param float $boost
   *   Valor del boost de primacia UE a sumar.
   *
   * @return float
   *   Valor del boost a aplicar (0.0 si no aplica).
   */
  private function calculateEuPrimacyBoost(array $result, float $boost): float {
    $sourceId = strtolower((string) ($result['source_id'] ?? ''));

    if (in_array($sourceId, self::EU_SOURCES, TRUE)) {
      return $boost;
    }

    return 0.0;
  }

  /**
   * Calcula el boost de frescura temporal para un resultado.
   *
   * Se aplica si la fecha de emision (date_issued) de la resolucion esta
   * dentro del periodo de frescura configurable (freshness_days desde hoy).
   * Si la fecha no se puede parsear, el boost no se aplica.
   *
   * @param array $result
   *   Resultado con la clave date_issued (formato YYYY-MM-DD).
   * @param float $boost
   *   Valor del boost de frescura a sumar.
   * @param int $freshnessDays
   *   Numero de dias que definen el periodo de frescura.
   *
   * @return float
   *   Valor del boost a aplicar (0.0 si no aplica o fecha invalida).
   */
  private function calculateFreshnessBoost(array $result, float $boost, int $freshnessDays): float {
    $dateIssued = (string) ($result['date_issued'] ?? '');

    if (empty($dateIssued)) {
      return 0.0;
    }

    try {
      $issuedDate = new \DateTimeImmutable($dateIssued);
      $today = new \DateTimeImmutable('today');
      $diffDays = (int) $today->diff($issuedDate)->days;

      if ($diffDays <= $freshnessDays) {
        return $boost;
      }
    }
    catch (\Exception $e) {
      // Fecha no parseable: no aplicar boost.
      $this->logger->debug('MergeRank: Fecha no parseable para freshness boost: @date', [
        '@date' => $dateIssued,
      ]);
    }

    return 0.0;
  }

  /**
   * Calcula el boost de importancia para un resultado.
   *
   * Se aplica segun el campo importance_level de la resolucion:
   * - importance_level 1 (caso clave): +0.03
   * - importance_level 2 (importancia media): +0.01
   * - importance_level 3 (importancia baja): +0.00 (sin boost)
   *
   * @param array $result
   *   Resultado con la clave importance_level (int 1-3).
   *
   * @return float
   *   Valor del boost a aplicar (0.0 si no aplica).
   */
  private function calculateImportanceBoost(array $result): float {
    $importanceLevel = (int) ($result['importance_level'] ?? 3);

    return match ($importanceLevel) {
      1 => 0.03,
      2 => 0.01,
      default => 0.0,
    };
  }

  // =========================================================================
  // METODOS PRIVADOS: Deduplicacion.
  // =========================================================================

  /**
   * Deduplica resultados por resolution_id conservando el score mas alto.
   *
   * Cuando una misma resolucion aparece en multiples colecciones o con
   * diferentes chunks, se conserva unicamente la entrada con el score
   * mas alto tras la aplicacion de boosts.
   *
   * @param array $results
   *   Array de resultados potencialmente con duplicados.
   *
   * @return array
   *   Array de resultados deduplicados, un resultado por resolution_id.
   */
  private function deduplicateByResolutionId(array $results): array {
    $seen = [];

    foreach ($results as $result) {
      $resolutionId = $result['id'] ?? NULL;
      if ($resolutionId === NULL) {
        continue;
      }

      $score = (float) ($result['score'] ?? 0.0);

      if (!isset($seen[$resolutionId]) || $score > (float) ($seen[$resolutionId]['score'] ?? 0.0)) {
        $seen[$resolutionId] = $result;
      }
    }

    return array_values($seen);
  }

}
