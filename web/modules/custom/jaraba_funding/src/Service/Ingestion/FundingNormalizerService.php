<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service\Ingestion;

use Psr\Log\LoggerInterface;

/**
 * Servicio de normalizacion de datos de convocatorias.
 *
 * Transforma datos crudos de diferentes fuentes (BDNS, BOJA) a un
 * formato estandar interno. Incluye extraccion de tipos de beneficiario,
 * sectores, normalizacion de regiones y parseo de importes y fechas
 * en formatos espanoles.
 *
 * ARQUITECTURA:
 * - Normalizadores especificos por fuente (BDNS, BOJA).
 * - Extraccion de entidades desde texto libre.
 * - Parseo de formatos numericos espanoles (1.234.567,89).
 * - Parseo de formatos de fecha espanoles (DD/MM/YYYY, etc.).
 *
 * RELACIONES:
 * - FundingNormalizerService -> FundingIngestionService (consumido por)
 */
class FundingNormalizerService {

  /**
   * Mapa de tipos de beneficiario reconocidos.
   */
  protected const BENEFICIARY_KEYWORDS = [
    'autonomo' => 'autonomo',
    'autonomos' => 'autonomo',
    'trabajador autonomo' => 'autonomo',
    'pyme' => 'pyme',
    'pymes' => 'pyme',
    'pequena empresa' => 'pyme',
    'mediana empresa' => 'pyme',
    'micropyme' => 'micropyme',
    'microempresa' => 'micropyme',
    'gran empresa' => 'gran_empresa',
    'grandes empresas' => 'gran_empresa',
    'entidad sin animo de lucro' => 'sin_animo_lucro',
    'entidades sin animo de lucro' => 'sin_animo_lucro',
    'ong' => 'sin_animo_lucro',
    'asociacion' => 'asociacion',
    'asociaciones' => 'asociacion',
    'fundacion' => 'fundacion',
    'fundaciones' => 'fundacion',
    'cooperativa' => 'cooperativa',
    'cooperativas' => 'cooperativa',
    'persona fisica' => 'persona_fisica',
    'personas fisicas' => 'persona_fisica',
    'ayuntamiento' => 'administracion_local',
    'ayuntamientos' => 'administracion_local',
    'entidad local' => 'administracion_local',
    'entidades locales' => 'administracion_local',
    'universidad' => 'universidad',
    'universidades' => 'universidad',
    'centro de investigacion' => 'centro_investigacion',
    'centros de investigacion' => 'centro_investigacion',
    'comunidad de bienes' => 'comunidad_bienes',
    'sociedad civil' => 'sociedad_civil',
  ];

  /**
   * Mapa de sectores reconocidos.
   */
  protected const SECTOR_KEYWORDS = [
    'agricultura' => 'agricultura',
    'agro' => 'agricultura',
    'ganaderia' => 'ganaderia',
    'pesca' => 'pesca',
    'industria' => 'industria',
    'industrial' => 'industria',
    'comercio' => 'comercio',
    'turismo' => 'turismo',
    'hosteleria' => 'turismo',
    'tecnologia' => 'tecnologia',
    'tic' => 'tecnologia',
    'digital' => 'tecnologia',
    'innovacion' => 'innovacion',
    'i+d' => 'innovacion',
    'i+d+i' => 'innovacion',
    'investigacion' => 'innovacion',
    'energia' => 'energia',
    'renovable' => 'energia',
    'medio ambiente' => 'medio_ambiente',
    'medioambiental' => 'medio_ambiente',
    'sostenibilidad' => 'medio_ambiente',
    'educacion' => 'educacion',
    'formacion' => 'educacion',
    'salud' => 'salud',
    'sanitario' => 'salud',
    'cultura' => 'cultura',
    'deporte' => 'deporte',
    'empleo' => 'empleo',
    'vivienda' => 'vivienda',
    'transporte' => 'transporte',
    'logistica' => 'transporte',
    'construccion' => 'construccion',
    'agroalimentario' => 'agroalimentario',
    'alimentacion' => 'agroalimentario',
    'servicios sociales' => 'servicios_sociales',
    'social' => 'servicios_sociales',
  ];

