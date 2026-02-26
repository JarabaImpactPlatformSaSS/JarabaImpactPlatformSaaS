<?php

/**
 * @file
 * Drush script para crear 3 BlogPosts de Casos de Exito con contenido
 * real extraido de jarabaimpact.com.
 *
 * Ejecucion: lando drush scr scripts/create-case-studies.php
 *
 * NOTA: Script idempotente (verifica existencia por slug antes de crear).
 */

declare(strict_types=1);

use Drupal\Core\Datetime\DrupalDateTime;

// --- 1. Intentar crear categoria "Casos de Exito" ---
$categoryId = NULL;
try {
    $categoryStorage = \Drupal::entityTypeManager()->getStorage('blog_category');
    $existing = $categoryStorage->loadByProperties(['slug' => 'casos-de-exito']);
    if (empty($existing)) {
        $category = $categoryStorage->create([
            'name' => 'Casos de Exito',
            'slug' => 'casos-de-exito',
            'description' => 'Historias reales de transformacion digital.',
            'tenant_id' => 5,
        ]);
        $category->save();
        $categoryId = (int) $category->id();
        echo "OK Categoria creada (ID: $categoryId)\n";
    } else {
        $category = reset($existing);
        $categoryId = (int) $category->id();
        echo "-> Categoria ya existe (ID: $categoryId)\n";
    }
} catch (\Exception $e) {
    echo "AVISO: blog_category no disponible, usando tags.\n";
    $categoryId = NULL;
}

// --- 2. Datos de los 3 casos ---
$cases = [];

$cases[] = [
    'title' => 'Caso de Exito: Como Marcela se Reinvento y Lanzo su Proyecto como Autonoma',
    'slug' => 'caso-exito-marcela-calabia-autonoma',
    'excerpt' => 'Marcela Calabia paso de no saber por donde empezar a lanzar dos emprendimientos como autonoma gracias al programa Andalucia +ei.',
    'body' => '<h2>El Desafio: Un Nuevo Mundo Profesional</h2><p>Marcela Calabia, con una solida trayectoria en otro sector, se enfrentaba a un reto que muchos profesionales conocen: la necesidad de reinventarse. El mundo del emprendimiento y el trabajo autonomo se presentaba como un mundo que no sabia por donde empezar.</p><blockquote><p>Este curso es oro puro. Es el mejor tiempo invertido que van a poder disponer, porque realmente vale la pena. - Marcela Calabia</p></blockquote><h2>El Descubrimiento: Una Formacion Con Alma</h2><p>Buscando una solucion, Marcela se inscribio en el programa de emprendimiento inclusivo <strong>Andalucia +ei</strong>, impulsado por el Metodo Jaraba. Lo que encontro fue mucho mas que un curso estandar.</p><h2>La Solucion Sin Humo: Herramientas Practicas y Apoyo Humano</h2><p>A lo largo del programa, Marcela no solo adquirio los conocimientos tecnicos que necesitaba, sino que encontro el apoyo y la claridad que marcan la diferencia.</p><h2>Los Resultados: De la Idea a la Accion</h2><ul><li><strong>Capacidad de Crear su Propia Web:</strong> Paso de no saber por donde empezar a ser capaz de construir su propia pagina web.</li><li><strong>Dominio de Redes Sociales:</strong> Aprendio a poner en marcha y gestionar sus perfiles profesionales.</li><li><strong>Autonomia en Marketing Digital:</strong> Adquirio la habilidad de generar y lanzar su propia publicidad online.</li></ul><blockquote><p>Ninguno de los cursos de pago que he hecho me ha dado lo que me dio este curso gratuito. Las herramientas que da, yo no las encontre en ningun otro lado. - Marcela Calabia</p></blockquote><h2>Conclusion: Un Nuevo Comienzo</h2><p>La historia de Marcela es un testimonio del poder de la formacion practica y el acompanamiento cercano. Hoy, no solo tiene un emprendimiento en marcha, sino dos, demostrando que con el impulso adecuado, la transformacion digital es posible para todos.</p><p><strong>Marcela Calabia</strong> es hoy Coach de Comunicacion Estrategica y Resiliencia, y autora del libro Sin culpa. Con coraje.</p>',
    'tags' => 'emprendimiento,andalucia-ei,caso-de-exito,autonoma,reinvencion-profesional',
    'reading_time' => 5,
    'meta_title' => 'Caso de Exito: Marcela Calabia - De la Incertidumbre a Autonoma',
    'meta_description' => 'Como Marcela Calabia se reinvento y lanzo 2 emprendimientos gracias al programa Andalucia +ei del Metodo Jaraba.',
];

