#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * @file validate-twig-ortografia.php
 *
 * ORTOGRAFIA-TRANS-001: Detecta errores ortograficos (tildes faltantes) en
 * bloques {% trans %} de templates Twig.
 *
 * En un SaaS multi-idioma, cada {% trans %} es una clave de traduccion.
 * "Atencion" y "Atención" son claves DISTINTAS. Un texto sin tilde genera
 * una clave huerfana que no coincide con la traduccion correcta.
 *
 * USO:
 *   php scripts/validation/validate-twig-ortografia.php            # Todo el proyecto
 *   php scripts/validation/validate-twig-ortografia.php <archivo>  # Un archivo concreto
 *
 * EXIT CODES:
 *   0 = PASS (sin errores ortograficos)
 *   1 = FAIL (errores detectados)
 */

$project_root = dirname(__DIR__, 2);

// ── Diccionario: palabra_incorrecta => palabra_correcta ──────────────────
// Solo palabras frecuentes en interfaces SaaS/admin en espanol.
// Organizadas por patron morfologico para facilitar mantenimiento.
$diccionario = [
  // -cion → -ción (sustantivos femeninos: terminacion mas comun)
  'Accion' => 'Acción',
  'Activacion' => 'Activación',
  'Actualizacion' => 'Actualización',
  'Administracion' => 'Administración',
  'Aplicacion' => 'Aplicación',
  'Asignacion' => 'Asignación',
  'Atencion' => 'Atención',
  'Autenticacion' => 'Autenticación',
  'Autorizacion' => 'Autorización',
  'Calificacion' => 'Calificación',
  'Cancelacion' => 'Cancelación',
  'Certificacion' => 'Certificación',
  'Clasificacion' => 'Clasificación',
  'Colaboracion' => 'Colaboración',
  'Comunicacion' => 'Comunicación',
  'Condicion' => 'Condición',
  'Configuracion' => 'Configuración',
  'Confirmacion' => 'Confirmación',
  'Conexion' => 'Conexión',
  'Contratacion' => 'Contratación',
  'Coordinacion' => 'Coordinación',
  'Correccion' => 'Corrección',
  'Creacion' => 'Creación',
  'Dedicacion' => 'Dedicación',
  'Derivacion' => 'Derivación',
  'Descripcion' => 'Descripción',
  'Deteccion' => 'Detección',
  'Direccion' => 'Dirección',
  'Distribucion' => 'Distribución',
  'Documentacion' => 'Documentación',
  'Duracion' => 'Duración',
  'Edicion' => 'Edición',
  'Educacion' => 'Educación',
  'Ejecucion' => 'Ejecución',
  'Eliminacion' => 'Eliminación',
  'Evaluacion' => 'Evaluación',
  'Excepcion' => 'Excepción',
  'Exportacion' => 'Exportación',
  'Facturacion' => 'Facturación',
  'Formacion' => 'Formación',
  'Funcion' => 'Función',
  'Gestion' => 'Gestión',
  'Identificacion' => 'Identificación',
  'Importacion' => 'Importación',
  'Informacion' => 'Información',
  'Inscripcion' => 'Inscripción',
  'Insercion' => 'Inserción',
  'Instalacion' => 'Instalación',
  'Instruccion' => 'Instrucción',
  'Integracion' => 'Integración',
  'Intencion' => 'Intención',
  'Intervencion' => 'Intervención',
  'Justificacion' => 'Justificación',
  'Localizacion' => 'Localización',
  'Migracion' => 'Migración',
  'Modificacion' => 'Modificación',
  'Motivacion' => 'Motivación',
  'Navegacion' => 'Navegación',
  'Notificacion' => 'Notificación',
  'Operacion' => 'Operación',
  'Opcion' => 'Opción',
  'Orientacion' => 'Orientación',
  'Paginacion' => 'Paginación',
  'Participacion' => 'Participación',
  'Planificacion' => 'Planificación',
  'Posicion' => 'Posición',
  'Prevencion' => 'Prevención',
  'Produccion' => 'Producción',
  'Programacion' => 'Programación',
  'Prospeccion' => 'Prospección',
  'Proteccion' => 'Protección',
  'Publicacion' => 'Publicación',
  'Realizacion' => 'Realización',
  'Recepcion' => 'Recepción',
  'Reduccion' => 'Reducción',
  'Relacion' => 'Relación',
  'Resolucion' => 'Resolución',
  'Revision' => 'Revisión',
  'Seccion' => 'Sección',
  'Seleccion' => 'Selección',
  'Situacion' => 'Situación',
  'Solucion' => 'Solución',
  'Subvencion' => 'Subvención',
  'Suscripcion' => 'Suscripción',
  'Ubicacion' => 'Ubicación',
  'Validacion' => 'Validación',
  'Verificacion' => 'Verificación',
  'Version' => 'Versión',
  // -sion → -sión
  'Sesion' => 'Sesión',
  'Comision' => 'Comisión',
  'Decision' => 'Decisión',
  'Dimension' => 'Dimensión',
  'Emision' => 'Emisión',
  'Expansion' => 'Expansión',
  'Extension' => 'Extensión',
  'Inclusion' => 'Inclusión',
  'Ocasion' => 'Ocasión',
  'Pension' => 'Pensión',
  'Precision' => 'Precisión',
  'Profesion' => 'Profesión',
  'Progresion' => 'Progresión',
  'Provision' => 'Provisión',
  'Tension' => 'Tensión',
  'Transmision' => 'Transmisión',
  // -ico/-ica → -ístico/-ística (esdrujulas frecuentes)
  'Diagnostico' => 'Diagnóstico',
  'Economico' => 'Económico',
  'Economica' => 'Económica',
  'Estadistico' => 'Estadístico',
  'Estadistica' => 'Estadística',
  'Historico' => 'Histórico',
  'Historica' => 'Histórica',
  'Logistico' => 'Logístico',
  'Logistica' => 'Logística',
  'Tecnico' => 'Técnico',
  'Tecnica' => 'Técnica',
  'Publico' => 'Público',
  'Publica' => 'Pública',
  'Automatico' => 'Automático',
  'Automatica' => 'Automática',
  'Periodico' => 'Periódico',
  'Periodica' => 'Periódica',
  // -ica/-icas (esdrujulas)
  'Metricas' => 'Métricas',
  'Metrica' => 'Métrica',
  'Practica' => 'Práctica',
  'Practicas' => 'Prácticas',
  // Nombres propios y topónimos
  'Andalucia' => 'Andalucía',
  'Malaga' => 'Málaga',
  'Cadiz' => 'Cádiz',
  'Cordoba' => 'Córdoba',
  'Jaen' => 'Jaén',
  'Almeria' => 'Almería',
  // Otros frecuentes
  'Numero' => 'Número',
  'Pagina' => 'Página',
  'Telefono' => 'Teléfono',
  'Credito' => 'Crédito',
  'Codigo' => 'Código',
  'Catalogo' => 'Catálogo',
  'Modulo' => 'Módulo',
  'Metodo' => 'Método',
  'Periodo' => 'Período',
  'Proposito' => 'Propósito',
  'Articulo' => 'Artículo',
  'Vehiculo' => 'Vehículo',
  'Curriculo' => 'Currículo',
  'Curriculum' => 'Currículum',
  'Calculo' => 'Cálculo',
  'Analisis' => 'Análisis',
  'Exito' => 'Éxito',
  'Indice' => 'Índice',
  'Limite' => 'Límite',
  'Minimo' => 'Mínimo',
  'Maximo' => 'Máximo',
  'Ultimo' => 'Último',
  'Unico' => 'Único',
  'Valido' => 'Válido',
  'Basico' => 'Básico',
  'Basica' => 'Básica',
  // ── ñ: palabras frecuentes donde se sustituye ñ por n ──────────────
  'Espana' => 'España',
  'Espanol' => 'Español',
  'Espanola' => 'Española',
  'Espanoles' => 'Españoles',
  'Ensenanza' => 'Enseñanza',
  'Ensenanzas' => 'Enseñanzas',
  'Desempeno' => 'Desempeño',
  'Diseno' => 'Diseño',
  'Empeno' => 'Empeño',
  'Companero' => 'Compañero',
  'Companera' => 'Compañera',
  'Companeros' => 'Compañeros',
  'Companeras' => 'Compañeras',
  'Compania' => 'Compañía',
  'Companias' => 'Compañías',
  'Pena' => 'Peña',
  'Nino' => 'Niño',
  'Nina' => 'Niña',
  'Ninos' => 'Niños',
  'Ninas' => 'Niñas',
  'Ano' => 'Año',
  'Anos' => 'Años',
  'Anual' => 'Anual', // Correcta, no confundir
  'Otono' => 'Otoño',
  'Sueno' => 'Sueño',
  'Dueno' => 'Dueño',
  'Pequeno' => 'Pequeño',
  'Pequena' => 'Pequeña',
  'Tamano' => 'Tamaño',
  'Banera' => 'Bañera',
  'Cana' => 'Caña',
  'Montana' => 'Montaña',
  'Campana' => 'Campaña',
  'Campanas' => 'Campañas',
  'Acompanamiento' => 'Acompañamiento',
  'Acompanante' => 'Acompañante',
  'Resena' => 'Reseña',
  'Resenas' => 'Reseñas',
  'Contrasena' => 'Contraseña',
  'Contrasenas' => 'Contraseñas',
  'Senal' => 'Señal',
  'Senales' => 'Señales',
  'Senor' => 'Señor',
  'Senora' => 'Señora',
];

