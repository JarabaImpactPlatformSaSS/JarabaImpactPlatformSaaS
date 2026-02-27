<?php

/**
 * @file
 * Seeder script for Success Case entities.
 *
 * Reads the pre-filled briefs from docs/assets/casos-de-exito/ and creates
 * SuccessCase entities with the known data. Photos and videos should be
 * added later via Field UI once the user provides the actual files.
 *
 * Usage:
 *   docker exec jarabasaas_appserver_1 drush scr \
 *     web/modules/custom/jaraba_success_cases/scripts/seed_success_cases.php
 */

declare(strict_types=1);

$entity_type_manager = \Drupal::entityTypeManager();
$storage = $entity_type_manager->getStorage('success_case');

// Check if entity type exists.
$definition = $entity_type_manager->getDefinition('success_case', FALSE);
if (!$definition) {
    echo "ERROR: success_case entity type not found. Enable the module first.\n";
    return;
}

// Seed data from the 3 known cases.
$cases = [
    [
        'name' => 'Marcela Calabia',
        'slug' => 'marcela-calabia',
        'profession' => 'Coach de Comunicación Estratégica y Resiliencia',
        'company' => '',
        'sector' => 'Coaching / Comunicación / Desarrollo personal',
        'location' => 'Andalucía',
        'vertical' => 'emprendimiento',
        'program_name' => 'Andalucía +ei',
        'challenge_before' => 'Marcela necesitaba estructurar su marca personal como coach de comunicación estratégica y resiliencia. Antes del programa, no tenía una presencia digital profesional ni una estrategia clara para captar clientes.',
        'solution_during' => 'A través del programa Andalucía +ei, recibió acompañamiento para desarrollar su web profesional, definir su propuesta de valor y establecer una estrategia de comunicación digital.',
        'result_after' => 'Pasó de no tener presencia digital a contar con una web profesional operativa y una estrategia de comunicación definida, con capacidad para captar clientes de forma autónoma como autónoma.',
        'quote_short' => '',
        'rating' => 5,
        'featured' => TRUE,
        'weight' => 0,
        'status' => TRUE,
    ],
    [
        'name' => 'Ángel Martínez',
        'slug' => 'angel-martinez',
        'profession' => 'Cofundador de Camino Viejo — Gastrobiking rural',
        'company' => 'Camino Viejo',
        'sector' => 'Turismo rural / Gastronomía / Experiencias',
        'location' => 'Andalucía',
        'vertical' => 'pymes',
        'program_name' => 'Andalucía +ei',
        'challenge_before' => 'Ángel tenía la idea de montar un negocio de turismo rural con rutas en bicicleta combinadas con gastronomía local (gastrobiking), pero necesitaba apoyo para profesionalizar el proyecto y llegar a su público objetivo.',
        'solution_during' => 'Mediante el programa Andalucía +ei, recibió acompañamiento en digitalización, estrategia de marketing y desarrollo del modelo de negocio "Camino Viejo".',
        'result_after' => 'Camino Viejo se convirtió en un proyecto operativo de gastrobiking rural, con presencia digital profesional y capacidad de captar clientes para sus experiencias.',
        'quote_short' => '',
        'rating' => 5,
        'featured' => TRUE,
        'weight' => 1,
        'status' => TRUE,
    ],
    [
        'name' => 'Luis Miguel Criado',
        'slug' => 'luis-miguel-criado',
        'profession' => 'Terapeuta de masajes — Autónomo',
        'company' => '',
        'sector' => 'Salud y bienestar / Terapias manuales',
        'location' => 'Andalucía',
        'vertical' => 'empleabilidad',
        'program_name' => 'Andalucía +ei',
        'challenge_before' => 'Luis Miguel buscaba establecerse como terapeuta de masajes autónomo. No tenía visibilidad digital ni una estrategia clara para darse a conocer y captar clientes en su zona.',
        'solution_during' => 'A través del programa Andalucía +ei, recibió orientación para definir su oferta de servicios, crear su presencia digital y desarrollar una estrategia de captación de clientes como autónomo.',
        'result_after' => 'Luis Miguel se dio de alta como autónomo y comenzó a ejercer como terapeuta de masajes, con una propuesta de valor definida y capacidad de captar clientes de forma independiente.',
        'quote_short' => '',
        'rating' => 5,
        'featured' => FALSE,
        'weight' => 2,
        'status' => TRUE,
    ],
];

$created = 0;
$skipped = 0;

foreach ($cases as $case_data) {
    // Check if already exists by slug.
    $existing = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('slug', $case_data['slug'])
        ->execute();

    if (!empty($existing)) {
        echo "SKIP: {$case_data['name']} (slug '{$case_data['slug']}' already exists)\n";
        $skipped++;
        continue;
    }

    $entity = $storage->create($case_data);
    $entity->save();
    echo "CREATED: {$case_data['name']} (ID: {$entity->id()}, slug: {$case_data['slug']})\n";
    $created++;
}

echo "\n--- Seed complete: $created created, $skipped skipped ---\n";
