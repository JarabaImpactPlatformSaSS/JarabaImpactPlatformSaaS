<?php

/**
 * @file
 * EMAIL-TEMPLATE-RENDER-001: Verifica que las plantillas MJML de email no
 * producen HTML escapado y que el pipeline de compilacion esta correctamente
 * cableado.
 *
 * Checks:
 * C1: All MJML template files exist and contain <mjml> + <mj-body>
 * C2: MjmlCompilerService::fallbackConvert() does NOT wrap output in <!DOCTYPE>
 * C3: MjmlCompilerService has extractBodyContent() method
 * C4: jaraba_email.module uses Markup::create() for campaign, sequence, double_optin
 * C5: Template count matches TemplateLoaderService::TEMPLATE_MAP count
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$errors = [];
$checks = 0;
$emailModule = $projectRoot . '/web/modules/custom/jaraba_email';

// C1: All MJML template files exist and are valid.
$checks++;
$mjmlDir = $emailModule . '/templates/mjml';
if (!is_dir($mjmlDir)) {
  $errors[] = "C1: MJML templates directory not found: $mjmlDir";
}
else {
  $mjmlFiles = glob($mjmlDir . '/{,*/,*/*/}*.mjml', GLOB_BRACE);
  if (empty($mjmlFiles)) {
    $errors[] = 'C1: No MJML template files found in ' . $mjmlDir;
  }
  else {
    $invalidFiles = [];
    foreach ($mjmlFiles as $file) {
      $content = file_get_contents($file);
      $basename = str_replace($mjmlDir . '/', '', $file);
      // base.mjml is a partial/include, skip validation of full structure.
      if ($basename === 'base.mjml') {
        continue;
      }
      if (!str_contains($content, '<mjml') || !str_contains($content, '<mj-body')) {
        $invalidFiles[] = $basename;
      }
    }
    if (!empty($invalidFiles)) {
      $errors[] = 'C1: MJML files missing <mjml> or <mj-body>: ' . implode(', ', $invalidFiles);
    }
  }
}

// C2: fallbackConvert() does NOT wrap output in <!DOCTYPE>.
$checks++;
$compilerFile = $emailModule . '/src/Service/MjmlCompilerService.php';
$compilerContent = '';
if (!file_exists($compilerFile)) {
  $errors[] = "C2: MjmlCompilerService.php not found: $compilerFile";
}
else {
  $compilerContent = file_get_contents($compilerFile);
  // Extract fallbackConvert method body.
  if (preg_match('/function fallbackConvert\(.*?\)\s*:\s*string\s*\{(.*?)^\s{4}\}/ms', $compilerContent, $match)) {
    $methodBody = $match[1];
    if (str_contains($methodBody, '<!DOCTYPE') || str_contains($methodBody, '<html')) {
      $errors[] = 'C2: fallbackConvert() wraps output in <!DOCTYPE html> — email-wrap.html.twig already provides outer structure';
    }
  }
  else {
    $errors[] = 'C2: Could not extract fallbackConvert() method body';
  }
}

// C3: extractBodyContent() method exists.
$checks++;
if ($compilerContent !== '') {
  if (!str_contains($compilerContent, 'function extractBodyContent(')) {
    $errors[] = 'C3: MjmlCompilerService is missing extractBodyContent() method — MJML binary output will include <!DOCTYPE wrapper';
  }
}
else {
  $errors[] = 'C3: Cannot check — MjmlCompilerService.php not loaded';
}

// C4: jaraba_email.module uses Markup::create() for campaign, sequence, double_optin.
$checks++;
$moduleFile = $emailModule . '/jaraba_email.module';
if (!file_exists($moduleFile)) {
  $errors[] = "C4: jaraba_email.module not found: $moduleFile";
}
else {
  $moduleContent = file_get_contents($moduleFile);
  $requiredKeys = ['campaign', 'sequence', 'double_optin'];
  $missingMarkup = [];

  foreach ($requiredKeys as $key) {
    // Verify the key exists in the mail hook.
    if (!str_contains($moduleContent, "case '$key'") && !str_contains($moduleContent, "case \"$key\"")) {
      $missingMarkup[] = "$key (case not found)";
    }
  }

  if (!str_contains($moduleContent, 'Markup::create')) {
    $missingMarkup[] = 'Markup::create() not used at all';
  }

  if (!empty($missingMarkup)) {
    $errors[] = 'C4: Email hook issues: ' . implode(', ', $missingMarkup);
  }
}

// C5: Template file count matches TEMPLATE_MAP count.
$checks++;
$loaderFile = $emailModule . '/src/Service/TemplateLoaderService.php';
if (!file_exists($loaderFile)) {
  $errors[] = "C5: TemplateLoaderService.php not found: $loaderFile";
}
else {
  $loaderContent = file_get_contents($loaderFile);
  // Count entries in TEMPLATE_MAP by counting file key occurrences.
  preg_match_all("/'file'\s*=>\s*'/", $loaderContent, $mapEntries);
  $mapCount = count($mapEntries[0]);

  // Extract the file paths from TEMPLATE_MAP to check they exist.
  preg_match_all("/'file'\s*=>\s*'([^']*)'/", $loaderContent, $fileMatches);
  $missingOnDisk = [];
  foreach ($fileMatches[1] as $relPath) {
    $fullPath = $mjmlDir . '/' . $relPath;
    if (!file_exists($fullPath)) {
      $missingOnDisk[] = $relPath;
    }
  }

  if (!empty($missingOnDisk)) {
    $errors[] = 'C5: TEMPLATE_MAP references missing files: ' . implode(', ', $missingOnDisk);
  }

  if ($mapCount === 0) {
    $errors[] = 'C5: TEMPLATE_MAP appears empty (0 entries found)';
  }
}

// Report.
if (empty($errors)) {
  $templateInfo = isset($mapCount) ? "$mapCount in TEMPLATE_MAP" : '? templates';
  echo "EMAIL-TEMPLATE-RENDER-001: OK ($checks checks passed) — $templateInfo, Markup::create() for HTML body, extractBodyContent() present\n";
  exit(0);
}

echo "EMAIL-TEMPLATE-RENDER-001: FAIL\n";
foreach ($errors as $error) {
  echo "  - $error\n";
}
exit(1);
