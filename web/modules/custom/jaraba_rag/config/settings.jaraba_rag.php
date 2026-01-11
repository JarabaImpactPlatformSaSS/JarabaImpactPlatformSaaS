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

// Detect environment.
$is_lando = getenv('LANDO') === 'ON';
$drupal_env = getenv('DRUPAL_ENV') ?: ($is_lando ? 'development' : 'production');

// ═══════════════════════════════════════════════════════════════════
// QDRANT CONFIGURATION - SECURE
// ═══════════════════════════════════════════════════════════════════
if ($is_lando) {
    // DESARROLLO LOCAL (Lando)
    $config['jaraba_rag.settings']['vector_db.host'] = 'http://qdrant:6333';
    $config['jaraba_rag.settings']['vector_db.api_key'] = getenv('QDRANT_DEV_API_KEY') ?: '';
    $config['jaraba_rag.settings']['environment'] = 'development';
} else {
    // PRODUCCION (IONOS) - Variables de entorno OBLIGATORIAS
    $qdrant_url = getenv('QDRANT_CLUSTER_URL');
    $qdrant_key = getenv('QDRANT_API_KEY');

    // 🔒 VALIDACION: Fallar si no hay credenciales en producción
    if (empty($qdrant_url)) {
        throw new \RuntimeException(
            'SEGURIDAD: QDRANT_CLUSTER_URL no configurada en variables de entorno'
        );
    }
    if (empty($qdrant_key)) {
        throw new \RuntimeException(
            'SEGURIDAD: QDRANT_API_KEY no configurada en variables de entorno'
        );
    }

    // 🔒 VALIDACION: URL debe ser HTTPS en producción
    if (!str_starts_with($qdrant_url, 'https://')) {
        throw new \RuntimeException(
            'SEGURIDAD: QDRANT_CLUSTER_URL debe usar HTTPS en produccion'
        );
    }

    $config['jaraba_rag.settings']['vector_db.host'] = $qdrant_url;
    $config['jaraba_rag.settings']['vector_db.api_key'] = $qdrant_key;
    $config['jaraba_rag.settings']['environment'] = 'production';
}

// ═══════════════════════════════════════════════════════════════════
// OPENAI CONFIGURATION - SECURE
// ═══════════════════════════════════════════════════════════════════
$openai_key = getenv('OPENAI_API_KEY');

if (empty($openai_key) && !$is_lando) {
    throw new \RuntimeException(
        'SEGURIDAD: OPENAI_API_KEY no configurada en variables de entorno'
    );
}

// Common configuration for both environments.
$config['jaraba_rag.settings']['vector_db.collection'] = 'jaraba_kb';
$config['jaraba_rag.settings']['vector_db.vector_dimensions'] = 1536;
$config['jaraba_rag.settings']['embeddings.model'] = 'text-embedding-3-small';
$config['jaraba_rag.settings']['embeddings.chunk_size'] = 500;
$config['jaraba_rag.settings']['embeddings.chunk_overlap'] = 100;

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

    // Multi-tenancy whitelists.
    'allowed_verticals' => ['agro', 'arte', 'turismo', 'empleo', 'kb'],
    'allowed_plans' => ['starter', 'growth', 'pro', 'enterprise'],
];
