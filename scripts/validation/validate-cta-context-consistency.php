<?php

/**
 * @file
 * CTA-CONTEXT-CONSISTENCY-001: Verifica que templates PDE no contienen CTAs
 * del SaaS que no tienen sentido en contexto corporativo.
 *
 * Previene mensajes como "Empieza gratis" o URLs como "/user/register"
 * en el meta-sitio corporativo PDE.
 *
 * Checks:
 * 1. PDE defaults en getMetasiteDefaults() no apuntan a /user/register
 * 2. Hero PDE no muestra "14 días gratis" (tiene badge de campaña activa)
 * 3. Quiz link se oculta en contexto PDE
 * 4. CTA final PDE recibe variables corporativas (no defaults SaaS)
 * 5. Header CTA PDE está configurado (enable_header_cta = TRUE)
 *
 * Uso: php scripts/validation/validate-cta-context-consistency.php
 */

$errors = 0;
$warnings = 0;
$pass = 0;

$theme_file = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';
$hero_file = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/templates/partials/_hero.html.twig';
$cta_final_template = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/templates/page--front.html.twig';
$pb_template = __DIR__ . '/../../web/themes/custom/ecosistema_jaraba_theme/templates/page--page-builder.html.twig';

if (!file_exists($theme_file)) {
  echo "SKIP: Theme file not found.\n";
  exit(0);
}

$theme_content = file_get_contents($theme_file);
$hero_content = file_exists($hero_file) ? file_get_contents($hero_file) : '';
$front_content = file_exists($cta_final_template) ? file_get_contents($cta_final_template) : '';
$pb_content = file_exists($pb_template) ? file_get_contents($pb_template) : '';

// Check 1: PDE defaults must NOT point to /user/register.
// Extract the 'pde' block from getMetasiteDefaults().
if (preg_match("/'pde'\s*=>\s*\[(.*?)\],\s*\n\s*'generic'/s", $theme_content, $pde_block)) {
  $pde_defaults = $pde_block[1];

  if (strpos($pde_defaults, '/user/register') !== false) {
    echo "FAIL: PDE defaults contain '/user/register' — must use corporate URL.\n";
    $errors++;
  } else {
    echo "PASS: PDE defaults don't point to /user/register.\n";
    $pass++;
  }

  if (preg_match("/hero_cta_primary_text.*?'([^']+)'/", $pde_defaults, $cta_text)) {
    $text = $cta_text[1];
    $saas_texts = ['Empezar gratis', 'Empieza gratis', 'Crear cuenta', 'Start free'];
    $is_saas = false;
    foreach ($saas_texts as $st) {
      if (stripos($text, $st) !== false) {
        $is_saas = true;
        break;
      }
    }
    if ($is_saas) {
      echo "FAIL: PDE hero CTA text is SaaS-oriented ('{$text}').\n";
      $errors++;
    } else {
      echo "PASS: PDE hero CTA text is corporate ('{$text}').\n";
      $pass++;
    }
  }
} else {
  echo "WARN: Could not extract PDE defaults block.\n";
  $warnings++;
}

// Check 2: Hero PDE has campaign badge (not "14 días gratis").
if (!empty($hero_content)) {
  if (strpos($hero_content, 'group_id|default(0) == 7') !== false ||
      strpos($hero_content, "group_id|default(0) == 7") !== false) {
    echo "PASS: Hero has PDE-specific badge (group_id 7 detection).\n";
    $pass++;
  } else {
    echo "FAIL: Hero lacks PDE-specific badge — shows '14 días gratis' for PDE.\n";
    $errors++;
  }
}

// Check 3: Quiz link is hidden in PDE context.
if (!empty($hero_content)) {
  // Look for conditional that hides quiz for PDE.
  if (preg_match('/not.*meta_site.*group_id.*7.*quiz|quiz.*not.*group_id.*7/s', $hero_content)) {
    echo "PASS: Quiz link hidden in PDE context.\n";
    $pass++;
  } elseif (strpos($hero_content, 'group_id|default(0) == 7') !== false &&
            strpos($hero_content, 'quiz') !== false) {
    echo "PASS: Quiz link conditionally handled for PDE.\n";
    $pass++;
  } else {
    echo "WARN: Quiz link may show in PDE context (verify manually).\n";
    $warnings++;
  }
}

// Check 4: CTA final PDE receives corporate variables (not SaaS defaults).
// In page--front.html.twig, the PDE section should pass cta_primary_text.
if (!empty($front_content)) {
  // Find the PDE elseif block with CTA final.
  if (preg_match('/is_ped.*?cta-banner-final.*?only\s*%\}/s', $front_content, $ped_cta)) {
    $cta_block = $ped_cta[0];
    if (strpos($cta_block, 'cta_primary_text') !== false) {
      echo "PASS: page--front.html.twig passes corporate CTA text to PDE final banner.\n";
      $pass++;
    } else {
      echo "FAIL: page--front.html.twig PDE section uses default CTA (Crear cuenta gratuita).\n";
      $errors++;
    }
  } else {
    echo "WARN: Could not find PDE CTA final section in page--front.html.twig.\n";
    $warnings++;
  }
}

// Check 5: page--page-builder CTA final also has corporate variables.
if (!empty($pb_content)) {
  if (preg_match('/ped_homepage.*?cta-banner-final.*?only\s*%\}/s', $pb_content, $pb_cta)) {
    $pb_block = $pb_cta[0];
    if (strpos($pb_block, 'cta_primary_text') !== false) {
      echo "PASS: page--page-builder.html.twig passes corporate CTA to PDE final banner.\n";
      $pass++;
    } else {
      echo "FAIL: page--page-builder.html.twig PDE uses default CTA.\n";
      $errors++;
    }
  }
}

// Check 6: Header CTA is explicitly enabled for PDE in preprocess.
if (strpos($theme_content, "isPedMegaMenu") !== false &&
    strpos($theme_content, "'enable_header_cta'] = TRUE") !== false) {
  echo "PASS: Header CTA explicitly enabled for PDE.\n";
  $pass++;
} else {
  echo "FAIL: Header CTA not explicitly enabled for PDE in preprocess.\n";
  $errors++;
}

// Check 7: PDE footer uses corporate columns (not SaaS defaults).
if (strpos($theme_content, "footer_nav_col1_links") !== false &&
    strpos($theme_content, '/sobre-nosotros') !== false) {
  echo "PASS: PDE footer uses corporate navigation columns.\n";
  $pass++;
} else {
  echo "FAIL: PDE footer not configured with corporate URLs.\n";
  $errors++;
}

echo "\n=== CTA-CONTEXT-CONSISTENCY-001: {$pass} PASS, {$warnings} WARN, {$errors} FAIL ===\n";
exit($errors > 0 ? 1 : 0);
