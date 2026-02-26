<?php

/**
 * @file
 * Drush script para crear 3 articulos de blog con contenido real.
 *
 * Ejecucion: lando drush scr scripts/create-blog-articles.php
 *
 * NOTA: Script idempotente (verifica existencia por slug antes de crear).
 */

declare(strict_types=1);

use Drupal\Core\Datetime\DrupalDateTime;

$postStorage = \Drupal::entityTypeManager()->getStorage('blog_post');

// --- Articulo 1: Las 7 Herramientas Gratuitas ---
$articles = [];

$articles[] = [
    'title' => 'Las 7 Herramientas Gratuitas que Uso con Mis Clientes para Transformar su Negocio',
    'slug' => '7-herramientas-gratuitas-transformacion-digital',
    'excerpt' => 'Descubre las 7 herramientas digitales gratuitas que Pepe Jaraba utiliza con sus clientes para digitalizar pymes. Sin tecnicismos, sin humo.',
    'body' => '<p>Despues de acompanar a mas de 100 empresarios, emprendedores y profesionales en su transformacion digital, he identificado un patron claro: <strong>no necesitas gastar dinero para empezar</strong>. Las herramientas mas poderosas ya existen y son gratuitas. Lo que cambia el juego es saber cuales elegir y como usarlas de forma estrategica.</p>

<p>Aqui van las 7 herramientas que uso con mis clientes desde el primer dia. Sin tecnicismos, sin humo.</p>

<h2>1. Canva: Tu Departamento de Diseno en el Bolsillo</h2>

<p><strong>Para que sirve:</strong> Crear posts para redes sociales, presentaciones, tarjetas de visita, carteles, infografias... todo sin saber diseno.</p>

<p><strong>Por que la recomiendo:</strong> Canva democratizo el diseno grafico. Con sus plantillas profesionales, cualquier empresario puede crear contenido visual que antes requeria un disenador. La clave es empezar con las plantillas y personalizarlas con tus colores de marca.</p>

<p><strong>Tip de experto:</strong> Crea un Kit de Marca en Canva con tus colores, logo y tipografias. Asi todo tu contenido mantiene coherencia visual sin esfuerzo.</p>

<h2>2. Google Business Profile: Tu Escaparate Digital Local</h2>

<p><strong>Para que sirve:</strong> Aparecer en Google Maps y en las busquedas locales cuando alguien busca tu tipo de negocio en tu zona.</p>

<p><strong>Por que la recomiendo:</strong> Es la herramienta con mayor ROI inmediato para negocios locales. Un perfil bien optimizado puede triplicar las llamadas y visitas a tu web. Y es completamente gratis.</p>

<p><strong>Tip de experto:</strong> Publica una foto nueva cada semana y responde a todas las resenas en menos de 24h. Google premia la actividad constante con mejor posicionamiento.</p>

<h2>3. LinkedIn: Tu Marca Personal Profesional</h2>

<p><strong>Para que sirve:</strong> Conectar con otros profesionales, generar leads B2B, demostrar expertise y encontrar oportunidades de negocio o empleo.</p>

<p><strong>Por que la recomiendo:</strong> LinkedIn es la unica red social donde el contenido profesional tiene alcance organico real. Un post bien escrito puede llegar a miles de personas sin invertir un euro en publicidad.</p>

<p><strong>Tip de experto:</strong> Publica tu historia de emprendimiento en formato de lista: 5 lecciones que aprendi al emprender. Este formato tiene 3x mas engagement que los posts genericos.</p>

<h2>4. Google Analytics 4: Entiende a Tus Visitantes</h2>

<p><strong>Para que sirve:</strong> Saber quien visita tu web, de donde viene, que paginas ve y cuanto tiempo se queda.</p>

<p><strong>Por que la recomiendo:</strong> No puedes mejorar lo que no mides. GA4 te dice exactamente que funciona y que no en tu web. Es la diferencia entre navegar a ciegas y tener un mapa.</p>

<p><strong>Tip de experto:</strong> Empieza monitorizando solo 3 metricas: usuarios nuevos, paginas mas vistas y porcentaje de rebote. No te pierdas en datos que no necesitas aun.</p>

<h2>5. Mailchimp (Plan Gratuito): Email Marketing Sin Complicaciones</h2>

<p><strong>Para que sirve:</strong> Enviar newsletters, secuencias de email automaticas y mantener el contacto con tus clientes y leads.</p>

<p><strong>Por que la recomiendo:</strong> El email tiene un ROI de 42:1. Es el canal de marketing mas rentable que existe, y Mailchimp te deja empezar gratis con hasta 500 contactos.</p>

<p><strong>Tip de experto:</strong> Crea una secuencia de 3 emails de bienvenida automatica. El primer email con un recurso gratuito, el segundo con tu historia, el tercero con una oferta suave.</p>

<h2>6. Trello o Notion: Organiza tu Negocio Visualmente</h2>

<p><strong>Para que sirve:</strong> Gestionar tareas, proyectos, ideas y procesos de negocio de forma visual con tableros Kanban.</p>

<p><strong>Por que la recomiendo:</strong> La mayoria de los emprendedores pierden horas buscando informacion dispersa en WhatsApp, notas y emails. Un tablero bien estructurado centraliza todo y reduce el estres.</p>

<p><strong>Tip de experto:</strong> Crea un tablero con 4 columnas: Ideas, En Progreso, En Revision y Completado. Mueve las tareas entre columnas. Simple y efectivo.</p>

<h2>7. ChatGPT / IA Generativa: Tu Asistente para Contenido</h2>

<p><strong>Para que sirve:</strong> Crear borradores de textos, emails, posts, descripciones de producto, brainstorming de ideas de negocio.</p>

<p><strong>Por que la recomiendo:</strong> La IA no sustituye tu experiencia, pero la amplifica. Un empresario con 20 anos de experiencia que usa IA para comunicar tiene una ventaja competitiva brutal.</p>

<p><strong>Tip de experto:</strong> No pidas a la IA que lo haga todo. Dale contexto sobre tu negocio, tu cliente ideal y tu tono de voz. Cuanto mejor sea tu instruccion, mejor sera el resultado.</p>

<h2>Conclusion: El Orden Importa</h2>

<p>No necesitas las 7 herramientas desde el primer dia. Mi recomendacion:</p>

<ol>
<li><strong>Semana 1:</strong> Google Business Profile + LinkedIn (presencia basica)</li>
<li><strong>Semana 2:</strong> Canva (contenido visual)</li>
<li><strong>Semana 3:</strong> Trello/Notion (organizacion)</li>
<li><strong>Semana 4:</strong> Mailchimp + GA4 + IA (crecimiento)</li>
</ol>

<p>Estas son las mismas herramientas que incluyo en el <strong>Kit de Impulso Digital</strong>. Si quieres la guia completa paso a paso, <a href="#kit-impulso">descargala gratis aqui</a>.</p>',
    'tags' => 'herramientas-gratuitas,transformacion-digital,pymes,canva,linkedin,google-analytics',
    'reading_time' => 8,
    'meta_title' => '7 Herramientas Gratuitas para Transformar tu Negocio - Pepe Jaraba',
    'meta_description' => 'Las 7 herramientas digitales gratuitas que Pepe Jaraba usa con +100 clientes para digitalizar pymes. Guia practica sin tecnicismos.',
];