// Tambien generar versiones en minuscula (para textos como "formacion").
$diccionario_lower = [];
foreach ($diccionario as $wrong => $correct) {
  $diccionario_lower[mb_strtolower($wrong)] = mb_strtolower($correct);
}
$diccionario = array_merge($diccionario, $diccionario_lower);

// ── Directorios a escanear ──────────────────────────────────────────────
$template_dirs = [
  $project_root . '/web/themes/custom/ecosistema_jaraba_theme/templates',
  $project_root . '/web/modules/custom',
];

// ── Modo archivo individual ─────────────────────────────────────────────
$single_file = $argv[1] ?? null;
if ($single_file && is_file($single_file)) {
  $files = [new SplFileInfo($single_file)];
} else {
  $files = [];
  foreach ($template_dirs as $dir) {
    if (!is_dir($dir)) {
      continue;
    }
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($iterator as $file) {
      if ($file->getExtension() === 'twig') {
        $files[] = $file;
      }
    }
  }
}

// ── Regex para extraer contenido dentro de {% trans %}...{% endtrans %} ──
// Soporta bloques single-line y multi-line.
$trans_pattern = '/\{%\s*trans\s*%\}(.*?)\{%\s*endtrans\s*%\}/su';

// ── Escaneo ─────────────────────────────────────────────────────────────
$errors = [];
$seen_generic = [];
$checked = 0;

