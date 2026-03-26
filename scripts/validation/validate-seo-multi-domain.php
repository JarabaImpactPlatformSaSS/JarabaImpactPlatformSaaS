<?php

/**
 * @file
 * SEO-MULTIDOMAIN-001: Valida integridad SEO multi-dominio.
 *
 * 17 checks:
 * 1. hreflang NO contiene /node
 * 2. Title homepage diferente por homepage_variant (theme settings)
 * 3. Canonical tag logica presente en page_attachments_alter
 * 4. og:url es absoluto (theme code)
 * 5. og:locale presente
 * 6. hreflang filtrado por seo_active_languages
 * 7. robots.txt estatico NO existe (debe ser dinamico)
 * 8. sitemap-static tiene >= 10 URLs
 * 9. SitemapController::robots() usa $request->getSchemeAndHttpHost()
 * 10. Schema.yml tiene campos seo_*
 * 11. SeoRedirectSubscriber existe y registrado
 * 12. SeoRedirectSubscriber redirige /node a homepage limpia
 * 13. Canonical cross-language para idiomas inactivos
 * 14. Canonical front page limpia (sin /node)
 * 15. Sitemap staticPages sin .html en URLs de verticales
 * 16. robots.txt tiene disallows con prefijo de idioma
 * 17. HreflangService filtra por seo_active_languages
 */

$errors = [];
$warnings = [];
$passed = 0;
$total = 17;

$base = dirname(__DIR__, 2);
$theme_file = $base . '/web/themes/custom/ecosistema_jaraba_theme/ecosistema_jaraba_theme.theme';
$schema_file = $base . '/web/themes/custom/ecosistema_jaraba_theme/config/schema/ecosistema_jaraba_theme.schema.yml';
$sitemap_ctrl = $base . '/web/modules/custom/jaraba_page_builder/src/Controller/SitemapController.php';
$robots_static = $base . '/web/robots.txt';
$seo_redirect_file = $base . '/web/modules/custom/jaraba_page_builder/src/EventSubscriber/SeoRedirectSubscriber.php';
$hreflang_file = $base . '/web/modules/custom/jaraba_page_builder/src/Service/HreflangService.php';
$services_file = $base . '/web/modules/custom/jaraba_page_builder/jaraba_page_builder.services.yml';

$theme_code = file_get_contents($theme_file);
$schema_code = file_get_contents($schema_file);
$sitemap_code = file_get_contents($sitemap_ctrl);
$seo_redirect_code = file_exists($seo_redirect_file) ? file_get_contents($seo_redirect_file) : '';
$hreflang_code = file_exists($hreflang_file) ? file_get_contents($hreflang_file) : '';
$services_code = file_exists($services_file) ? file_get_contents($services_file) : '';

echo "\n=== SEO-MULTIDOMAIN-001: Validacion SEO Multi-Dominio ===\n\n";

// CHECK 1: hreflang NO contiene /node.
if (preg_match('/SEO-HREFLANG-FRONT-001/', $theme_code) && preg_match('/is_front_hl/', $theme_code)) {
    $passed++;
    echo "  ✓ CHECK 1: hreflang frontend fix (SEO-HREFLANG-FRONT-001) implementado\n";
} else {
    $errors[] = 'CHECK 1: SEO-HREFLANG-FRONT-001 no encontrado — hreflang puede generar /node';
}

// CHECK 2: Title diferenciado por metasitio.
if (preg_match('/seo_.*_title/', $theme_code) && preg_match('/homepage_variant/', $theme_code)) {
    $passed++;
    echo "  ✓ CHECK 2: Title diferenciado por homepage_variant\n";
} else {
    $errors[] = 'CHECK 2: Title no diferenciado por metasitio';
}

// CHECK 3: Canonical tag en page_attachments_alter.
if (preg_match('/seo_canonical/', $theme_code) && preg_match("/rel.*canonical/", $theme_code)) {
    $passed++;
    echo "  ✓ CHECK 3: Canonical tag presente en page_attachments_alter\n";
} else {
    $errors[] = 'CHECK 3: Canonical tag no implementado';
}

// CHECK 4: og:url absoluto.
if (preg_match('/seo_og_url/', $theme_code) && preg_match('/getSchemeAndHttpHost/', $theme_code)) {
    $passed++;
    echo "  ✓ CHECK 4: og:url absoluto (usa getSchemeAndHttpHost)\n";
} else {
    $errors[] = 'CHECK 4: og:url no es absoluto';
}

// CHECK 5: og:locale presente.
if (preg_match('/seo_og_locale/', $theme_code)) {
    $passed++;
    echo "  ✓ CHECK 5: og:locale inyectado\n";
} else {
    $errors[] = 'CHECK 5: og:locale no encontrado';
}

// CHECK 6: hreflang filtrado por seo_active_languages.
if (preg_match('/seo_active_languages/', $theme_code) && preg_match('/seo_active_langs/', $theme_code)) {
    $passed++;
    echo "  ✓ CHECK 6: hreflang filtrado por seo_active_languages\n";
} else {
    $errors[] = 'CHECK 6: hreflang no filtrado por idiomas activos';
}

// CHECK 7: robots.txt estatico NO existe.
if (!file_exists($robots_static)) {
    $passed++;
    echo "  ✓ CHECK 7: robots.txt estatico eliminado (dinamico activo)\n";
} else {
    $errors[] = 'CHECK 7: web/robots.txt estatico existe — el controller dinamico no se ejecuta';
}

