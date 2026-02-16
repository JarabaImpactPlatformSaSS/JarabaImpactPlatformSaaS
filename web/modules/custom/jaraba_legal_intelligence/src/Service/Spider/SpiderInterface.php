<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_intelligence\Service\Spider;

/**
 * Interfaz para los spiders del Legal Intelligence Hub.
 *
 * ESTRUCTURA:
 * Contrato que deben cumplir todos los conectores (spiders) a fuentes de datos
 * juridicos. Cada spider implementa la logica de extraccion especifica para
 * una fuente concreta: CENDOJ (jurisprudencia), BOE (legislacion), DGT
 * (consultas vinculantes) y TEAC (resoluciones economico-administrativas).
 * Las fuentes europeas (EUR-Lex, CURIA, HUDOC, EDPB) se anaden en Fase 4.
 *
 * LOGICA:
 * El metodo crawl() es el punto de entrada principal. Recibe opciones de
 * filtrado (date_from, date_to, etc.) y devuelve un array de arrays asociativos
 * con los datos crudos de cada resolucion. El metodo supports() permite al
 * LegalIngestionService resolver dinamicamente que spider manejar para cada
 * LegalSource. getFrequency() informa la cadencia de ejecucion para que el
 * scheduler determine si es momento de ejecutar el spider.
 *
 * RELACIONES:
 * - SpiderInterface <- CendojSpider, BoeSpider, DgtSpider, TeacSpider:
 *   implementaciones concretas para cada fuente nacional.
 * - SpiderInterface <- EurLexSpider, CuriaSpider, HudocSpider, EdpbSpider:
 *   implementaciones concretas para cada fuente europea (Fase 4).
 * - SpiderInterface <- LegalIngestionService: orquestador que invoca crawl().
 * - SpiderInterface -> jaraba_legal_intelligence.sources.yml: configuracion
 *   de URLs base y parametros de cada fuente.
 */
interface SpiderInterface {

  /**
   * Devuelve el identificador maquina del spider.
   *
   * El ID coincide con el machine_name de la entidad LegalSource
   * correspondiente (cendoj, boe, dgt, teac, eurlex, curia, hudoc, edpb).
   *
   * @return string
   *   Identificador maquina del spider.
   */
  public function getId(): string;

  /**
   * Ejecuta el rastreo de la fuente y extrae resoluciones.
   *
   * @param array $options
   *   Opciones de filtrado. Claves soportadas:
   *   - 'date_from' (string): Fecha inicio en formato Y-m-d.
   *   - 'date_to' (string): Fecha fin en formato Y-m-d.
   *   - 'max_results' (int): Limite de resultados a devolver.
   *
   * @return array
   *   Array de arrays asociativos con datos crudos de resoluciones.
   *   Cada elemento contiene: source_id, external_ref, title,
   *   resolution_type, issuing_body, jurisdiction, date_issued,
   *   date_published, original_url, full_text.
   */
  public function crawl(array $options = []): array;

  /**
   * Devuelve la frecuencia de ejecucion del spider.
   *
   * @return string
   *   Cadencia: 'daily', 'weekly' o 'monthly'.
   */
  public function getFrequency(): string;

  /**
   * Comprueba si este spider soporta la fuente indicada.
   *
   * @param string $sourceId
   *   Identificador maquina de la fuente (machine_name de LegalSource).
   *
   * @return bool
   *   TRUE si este spider maneja la fuente indicada.
   */
  public function supports(string $sourceId): bool;

}
