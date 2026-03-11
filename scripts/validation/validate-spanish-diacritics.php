<?php

/**
 * @file
 * DIACRITICS-ES-001: Validates Spanish orthography in page_content canvas_data.
 *
 * Checks:
 * 1. Missing tildes (á, é, í, ó, ú) and eñes (ñ) — word-boundary safe.
 * 2. Missing opening question marks (¿) in Spanish questions.
 *
 * Uses \b word boundaries to avoid corrupting derived words
 * (e.g. "gestion" → "gestión" but NOT "gestionado" → "gestiónado").
 *
 * Usage:
 *   lando drush php:script scripts/validation/validate-spanish-diacritics.php
 *   lando drush php:script scripts/validation/validate-spanish-diacritics.php -- --fix
 *
 * Part of Safeguard Layer 1 (scripts/validation/).
 * Integrated in validate-all.sh.
 */

declare(strict_types=1);

$fix_mode = in_array('--fix', $argv ?? [], TRUE)
  || in_array('--fix', $extra ?? [], TRUE);

// =========================================================================
// DICTIONARY: unaccented → accented (word-boundary safe, complete words only)
// =========================================================================
$dictionary = [
  // ñ
  'anos' => 'años', 'ano' => 'año',
  'Espana' => 'España', 'espanola' => 'española', 'Espanola' => 'Española',
  'diseno' => 'diseño', 'Diseno' => 'Diseño',
  'ensenanza' => 'enseñanza', 'Ensenanza' => 'Enseñanza',
  'ninos' => 'niños', 'Ninos' => 'Niños',
  'pequeno' => 'pequeño', 'pequena' => 'pequeña',
  'pequenos' => 'pequeños', 'pequenas' => 'pequeñas',
  'companero' => 'compañero', 'companera' => 'compañera',
  'companeros' => 'compañeros', 'companeras' => 'compañeras',

  // -ción/-sión (exact words only, \b prevents matching gestionado etc.)
  'gestion' => 'gestión', 'Gestion' => 'Gestión',
  'formacion' => 'formación', 'Formacion' => 'Formación',
  'informacion' => 'información', 'Informacion' => 'Información',
  'direccion' => 'dirección', 'Direccion' => 'Dirección',
  'orientacion' => 'orientación', 'insercion' => 'inserción',
  'intervencion' => 'intervención', 'transformacion' => 'transformación',
  'innovacion' => 'innovación', 'Innovacion' => 'Innovación',
  'coordinacion' => 'coordinación', 'administracion' => 'administración',
  'organizacion' => 'organización', 'comunicacion' => 'comunicación',
  'colaboracion' => 'colaboración', 'contratacion' => 'contratación',
  'operacion' => 'operación', 'relacion' => 'relación',
  'fundacion' => 'fundación', 'Fundacion' => 'Fundación',
  'educacion' => 'educación', 'certificacion' => 'certificación',
  'habilitacion' => 'habilitación', 'evaluacion' => 'evaluación',
  'prestacion' => 'prestación', 'capacitacion' => 'capacitación',
  'tramitacion' => 'tramitación', 'configuracion' => 'configuración',
  'aplicacion' => 'aplicación', 'validacion' => 'validación',
  'notificacion' => 'notificación', 'facturacion' => 'facturación',
  'documentacion' => 'documentación', 'implementacion' => 'implementación',
  'participacion' => 'participación', 'integracion' => 'integración',
  'proteccion' => 'protección', 'situacion' => 'situación',
  'verificacion' => 'verificación', 'optimizacion' => 'optimización',
  'actualizacion' => 'actualización', 'automatizacion' => 'automatización',
  'personalizacion' => 'personalización', 'visualizacion' => 'visualización',
  'atencion' => 'atención', 'Atencion' => 'Atención',
  'poblacion' => 'población', 'expansion' => 'expansión',
  'solucion' => 'solución', 'decision' => 'decisión',
  'mision' => 'misión', 'Mision' => 'Misión',
  'vision' => 'visión', 'Vision' => 'Visión',
  'profesion' => 'profesión', 'accion' => 'acción', 'Accion' => 'Acción',
  'subvencion' => 'subvención', 'mediacion' => 'mediación',
  'migracion' => 'migración', 'explicacion' => 'explicación',

  // Adjectives/nouns (complete words, \b safe)
  'detras' => 'detrás', 'ademas' => 'además', 'Ademas' => 'Además',
  'ambito' => 'ámbito', 'Cordoba' => 'Córdoba', 'Estevez' => 'Estévez',
  'tambien' => 'también', 'interes' => 'interés', 'traves' => 'través',
  'decadas' => 'décadas', 'regimen' => 'régimen',
  'proposito' => 'propósito', 'codigo' => 'código',
  'titulo' => 'título', 'Titulo' => 'Título',
  'articulo' => 'artículo', 'metodo' => 'método', 'Metodo' => 'Método',
  'unico' => 'único', 'unica' => 'única',
  'ultimo' => 'último', 'ultima' => 'última',
  'busqueda' => 'búsqueda', 'razon' => 'razón',
  'publica' => 'pública', 'publico' => 'público',
  'publicas' => 'públicas', 'publicos' => 'públicos',
  'Publica' => 'Pública', 'Publico' => 'Público',
  'practica' => 'práctica', 'practico' => 'práctico',
  'practicas' => 'prácticas',
  'basico' => 'básico', 'basica' => 'básica',
  'basicos' => 'básicos', 'basicas' => 'básicas',
  'economica' => 'económica', 'economico' => 'económico',
  'Economia' => 'Economía', 'economia' => 'economía',
  'estrategica' => 'estratégica', 'estrategico' => 'estratégico',
  'tecnologica' => 'tecnológica', 'Tecnologia' => 'Tecnología',
  'tecnologia' => 'tecnología', 'logistica' => 'logística',
  'electronico' => 'electrónico', 'electronica' => 'electrónica',
  'automatica' => 'automática',
  'juridico' => 'jurídico', 'juridica' => 'jurídica',
  'Juridico' => 'Jurídico',
  'academica' => 'académica', 'academico' => 'académico',
  'analisis' => 'análisis', 'Analisis' => 'Análisis',
  'diagnostico' => 'diagnóstico', 'diagnosticos' => 'diagnósticos',
  'catalogo' => 'catálogo',
  'caracteristicas' => 'características', 'caracteristica' => 'característica',
  'pagina' => 'página', 'paginas' => 'páginas',
  'numero' => 'número', 'numeros' => 'números',
  'rapida' => 'rápida', 'rapido' => 'rápido',
  'credito' => 'crédito', 'creditos' => 'créditos',
  'metrica' => 'métrica', 'metricas' => 'métricas',
  'tecnico' => 'técnico', 'tecnica' => 'técnica',
  'Tecnico' => 'Técnico', 'Tecnica' => 'Técnica',
  'tecnicas' => 'técnicas', 'tecnicos' => 'técnicos',
  'especifico' => 'específico', 'especifica' => 'específica',
  'Politica' => 'Política', 'politica' => 'política', 'politicas' => 'políticas',
  'autonomo' => 'autónomo', 'autonomos' => 'autónomos',
  'consultoria' => 'consultoría', 'Consultoria' => 'Consultoría',
  'ingenieria' => 'ingeniería', 'asesoria' => 'asesoría',
  'auditoria' => 'auditoría', 'categoria' => 'categoría',
  'categorias' => 'categorías', 'metodologia' => 'metodología',
  'exito' => 'éxito', 'Exito' => 'Éxito',
  // Adverbs (correct with accent)
  'practicamente' => 'prácticamente', 'basicamente' => 'básicamente',
  'economicamente' => 'económicamente', 'tecnicamente' => 'técnicamente',
  'especificamente' => 'específicamente', 'publicamente' => 'públicamente',
  'automaticamente' => 'automáticamente',
];

