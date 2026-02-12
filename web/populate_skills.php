<?php

/**
 * @file
 * Script to populate skills taxonomy.
 * Run with: drush scr web/populate_skills.php
 */

use Drupal\taxonomy\Entity\Term;

$skills = [
    // Programación
    'Programación' => [
        'JavaScript',
        'Python',
        'PHP',
        'Java',
        'TypeScript',
        'SQL',
        'C#',
        'Go',
        'Ruby',
        'Rust',
    ],
    // Frameworks
    'Frameworks' => [
        'React',
        'Vue.js',
        'Angular',
        'Drupal',
        'Laravel',
        'Django',
        'Spring Boot',
        'Node.js',
        'Next.js',
        'Symfony',
    ],
    // Soft Skills
    'Soft Skills' => [
        'Comunicación',
        'Liderazgo',
        'Trabajo en equipo',
        'Resolución de problemas',
        'Pensamiento crítico',
        'Adaptabilidad',
        'Gestión del tiempo',
        'Creatividad',
    ],
    // Marketing
    'Marketing' => [
        'Marketing Digital',
        'SEO',
        'SEM',
        'Redes Sociales',
        'Email Marketing',
        'Content Marketing',
        'Google Analytics',
        'Meta Ads',
    ],
    // Diseño
    'Diseño' => [
        'UX/UI',
        'Figma',
        'Adobe Creative Suite',
        'Sketch',
        'Diseño Web',
        'Prototipado',
    ],
    // DevOps
    'DevOps' => [
        'Docker',
        'Kubernetes',
        'AWS',
        'Azure',
        'CI/CD',
        'Linux',
        'Git',
        'Terraform',
    ],
    // Data
    'Data' => [
        'Machine Learning',
        'Data Analysis',
        'Power BI',
        'Tableau',
        'Excel Avanzado',
        'Big Data',
    ],
    // Idiomas
    'Idiomas' => [
        'Inglés',
        'Francés',
        'Alemán',
        'Portugués',
        'Español nativo',
        'Chino',
    ],
];

$total_skills = 0;
$total_categories = 0;

foreach ($skills as $category => $skill_list) {
    // Create parent category
    $parent = Term::create([
        'vid' => 'skills',
        'name' => $category,
    ]);
    $parent->save();
    $parent_id = $parent->id();
    $total_categories++;

    // Create child skills
    foreach ($skill_list as $skill_name) {
        $term = Term::create([
            'vid' => 'skills',
            'name' => $skill_name,
            'parent' => $parent_id,
        ]);
        $term->save();
        $total_skills++;
    }
}

print "✅ Created $total_categories categories and $total_skills skills.\n";
