<?php

/**
 * @file
 * Script para crear productos y precios en Stripe para las 5 verticales.
 *
 * Crea 5 productos (1 por vertical) con 4 tiers de precio cada uno
 * (Starter, Growth, Pro, Enterprise) en modalidad mensual y anual.
 *
 * Requisitos:
 * - Stripe API key configurada en ecosistema_jaraba_core.stripe
 * - Módulo jaraba_foc habilitado (StripeConnectService)
 *
 * Ejecutar con: lando drush php:script scripts/stripe/seed_products_prices.php
 *
 * IMPORTANTE: Este script es idempotente — usa lookup_keys para verificar
 * existencia antes de crear. Seguro de ejecutar múltiples veces.
 */

// Verificar que tenemos acceso a Stripe.
$stripeConnect = \Drupal::service('jaraba_foc.stripe_connect');
$secretKey = $stripeConnect->getSecretKey();

if (empty($secretKey)) {
  echo "❌ Error: No se encontró la API key de Stripe.\n";
  echo "   Configura la clave en: /admin/config/jaraba/stripe\n";
  exit(1);
}

echo "🏪 Creando catálogo de productos Stripe para Jaraba Impact Platform...\n\n";

// =============================================================================
// DEFINICIÓN DE PRODUCTOS Y PRECIOS
// =============================================================================

$verticals = [
  [
    'vertical_id' => 'empleabilidad',
    'name' => 'Jaraba Empleabilidad',
    'description' => 'Plataforma de empleabilidad con IA: matching inteligente, CV optimization, preparación de entrevistas y orientación laboral.',
    'tiers' => [
      'starter' => [
        'name' => 'Starter',
        'monthly' => 2900,   // 29.00€
        'yearly' => 29000,   // 290.00€ (2 meses gratis)
        'features' => 'Hasta 50 candidatos, 3 ofertas activas, matching básico',
      ],
      'growth' => [
        'name' => 'Growth',
        'monthly' => 7900,   // 79.00€
        'yearly' => 79000,   // 790.00€
        'features' => 'Hasta 500 candidatos, 20 ofertas, matching IA, analytics',
      ],
      'pro' => [
        'name' => 'Pro',
        'monthly' => 14900,  // 149.00€
        'yearly' => 149000,  // 1.490.00€
        'features' => 'Candidatos ilimitados, ofertas ilimitadas, IA avanzada, API',
      ],
      'enterprise' => [
        'name' => 'Enterprise',
        'monthly' => 29900,  // 299.00€
        'yearly' => 299000,  // 2.990.00€
        'features' => 'Todo Pro + SSO, SLA, soporte dedicado, personalización',
      ],
    ],
  ],
  [
    'vertical_id' => 'emprendimiento',
    'name' => 'Jaraba Emprendimiento',
    'description' => 'Plataforma de aceleración con IA: canvas coaching, pitch review, proyecciones financieras y validación de MVP.',
    'tiers' => [
      'starter' => [
        'name' => 'Starter',
        'monthly' => 1900,   // 19.00€
        'yearly' => 19000,   // 190.00€
        'features' => 'Hasta 5 proyectos, canvas básico, 1 usuario',
      ],
      'growth' => [
        'name' => 'Growth',
        'monthly' => 4900,   // 49.00€
        'yearly' => 49000,   // 490.00€
        'features' => 'Hasta 20 proyectos, IA completa, 5 usuarios, mentoring',
      ],
      'pro' => [
        'name' => 'Pro',
        'monthly' => 9900,   // 99.00€
        'yearly' => 99000,   // 990.00€
        'features' => 'Proyectos ilimitados, equipo ilimitado, analytics, API',
      ],
      'enterprise' => [
        'name' => 'Enterprise',
        'monthly' => 19900,  // 199.00€
        'yearly' => 199000,  // 1.990.00€
        'features' => 'Todo Pro + white label, SSO, soporte dedicado',
      ],
    ],
  ],
  [
    'vertical_id' => 'agroconecta',
    'name' => 'Jaraba AgroConecta',
    'description' => 'Marketplace agroalimentario con IA: fichas de producto, trazabilidad, marketing estacional y canal HORECA.',
    'tiers' => [
      'starter' => [
        'name' => 'Starter',
        'monthly' => 1900,   // 19.00€
        'yearly' => 19000,   // 190.00€
        'features' => 'Hasta 20 productos, tienda básica, 1 usuario',
      ],
      'growth' => [
        'name' => 'Growth',
        'monthly' => 4900,   // 49.00€
        'yearly' => 49000,   // 490.00€
        'features' => 'Hasta 100 productos, trazabilidad, IA contenido, analytics',
      ],
      'pro' => [
        'name' => 'Pro',
        'monthly' => 9900,   // 99.00€
        'yearly' => 99000,   // 990.00€
        'features' => 'Productos ilimitados, canal HORECA, marketing IA, API',
      ],
      'enterprise' => [
        'name' => 'Enterprise',
        'monthly' => 19900,  // 199.00€
        'yearly' => 199000,  // 1.990.00€
        'features' => 'Todo Pro + cooperativa multi-sede, SSO, soporte dedicado',
      ],
    ],
  ],
  [
    'vertical_id' => 'comercioconecta',
    'name' => 'Jaraba ComercioConecta',
    'description' => 'Digitalización de comercio local con IA: ofertas flash, SEO local, fidelización y gestión de reseñas.',
    'tiers' => [
      'starter' => [
        'name' => 'Starter',
        'monthly' => 1500,   // 15.00€
        'yearly' => 15000,   // 150.00€
        'features' => 'Perfil digital, ofertas básicas, Google Business',
      ],
      'growth' => [
        'name' => 'Growth',
        'monthly' => 3900,   // 39.00€
        'yearly' => 39000,   // 390.00€
        'features' => 'Todo Starter + fidelización, SEO local, IA contenido',
      ],
      'pro' => [
        'name' => 'Pro',
        'monthly' => 7900,   // 79.00€
        'yearly' => 79000,   // 790.00€
        'features' => 'Todo Growth + multi-local, analytics avanzado, API',
      ],
      'enterprise' => [
        'name' => 'Enterprise',
        'monthly' => 14900,  // 149.00€
        'yearly' => 149000,  // 1.490.00€
        'features' => 'Todo Pro + cadena/franquicia, SSO, soporte dedicado',
      ],
    ],
  ],
  [
    'vertical_id' => 'serviciosconecta',
    'name' => 'Jaraba ServiciosConecta',
    'description' => 'Gestión de servicios profesionales con IA: resúmenes de caso, documentación, presupuestos y comunicación con clientes.',
    'tiers' => [
      'starter' => [
        'name' => 'Starter',
        'monthly' => 2900,   // 29.00€
        'yearly' => 29000,   // 290.00€
        'features' => 'Hasta 50 clientes, documentación básica, 1 usuario',
      ],
      'growth' => [
        'name' => 'Growth',
        'monthly' => 5900,   // 59.00€
        'yearly' => 59000,   // 590.00€
        'features' => 'Hasta 200 clientes, IA completa, 5 usuarios, analytics',
      ],
      'pro' => [
        'name' => 'Pro',
        'monthly' => 11900,  // 119.00€
        'yearly' => 119000,  // 1.190.00€
        'features' => 'Clientes ilimitados, equipo ilimitado, API, integraciones',
      ],
      'enterprise' => [
        'name' => 'Enterprise',
        'monthly' => 24900,  // 249.00€
        'yearly' => 249000,  // 2.490.00€
        'features' => 'Todo Pro + multi-despacho, SSO, soporte dedicado',
      ],
    ],
  ],
];