foreach ($files as $file) {
  $filepath = $file instanceof SplFileInfo ? $file->getPathname() : (string) $file;
  if (!is_file($filepath)) {
    continue;
  }

  $content = file_get_contents($filepath);
  $relative = str_replace($project_root . '/', '', $filepath);
  $checked++;

  // Eliminar comentarios Twig {# ... #} antes de buscar trans blocks.
  // Evita falsos positivos cuando documentacion menciona {% trans %}.
  $content_no_comments = preg_replace('/\{#.*?#\}/su', '', $content);

  // Extraer todos los bloques {% trans %}...{% endtrans %} (sin comentarios).
  if (!preg_match_all($trans_pattern, $content_no_comments, $matches, PREG_OFFSET_CAPTURE)) {
    continue;
  }

  foreach ($matches[1] as $match) {
    $trans_text = $match[0];
    $offset = $match[1];
    // Calcular linea sobre contenido sin comentarios (aproximado pero util).
    $line_number = substr_count($content_no_comments, "\n", 0, $offset) + 1;
    // Limitar contexto a 80 chars para legibilidad.
    $context = mb_substr(trim($trans_text), 0, 80);

    // 1. Buscar palabras del diccionario (tildes + ñ).
    foreach ($diccionario as $wrong => $correct) {
      $word_pattern = '/\b' . preg_quote($wrong, '/') . '\b/u';
      if (preg_match($word_pattern, $trans_text)) {
        $errors[] = [
          'file' => $relative,
          'line' => $line_number,
          'wrong' => $wrong,
          'correct' => $correct,
          'context' => $context,
        ];
      }
    }

    // 2. Patron generico: -cion sin tilde (captura palabras no en diccionario).
    if (preg_match_all('/\b([A-Za-záéíóúñÁÉÍÓÚÑ]*[cC]ion)\b/u', $trans_text, $generic_matches)) {
      foreach ($generic_matches[1] as $word) {
        if (mb_strpos($word, 'ción') === false && mb_strpos($word, 'Ción') === false) {
          $key = $relative . ':' . $line_number . ':' . mb_strtolower($word);
          if (!isset($seen_generic[$key])) {
            $seen_generic[$key] = true;
            $suggested = preg_replace('/cion$/u', 'ción', $word);
            $suggested = preg_replace('/Cion$/u', 'Ción', $suggested);
            $errors[] = [
              'file' => $relative,
              'line' => $line_number,
              'wrong' => $word,
              'correct' => $suggested,
              'context' => $context,
            ];
          }
        }
      }
    }

    // 3. Patron generico: -sion sin tilde.
    if (preg_match_all('/\b([A-Za-záéíóúñÁÉÍÓÚÑ]*[sS]ion)\b/u', $trans_text, $generic_matches)) {
      foreach ($generic_matches[1] as $word) {
        if (mb_strpos($word, 'sión') === false && mb_strpos($word, 'Sión') === false) {
          $key = $relative . ':' . $line_number . ':' . mb_strtolower($word);
          if (!isset($seen_generic[$key])) {
            $seen_generic[$key] = true;
            $suggested = preg_replace('/sion$/u', 'sión', $word);
            $suggested = preg_replace('/Sion$/u', 'Sión', $suggested);
            $errors[] = [
              'file' => $relative,
              'line' => $line_number,
              'wrong' => $word,
              'correct' => $suggested,
              'context' => $context,
            ];
          }
        }
      }
    }
  }
}

