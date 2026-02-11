/**
 * @file
 * Script de generación de CSS crítico para optimización de Core Web Vitals.
 *
 * PROPÓSITO
 * =========
 * Este script extrae el CSS necesario para renderizar el contenido "above the fold"
 * (visible sin scroll) de las páginas principales del sitio. El CSS extraído se
 * guarda en archivos separados que luego se inyectan inline en el <head>.
 *
 * ¿POR QUÉ ES NECESARIO?
 * ======================
 * Los archivos CSS del theme (~778KB) bloquean el renderizado de la página.
 * Al inyectar solo el CSS crítico (~30-50KB) inline y cargar el resto de forma
 * asíncrona, mejoramos significativamente las métricas LCP y FCP.
 *
 * CÓMO EJECUTAR
 * =============
 * Desde el directorio del theme (ecosistema_jaraba_theme):
 *
 *   npm run build:critical
 *
 * O directamente:
 *
 *   node scripts/generate-critical.js
 *
 * REQUISITOS
 * ==========
 * - Node.js 18+ (para Puppeteer)
 * - El sitio debe estar corriendo en https://jaraba-saas.lndo.site
 * - Sesión autenticada si se generan páginas de admin
 *
 * ARCHIVOS GENERADOS
 * ==================
 * css/critical/
 * ├── homepage.css        # Página de inicio
 * ├── templates.css       # Selector de plantillas Page Builder
 * ├── landing-empleo.css  # Landings verticales (empleo, talento, etc.)
 * └── admin-pages.css     # Dashboards administrativos
 *
 * FRECUENCIA DE REGENERACIÓN
 * ==========================
 * Debe ejecutarse:
 * - Después de cambios significativos en SCSS
 * - Antes de cada deploy a producción
 * - Si se añaden nuevas rutas importantes
 *
 * @see docs/planificacion/20260202-Auditoria_Plan_Elevacion_Clase_Mundial_v1.md
 * @author Jaraba Impact Platform SaaS
 */

// critical v6+ is ESM-only; use dynamic import for Node CJS compat.
const fs = require('fs');
const path = require('path');
let critical;
async function loadCritical() {
  if (!critical) {
    critical = await import('critical');
  }
  return critical;
}

// ============================================================================
// CONFIGURACIÓN DE RUTAS A PROCESAR
// ============================================================================
// Cada entrada define una ruta del sitio y el nombre del archivo CSS resultante.
// El viewport (1300x900) simula un escritorio típico para capturar el above-the-fold.

const RUTAS_CRITICAS = [
    {
        // Página de inicio - La más importante para SEO y primera impresión.
        nombre: 'homepage',
        url: 'https://jaraba-saas.lndo.site/es',
        descripcion: 'Página de inicio con hero, features y CTAs principales'
    },
    {
        // Selector de plantillas del Page Builder.
        nombre: 'templates',
        url: 'https://jaraba-saas.lndo.site/es/page-builder/templates',
        descripcion: 'Galería de plantillas con cards y previews'
    },
    {
        // Landing de vertical Empleabilidad (representativa de todas las landings).
        nombre: 'landing-empleo',
        url: 'https://jaraba-saas.lndo.site/es/empleo',
        descripcion: 'Landing vertical con hero, beneficios y formularios'
    },
    {
        // Dashboard de gestión de páginas (representativo de dashboards admin).
        nombre: 'admin-pages',
        url: 'https://jaraba-saas.lndo.site/es/admin/content/pages',
        descripcion: 'Dashboard con tabla, acciones y slide-panel'
    },
];

// ============================================================================
// CONFIGURACIÓN DEL VIEWPORT Y OPCIONES
// ============================================================================

const CONFIGURACION = {
    // Dimensiones del viewport para extracción.
    // 1300x900 representa un monitor típico de escritorio.
    ancho: 1300,
    alto: 900,

    // Timeout para renderizado de página (ms).
    // Aumentar si las páginas tardan en cargar.
    timeout: 60000,

    // Directorio de salida relativo al script.
    directorioSalida: '../css/critical',
};

// ============================================================================
// FUNCIÓN PRINCIPAL DE GENERACIÓN
// ============================================================================

/**
 * Genera los archivos CSS críticos para todas las rutas configuradas.
 *
 * Proceso:
 * 1. Crea el directorio de salida si no existe.
 * 2. Para cada ruta, lanza Puppeteer para renderizar la página.
 * 3. Extrae el CSS necesario para el viewport especificado.
 * 4. Guarda el resultado en un archivo .css.
 */
async function generarCssCritico() {
    console.log('═══════════════════════════════════════════════════════════');
    console.log('  JARABA PERFORMANCE - Generador de CSS Crítico');
    console.log('═══════════════════════════════════════════════════════════\n');

    // Asegurar que existe el directorio de salida.
    const dirSalida = path.resolve(__dirname, CONFIGURACION.directorioSalida);
    if (!fs.existsSync(dirSalida)) {
        fs.mkdirSync(dirSalida, { recursive: true });
        console.log(`📁 Directorio creado: ${dirSalida}\n`);
    }

    // Contadores para resumen final.
    let exitosos = 0;
    let fallidos = 0;

    // Procesar cada ruta configurada.
    for (const ruta of RUTAS_CRITICAS) {
        console.log(`\n🔍 Procesando: ${ruta.nombre}`);
        console.log(`   URL: ${ruta.url}`);
        console.log(`   Descripción: ${ruta.descripcion}`);

        try {
            // Generar CSS crítico usando la librería 'critical'.
            const criticalModule = await loadCritical();
            const generate = criticalModule.generate || criticalModule.default?.generate;
            const resultado = await generate({
                src: ruta.url,
                width: CONFIGURACION.ancho,
                height: CONFIGURACION.alto,
                inline: false,  // No queremos HTML, solo CSS.
                extract: false, // No eliminar CSS del archivo original.
                penthouse: {
                    timeout: CONFIGURACION.timeout,
                    puppeteer: {
                        // Opciones de Puppeteer para entornos sin sandbox (Docker, CI).
                        // ignoreHTTPSErrors: certificados auto-firmados de Lando/DDEV.
                        args: ['--no-sandbox', '--disable-setuid-sandbox', '--ignore-certificate-errors']
                    }
                },
                // Aceptar certificados auto-firmados (Lando dev environment).
                request: { https: { rejectUnauthorized: false } },
            });

            // Guardar el CSS extraído.
            const archivoSalida = path.join(dirSalida, `${ruta.nombre}.css`);
            fs.writeFileSync(archivoSalida, resultado.css);

            // Calcular tamaño para mostrar en consola.
            const tamanoKb = (Buffer.byteLength(resultado.css, 'utf8') / 1024).toFixed(2);
            console.log(`   ✅ Generado: ${ruta.nombre}.css (${tamanoKb} KB)`);

            exitosos++;

        } catch (error) {
            console.error(`   ❌ Error: ${error.message}`);
            fallidos++;
        }
    }

    // Resumen final.
    console.log('\n═══════════════════════════════════════════════════════════');
    console.log(`  RESUMEN: ${exitosos} exitosos, ${fallidos} fallidos`);
    console.log('═══════════════════════════════════════════════════════════\n');

    if (fallidos > 0) {
        console.log('⚠️  Algunos archivos no se generaron. Verifica que el sitio esté accesible.\n');
        process.exit(1);
    }
}

// ============================================================================
// EJECUCIÓN
// ============================================================================

generarCssCritico().catch(error => {
    console.error('\n💥 Error fatal:', error.message);
    process.exit(1);
});
