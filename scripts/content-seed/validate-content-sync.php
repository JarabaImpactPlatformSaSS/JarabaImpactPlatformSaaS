<?php

/**
 * @file
 * CONTENT-SEED-PIPELINE-001: Validación post-importación de contenido de metasitios.
 *
 * Ejecuta 15 checks para verificar que el contenido de los 3 metasitios
 * existe y está correctamente vinculado.
 *
 * Uso:
 *   lando drush php:script scripts/content-seed/validate-content-sync.php
 *   drush php:script scripts/content-seed/validate-content-sync.php
 *
 * Exit: 0 = todos los checks pasan, 1 = errores encontrados
 */

declare(strict_types=1);

const METASITE_DOMAINS = [
  'pepejaraba.com',
  'jarabaimpact.com',
  'plataformadeecosistemas.es',
];

echo "\n==========================================\n";
echo "Content Sync Validation — 15 Checks\n";
echo "==========================================\n\n";

$entityTypeManager = \Drupal::entityTypeManager();
$tenantStorage = $entityTypeManager->getStorage('tenant');
$pageStorage = $entityTypeManager->getStorage('page_content');
$sptStorage = $entityTypeManager->getStorage('site_page_tree');
$scStorage = $entityTypeManager->getStorage('site_config');

$errors = [];
$warnings = [];
$passed = 0;
$total = 0;

/**
 * Helper para registrar resultado de un check.
 */
$check = function (string $id, string $description, bool $ok, bool $isWarning = FALSE) use (&$errors, &$warnings, &$passed, &$total): void {
  $total++;
  if ($ok) {
    $passed++;
    echo "  ✓ {$id}: {$description}\n";
  }
  elseif ($isWarning) {
    $warnings[] = "{$id}: {$description}";
    $passed++; // Warnings no bloquean.
    echo "  ⚠ {$id}: {$description}\n";
  }
  else {
    $errors[] = "{$id}: {$description}";
    echo "  ✗ {$id}: {$description}\n";
  }
};