// Deduplicar errores (misma linea, misma palabra).
$seen = [];
$unique_errors = [];
foreach ($errors as $error) {
  $key = $error['file'] . ':' . $error['line'] . ':' . mb_strtolower($error['wrong']);
  if (!isset($seen[$key])) {
    $seen[$key] = true;
    $unique_errors[] = $error;
  }
}
$errors = $unique_errors;

// ── Reportar ────────────────────────────────────────────────────────────
echo "ORTOGRAFIA-TRANS-001: Ortografia en bloques {% trans %} de Twig\n";
echo str_repeat('=', 70) . "\n";
echo "Templates verificados: {$checked}\n";
echo "Diccionario: " . count($diccionario) . " entradas\n";

if (empty($errors)) {
  echo "\n\033[32mPASS\033[0m — Sin errores ortograficos en textos traducibles.\n";
  exit(0);
}

echo "\n\033[31mFAIL\033[0m — " . count($errors) . " error(es) ortografico(s) detectado(s):\n\n";

foreach ($errors as $error) {
  echo "  \033[33m{$error['file']}:{$error['line']}\033[0m\n";
  echo "    \033[31m{$error['wrong']}\033[0m → \033[32m{$error['correct']}\033[0m\n";
  echo "    Contexto: {$error['context']}\n\n";
}

echo "SOLUCION: Corregir las tildes en los bloques {% trans %}.\n";
echo "  Cada {% trans %} con tilde incorrecta crea una clave de traduccion huerfana.\n";
echo "  Diccionario: scripts/validation/validate-twig-ortografia.php\n";

exit(1);