$articles[] = [
    'title' => 'Como Optimizar tu Perfil de LinkedIn para Encontrar Trabajo en 2026',
    'slug' => 'optimizar-linkedin-encontrar-trabajo-2026',
    'excerpt' => 'Guia practica para convertir tu perfil de LinkedIn en un iman de oportunidades. 12 pasos concretos para destacar.',
    'body' => '<p>LinkedIn tiene <strong>1.000 millones de usuarios</strong>. Pero el 95% tiene un perfil que no dice nada, no atrae a nadie y no genera oportunidades. La buena noticia: con 12 cambios concretos, puedes pasar del anonimato a recibir mensajes de reclutadores cada semana.</p>

<p>Llevo anos ayudando a profesionales a reinventarse digitalmente, y LinkedIn es siempre el primer paso. Aqui va la guia que uso con mis clientes.</p>

<h2>Paso 1: La Foto de Perfil que Genera Confianza</h2>

<p>Tu foto es tu primera impresion digital. Los perfiles con foto profesional reciben <strong>21 veces mas visitas</strong> y <strong>9 veces mas solicitudes de conexion</strong>.</p>

<p><strong>Criterios:</strong> Fondo limpio (no tiene que ser blanco), luz natural, ropa profesional coherente con tu sector, sonrisa autentica. No uses selfies, fotos de vacaciones ni fotos de hace 10 anos.</p>

<h2>Paso 2: El Banner que Cuenta tu Historia</h2>

<p>El banner (imagen de portada) es el lienzo mas infrautilizado de LinkedIn. Usalo para comunicar tu propuesta de valor en una frase. Disena uno en Canva con tu tagline y contacto.</p>

<h2>Paso 3: El Titular que Vende (No el Cargo)</h2>

<p>El titular NO es tu cargo. Es tu propuesta de valor en 120 caracteres. El 99% de los usuarios pone Director Comercial en Empresa X. Eso no dice nada.</p>

<p><strong>Ejemplo que funciona:</strong> Ayudo a PYMEs a vender online sin gastar una fortuna | Experto en E-commerce y Marketing Digital | +100 proyectos</p>

<h2>Paso 4: El Extracto (Acerca de) que Engancha</h2>

<p>Las primeras 3 lineas son criticas porque son lo unico que se ve sin hacer clic. Estructura:</p>

<ol>
<li><strong>Linea 1:</strong> Problema que resuelves</li>
<li><strong>Linea 2:</strong> Para quien</li>
<li><strong>Linea 3:</strong> Tu credencial mas potente</li>
<li><strong>Resto:</strong> Tu historia, metodo y CTA</li>
</ol>

<h2>Paso 5: Experiencia con Resultados, No Funciones</h2>

<p>No listes funciones. Lista logros. No: Gestion de equipo comercial. Si: Lidere un equipo de 12 personas que incremento la facturacion un 34% en 18 meses.</p>

<h2>Paso 6: Las 5 Aptitudes Estrategicas</h2>

<p>LinkedIn permite hasta 50 aptitudes, pero las 3 primeras son las que importan para el algoritmo. Elige las 3 que mejor representan tu expertise y pide recomendaciones especificas para ellas.</p>

<h2>Paso 7: Recomendaciones que Validan</h2>

<p>Pide 3-5 recomendaciones a personas que hayan trabajado contigo. La clave: pideles que mencionen un resultado concreto, no solo que eres buena persona.</p>

<h2>Paso 8: La URL Personalizada</h2>

<p>Cambia tu URL de linkedin.com/in/a8f7g2k4 a linkedin.com/in/tu-nombre. Parece un detalle menor, pero transmite profesionalismo y facilita que te encuentren.</p>

<h2>Paso 9: Publicar Contenido (La Clave del Alcance)</h2>

<p>El algoritmo de LinkedIn premia la actividad. Publicar <strong>2-3 veces por semana</strong> es la frecuencia optima. Formatos que funcionan: listas de aprendizajes, historias personales de carrera, opiniones sobre tendencias de tu sector.</p>

<h2>Paso 10: Red Estrategica, No Masiva</h2>

<p>Conecta con personas relevantes, no con todo el mundo. Antes de conectar, personaliza el mensaje: menciona algo especifico de su perfil o contenido.</p>

<h2>Paso 11: Activar Modo Open to Work (Sin Verguenza)</h2>

<p>Puedes activar Open to Work de forma visible (con el marco verde) o solo visible para reclutadores. Para quienes buscan empleo activamente, la version visible genera <strong>40% mas contactos de reclutadores</strong>.</p>

<h2>Paso 12: Medir y Ajustar</h2>

<p>LinkedIn te da datos de quien ve tu perfil, el rendimiento de tus posts y las busquedas en las que apareces. Revisa estos datos semanalmente y ajusta tu estrategia.</p>

<h2>Conclusion</h2>

<p>LinkedIn no es un CV digital. Es tu escaparate profesional, tu red de oportunidades y tu plataforma de contenido. Con estos 12 pasos, estaras en el top 5% de perfiles de tu sector.</p>

<p>Si quieres ir mas lejos, el programa <strong>Empleabilidad Digital</strong> de la Plataforma de Ecosistemas Digitales esta disenado para profesionales que quieren reinventar su carrera usando herramientas digitales.</p>',
    'tags' => 'linkedin,empleo,marca-personal,networking,optimizacion-perfil',
    'reading_time' => 7,
    'meta_title' => 'Optimizar LinkedIn para Encontrar Trabajo en 2026 - 12 Pasos',
    'meta_description' => '12 pasos concretos para optimizar tu perfil de LinkedIn y encontrar trabajo. Guia practica por Pepe Jaraba, experto en empleabilidad digital.',
];