foreach (METASITE_DOMAINS as $domain) {
  echo "─── {$domain} ───\n";

  // Resolver tenant.
  $tenants = $tenantStorage->loadByProperties(['domain' => $domain]);
  if (empty($tenants)) {
    $check("TENANT", "Tenant existe para {$domain}", FALSE);
    echo "\n";
    continue;
  }
  $tenant = reset($tenants);
  $groupId = (int) $tenant->get('group_id')->target_id;

  // =========================================================================
  // CHECK 1: PAGES_EXIST — PageContent por metasitio > 0.
  // =========================================================================
  $pageCount = (int) $pageStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('tenant_id', $groupId)
    ->count()
    ->execute();

  $check("PAGES_EXIST", "PageContent: {$pageCount} páginas (tenant {$groupId})", $pageCount > 0);

  // =========================================================================
  // CHECK 2: HOMEPAGE_LINKED — SiteConfig.homepage_id NOT NULL.
  // =========================================================================
  $scIds = $scStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('tenant_id', $groupId)
    ->execute();

  $homepageLinked = FALSE;
  $homepageId = NULL;
  $sc = NULL;
  if (!empty($scIds)) {
    $sc = $scStorage->load(reset($scIds));
    $homepageId = $sc?->get('homepage_id')->target_id;
    $homepageLinked = !empty($homepageId);
  }
  $check("HOMEPAGE_LINKED", "SiteConfig.homepage_id = {$homepageId}", $homepageLinked);

  // =========================================================================
  // CHECK 3: HOMEPAGE_RESOLVES — PageContent con ese ID existe y está publicado.
  // =========================================================================
  $homepageResolves = FALSE;
  if ($homepageId) {
    $homepage = $pageStorage->load($homepageId);
    $homepageResolves = $homepage && (bool) $homepage->get('status')->value;
  }
  $check("HOMEPAGE_RESOLVES", "Homepage ID:{$homepageId} existe y publicada", $homepageResolves);

  // =========================================================================
  // CHECK 4: LEGAL_PAGES — privacy + terms + cookies referenciados.
  // =========================================================================
  $legalOk = FALSE;
  if ($sc) {
    $privacyId = $sc->get('privacy_policy_id')->target_id;
    $termsId = $sc->get('terms_conditions_id')->target_id;
    $cookiesId = $sc->get('cookies_policy_id')->target_id;
    $legalOk = !empty($privacyId) && !empty($termsId) && !empty($cookiesId);
  }
  $check("LEGAL_PAGES", "Páginas legales vinculadas (privacy={$privacyId}, terms={$termsId}, cookies={$cookiesId})", $legalOk);

  // =========================================================================
  // CHECK 5: SPT_INTEGRITY — SitePageTree.page_id → PageContent existente.
  // =========================================================================
  $sptIds = $sptStorage->getQuery()
    ->accessCheck(FALSE)
    ->condition('tenant_id', $groupId)
    ->execute();

  $orphanSpts = 0;
  foreach ($sptStorage->loadMultiple($sptIds) as $spt) {
    $pageId = $spt->get('page_id')->target_id;
    if ($pageId && !$pageStorage->load($pageId)) {
      $orphanSpts++;
    }
  }
  $check("SPT_INTEGRITY", "SitePageTree sin referencias huérfanas ({$orphanSpts} huérfanas)", $orphanSpts === 0);

  // =========================================================================
  // CHECK 6: RENDERED_HTML — % de páginas con rendered_html > 0.
  // =========================================================================
  $pages = $pageStorage->loadMultiple(
    $pageStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('tenant_id', $groupId)
      ->execute()
  );
  $withRender = 0;
  foreach ($pages as $page) {
    if (strlen($page->get('rendered_html')->value ?? '') > 50) {
      $withRender++;
    }
  }
  $pct = $pageCount > 0 ? round(($withRender / $pageCount) * 100) : 0;
  $check("RENDERED_HTML", "rendered_html: {$withRender}/{$pageCount} ({$pct}%)", $pct >= 80, $pct < 80 && $pct > 0);

  // =========================================================================
  // CHECK 7: PATH_ALIAS_UNIQUE — Sin duplicados por tenant.
  // =========================================================================
  $aliases = [];
  $duplicates = 0;
  foreach ($pages as $page) {
    $alias = $page->get('path_alias')->value ?? '';
    if (!empty($alias)) {
      if (isset($aliases[$alias])) {
        $duplicates++;
      }
      $aliases[$alias] = TRUE;
    }
  }
  $check("PATH_ALIAS_UNIQUE", "Path aliases únicos ({$duplicates} duplicados)", $duplicates === 0);

  // =========================================================================
  // CHECK 8: TENANT_ISOLATION — Todas las entities del tenant correcto.
  // =========================================================================
  $wrongTenant = 0;
  foreach ($pages as $page) {
    $pageTenantId = (int) ($page->get('tenant_id')->target_id ?? 0);
    if ($pageTenantId !== $groupId) {
      $wrongTenant++;
    }
  }
  $check("TENANT_ISOLATION", "Tenant isolation ({$wrongTenant} violaciones)", $wrongTenant === 0);

  // =========================================================================
  // CHECK 9: CANVAS_VALID — canvas_data es JSON válido.
  // =========================================================================
  $invalidCanvas = 0;
  foreach ($pages as $page) {
    $canvas = $page->get('canvas_data')->value ?? '';
    if (!empty($canvas) && strlen($canvas) > 10) {
      $decoded = json_decode($canvas, TRUE);
      if ($decoded === NULL && json_last_error() !== JSON_ERROR_NONE) {
        $invalidCanvas++;
      }
    }
  }
  $check("CANVAS_VALID", "canvas_data JSON válido ({$invalidCanvas} inválidos)", $invalidCanvas === 0);

  // =========================================================================
  // CHECK 10: NAV_COMPLETENESS — Al menos 3 items en navegación.
  // =========================================================================
  $navCount = 0;
  foreach ($sptStorage->loadMultiple($sptIds) as $spt) {
    if ((bool) $spt->get('show_in_navigation')->value) {
      $navCount++;
    }
  }
  $check("NAV_COMPLETENESS", "Navegación: {$navCount} items visibles", $navCount >= 3, $navCount < 3);

  // =========================================================================
  // CHECK 11: FOOTER_COMPLETENESS — Al menos 2 items en footer.
  // =========================================================================
  $footerCount = 0;
  foreach ($sptStorage->loadMultiple($sptIds) as $spt) {
    if ((bool) $spt->get('show_in_footer')->value) {
      $footerCount++;
    }
  }
  $check("FOOTER_COMPLETENESS", "Footer: {$footerCount} items visibles", $footerCount >= 2, $footerCount < 2);

  // =========================================================================
  // CHECK 12: META_TITLE_SUFFIX — SiteConfig.meta_title_suffix NOT NULL.
  // =========================================================================
  $suffix = $sc?->get('meta_title_suffix')->value ?? '';
  $check("META_TITLE_SUFFIX", "meta_title_suffix = '{$suffix}'", !empty($suffix), empty($suffix));

  // =========================================================================
  // CHECK 13: HEADER_CTA — header_cta_text y header_cta_url configurados.
  // =========================================================================
  $ctaText = $sc?->get('header_cta_text')->value ?? '';
  $ctaUrl = $sc?->get('header_cta_url')->value ?? '';
  $check("HEADER_CTA", "Header CTA: '{$ctaText}' → '{$ctaUrl}'", !empty($ctaText) && !empty($ctaUrl), empty($ctaText));

  // =========================================================================
  // CHECK 14: CONTACT_INFO — contact_email y contact_phone configurados.
  // =========================================================================
  $email = $sc?->get('contact_email')->value ?? '';
  $phone = $sc?->get('contact_phone')->value ?? '';
  $check("CONTACT_INFO", "Contacto: {$email} / {$phone}", !empty($email) && !empty($phone), empty($email));

  // =========================================================================
  // CHECK 15: SC_EXISTS — SiteConfig existe para este tenant.
  // =========================================================================
  $check("SC_EXISTS", "SiteConfig existe", $sc !== NULL);

  echo "\n";
}

// ============================================================================
// Resumen.
// ============================================================================
echo "==========================================\n";

if (!empty($warnings)) {
  echo "Warnings:\n";
  foreach ($warnings as $w) {
    echo "  ⚠ {$w}\n";
  }
  echo "\n";
}

if (!empty($errors)) {
  echo "Errores:\n";
  foreach ($errors as $e) {
    echo "  ✗ {$e}\n";
  }
  echo "\n";
}

echo "Resultado: {$passed}/{$total} checks OK\n";

if (!empty($errors)) {
  echo "❌ FAILED (" . count($errors) . " errores)\n";
  echo "==========================================\n\n";
  exit(1);
}

echo "✅ ALL CHECKS PASSED\n";
echo "==========================================\n\n";
exit(0);
