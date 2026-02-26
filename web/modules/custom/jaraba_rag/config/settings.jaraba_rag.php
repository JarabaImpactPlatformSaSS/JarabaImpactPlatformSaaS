<?php

/**
 * @file
 * Jaraba RAG Qdrant Configuration - Secure Dual Environment v3.0.
 *
 * Include this file from settings.php:
 *   if (file_exists($app_root . '/modules/custom/jaraba_rag/config/settings.jaraba_rag.php')) {
 *     include $app_root . '/modules/custom/jaraba_rag/config/settings.jaraba_rag.php';
 *   }
 *
 * @see docs/tecnicos/20260111b-Anexo_A1_Integracion_Qdrant_Seguro_v3_Claude.md
 */

// ═══════════════════════════════════════════════════════════════════
// CONFIG SYNC DIRECTORY - Git-tracked en raiz del proyecto
// Sobrescribe el valor por defecto de settings.php (linea 879) que apunta a
// web/sites/default/files/config_*/ (gitignored, nunca llega a produccion).
// Ruta relativa a DRUPAL_ROOT (web/): ../config/sync = <repo>/config/sync
// ═══════════════════════════════════════════════════════════════════
$settings['config_sync_directory'] = '../config/sync';

// Detect environment.
$is_lando = getenv('LANDO') === 'ON';
$drupal_env = getenv('DRUPAL_ENV') ?: ($is_lando ? 'development' : 'production');

// Flag para indicar si Qdrant/RAG están disponibles.
$_jaraba_rag_available = TRUE;

// ═══════════════════════════════════════════════════════════════════
// QDRANT CONFIGURATION - SECURE
// Degradación graceful: si faltan variables de entorno en producción,
// las features RAG/Qdrant se deshabilitan sin romper Drupal.
// ═══════════════════════════════════════════════════════════════════
if ($is_lando) {
    // DESARROLLO LOCAL (Lando)
    // IMPORTANTE: Usar keys anidadas ['vector_db']['host'], NO planas ['vector_db.host'].
    // Drupal config overrides con dot-notation plana no se aplican a config YAML anidada.
    $config['jaraba_rag.settings']['vector_db']['host'] = 'http://qdrant:6333';
    $config['jaraba_rag.settings']['vector_db']['api_key'] = getenv('QDRANT_DEV_API_KEY') ?: '';
    $config['jaraba_rag.settings']['environment'] = 'development';
} else {
    // PRODUCCION (IONOS) - Variables de entorno recomendadas.
    // NOTA: En IONOS shared hosting, las env vars no están disponibles.
    // El CI/CD (deploy.yml) escribe $config overrides directamente en
    // settings.local.php (que se incluye DESPUÉS de este archivo).
    // Esos overrides prevalecen sobre los valores de aquí ("last write wins").
    // Para actualizar credenciales: ejecutar deploy con force_regenerate_settings=true.
    $qdrant_url = getenv('QDRANT_CLUSTER_URL');
    $qdrant_key = getenv('QDRANT_API_KEY');

    if (empty($qdrant_url) || empty($qdrant_key)) {
        // Degradación graceful: RAG deshabilitado, Drupal sigue funcionando.
        $_jaraba_rag_available = FALSE;
        $config['jaraba_rag.settings']['vector_db']['host'] = '';
        $config['jaraba_rag.settings']['vector_db']['api_key'] = '';
        $config['jaraba_rag.settings']['environment'] = 'production';
        $config['jaraba_rag.settings']['disabled'] = TRUE;
        error_log('JARABA RAG: QDRANT_CLUSTER_URL o QDRANT_API_KEY no configuradas. Features RAG/Qdrant deshabilitadas.');
    }
    elseif (!str_starts_with($qdrant_url, 'https://')) {
        $_jaraba_rag_available = FALSE;
        $config['jaraba_rag.settings']['vector_db']['host'] = '';
        $config['jaraba_rag.settings']['vector_db']['api_key'] = '';
        $config['jaraba_rag.settings']['environment'] = 'production';
        $config['jaraba_rag.settings']['disabled'] = TRUE;
        error_log('JARABA RAG: QDRANT_CLUSTER_URL debe usar HTTPS en producción. Features RAG/Qdrant deshabilitadas.');
    }
    else {
        $config['jaraba_rag.settings']['vector_db']['host'] = $qdrant_url;
        $config['jaraba_rag.settings']['vector_db']['api_key'] = $qdrant_key;
        $config['jaraba_rag.settings']['environment'] = 'production';
        $config['jaraba_rag.settings']['disabled'] = FALSE;
    }
}

// ═══════════════════════════════════════════════════════════════════
// OPENAI CONFIGURATION - SECURE
// ═══════════════════════════════════════════════════════════════════
$openai_key = getenv('OPENAI_API_KEY');

if (empty($openai_key) && !$is_lando) {
    $_jaraba_rag_available = FALSE;
    $config['jaraba_rag.settings']['disabled'] = TRUE;
    error_log('JARABA RAG: OPENAI_API_KEY no configurada. Features RAG/embeddings deshabilitadas.');
}

// Common configuration for both environments.
$config['jaraba_rag.settings']['vector_db']['collection'] = 'jaraba_kb';
$config['jaraba_rag.settings']['vector_db']['dimensions'] = 1536;
$config['jaraba_rag.settings']['embeddings']['model'] = 'text-embedding-3-small';
$config['jaraba_rag.settings']['embeddings']['chunk_size'] = 500;
$config['jaraba_rag.settings']['embeddings']['chunk_overlap'] = 100;

// ═══════════════════════════════════════════════════════════════════
// SECURITY CONFIGURATION
// ═══════════════════════════════════════════════════════════════════
$config['jaraba_rag.settings']['security'] = [
    // Rate limiting.
    'rate_limit_window' => 60,      // Seconds.
    'rate_limit_max_auth' => 100,   // Authenticated users.
    'rate_limit_max_anon' => 10,    // Anonymous users.

    // Input validation.
    'max_query_length' => 1000,     // Characters.
    'max_embedding_text' => 8000,   // Characters.

    // FIX-027: Multi-tenancy whitelists using canonical vertical names.
    'allowed_verticals' => [
        'empleabilidad',
        'emprendimiento',
        'comercioconecta',
        'agroconecta',
        'jarabalex',
        'serviciosconecta',
        'andalucia_ei',
        'jaraba_content_hub',
        'formacion',
        'demo',
    ],
    'allowed_plans' => ['starter', 'growth', 'pro', 'enterprise'],
];