$articles[] = [
    'title' => 'Del Estres Corporativo al Emprendimiento Rural: 5 Lecciones del Caso Camino Viejo',
    'slug' => 'lecciones-caso-camino-viejo-emprendimiento-rural',
    'excerpt' => '5 lecciones clave del caso de Angel Martinez, que dejo la gran empresa para fundar Camino Viejo en Cazalla de la Sierra.',
    'body' => '<p>Cuando Angel Martinez dejo su trabajo corporativo para fundar <strong>Camino Viejo</strong> en Cazalla de la Sierra, muchos pensaron que estaba loco. Hoy, su negocio de gastrobiking y turismo activo es un referente en la Sierra Norte de Sevilla. Estas son las 5 lecciones que extraigo como consultor de su proceso de transformacion.</p>

<h2>Leccion 1: El Exito No Siempre Parece Exito</h2>

<p>Angel tenia todo lo que la sociedad considera exito: salario alto, grandes responsabilidades, estabilidad. Pero el coste personal era insostenible. <strong>La primera leccion es distinguir entre exito aparente y bienestar real.</strong></p>

<p>Muchos de mis clientes llegan con este mismo patron: han alcanzado metas profesionales que no les hacen felices. El primer paso siempre es reconocer que querer algo diferente no es un fracaso, es valentia.</p>

<h2>Leccion 2: Emprende desde el Territorio</h2>

<p>Angel no importo un modelo de negocio de la ciudad al campo. Observo su entorno, identifico una necesidad local (turismo activo en la Sierra Norte) y construyo algo organico, enraizado en el territorio.</p>

<p><strong>Tip para emprendedores rurales:</strong> No copies lo que funciona en Madrid. Preguntate: que tiene mi pueblo que no tiene otro? Esa respuesta es tu ventaja competitiva.</p>

<h2>Leccion 3: Minimiza la Inversion Inicial</h2>

<p>Una de las frases que Angel repite del programa es: <strong>Hay que emprender minimizando la inversion inicial</strong>. En lugar de pedir un prestamo para montar un local, empezo con lo minimo viable: rutas en bicicleta con paradas gastronomicas. Sin local, sin empleados, sin deuda.</p>

<p>Este enfoque lean le permitio validar la idea antes de escalar. Si la demanda hubiera sido nula, no habria perdido nada. Pero funciono.</p>

<h2>Leccion 4: La Burocracia No Es el Monstruo que Parece</h2>

<p>El miedo a la burocracia paraliza a miles de emprendedores potenciales. Angel lo vivio en primera persona. Lo que cambio fue desmitificar el proceso: <strong>realmente es cuestion de ser ordenado y apuntarlo todo</strong>.</p>

<p>En el programa Andalucia +ei trabajamos esto de forma practica: alta de autonomo paso a paso, facturacion basica, obligaciones trimestrales. Sin jerga legal, sin sustos.</p>

<h2>Leccion 5: Construye una Tribu, No Solo Clientes</h2>

<p>Lo mas inspirador de Camino Viejo no es el modelo de negocio, sino la <strong>comunidad</strong> que Angel ha construido. Sus clientes no son clientes: son una tribu de personas que comparten experiencias, risas y caminos.</p>

<p>Esta es quiza la leccion mas profunda: en el mundo digital, los negocios que crean comunidad superan a los que solo venden. El engagement de las redes de Camino Viejo lo demuestra: comentarios reales, fotos compartidas, repeticion.</p>

<h2>Conclusion: ES POSIBLE</h2>

<p>La frase que Angel repite al hablar de su experiencia es contundente: <strong>Ahora tengo conciencia de que ES POSIBLE</strong>. Y eso, al final, es lo que diferencia a los que emprenden de los que se quedan pensando.</p>

<p>Si te identificas con la historia de Angel, te invito a explorar el programa <strong>Emprendimiento Digital</strong> de la Plataforma de Ecosistemas Digitales. Sin humo, sin promesas vacias: herramientas reales para personas reales.</p>',
    'tags' => 'emprendimiento-rural,lecciones-aprendidas,camino-viejo,lean-startup,comunidad',
    'reading_time' => 6,
    'meta_title' => '5 Lecciones del Caso Camino Viejo - Emprendimiento Rural',
    'meta_description' => '5 lecciones clave del caso de Angel Martinez y Camino Viejo. Del estres corporativo al emprendimiento rural en Cazalla de la Sierra.',
];

// --- Crear BlogPosts ---
foreach ($articles as $i => $article) {
    $existing = $postStorage->loadByProperties(['slug' => $article['slug']]);
    if (!empty($existing)) {
        $post = reset($existing);
        $num = $i + 1;
        echo "-> Articulo #$num ya existe: '{$article['title']}' (ID: " . $post->id() . ")\n";
        continue;
    }

    $post = $postStorage->create([
        'title' => $article['title'],
        'slug' => $article['slug'],
        'excerpt' => $article['excerpt'],
        'body' => [
            'value' => $article['body'],
            'format' => 'full_html',
        ],
        'tags' => $article['tags'],
        'status' => 'published',
        'is_featured' => TRUE,
        'reading_time' => $article['reading_time'],
        'meta_title' => $article['meta_title'],
        'meta_description' => $article['meta_description'],
        'schema_type' => 'BlogPosting',
        'tenant_id' => 5,
        'uid' => 1,
        'published_at' => (new DrupalDateTime())->format('Y-m-d\TH:i:s'),
    ]);

    $post->save();
    $num = $i + 1;
    echo "OK Articulo #$num creado: '{$article['title']}' (ID: " . $post->id() . ")\n";
}

echo "\nScript completado. 3 articulos de blog creados/verificados.\n";
