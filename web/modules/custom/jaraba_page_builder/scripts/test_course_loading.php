<?php

/**
 * @file
 * Script para probar la carga de cursos desde el LMS.
 */

echo "=== Probando carga de cursos del LMS ===\n\n";

try {
    $course_storage = \Drupal::entityTypeManager()->getStorage('lms_course');
    $courses = $course_storage->loadMultiple();

    echo "Total cursos encontrados: " . count($courses) . "\n\n";

    foreach ($courses as $course) {
        echo "- ID: " . $course->id() . "\n";
        echo "  Título: " . $course->getTitle() . "\n";
        echo "  Nivel: " . $course->getDifficultyLevel() . "\n";
        echo "  Duración: " . $course->getDurationMinutes() . " min\n";
        echo "  Precio: " . ($course->getPrice() ?: 'Gratis') . "\n";
        echo "  Publicado: " . ($course->isPublished() ? 'Sí' : 'No') . "\n";
        echo "\n";
    }

    // Probar carga específica por IDs (simulando lo que hace el preprocess)
    $test_ids = [1, 2, 3];
    echo "=== Probando carga por IDs: " . implode(', ', $test_ids) . " ===\n\n";

    $selected = $course_storage->loadMultiple($test_ids);
    foreach ($selected as $course) {
        echo "✅ Cargado: " . $course->getTitle() . " (ID: " . $course->id() . ")\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