  /**
   * Mapa de normalizacion de regiones.
   */
  protected const REGION_MAP = [
    'andalucia' => 'andalucia',
    'andalucía' => 'andalucia',
    'aragon' => 'aragon',
    'aragón' => 'aragon',
    'asturias' => 'asturias',
    'principado de asturias' => 'asturias',
    'baleares' => 'baleares',
    'islas baleares' => 'baleares',
    'illes balears' => 'baleares',
    'canarias' => 'canarias',
    'islas canarias' => 'canarias',
    'cantabria' => 'cantabria',
    'castilla y leon' => 'castilla_y_leon',
    'castilla y león' => 'castilla_y_leon',
    'castilla-la mancha' => 'castilla_la_mancha',
    'cataluna' => 'cataluna',
    'cataluña' => 'cataluna',
    'catalunya' => 'cataluna',
    'comunidad valenciana' => 'comunidad_valenciana',
    'comunitat valenciana' => 'comunidad_valenciana',
    'valencia' => 'comunidad_valenciana',
    'extremadura' => 'extremadura',
    'galicia' => 'galicia',
    'madrid' => 'madrid',
    'comunidad de madrid' => 'madrid',
    'murcia' => 'murcia',
    'region de murcia' => 'murcia',
    'navarra' => 'navarra',
    'comunidad foral de navarra' => 'navarra',
    'pais vasco' => 'pais_vasco',
    'país vasco' => 'pais_vasco',
    'euskadi' => 'pais_vasco',
    'la rioja' => 'la_rioja',
    'rioja' => 'la_rioja',
    'ceuta' => 'ceuta',
    'melilla' => 'melilla',
    'nacional' => 'nacional',
    'estatal' => 'nacional',
    'estado' => 'nacional',
  ];

  /**
   * Constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {}

  /**
   * Normaliza datos crudos de una convocatoria segun la fuente.
   *
   * @param array $rawData
   *   Datos crudos de la convocatoria.
   * @param string $source
   *   Fuente de datos: 'bdns' o 'boja'.
   *
   * @return array
   *   Datos normalizados con formato estandar.
   */
  public function normalize(array $rawData, string $source): array {
    return match ($source) {
      'bdns' => $this->normalizeBdns($rawData),
      'boja' => $this->normalizeBoja($rawData),
      default => $rawData,
    };
  }

  /**
   * Normalizacion especifica para datos de BDNS.
   *
   * @param array $raw
   *   Datos crudos de la BDNS.
   *
   * @return array
   *   Datos normalizados.
   */
  public function normalizeBdns(array $raw): array {
    $title = (string) ($raw['title'] ?? $raw['titulo'] ?? '');
    $description = (string) ($raw['description'] ?? $raw['descripcion'] ?? '');
    $fullText = $title . ' ' . $description;

    $beneficiaryTypes = $raw['beneficiary_types'] ?? $raw['tipos_beneficiario'] ?? [];
    if (empty($beneficiaryTypes) || !is_array($beneficiaryTypes)) {
      $beneficiaryTypes = $this->extractBeneficiaryTypes($fullText);
    }

    $sectors = $raw['sectors'] ?? $raw['sectores'] ?? [];
    if (empty($sectors) || !is_array($sectors)) {
      $sectors = $this->extractSectors($fullText);
    }

    $region = (string) ($raw['region'] ?? $raw['ccaa'] ?? 'nacional');
    $region = $this->normalizeRegion($region);

    $amountMin = $this->parseAmount((string) ($raw['amount_min'] ?? $raw['importe_minimo'] ?? '0'));
    $amountMax = $this->parseAmount((string) ($raw['amount_max'] ?? $raw['importe_maximo'] ?? $raw['importe_total'] ?? '0'));

    $deadline = $raw['deadline'] ?? $raw['fecha_fin_solicitud'] ?? NULL;
    if ($deadline !== NULL) {
      $deadline = $this->parseDate((string) $deadline);
    }

    $publicationDate = $raw['publication_date'] ?? $raw['fecha_publicacion'] ?? NULL;
    if ($publicationDate !== NULL) {
      $publicationDate = $this->parseDate((string) $publicationDate);
    }

    return [
      'source_id' => (string) ($raw['source_id'] ?? $raw['id'] ?? $raw['bdns_id'] ?? ''),
      'source' => 'bdns',
      'title' => $title,
      'description' => $description,
      'organism' => (string) ($raw['organism'] ?? $raw['organo'] ?? $raw['organismo'] ?? ''),
      'region' => $region,
      'beneficiary_types' => $beneficiaryTypes,
      'sectors' => $sectors,
      'amount_min' => $amountMin,
      'amount_max' => $amountMax,
      'deadline' => $deadline,
      'publication_date' => $publicationDate,
      'url' => (string) ($raw['url'] ?? ''),
      'status' => (string) ($raw['status'] ?? $raw['estado'] ?? 'abierta'),
    ];
  }