// CHECK 8: sitemap-static tiene >= 10 URLs.
$sitemap_urls = preg_match_all("/\['path' => /", $sitemap_code);
if ($sitemap_urls >= 10) {
    $passed++;
    echo "  ✓ CHECK 8: sitemap-static tiene {$sitemap_urls} URLs (>= 10)\n";
} else {
    $errors[] = "CHECK 8: sitemap-static solo tiene {$sitemap_urls} URLs (necesita >= 10)";
}

// CHECK 9: robots() usa dominio dinamico.
if (preg_match('/getSchemeAndHttpHost/', $sitemap_code) && preg_match('/Sitemap:.*baseUrl/', $sitemap_code)) {
    $passed++;
    echo "  ✓ CHECK 9: robots.txt Sitemap usa dominio dinamico\n";
} else {
    $errors[] = 'CHECK 9: robots.txt Sitemap no usa dominio dinamico';
}

// CHECK 10: Schema.yml tiene campos SEO.
if (preg_match('/seo_active_languages/', $schema_code) && preg_match('/seo_generic_title/', $schema_code)) {
    $passed++;
    echo "  ✓ CHECK 10: Schema.yml tiene campos SEO multi-dominio\n";
} else {
    $errors[] = 'CHECK 10: Schema.yml falta campos SEO';
}

// CHECK 11: SeoRedirectSubscriber existe y registrado.
if (file_exists($seo_redirect_file) && preg_match('/seo_redirect/', $services_code)) {
    $passed++;
    echo "  ✓ CHECK 11: SeoRedirectSubscriber existe y registrado en services.yml\n";
} else {
    $errors[] = 'CHECK 11: SeoRedirectSubscriber no existe o no registrado';
}

// CHECK 12: SeoRedirectSubscriber redirige /node a homepage limpia.
if (preg_match('/system\.site/', $seo_redirect_code) && preg_match('/RedirectResponse/', $seo_redirect_code) && preg_match('/page\.front/', $seo_redirect_code)) {
    $passed++;
    echo "  ✓ CHECK 12: SeoRedirectSubscriber redirige front page a homepage limpia\n";
} else {
    $errors[] = 'CHECK 12: SeoRedirectSubscriber no detecta front page path dinamicamente';
}

// CHECK 13: Canonical cross-language para idiomas inactivos.
if (preg_match('/seo_active_langs/', $theme_code) && preg_match('/defaultActiveLang/', $theme_code) && preg_match('/preg_replace/', $theme_code)) {
    $passed++;
    echo "  ✓ CHECK 13: Canonical cross-language para idiomas inactivos implementado\n";
} else {
    $errors[] = 'CHECK 13: Canonical cross-language no implementado en page_attachments_alter';
}

// CHECK 14: Canonical front page limpia (sin /node).
if (preg_match('/is_front.*canonical.*canonicalLang/s', $theme_code) || preg_match('/SEO-CANONICAL-CLEAN-001/', $theme_code)) {
    $passed++;
    echo "  ✓ CHECK 14: Canonical front page usa URL limpia (sin /node)\n";
} else {
    $errors[] = 'CHECK 14: Canonical front page puede generar /node en URL';
}

// CHECK 15: Sitemap staticPages NO tiene .html en URLs de verticales.
// Extraer solo la seccion staticPages y buscar .html en las lineas de path (no comentarios).
$staticSection = '';
if (preg_match('/staticPages.*?\$staticPages\s*=\s*\[(.*?)\];/s', $sitemap_code, $m)) {
    $staticSection = $m[1];
}
$hasHtmlInPaths = FALSE;
if (!empty($staticSection)) {
    // Solo revisar lineas con 'path' => (excluir comentarios).
    foreach (explode("\n", $staticSection) as $line) {
        if (preg_match("/'\s*path\s*'/", $line) && preg_match('/\.html/', $line)) {
            $hasHtmlInPaths = TRUE;
            break;
        }
    }
}
if (!empty($staticSection) && !$hasHtmlInPaths) {
    $passed++;
    echo "  ✓ CHECK 15: sitemap-static sin .html en URLs de verticales\n";
} else {
    $errors[] = 'CHECK 15: sitemap-static contiene .html en URLs — rutas reales no tienen extension';
}

// CHECK 16: robots.txt tiene disallows con prefijo de idioma.
if (preg_match('/SEO-ROBOTS-LANGPREFIX-001/', $sitemap_code) && preg_match('/langPrefixedPaths/', $sitemap_code)) {
    $passed++;
    echo "  ✓ CHECK 16: robots.txt genera disallows con prefijo de idioma\n";
} else {
    $errors[] = 'CHECK 16: robots.txt falta disallows con prefijo de idioma';
}

// CHECK 17: HreflangService filtra por seo_active_languages.
if (preg_match('/seo_active_languages/', $hreflang_code) && preg_match('/getActiveLanguages/', $hreflang_code)) {
    $passed++;
    echo "  ✓ CHECK 17: HreflangService filtra por seo_active_languages\n";
} else {
    $errors[] = 'CHECK 17: HreflangService no filtra por idiomas activos';
}

// Resultado.
echo "\n";
if (!empty($errors)) {
    foreach ($errors as $e) {
        echo "  ✗ {$e}\n";
    }
}
if (!empty($warnings)) {
    foreach ($warnings as $w) {
        echo "  ⚠ {$w}\n";
    }
}

echo "\n  Resultado: {$passed}/{$total} checks OK\n";

if (!empty($errors)) {
    echo "\n  ❌ FAILED\n\n";
    exit(1);
}
echo "\n  ✅ ALL CHECKS PASSED\n\n";
exit(0);
