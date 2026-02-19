<?php

/**
 * @file
 * Script para generar datos de prueba SEPE.
 *
 * Ejecutar con: drush php:script sepe_test_data.php
 */

use Drupal\Core\Entity\EntityTypeManagerInterface;

// Verificar que estamos en Drupal.
if (!defined('DRUPAL_ROOT')) {
    throw new \RuntimeException('Este script debe ejecutarse con Drush.');
}

$entityTypeManager = \Drupal::entityTypeManager();

// ============================================
// 1. CREAR CENTRO SEPE
// ============================================
echo "Creando centro SEPE de prueba...\n";

$centro = $entityTypeManager->getStorage('sepe_centro')->create([
    'cif' => 'B12345678',
    'razon_social' => 'Jaraba Formación S.L.',
    'codigo_sepe' => 'AN0001234',
    'tipo_registro' => 'inscripcion',
    'direccion' => 'Calle Ejemplo, 123',
    'codigo_postal' => '41001',
    'municipio' => 'Sevilla',
    'provincia' => 'Sevilla',
    'telefono' => '954123456',
    'email' => 'formacion@jarabaosc.com',
    'url_plataforma' => 'https://plataformadeecosistemas.com',
    'url_seguimiento' => 'https://plataformadeecosistemas.com/sepe/ws/seguimiento',
    'is_active' => TRUE,
]);

$centro->save();
$centro_id = $centro->id();
echo "✅ Centro SEPE creado: ID $centro_id\n";

// Configurar como centro activo.
$config = \Drupal::configFactory()->getEditable('jaraba_sepe_teleformacion.settings');
$config->set('centro_activo_id', $centro_id);
$config->save();
echo "✅ Centro configurado como activo\n";

// ============================================
// 2. CREAR ACCIÓN FORMATIVA
// ============================================
echo "\nCreando acción formativa de prueba...\n";

$accion = $entityTypeManager->getStorage('sepe_accion_formativa')->create([
    'id_accion_sepe' => 'AF2026-001-JARABA',
    'centro_id' => $centro_id,
    'denominacion' => 'Competencias Digitales para el Empleo',
    'codigo_especialidad' => 'ADGG057PO',
    'modalidad' => 'T',
    'numero_horas' => 60,
    'fecha_inicio' => date('Y-m-d'),
    'fecha_fin' => date('Y-m-d', strtotime('+2 months')),
    'estado' => 'en_curso',
    'num_participantes_max' => 25,
]);

$accion->save();
$accion_id = $accion->id();
echo "✅ Acción formativa creada: ID $accion_id\n";
echo "   ID SEPE: AF2026-001-JARABA\n";

// ============================================
// 3. CREAR PARTICIPANTE DE PRUEBA
// ============================================
echo "\nCreando participante de prueba...\n";

$participante = $entityTypeManager->getStorage('sepe_participante')->create([
    'accion_id' => $accion_id,
    'dni' => '12345678A',
    'nombre' => 'Usuario',
    'apellidos' => 'Prueba SEPE',
    'fecha_alta' => date('Y-m-d'),
    'estado' => 'activo',
    'horas_conectado' => 5.5,
    'porcentaje_progreso' => 25,
    'num_actividades' => 3,
    'nota_media' => 7.5,
]);

$participante->save();
$participante_id = $participante->id();
echo "✅ Participante creado: ID $participante_id\n";
echo "   DNI: 12345678A\n";

// ============================================
// 4. RESUMEN
// ============================================
echo "\n========================================\n";
echo "DATOS DE PRUEBA SEPE CREADOS\n";
echo "========================================\n";
echo "Centro ID: $centro_id (B12345678 - Jaraba Formación S.L.)\n";
echo "Acción ID: $accion_id (AF2026-001-JARABA)\n";
echo "Participante ID: $participante_id (DNI: 12345678A)\n";
echo "\nPara validar con el kit SEPE:\n";
echo "- idAccion: AF2026-001-JARABA\n";
echo "- dni: 12345678A\n";
echo "========================================\n";

echo "\n✅ Datos de prueba creados correctamente.\n";
echo "Ahora puede ejecutar el kit de autoevaluación SEPE.\n";