  /**
   * Normalizacion especifica para datos de BOJA.
   *
   * @param array $raw
   *   Datos crudos del BOJA.
   *
   * @return array
   *   Datos normalizados.
   */
  public function normalizeBoja(array $raw): array {
    $title = (string) ($raw['title'] ?? $raw['titulo'] ?? '');
    $description = (string) ($raw['description'] ?? $raw['descripcion'] ?? $raw['extracto'] ?? '');
    $fullText = $title . ' ' . $description;

    $beneficiaryTypes = $raw['beneficiary_types'] ?? $raw['tipos_beneficiario'] ?? $raw['destinatarios'] ?? [];
    if (empty($beneficiaryTypes) || !is_array($beneficiaryTypes)) {
      $beneficiaryTypes = $this->extractBeneficiaryTypes($fullText);
    }

    $sectors = $raw['sectors'] ?? $raw['sectores'] ?? $raw['materias'] ?? [];
    if (empty($sectors) || !is_array($sectors)) {
      $sectors = $this->extractSectors($fullText);
    }

    $amountMin = $this->parseAmount((string) ($raw['amount_min'] ?? $raw['importe_minimo'] ?? '0'));
    $amountMax = $this->parseAmount((string) ($raw['amount_max'] ?? $raw['importe_maximo'] ?? $raw['dotacion'] ?? '0'));

    $deadline = $raw['deadline'] ?? $raw['fecha_fin_solicitud'] ?? $raw['plazo_fin'] ?? NULL;
    if ($deadline !== NULL) {
      $deadline = $this->parseDate((string) $deadline);
    }

    $publicationDate = $raw['publication_date'] ?? $raw['fecha_publicacion'] ?? NULL;
    if ($publicationDate !== NULL) {
      $publicationDate = $this->parseDate((string) $publicationDate);
    }

    return [
      'source_id' => (string) ($raw['source_id'] ?? $raw['id'] ?? $raw['boja_id'] ?? ''),
      'source' => 'boja',
      'title' => $title,
      'description' => $description,
      'organism' => (string) ($raw['organism'] ?? $raw['consejeria'] ?? $raw['organo'] ?? ''),
      'region' => 'andalucia',
      'beneficiary_types' => $beneficiaryTypes,
      'sectors' => $sectors,
      'amount_min' => $amountMin,
      'amount_max' => $amountMax,
      'deadline' => $deadline,
      'publication_date' => $publicationDate,
      'url' => (string) ($raw['url'] ?? $raw['enlace'] ?? ''),
      'status' => (string) ($raw['status'] ?? $raw['estado'] ?? 'abierta'),
    ];
  }

  /**
   * Extrae tipos de beneficiario desde un texto libre.
   *
   * Busca palabras clave en el texto y las mapea a tipos normalizados.
   *
   * @param string $text
   *   Texto donde buscar tipos de beneficiario.
   *
   * @return array
   *   Lista de tipos de beneficiario encontrados (valores unicos).
   */
  public function extractBeneficiaryTypes(string $text): array {
    $found = [];
    $lowerText = mb_strtolower($text);

    foreach (self::BENEFICIARY_KEYWORDS as $keyword => $type) {
      if (str_contains($lowerText, $keyword)) {
        $found[$type] = $type;
      }
    }

    return array_values($found);
  }

  /**
   * Extrae sectores de actividad desde un texto libre.
   *
   * Busca palabras clave en el texto y las mapea a sectores normalizados.
   *
   * @param string $text
   *   Texto donde buscar sectores.
   *
   * @return array
   *   Lista de sectores encontrados (valores unicos).
   */
  public function extractSectors(string $text): array {
    $found = [];
    $lowerText = mb_strtolower($text);

    foreach (self::SECTOR_KEYWORDS as $keyword => $sector) {
      if (str_contains($lowerText, $keyword)) {
        $found[$sector] = $sector;
      }
    }

    return array_values($found);
  }