$cases[] = [
    'title' => 'Caso de Exito: Del Estres Corporativo al Exito Rural - La Historia de Camino Viejo',
    'slug' => 'caso-exito-camino-viejo-estres-corporativo-exito-rural',
    'excerpt' => 'Angel Martinez dejo una carrera corporativa para crear Camino Viejo en Cazalla de la Sierra. El programa Andalucia +ei le dio las herramientas lean.',
    'body' => '<h2>El Desafio: El Equilibrio Inalcanzable y un Sueno Local</h2><p><strong>Angel Martinez</strong> se encontraba en una encrucijada que muchos profesionales reconocen: una carrera de exito en una gran empresa, con un buen salario, pero <strong>a un coste personal insostenible</strong>. El ritmo de trabajo se habia vuelto incompatible con la vida familiar.</p><p>Su objetivo era claro: <strong>hacer algo que me gustase y me permitiera quedarme en mi pueblo</strong>, identificando y cubriendo una necesidad local.</p><blockquote><p>La formacion Jaraba Impact de PED es oro puro. Ahora tengo conciencia de que ES POSIBLE. - Angel Martinez</p></blockquote><h2>El Descubrimiento: Un Mapa de Oro Puro</h2><p>La transicion de la idea a la accion a menudo se ve frenada por la incertidumbre. Fue en esa busqueda de una hoja de ruta clara cuando Angel encontro la Plataforma de Ecosistemas Digitales de Pepe Jaraba.</p><h2>La Solucion: Principios Lean y Empoderamiento Administrativo</h2><ol><li><strong>Cambio de paradigma financiero:</strong> Hay que emprender minimizando la inversion inicial.</li><li><strong>Empoderamiento frente a la burocracia:</strong> El programa desmitifico la parte administrativa.</li></ol><h2>Los Resultados: Un Negocio Local Prospero</h2><p>Armado con una nueva mentalidad y herramientas practicas, Angel co-fundo <strong>Camino Viejo</strong>, un negocio de gastrobiking y turismo activo en Cazalla de la Sierra.</p><blockquote><p>Angel no ha creado clientes, ha unido a una Tribu. Las imagenes de sus eventos, con grupos de ciclistas compartiendo risas, son el testimonio visual de su verdadero exito.</p></blockquote><p><strong>Angel Martinez</strong> - Cofundador de Camino Viejo | <a href="https://www.caminoviejo.es/" target="_blank" rel="noopener">caminoviejo.es</a></p>',
    'tags' => 'emprendimiento,andalucia-ei,caso-de-exito,turismo-rural,cazalla-de-la-sierra',
    'reading_time' => 6,
    'meta_title' => 'Caso de Exito: Camino Viejo - del Estres Corporativo al Exito Rural',
    'meta_description' => 'Angel Martinez dejo la gran empresa para fundar Camino Viejo en Cazalla. El Metodo Jaraba le dio las herramientas lean.',
];

