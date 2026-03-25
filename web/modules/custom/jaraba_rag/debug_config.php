<?php

/**
 * @file
 * Test script to diagnose configuration loading.
 *
 * Run: lando drush php-script debug_config.php.
 */

// Get the config factory.
$config = \Drupal::config('jaraba_rag.settings');

// Read specific values.
$chunk_size = $config->get('embeddings.chunk_size');
$chunk_overlap = $config->get('embeddings.chunk_overlap');
$vector_dimensions = $config->get('vector_db.vector_dimensions');
$host = $config->get('vector_db.host');
$collection = $config->get('vector_db.collection');

echo "=== JARABA RAG Configuration Debug ===\n\n";
echo "vector_db.host: " . var_export($host, TRUE) . "\n";
echo "vector_db.collection: " . var_export($collection, TRUE) . "\n";
echo "vector_db.vector_dimensions: " . var_export($vector_dimensions, TRUE) . "\n";
echo "embeddings.chunk_size: " . var_export($chunk_size, TRUE) . "\n";
echo "embeddings.chunk_overlap: " . var_export($chunk_overlap, TRUE) . "\n";

echo "\n=== Raw Config Data ===\n";
print_r($config->getRawData());