  /**
   * Normaliza nombres de regiones/comunidades autonomas.
   *
   * @param string $raw
   *   Nombre de region tal como viene de la fuente.
   *
   * @return string
   *   Nombre normalizado de la region (clave estandar).
   */
  public function normalizeRegion(string $raw): string {
    $lower = mb_strtolower(trim($raw));

    if (isset(self::REGION_MAP[$lower])) {
      return self::REGION_MAP[$lower];
    }

    // Busqueda parcial.
    foreach (self::REGION_MAP as $key => $normalized) {
      if (str_contains($lower, $key) || str_contains($key, $lower)) {
        return $normalized;
      }
    }

    // Si no se reconoce, devolver en minusculas sin acentos.
    $this->logger->warning('Region no reconocida: "@raw". Se usara tal cual.', [
      '@raw' => $raw,
    ]);

    return $lower;
  }

  /**
   * Parsea importes en formato espanol a float.
   *
   * Convierte formatos como "1.234.567,89" a 1234567.89.
   * Soporta tambien formatos con simbolo de euro y espacios.
   *
   * @param string $raw
   *   Importe en formato texto.
   *
   * @return float
   *   Importe como numero decimal.
   */
  public function parseAmount(string $raw): float {
    if (empty($raw)) {
      return 0.0;
    }

    // Eliminar simbolos de moneda y espacios.
    $cleaned = preg_replace('/[€$\s]/', '', trim($raw));

    if (empty($cleaned)) {
      return 0.0;
    }

    // Si ya es un numero valido en formato ingles, devolverlo directamente.
    if (is_numeric($cleaned)) {
      return (float) $cleaned;
    }

    // Formato espanol: puntos como separadores de miles, coma decimal.
    // "1.234.567,89" -> "1234567.89"
    $cleaned = str_replace('.', '', $cleaned);
    $cleaned = str_replace(',', '.', $cleaned);

    if (is_numeric($cleaned)) {
      return (float) $cleaned;
    }

    return 0.0;
  }

  /**
   * Parsea formatos de fecha espanoles a formato ISO Y-m-d.
   *
   * Soporta formatos:
   * - DD/MM/YYYY
   * - DD-MM-YYYY
   * - DD.MM.YYYY
   * - YYYY-MM-DD (ya en formato ISO)
   * - "DD de mes de YYYY" (formato largo espanol)
   *
   * @param string $raw
   *   Fecha en formato texto.
   *
   * @return string|null
   *   Fecha en formato Y-m-d o NULL si no se puede parsear.
   */
  public function parseDate(string $raw): ?string {
    $raw = trim($raw);

    if (empty($raw)) {
      return NULL;
    }

    // Ya en formato ISO (YYYY-MM-DD).
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
      return $raw;
    }

    // Formato DD/MM/YYYY o DD-MM-YYYY o DD.MM.YYYY.
    if (preg_match('#^(\d{1,2})[/\-.](\d{1,2})[/\-.](\d{4})$#', $raw, $matches)) {
      $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
      $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
      $year = $matches[3];

      if (checkdate((int) $month, (int) $day, (int) $year)) {
        return "{$year}-{$month}-{$day}";
      }
    }

    // Formato largo espanol: "15 de enero de 2026".
    $meses = [
      'enero' => '01', 'febrero' => '02', 'marzo' => '03',
      'abril' => '04', 'mayo' => '05', 'junio' => '06',
      'julio' => '07', 'agosto' => '08', 'septiembre' => '09',
      'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12',
    ];

    $pattern = '/(\d{1,2})\s+de\s+(' . implode('|', array_keys($meses)) . ')\s+de\s+(\d{4})/i';
    if (preg_match($pattern, $raw, $matches)) {
      $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
      $monthName = mb_strtolower($matches[2]);
      $month = $meses[$monthName] ?? NULL;
      $year = $matches[3];

      if ($month !== NULL && checkdate((int) $month, (int) $day, (int) $year)) {
        return "{$year}-{$month}-{$day}";
      }
    }

    // Intentar parseo generico con strtotime.
    $timestamp = strtotime($raw);
    if ($timestamp !== FALSE) {
      return date('Y-m-d', $timestamp);
    }

    $this->logger->warning('Fecha no reconocida: "@raw".', ['@raw' => $raw]);

    return NULL;
  }

}
