<?php

/**
 * @file
 * Script para probar el renderizado del bloque recommended_courses.
 */

use Drupal\Core\Render\Markup;

echo "=== Probando renderizado del bloque Cursos Recomendados ===\n\n";

try {
    // Simular el contenido del bloque.
    $content = [
        'title' => 'Cursos Recomendados para Ti',
        'subtitle' => 'Basados en tu perfil profesional',
        'course_ids' => [1, 2, 3],
        'layout' => 'grid',
        'columns' => 3,
        'show_price' => TRUE,
        'show_duration' => TRUE,
        'show_rating' => TRUE,
        'show_instructor' => TRUE,
        'background' => 'light',
    ];

    // Cargar cursos.
    $course_storage = \Drupal::entityTypeManager()->getStorage('lms_course');
    $courses_entities = $course_storage->loadMultiple($content['course_ids']);

    $courses = [];
    foreach ($courses_entities as $course) {
        $duration_minutes = $course->getDurationMinutes();
        $duration_formatted = $duration_minutes
            ? ($duration_minutes >= 60
                ? floor($duration_minutes / 60) . 'h ' . ($duration_minutes % 60 > 0 ? ($duration_minutes % 60) . 'min' : '')
                : $duration_minutes . ' min')
            : NULL;

        $courses[] = [
            'id' => $course->id(),
            'title' => $course->getTitle(),
            'image' => NULL,
            'level' => $course->getDifficultyLevel(),
            'duration' => $duration_formatted,
            'price' => $course->getPrice() ? '€' . number_format($course->getPrice(), 2, ',', '.') : 'Gratis',
            'rating' => 4.5,
            'instructor' => $course->getOwner()->getDisplayName(),
            'category' => 'Tecnología',
            'url' => $course->toUrl()->toString(),
        ];
    }

    echo "Cursos preparados para el template:\n\n";
    foreach ($courses as $c) {
        echo "- {$c['title']}\n";
        echo "  Nivel: {$c['level']}, Duración: {$c['duration']}, Precio: {$c['price']}\n";
        echo "  URL: {$c['url']}\n\n";
    }

    // Probar renderizado del tema.
    $build = [
        '#theme' => 'recommended_courses',
        '#content' => $content,
        '#courses' => $courses,
    ];

    $renderer = \Drupal::service('renderer');
    $html = (string) $renderer->renderRoot($build);

    if (strlen($html) > 100) {
        echo "✅ Renderizado exitoso! HTML generado: " . strlen($html) . " bytes\n";
        echo "\nPrimeros 500 caracteres:\n";
        echo substr(strip_tags($html), 0, 500) . "...\n";
    } else {
        echo "⚠️ HTML muy corto, posible problema con el template.\n";
        echo "HTML: " . $html . "\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
