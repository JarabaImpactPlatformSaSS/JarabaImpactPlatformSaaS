<?php

/**
 * @file
 * Validador de la landing Método Jaraba.
 *
 * 6 checks: controller, template, SCSS compilado, mega menu link,
 * Schema.org, MARKETING-TRUTH (constante INSERTION_RATE).
 *
 * Uso: php scripts/validation/validate-metodo-landing.php
 */

$basePath = dirname(__DIR__, 2);
$pass = 0;
$fail = 0;
$checks = 0;

function check(string $label, bool $result, string $detail = ''): void {
  global $pass, $fail, $checks;
  $checks++;
  if ($result) {
    $pass++;
    echo "  \033[32mPASS\033[0m $label\n";
  }
  else {
    $fail++;
    echo "  \033[31mFAIL\033[0m $label" . ($detail ? " — $detail" : '') . "\n";
  }
}

echo "\n\033[1m[METODO-LANDING-001]\033[0m Método Jaraba landing integrity\n\n";

// 1. Controller exists with 8 build methods.
$controller = "$basePath/web/modules/custom/jaraba_page_builder/src/Controller/MetodoLandingController.php";
$content = file_exists($controller) ? file_get_contents($controller) : '';
check('MetodoLandingController exists with 8 sections',
  str_contains($content, 'buildHero') &&
  str_contains($content, 'buildProblema') &&
  str_contains($content, 'buildSolucion') &&
  str_contains($content, 'buildCapas') &&
  str_contains($content, 'buildCompetencias') &&
  str_contains($content, 'buildCid') &&
  str_contains($content, 'buildCaminos') &&
  str_contains($content, 'buildEvidencia'));

// 2. Template exists.
$template = "$basePath/web/modules/custom/jaraba_page_builder/templates/metodo-landing.html.twig";
check('metodo-landing.html.twig template exists', file_exists($template));

// 3. SCSS compiled.
$scss = "$basePath/web/themes/custom/ecosistema_jaraba_theme/scss/routes/metodo-landing.scss";
$css = "$basePath/web/themes/custom/ecosistema_jaraba_theme/css/routes/metodo-landing.css";
check('SCSS compiled (CSS exists)', file_exists($css));

// 4. Mega menu link to /metodologia.
$theme = "$basePath/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme";
$themeContent = file_exists($theme) ? file_get_contents($theme) : '';
check('Mega menu link /metodologia', str_contains($themeContent, '/metodologia'));

// 5. Schema.org Course in template.
$tplContent = file_exists($template) ? file_get_contents($template) : '';
check('Schema.org Course JSON-LD', str_contains($tplContent, 'application/ld+json') && str_contains($tplContent, 'Course'));

// 6. MARKETING-TRUTH-001: INSERTION_RATE constant.
check('MARKETING-TRUTH-001: INSERTION_RATE constant',
  str_contains($content, 'INSERTION_RATE'));

echo "\n============================================================\n";
echo "  \033[1mResults:\033[0m $pass passed, $fail failed (of $checks)\n";
echo "============================================================\n";

exit($fail > 0 ? 1 : 0);