// Remove identity entries and sort longest first.
$dictionary = array_filter($dictionary, fn($v, $k) => $k !== $v, ARRAY_FILTER_USE_BOTH);
uksort($dictionary, fn($a, $b) => strlen($b) - strlen($a));

// =========================================================================
// SCAN
// =========================================================================
$database = \Drupal::database();
$rows = $database->query(
  "SELECT id, title, langcode, canvas_data FROM {page_content_field_data}
   WHERE langcode = 'es' AND canvas_data IS NOT NULL AND LENGTH(canvas_data) > 100
   ORDER BY id"
)->fetchAll();

$violations = [];
$total_fixes = 0;

foreach ($rows as $row) {
  $obj = json_decode($row->canvas_data);
  if (!$obj || !isset($obj->html)) continue;

  $html = $obj->html;
  $original = $html;
  $page_issues = [];

  // CHECK 1: Missing diacritics (word-boundary regex).
  foreach ($dictionary as $old => $new) {
    $pattern = '/\b' . preg_quote($old, '/') . '\b/u';
    $count = preg_match_all($pattern, $html);
    if ($count > 0) {
      $page_issues[] = sprintf('%dx "%s" → "%s"', $count, $old, $new);
      if ($fix_mode) {
        $html = preg_replace($pattern, $new, $html);
        $total_fixes += $count;
      }
    }
  }

  // CHECK 2: Missing ¿ in questions.
  if (preg_match_all('/(?<=>)([^<¿?]{2,})\?/', $html, $qmatches)) {
    foreach ($qmatches[0] as $i => $full) {
      $text = $qmatches[1][$i];
      if (!str_contains($text, '¿') && !str_contains($text, '=')) {
        $page_issues[] = sprintf('Missing ¿: "%s?"', mb_substr(trim($text), 0, 50));
        if ($fix_mode) {
          $html = str_replace($full, '¿' . $text . '?', $html);
          $total_fixes++;
        }
      }
    }
  }

  if (!empty($page_issues)) {
    $violations[] = [
      'id' => $row->id,
      'title' => $row->title ?: '(sin título)',
      'issues' => $page_issues,
    ];

    if ($fix_mode && $html !== $original) {
      $obj->html = $html;
      $new_canvas = json_encode($obj, JSON_UNESCAPED_UNICODE);
      if (json_decode($new_canvas)) {
        $database->update('page_content_field_data')
          ->fields(['canvas_data' => $new_canvas])
          ->condition('id', $row->id)
          ->condition('langcode', 'es')
          ->execute();
        $rev_id = $database->query(
          "SELECT revision_id FROM {page_content_field_data} WHERE id = :id AND langcode = 'es'",
          [':id' => $row->id]
        )->fetchField();
        if ($rev_id) {
          $database->update('page_content_field_revision')
            ->fields(['canvas_data' => $new_canvas])
            ->condition('id', $row->id)
            ->condition('revision_id', $rev_id)
            ->condition('langcode', 'es')
            ->execute();
        }
      }
    }
  }
}

// =========================================================================
// REPORT
// =========================================================================
if (empty($violations)) {
  echo "DIACRITICS-ES-001: PASS — No issues found in " . count($rows) . " pages.\n";
  return;
}

echo "DIACRITICS-ES-001: " . ($fix_mode ? "FIXED" : "FAIL") . " — "
  . count($violations) . " page(s) with Spanish orthography issues.\n\n";

foreach ($violations as $v) {
  echo "  Page {$v['id']} — {$v['title']}:\n";
  foreach ($v['issues'] as $issue) {
    echo "    - $issue\n";
  }
  echo "\n";
}

if ($fix_mode) {
  \Drupal::entityTypeManager()->getStorage('page_content')->resetCache();
  echo "Total fixes applied: $total_fixes\n";
  echo "Run `lando drush cr` to clear render cache.\n";
} else {
  echo "Run with --fix to auto-correct:\n";
  echo "  lando drush php:script scripts/validation/validate-spanish-diacritics.php -- --fix\n";
}

if (!empty($violations) && !$fix_mode) {
  throw new \RuntimeException('DIACRITICS-ES-001: FAIL');
}