$cases[] = [
    'title' => 'Caso de Exito: De la Paralisis Administrativa a la Accion - Luis Miguel Criado',
    'slug' => 'caso-exito-luis-miguel-criado-paralisis-administrativa',
    'excerpt' => 'Luis Miguel supero el miedo a la burocracia para darse de alta como autonomo, conseguir ayudas de la Junta y gestionar sus cuotas.',
    'body' => '<h2>Un Exito Fundacional: Superar la Barrera Mas Comun</h2><p>No todas las historias de exito se miden en facturacion o en el tamano del equipo. Algunas se miden en <strong>barreras derribadas</strong> y en el <strong>coraje de empezar</strong>. El caso de Luis Miguel Criado es el arquetipo del <strong>Exito Fundacional</strong>.</p><blockquote><p>El programa me dio las herramientas para dar los pasos mas dificiles: empezar a cotizar y conseguir mis primeras ayudas. - Luis Miguel Criado</p></blockquote><h2>El Desafio: El Muro Invisible</h2><p>Luis Miguel representa al <strong>profesional vocacional</strong>: un experto en su campo (terapia de masajes) que se encuentra ante un muro invisible de complejidad administrativa. El vertigo a darse de alta como autonomo, la gestion de cuotas, la solicitud de ayudas...</p><h2>La Solucion: El Mapa Sin Humo</h2><p>El <strong>programa Andalucia +ei</strong> se convirtio en el catalizador que transformo la paralisis en accion:</p><ol><li><strong>El Salto al Trabajo por Cuenta Propia:</strong> Claridad y secuencia para empezar a cotizar como autonomo.</li><li><strong>La Captacion de Recursos:</strong> Logro la ayuda de la Junta, preparando documentacion y un proyecto solido.</li><li><strong>La Autonomia de Gestion:</strong> Saber gestionar por mi mismo las cuotas de autonomo sin depender de una gestoria.</li></ol><h2>Los Resultados: La Confianza para Construir</h2><ul><li><strong>Negocio Operativo:</strong> Ha pasado del mundo de las ideas a tener un negocio real.</li><li><strong>Empoderamiento y Ahorro:</strong> Gestiona sus propias cuotas, ahorra dinero y gana control.</li><li><strong>Validacion y Financiacion:</strong> La ayuda de la Junta es una validacion externa del potencial de su proyecto.</li></ul>',
    'tags' => 'emprendimiento,andalucia-ei,caso-de-exito,autonomo,burocracia',
    'reading_time' => 5,
    'meta_title' => 'Caso de Exito: Luis Miguel Criado - De la Paralisis a la Accion',
    'meta_description' => 'Como Luis Miguel supero el miedo burocratico para darse de alta como autonomo y conseguir ayudas de la Junta.',
];

// --- 3. Crear BlogPosts ---
$postStorage = \Drupal::entityTypeManager()->getStorage('blog_post');

foreach ($cases as $i => $case) {
    $existing = $postStorage->loadByProperties(['slug' => $case['slug']]);
    if (!empty($existing)) {
        $post = reset($existing);
        $num = $i + 1;
        echo "-> Post #$num ya existe: '{$case['title']}' (ID: " . $post->id() . ")\n";
        continue;
    }

    $post = $postStorage->create([
        'title' => $case['title'],
        'slug' => $case['slug'],
        'excerpt' => $case['excerpt'],
        'body' => [
            'value' => $case['body'],
            'format' => 'full_html',
        ],
        'tags' => $case['tags'],
        'status' => 'published',
        'is_featured' => TRUE,
        'reading_time' => $case['reading_time'],
        'meta_title' => $case['meta_title'],
        'meta_description' => $case['meta_description'],
        'schema_type' => 'Article',
        'category_id' => $categoryId,
        'tenant_id' => 5,
        'uid' => 1,
        'published_at' => (new DrupalDateTime())->format('Y-m-d\TH:i:s'),
    ]);

    $post->save();
    $num = $i + 1;
    echo "OK Post #$num creado: '{$case['title']}' (ID: " . $post->id() . ")\n";
}

echo "\nScript completado. 3 Casos de Exito creados/verificados.\n";
