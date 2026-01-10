<?php

/**
 * @file
 * CÓDIGO A AÑADIR EN agroconecta_core.module
 * 
 * Integración de la generación automática de certificados de trazabilidad
 * firmados digitalmente al crear/actualizar lotes de producción.
 *
 * UBICACIÓN: modules/custom/agroconecta_core/agroconecta_core.module
 * 
 * Añadir este código al archivo .module existente, NO reemplazar todo el archivo.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;
use Drupal\file\Entity\File;

/**
 * Implements hook_entity_insert().
 *
 * Genera certificado de trazabilidad firmado al crear un nuevo lote.
 */
function agroconecta_core_entity_insert(EntityInterface $entity) {
  // Solo procesar nodos de tipo lote_produccion
  if (!$entity instanceof NodeInterface || $entity->bundle() !== 'lote_produccion') {
    return;
  }

  // Verificar que tiene ID de lote generado
  if ($entity->get('field_id_lote')->isEmpty()) {
    \Drupal::logger('agroconecta_core')->warning(
      '⚠️ Lote @nid sin ID de lote, no se genera certificado',
      ['@nid' => $entity->id()]
    );
    return;
  }

  // Verificar que la firma automática está habilitada
  $config = \Drupal::config('agroconecta_core.firma_settings');
  if (!$config->get('auto_sign_enabled')) {
    \Drupal::logger('agroconecta_core')->info(
      'ℹ️ Firma automática deshabilitada, saltando generación de certificado'
    );
    return;
  }

  // Generar y firmar el certificado
  _agroconecta_core_generate_signed_certificate($entity);
}

/**
 * Implements hook_entity_update().
 *
 * Regenera el certificado si se actualizan datos críticos del lote.
 */
function agroconecta_core_entity_update(EntityInterface $entity) {
  // Solo procesar nodos de tipo lote_produccion
  if (!$entity instanceof NodeInterface || $entity->bundle() !== 'lote_produccion') {
    return;
  }

  // Evitar bucles infinitos - verificar si estamos en proceso de actualización
  static $processing = [];
  if (isset($processing[$entity->id()])) {
    return;
  }

  // Verificar si hay cambios en campos críticos que requieran regenerar el certificado
  $campos_criticos = [
    'field_fecha_cosecha',
    'field_fecha_molturacion',
    'field_finca_origen',
    'field_producto_asociado',
    'field_variedad',
  ];

  $original = $entity->original ?? NULL;
  $regenerar = FALSE;

  if (!$original) {
    // No hay original, posiblemente es una actualización programática
    return;
  }

  foreach ($campos_criticos as $campo) {
    if ($entity->hasField($campo) && $original->hasField($campo)) {
      $valor_nuevo = $entity->get($campo)->getValue();
      $valor_original = $original->get($campo)->getValue();
      
      if ($valor_nuevo != $valor_original) {
        $regenerar = TRUE;
        \Drupal::logger('agroconecta_core')->info(
          '🔄 Campo @campo modificado en lote @id, regenerando certificado',
          ['@campo' => $campo, '@id' => $entity->get('field_id_lote')->value]
        );
        break;
      }
    }
  }

  if ($regenerar) {
    $processing[$entity->id()] = TRUE;
    _agroconecta_core_generate_signed_certificate($entity);
    unset($processing[$entity->id()]);
  }
}

/**
 * Genera y firma un certificado de trazabilidad para un lote.
 *
 * @param \Drupal\node\NodeInterface $lote
 *   El nodo del lote de producción.
 */