// =============================================================================
// CREACIÓN EN STRIPE
// =============================================================================

$productsCreated = 0;
$productsSkipped = 0;
$pricesCreated = 0;
$pricesSkipped = 0;

foreach ($verticals as $vertical) {
  echo "📦 Vertical: {$vertical['name']}\n";

  // Buscar producto existente por metadata.
  $existingProducts = $stripeConnect->stripeRequest('GET', '/products', [
    'active' => 'true',
    'limit' => 100,
  ]);

  $productId = NULL;
  if (!empty($existingProducts['data'])) {
    foreach ($existingProducts['data'] as $product) {
      if (isset($product['metadata']['vertical_id']) && $product['metadata']['vertical_id'] === $vertical['vertical_id']) {
        $productId = $product['id'];
        echo "  ⏭️  Producto ya existe: {$product['id']}\n";
        $productsSkipped++;
        break;
      }
    }
  }

  // Crear producto si no existe.
  if (!$productId) {
    $product = $stripeConnect->stripeRequest('POST', '/products', [
      'name' => $vertical['name'],
      'description' => $vertical['description'],
      'metadata' => [
        'vertical_id' => $vertical['vertical_id'],
        'platform' => 'jaraba_impact',
      ],
    ]);
    $productId = $product['id'];
    echo "  ✅ Producto creado: {$productId}\n";
    $productsCreated++;
  }

  // Crear precios por tier.
  foreach ($vertical['tiers'] as $tierKey => $tier) {
    foreach (['monthly' => 'month', 'yearly' => 'year'] as $intervalKey => $interval) {
      $lookupKey = "{$vertical['vertical_id']}_{$tierKey}_{$intervalKey}";
      $amount = $tier[$intervalKey];

      // Verificar si el precio ya existe por lookup_key.
      $existingPrices = $stripeConnect->stripeRequest('GET', '/prices', [
        'lookup_keys' => [$lookupKey],
      ]);

      if (!empty($existingPrices['data'])) {
        echo "    ⏭️  Precio ya existe: {$lookupKey}\n";
        $pricesSkipped++;
        continue;
      }

      // Crear precio.
      $priceData = [
        'product' => $productId,
        'unit_amount' => $amount,
        'currency' => 'eur',
        'recurring' => [
          'interval' => $interval,
        ],
        'lookup_key' => $lookupKey,
        'nickname' => "{$vertical['name']} - {$tier['name']} ({$intervalKey})",
        'metadata' => [
          'vertical_id' => $vertical['vertical_id'],
          'tier' => $tierKey,
          'interval' => $intervalKey,
          'features' => $tier['features'],
          'platform' => 'jaraba_impact',
        ],
      ];

      $price = $stripeConnect->stripeRequest('POST', '/prices', $priceData);
      $amountFormatted = number_format($amount / 100, 2);
      echo "    ✅ Precio creado: {$tier['name']} {$intervalKey} — {$amountFormatted}€/{$interval} ({$price['id']})\n";
      $pricesCreated++;
    }
  }

  echo "\n";
}

// =============================================================================
// RESUMEN
// =============================================================================

echo "========================================\n";
echo "📊 Resumen del catálogo Stripe:\n";
echo "  Productos: {$productsCreated} creados, {$productsSkipped} existentes\n";
echo "  Precios: {$pricesCreated} creados, {$pricesSkipped} existentes\n";
echo "  Total esperado: 5 productos, 40 precios (5 × 4 tiers × 2 intervalos)\n";
echo "========================================\n";
echo "\n💡 Verificar en: https://dashboard.stripe.com/products\n";
echo "💡 Lookup keys formato: {vertical}_{tier}_{monthly|yearly}\n";
echo "   Ejemplo: empleabilidad_starter_monthly\n";
