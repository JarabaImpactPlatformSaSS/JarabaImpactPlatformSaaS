<?php

/**
 * @file
 * COPILOT-INTENT-ACCURACY-001: Valida patrones de deteccion de intencion.
 *
 * Test de regresion: 20+ frases con resultado esperado.
 * Verifica que los regex de CopilotLeadCaptureService detectan correctamente.
 *
 * Uso: php scripts/validation/validate-copilot-intent-patterns.php
 */

echo "=== COPILOT-INTENT-ACCURACY-001: Patrones de detección de intención ===\n\n";

// Duplicamos los patterns aquí para testear sin bootstrap Drupal.
$intentPatterns = [
  'andalucia_ei' => '/\b(inserci[oó]n\s+laboral|piil|andaluc[ií]a\s*\+?ei|fse\+?|incentivo\s+laboral|programa\s+gratuito|junta\s+de\s+andaluc|colectivos?\s+vulnerable)/iu',
  'formacion' => '/\b(curso|formaci[oó]n|certificad|lecci[oó]n|instructor|capacitaci[oó]n|incentivo.{0,20}(curso|formaci)|aprender\s+(algo|un))/iu',
  'empleabilidad' => '/\b(empleo|trabajo|curr[ií]cul|oferta\s+de\s+(empleo|trabajo)|busco\s+trabajo|orientaci[oó]n\s+profesional|riasec|candidate)/iu',
  'emprendimiento' => '/\b(negocio|empresa|emprender|startup|idea\s+de\s+negocio|canvas|validar\s+(mi\s+)?idea|mentor[ií]a)/iu',
  'comercioconecta' => '/\b(vender|tienda|comercio|ecommerce|marketplace|producto.{0,10}online|catalogo\s+digital)/iu',
  'agroconecta' => '/\b(productor|cosecha|agr[oí]col|campo|finca|bodega|trazabilidad|ecol[oó]gico|denominaci[oó]n\s+de\s+origen)/iu',
  'jarabalex' => '/\b(ley|legal|abogad|normativa|contrato|jurisprudencia|legislaci[oó]n|bufete)/iu',
  'serviciosconecta' => '/\b(servicio\s+profesional|freelance|consultor[ií]a|reserva\s+online|agenda\s+digital)/iu',
  'purchase_generic' => '/\b(precio|plan|contratar|suscripci[oó]n|pagar|coste|tarifa|presupuesto|cu[aá]nto\s+cuesta)/iu',
  'trial' => '/\b(probar|gratis|demo|free|sin\s+compromiso|prueba\s+gratuita)/iu',
];

// Test cases: [message, expected_vertical (first match wins)]
$testCases = [
  // Andalucía EI (prioridad alta).
  ['Busco inserción laboral en Andalucía', 'andalucia_ei'],
  ['¿Qué es el programa PIIL?', 'andalucia_ei'],
  ['Información sobre programa gratuito de empleo', 'andalucia_ei'],
  ['Colectivos vulnerables empleo', 'andalucia_ei'],

  // Formación.
  ['busco curso con incentivo', 'formacion'],
  ['Quiero hacer un curso de certificado', 'formacion'],
  ['¿Tenéis formación online?', 'formacion'],
  ['Necesito una capacitación técnica', 'formacion'],

  // Empleabilidad.
  ['Busco trabajo de diseñador', 'empleabilidad'],
  ['¿Hay ofertas de empleo?', 'empleabilidad'],
  ['Quiero mejorar mi currículum', 'empleabilidad'],
  ['Orientación profesional gratuita', 'empleabilidad'],

  // Emprendimiento.
  ['Tengo una idea de negocio', 'emprendimiento'],
  ['Quiero validar mi idea de startup', 'emprendimiento'],
  ['¿Cómo hacer un canvas de modelo de negocio?', 'emprendimiento'],

  // ComercioConecta.
  ['Quiero vender mis productos online', 'comercioconecta'],
  ['Necesito una tienda ecommerce', 'comercioconecta'],

  // AgroConecta.
  ['Soy productor ecológico', 'agroconecta'],
  ['Trazabilidad de productos agrícolas', 'agroconecta'],

  // JarabaLex.
  ['Necesito un abogado laboral', 'jarabalex'],
  ['Consulta sobre normativa vigente', 'jarabalex'],

  // ServiciosConecta.
  ['Soy freelance y quiero ofrecer mis servicios', 'serviciosconecta'],

  // Purchase generic.
  ['¿Cuánto cuesta el plan profesional?', 'purchase_generic'],
  ['Quiero contratar la suscripción', 'purchase_generic'],

  // Trial.
  ['¿Puedo probar gratis?', 'trial'],
  ['Me gustaría ver una demo', 'trial'],

  // No intent (should not match any).
  ['Hola, buenas tardes', 'none'],
  ['Gracias por la información', 'none'],
  ['¿A qué hora abrís?', 'none'],
];

$passed = 0;
$failed = 0;
$errors = [];

foreach ($testCases as $index => [$message, $expected]) {
  $detected = 'none';

  foreach ($intentPatterns as $vertical => $pattern) {
    if (preg_match($pattern, $message)) {
      $detected = $vertical;
      break;
    }
  }

  $ok = $detected === $expected;
  $status = $ok ? 'PASS' : 'FAIL';
  $icon = $ok ? '✓' : '✗';

  if ($ok) {
    $passed++;
  } else {
    $failed++;
    $errors[] = "  #{$index}: \"{$message}\" → esperado={$expected}, detectado={$detected}";
  }

  echo "  [{$status}] \"{$message}\" → {$detected}" . ($ok ? '' : " (esperado: {$expected})") . "\n";
}

echo "\n=== Resumen ===\n";
echo "Total: " . count($testCases) . " | Pasados: {$passed} | Fallidos: {$failed}\n";

if ($failed > 0) {
  echo "\nFallos:\n";
  foreach ($errors as $err) {
    echo "{$err}\n";
  }
}

$exitCode = $failed > 0 ? 1 : 0;
echo "\n" . ($exitCode === 0 ? '[OK]' : '[FAIL]') . " COPILOT-INTENT-ACCURACY-001: Validación completada.\n";
exit($exitCode);
