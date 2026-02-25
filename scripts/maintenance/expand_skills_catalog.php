<?php

/**
 * @file
 * Script para ampliar la taxonomía de skills con categorías diversas.
 * 
 * Basado en frameworks ESCO (Europa) y O*NET (EEUU) para cubrir
 * múltiples sectores profesionales además de tecnología.
 * 
 * Ejecutar: drush scr web/expand_skills_catalog.php
 */

use Drupal\taxonomy\Entity\Term;

$vid = 'skills';

// Verificar que el vocabulario existe
$vocabulary = \Drupal::entityTypeManager()
    ->getStorage('taxonomy_vocabulary')
    ->load($vid);

if (!$vocabulary) {
    echo "❌ Vocabulario 'skills' no encontrado.\n";
    return;
}

// Catálogo ampliado basado en ESCO/O*NET
$expanded_catalog = [
    // === HABILIDADES TÉCNICAS ESPECIALIZADAS ===
    'Administración y Finanzas' => [
        'Contabilidad',
        'Gestión presupuestaria',
        'Facturación',
        'Nóminas',
        'Control de costes',
        'Auditoría interna',
        'SAP',
        'Sage',
        'A3',
        'Excel financiero',
    ],
    'Ventas y Comercio' => [
        'Negociación comercial',
        'Atención al cliente',
        'Gestión de cuentas',
        'CRM (Salesforce, HubSpot)',
        'Técnicas de venta',
        'Desarrollo de negocio',
        'Trade marketing',
        'Venta B2B',
        'Venta B2C',
        'E-commerce',
    ],
    'Logística y Operaciones' => [
        'Gestión de almacén',
        'Supply chain',
        'Transporte y distribución',
        'Inventario',
        'Picking y packing',
        'Last mile',
        'Lean management',
        'Six Sigma',
        'Planificación de producción',
        'Control de calidad',
    ],
    'Recursos Humanos' => [
        'Selección de personal',
        'Gestión del talento',
        'Formación y desarrollo',
        'Evaluación del desempeño',
        'Relaciones laborales',
        'Compensación y beneficios',
        'Onboarding',
        'Employer branding',
        'Comunicación interna',
        'People analytics',
    ],
    'Sector Sanitario' => [
        'Enfermería',
        'Auxiliar de enfermería',
        'Fisioterapia',
        'Nutrición y dietética',
        'Atención socio-sanitaria',
        'Geriatría',
        'Primeros auxilios',
        'Gestión sanitaria',
        'Farmacia',
        'Salud mental',
    ],
    'Hostelería y Turismo' => [
        'Recepción de hotel',
        'Servicio de sala',
        'Cocina profesional',
        'Repostería',
        'Sumillería',
        'Organización de eventos',
        'Guía turístico',
        'Revenue management',
        'Channel manager',
        'Animación turística',
    ],
    'Construcción e Ingeniería' => [
        'Dirección de obra',
        'Presupuestos de obra',
        'AutoCAD',
        'BIM (Revit)',
        'Instalaciones eléctricas',
        'Fontanería',
        'Climatización (HVAC)',
        'Soldadura',
        'Carpintería',
        'Prevención de riesgos laborales',
    ],
    'Sector Agrícola y Agroalimentario' => [
        'Agricultura ecológica',
        'Ganadería',
        'Viticultura',
        'Olivicultura',
        'Trazabilidad alimentaria',
        'Control de calidad alimentaria',
        'APPCC',
        'Agricultura de precisión',
        'Maquinaria agrícola',
        'Gestión de cooperativas',
    ],
    'Educación y Formación' => [
        'Docencia',
        'Diseño instruccional',
        'E-learning',
        'Tutoría',
        'Evaluación educativa',
        'Educación infantil',
        'Educación especial',
        'Formación para el empleo',
        'Coaching educativo',
        'Gamificación educativa',
    ],
    'Legal y Jurídico' => [
        'Derecho laboral',
        'Derecho mercantil',
        'Derecho civil',
        'Protección de datos (RGPD)',
        'Compliance',
        'Propiedad intelectual',
        'Contratos',
        'Mediación',
        'Asesoría fiscal',
        'Derecho administrativo',
    ],

    // === HABILIDADES TRANSVERSALES (ESCO) ===
    'Gestión y Organización' => [
        'Planificación estratégica',
        'Gestión de proyectos',
        'Gestión del cambio',
        'Toma de decisiones',
        'Delegación',
        'Priorización',
        'Gestión del estrés',
        'Metodologías ágiles (Scrum)',
        'OKRs',
        'Kanban',
    ],
    'Comunicación' => [
        'Comunicación oral',
        'Comunicación escrita',
        'Presentaciones',
        'Escucha activa',
        'Negociación',
        'Persuasión',
        'Storytelling',
        'Comunicación intercultural',
        'Oratoria',
        'Asertividad',
    ],
    'Relaciones Interpersonales' => [
        'Trabajo en equipo',
        'Colaboración',
        'Gestión de conflictos',
        'Networking',
        'Empatía',
        'Inteligencia emocional',
        'Influencia',
        'Facilitación de grupos',
        'Mentoring',
        'Feedback constructivo',
    ],
    'Pensamiento y Análisis' => [
        'Pensamiento analítico',
        'Pensamiento crítico',
        'Resolución de problemas',
        'Pensamiento sistémico',
        'Creatividad e innovación',
        'Design thinking',
        'Toma de decisiones basada en datos',
        'Síntesis de información',
        'Conceptualización',
        'Investigación',
    ],
    'Autonomía y Desarrollo' => [
        'Automotivación',
        'Iniciativa',
        'Aprendizaje continuo',
        'Adaptabilidad',
        'Resiliencia',
        'Orientación a resultados',
        'Responsabilidad',
        'Proactividad',
        'Autogestión',
        'Growth mindset',
    ],
];

$categories_created = 0;
$skills_created = 0;

foreach ($expanded_catalog as $category_name => $skills) {
    // Verificar si la categoría ya existe
    $existing_cat = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => $vid, 'name' => $category_name, 'parent' => 0]);

    if (!empty($existing_cat)) {
        $category = reset($existing_cat);
        echo "⏭️ Categoría existente: $category_name\n";
    } else {
        // Crear categoría
        $category = Term::create([
            'vid' => $vid,
            'name' => $category_name,
            'parent' => [],
        ]);
        $category->save();
        $categories_created++;
        echo "✅ Nueva categoría: $category_name\n";
    }

    $parent_id = $category->id();

    foreach ($skills as $skill_name) {
        // Verificar si el skill ya existe bajo esta categoría
        $existing_skill = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadByProperties(['vid' => $vid, 'name' => $skill_name]);

        if (!empty($existing_skill)) {
            continue; // Skip if exists anywhere
        }

        // Crear skill
        $skill = Term::create([
            'vid' => $vid,
            'name' => $skill_name,
            'parent' => [$parent_id],
        ]);
        $skill->save();
        $skills_created++;
    }
}

echo "\n✅ Catálogo ampliado: $categories_created nuevas categorías, $skills_created nuevos skills.\n";
echo "📊 Total ahora: Consultar /admin/structure/taxonomy/manage/skills/overview\n";
