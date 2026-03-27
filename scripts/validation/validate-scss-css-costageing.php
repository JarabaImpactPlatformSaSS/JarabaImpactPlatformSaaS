<?php

/**
 * @file
 * SCSS-CSS-COSTAGE-001: Ensures SCSS and compiled CSS are committed together.
 *
 * When SCSS files are staged for commit, verifies that the corresponding
 * compiled CSS is ALSO staged. Prevents the root cause of CI failures where
 * SCSS is committed but CSS is stale (not recompiled).
 *
 * This validator runs as a pre-commit hook via lint-staged.
 *
 * Usage: php scripts/validation/validate-scss-css-costageing.php
 * Exit:  0 = OK, 1 = CSS missing from staging
 *
 * RULE: SCSS-CSS-COSTAGE-001
 * When you modify SCSS, you MUST run `npm run build` and stage the CSS output.
 * Without this, CI will fail on SCSS-COMPILE-VERIFY-001.
 */

declare(strict_types=1);

$themeDir = 'web/themes/custom/ecosistema_jaraba_theme';

// Get staged SCSS files.
exec('git diff --cached --name-only --diff-filter=ACMR -- "*.scss"', $stagedScss);

if (empty($stagedScss)) {
  exit(0); // No SCSS staged, nothing to check.
}

// Filter to theme SCSS only.
$themeScss = array_filter($stagedScss, fn($f) => str_starts_with($f, $themeDir));
if (empty($themeScss)) {
  exit(0);
}

// Get staged CSS files.
exec('git diff --cached --name-only -- "*.css"', $stagedCss);

// Map SCSS entry points to expected CSS outputs.
$scssDir = "$themeDir/scss";
$cssDir = "$themeDir/css";

$entryPoints = [
  "$scssDir/main.scss" => "$cssDir/ecosistema-jaraba-theme.css",
  "$scssDir/admin-settings.scss" => "$cssDir/admin-settings.css",
];

// Route, bundle, component SCSS → CSS.
foreach (['routes', 'bundles', 'components'] as $subdir) {
  foreach (glob("$scssDir/$subdir/*.scss") ?: [] as $scss) {
    $basename = basename($scss, '.scss');
    // Skip partials (_name.scss) — they compile via their entry point.
    if (str_starts_with($basename, '_')) {
      $basename = substr($basename, 1);
    }
    $entryPoints[$scss] = "$cssDir/$subdir/$basename.css";
  }
}

// Check: is ANY staged SCSS file an entry point or partial of a mapping?
$affectedCss = [];
foreach ($themeScss as $staged) {
  // Direct entry point match.
  if (isset($entryPoints[$staged])) {
    $affectedCss[$entryPoints[$staged]] = $staged;
    continue;
  }

  // Partial: _name.scss — affects main.scss and any route/bundle that @use it.
  // Conservative: if ANY partial changes, main CSS should be re-staged.
  if (str_contains(basename($staged), '_')) {
    $affectedCss["$cssDir/ecosistema-jaraba-theme.css"] = $staged;
  }

  // Route partials: check if a route SCSS was also staged.
  if (str_contains($staged, '/routes/')) {
    $routeBase = basename($staged, '.scss');
    if (str_starts_with($routeBase, '_')) {
      $routeBase = substr($routeBase, 1);
    }
    $expectedCss = "$cssDir/routes/$routeBase.css";
    if (file_exists($expectedCss)) {
      $affectedCss[$expectedCss] = $staged;
    }
  }
}

if (empty($affectedCss)) {
  exit(0); // No mappable entry points affected.
}

// Verify affected CSS files are staged.
$errors = [];
foreach ($affectedCss as $css => $scss) {
  if (!in_array($css, $stagedCss, true)) {
    $errors[] = "SCSS staged ($scss) but compiled CSS NOT staged: $css";
  }
}

if (!empty($errors)) {
  fwrite(STDERR, "SCSS-CSS-COSTAGE-001: SCSS committed without compiled CSS!\n\n");
  foreach ($errors as $e) {
    fwrite(STDERR, "  [ERROR] $e\n");
  }
  fwrite(STDERR, "\nFix: Run 'npm run build' in $themeDir and 'git add' the CSS output.\n");
  fwrite(STDERR, "This prevents CI SCSS-COMPILE-VERIFY-001 failures.\n");
  exit(1);
}

echo "SCSS-CSS-COSTAGE-001: All SCSS changes have compiled CSS staged.\n";
exit(0);