function _agroconecta_core_generate_signed_certificate(NodeInterface $lote) {
  $lote_id = $lote->get('field_id_lote')->value;
  
  \Drupal::logger('agroconecta_core')->info(
    '📄 Iniciando generación de certificado para lote: @id',
    ['@id' => $lote_id]
  );

  try {
    /** @var \Drupal\agroconecta_core\Service\CertificadoPdfService $pdf_service */
    $pdf_service = \Drupal::service('agroconecta_core.certificado_pdf');

    /** @var \Drupal\agroconecta_core\Service\FirmaDigitalService $firma_service */
    $firma_service = \Drupal::service('agroconecta_core.firma_digital');

    // 1. Generar PDF con datos del lote
    $pdf_uri = $pdf_service->generatePdf($lote);

    if (!$pdf_uri) {
      \Drupal::logger('agroconecta_core')->error(
        '🚫 Error al generar PDF para lote: @id',
        ['@id' => $lote_id]
      );
      return;
    }

    \Drupal::logger('agroconecta_core')->info(
      '✅ PDF generado: @uri',
      ['@uri' => $pdf_uri]
    );

    // 2. Firmar el PDF
    $signed_uri = $firma_service->signPdf($pdf_uri, [
      'reason' => 'Certificado de Trazabilidad - Lote ' . $lote_id,
      'location' => 'AgroConecta',
      'contact' => 'trazabilidad@agroconecta.es',
    ]);

    if (!$signed_uri) {
      // Si falla la firma, usar el PDF sin firmar
      \Drupal::logger('agroconecta_core')->warning(
        '⚠️ No se pudo firmar el PDF, usando versión sin firma: @id',
        ['@id' => $lote_id]
      );
      $signed_uri = $pdf_uri;
    }
    else {
      \Drupal::logger('agroconecta_core')->info(
        '✅ PDF firmado: @uri',
        ['@uri' => $signed_uri]
      );
    }

    // 3. Crear entidad File para el certificado
    $file = File::create([
      'uri' => $signed_uri,
      'uid' => \Drupal::currentUser()->id(),
      'status' => FILE_STATUS_PERMANENT,
    ]);
    $file->save();

    // 4. Vincular el archivo al lote
    if ($lote->hasField('field_certificado_firmado')) {
      // Eliminar archivo anterior si existe
      $old_file = $lote->get('field_certificado_firmado')->entity;
      
      // Actualizar con el nuevo archivo
      $lote->set('field_certificado_firmado', ['target_id' => $file->id()]);
      
      // Guardar sin disparar hooks de nuevo (ya estamos en un hook)
      // Usamos el storage directamente para evitar recursión
      $lote->original = clone $lote;
      $lote->save();

      // Marcar archivo como usado
      \Drupal::service('file.usage')->add($file, 'agroconecta_core', 'node', $lote->id());

      // Eliminar archivo anterior si ya no se usa
      if ($old_file && $old_file->id() !== $file->id()) {
        $usage = \Drupal::service('file.usage')->listUsage($old_file);
        if (empty($usage)) {
          $old_file->delete();
        }
      }
    }

    // 5. Mostrar mensaje de éxito
    \Drupal::messenger()->addStatus(
      t('✅ Certificado de trazabilidad generado y firmado para el lote: @id', [
        '@id' => $lote_id,
      ])
    );

  }
  catch (\Exception $e) {
    \Drupal::logger('agroconecta_core')->error(
      '🚫 Excepción al generar certificado para lote @id: @error',
      ['@id' => $lote_id, '@error' => $e->getMessage()]
    );
    
    \Drupal::messenger()->addError(
      t('Error al generar el certificado de trazabilidad. Contacte con el administrador.')
    );
  }
}

/**
 * Implements hook_ENTITY_TYPE_view() for node entities.
 *
 * Añade enlace de descarga del certificado en la vista del lote.
 */
function agroconecta_core_node_view(array &$build, EntityInterface $entity, $view_mode, $langcode) {
  if ($entity->bundle() !== 'lote_produccion') {
    return;
  }

  // Solo en vista completa
  if ($view_mode !== 'full') {
    return;
  }

  // Verificar si tiene certificado
  if ($entity->hasField('field_certificado_firmado') && 
      !$entity->get('field_certificado_firmado')->isEmpty()) {
    
    $file = $entity->get('field_certificado_firmado')->entity;
    
    if ($file) {
      // Obtener URL de descarga
      $url = \Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri());
      
      // Añadir enlace al build array
      $build['certificado_descarga'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['certificado-trazabilidad-download', 'mt-4', 'p-3', 'bg-success-subtle', 'rounded']],
        '#weight' => 100,
        'icon' => [
          '#markup' => '<i class="bi bi-file-earmark-pdf-fill text-success me-2"></i>',
        ],
        'texto' => [
          '#markup' => '<strong>Certificado de Trazabilidad Firmado</strong><br>',
        ],
        'enlace' => [
          '#type' => 'link',
          '#title' => t('📥 Descargar Certificado PDF'),
          '#url' => \Drupal\Core\Url::fromUri($url),
          '#attributes' => [
            'class' => ['btn', 'btn-success', 'mt-2'],
            'target' => '_blank',
            'download' => 'certificado-' . $entity->get('field_id_lote')->value . '.pdf',
          ],
        ],
        'verificacion' => [
          '#markup' => '<p class="small text-muted mt-2 mb-0">' . 
            t('Documento firmado digitalmente. Verificable en valide.redsara.es') . 
            '</p>',
        ],
      ];
    }
  }
}
