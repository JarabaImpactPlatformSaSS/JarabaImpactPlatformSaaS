<?php

namespace Drupal\ecosistema_jaraba_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador para gestionar la firma electrónica con AutoFirma.
 *
 * Este controlador proporciona endpoints para:
 * - Generar documentos PDF para firma
 * - Recibir documentos firmados
 * - Validar firmas
 * - Consultar estado de documentos
 */
class AutoFirmaController extends ControllerBase
{

  /**
   * Sistema de archivos.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  // NOTA: No se redeclara $currentUser porque ya está definida en ControllerBase.
  // PHP 8.4 no permite redefinir propiedades heredadas con tipo explícito.

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    $instance = parent::create($container);
    $instance->fileSystem = $container->get('file_system');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * Obtiene el documento PDF para firmar.
   *
   * Endpoint: GET /api/v1/autofirma/documento/{document_id}
   *
   * @param string $document_id
   *   ID del documento a firmar.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con el documento en Base64 y metadatos.
   */
  public function getDocumento(string $document_id, Request $request): JsonResponse
  {
    // Validar que el usuario tiene permiso
    if (!$this->currentUser->isAuthenticated()) {
      throw new AccessDeniedHttpException('Usuario no autenticado');
    }

    try {
      // Cargar el nodo del documento
      $node = $this->loadDocumentoNode($document_id);

      if (!$node) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Documento no encontrado',
        ], 404);
      }

      // Verificar que el usuario puede firmar este documento
      if (!$this->canUserSign($node)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No tiene permiso para firmar este documento',
        ], 403);
      }

      // Verificar estado del documento
      $estado = $node->get('field_estado_firma')->value ?? 'pendiente';
      if ($estado === 'firmado') {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Este documento ya ha sido firmado',
        ], 400);
      }

      // Obtener el archivo PDF
      $file = $node->get('field_documento_pdf')->entity;
      if (!$file) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'El documento PDF no está disponible',
        ], 404);
      }

      // Leer y codificar en Base64
      $file_path = $this->fileSystem->realpath($file->getFileUri());
      $pdf_content = file_get_contents($file_path);
      $pdf_base64 = base64_encode($pdf_content);

      // Calcular hash para verificación
      $hash = hash('sha256', $pdf_content);

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'document_id' => $document_id,
          'title' => $node->getTitle(),
          'content' => $pdf_base64,
          'filename' => $file->getFilename(),
          'hash' => $hash,
          'mime_type' => 'application/pdf',
          'sign_format' => 'PAdES',  // Formato de firma para PDF
          'sign_algorithm' => 'SHA256withRSA',
        ],
      ]);

    } catch (\Exception $e) {
      $this->getLogger('ecosistema_jaraba_core')->error(
        '🚫 AutoFirma: Error al obtener documento: @error',
        ['@error' => $e->getMessage()]
      );

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error interno al procesar el documento',
      ], 500);
    }
  }

  /**
   * Recibe el documento firmado desde AutoFirma.
   *
   * Endpoint: POST /api/v1/autofirma/firmar
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La petición HTTP con el documento firmado.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado de la operación.
   */
  public function recibirFirma(Request $request): JsonResponse
  {
    if (!$this->currentUser->isAuthenticated()) {
      throw new AccessDeniedHttpException('Usuario no autenticado');
    }

    try {
      // Obtener datos de la petición
      $content = json_decode($request->getContent(), TRUE);

      if (!$content) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Datos de petición inválidos',
        ], 400);
      }

      $document_id = $content['document_id'] ?? NULL;
      $signed_content = $content['signed_content'] ?? NULL;
      $certificate_info = $content['certificate_info'] ?? [];

      if (!$document_id || !$signed_content) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Faltan datos requeridos: document_id y signed_content',
        ], 400);
      }

      // Cargar el documento
      $node = $this->loadDocumentoNode($document_id);

      if (!$node) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Documento no encontrado',
        ], 404);
      }

      // Verificar permisos
      if (!$this->canUserSign($node)) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No tiene permiso para firmar este documento',
        ], 403);
      }

      // Decodificar el contenido firmado
      $signed_pdf = base64_decode($signed_content);

      if (!$signed_pdf) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'El contenido firmado no es válido',
        ], 400);
      }

      // Validar que es un PDF válido
      if (substr($signed_pdf, 0, 4) !== '%PDF') {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'El archivo firmado no es un PDF válido',
        ], 400);
      }

      // Validar la firma (básico - en producción usar librería de validación)
      $signature_valid = $this->validateSignature($signed_pdf);

      if (!$signature_valid) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'La firma no pudo ser validada',
        ], 400);
      }

      // Guardar el documento firmado
      $saved_file = $this->saveSignedDocument($node, $signed_pdf, $certificate_info);

      if (!$saved_file) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'Error al guardar el documento firmado',
        ], 500);
      }

      // Actualizar estado del documento
      $node->set('field_estado_firma', 'firmado');
      $node->set('field_fecha_firma', date('Y-m-d\TH:i:s'));
      $node->set('field_firmante_uid', $this->currentUser->id());
      $node->set('field_documento_firmado', ['target_id' => $saved_file->id()]);

      // Guardar información del certificado (sin datos sensibles)
      if (!empty($certificate_info)) {
        $node->set('field_info_certificado', json_encode([
          'cn' => $certificate_info['cn'] ?? 'No disponible',
          'issuer' => $certificate_info['issuer'] ?? 'No disponible',
          'serial' => $certificate_info['serial'] ?? 'No disponible',
          'valid_from' => $certificate_info['valid_from'] ?? '',
          'valid_to' => $certificate_info['valid_to'] ?? '',
        ]));
      }

      $node->save();

      // Registrar en auditoría
      $this->logSignatureAudit($node, $certificate_info);

      // Disparar evento para notificaciones
      // \Drupal::service('event_dispatcher')->dispatch(...);

      return new JsonResponse([
        'success' => TRUE,
        'message' => 'Documento firmado correctamente',
        'data' => [
          'document_id' => $document_id,
          'signed_file_id' => $saved_file->id(),
          'signed_at' => date('c'),
          'signer' => $this->currentUser->getDisplayName(),
        ],
      ]);

    } catch (\Exception $e) {
      $this->getLogger('ecosistema_jaraba_core')->error(
        '🚫 AutoFirma: Error al procesar firma: @error',
        ['@error' => $e->getMessage()]
      );

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error interno al procesar la firma',
      ], 500);
    }
  }

  /**
   * Obtiene el estado de un documento.
   *
   * Endpoint: GET /api/v1/autofirma/estado/{document_id}
   *
   * @param string $document_id
   *   ID del documento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Estado del documento.
   */
  public function getEstado(string $document_id): JsonResponse
  {
    if (!$this->currentUser->isAuthenticated()) {
      throw new AccessDeniedHttpException('Usuario no autenticado');
    }

    $node = $this->loadDocumentoNode($document_id);

    if (!$node) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Documento no encontrado',
      ], 404);
    }

    $estado = $node->get('field_estado_firma')->value ?? 'borrador';
    $fecha_firma = $node->get('field_fecha_firma')->value ?? NULL;
    $firmante = NULL;

    if ($firmante_uid = $node->get('field_firmante_uid')->target_id) {
      $firmante_user = \Drupal\user\Entity\User::load($firmante_uid);
      $firmante = $firmante_user ? $firmante_user->getDisplayName() : NULL;
    }

    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'document_id' => $document_id,
        'title' => $node->getTitle(),
        'estado' => $estado,
        'fecha_firma' => $fecha_firma,
        'firmante' => $firmante,
        'puede_firmar' => $this->canUserSign($node) && $estado !== 'firmado',
      ],
    ]);
  }

  /**
   * Verifica la disponibilidad de AutoFirma.
   *
   * Endpoint: GET /api/v1/autofirma/check
   *
   * Devuelve información para el cliente sobre cómo conectar.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Configuración de AutoFirma.
   */
  public function checkAutoFirma(): JsonResponse
  {
    return new JsonResponse([
      'success' => TRUE,
      'data' => [
        'websocket_ports' => [63117, 63217, 63317],  // Puertos estándar de AutoFirma
        'protocol' => 'afirma',
        'download_url' => 'https://firmaelectronica.gob.es/Home/Descargas.html',
        'help_url' => '/ayuda/autofirma',
      ],
    ]);
  }

  /**
   * Genera un documento PDF para firma.
   *
   * Endpoint: POST /api/v1/autofirma/generar
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Petición con datos del documento a generar.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   ID del documento generado.
   */
  public function generarDocumento(Request $request): JsonResponse
  {
    if (!$this->currentUser->isAuthenticated()) {
      throw new AccessDeniedHttpException('Usuario no autenticado');
    }

    $content = json_decode($request->getContent(), TRUE);
    $tipo = $content['tipo'] ?? NULL;
    $datos = $content['datos'] ?? [];

    if (!$tipo) {
      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Tipo de documento requerido',
      ], 400);
    }

    try {
      // Generar PDF según el tipo
      $pdf_service = \Drupal::service('ecosistema_jaraba_core.documento_firma_pdf');
      $result = $pdf_service->generate($tipo, $datos, $this->currentUser->id());

      if (!$result) {
        return new JsonResponse([
          'success' => FALSE,
          'error' => 'No se pudo generar el documento',
        ], 500);
      }

      return new JsonResponse([
        'success' => TRUE,
        'data' => [
          'document_id' => $result['document_id'],
          'title' => $result['title'],
          'created_at' => date('c'),
        ],
      ]);

    } catch (\Exception $e) {
      $this->getLogger('ecosistema_jaraba_core')->error(
        '🚫 AutoFirma: Error al generar documento: @error',
        ['@error' => $e->getMessage()]
      );

      return new JsonResponse([
        'success' => FALSE,
        'error' => 'Error al generar el documento',
      ], 500);
    }
  }

  /**
   * Carga un nodo de documento por su ID.
   *
   * @param string $document_id
   *   ID del documento (puede ser NID o UUID).
   *
   * @return \Drupal\node\Entity\Node|null
   *   El nodo o NULL si no existe.
   */
  protected function loadDocumentoNode(string $document_id): ?Node
  {
    // Intentar cargar por NID
    if (is_numeric($document_id)) {
      $node = Node::load($document_id);
      if ($node && $node->bundle() === 'documento_firma') {
        return $node;
      }
    }

    // Intentar cargar por UUID
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties([
        'uuid' => $document_id,
        'type' => 'documento_firma',
      ]);

    return $nodes ? reset($nodes) : NULL;
  }

  /**
   * Verifica si el usuario puede firmar el documento.
   *
   * @param \Drupal\node\Entity\Node $node
   *   El nodo del documento.
   *
   * @return bool
   *   TRUE si puede firmar.
   */
  protected function canUserSign(Node $node): bool
  {
    $uid = $this->currentUser->id();

    // Administradores pueden firmar cualquier documento
    if ($this->currentUser->hasPermission('administer ecosistema jaraba core')) {
      return TRUE;
    }

    // Verificar si el usuario es el destinatario de la firma
    if ($node->hasField('field_firmante_destinatario')) {
      $destinatario_uid = $node->get('field_firmante_destinatario')->target_id;
      if ($destinatario_uid && $destinatario_uid == $uid) {
        return TRUE;
      }
    }

    // Verificar si es el autor del contenido relacionado
    if ($node->hasField('field_contenido_relacionado')) {
      $contenido = $node->get('field_contenido_relacionado')->entity;
      if ($contenido && $contenido->getOwnerId() == $uid) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Valida la firma de un PDF.
   *
   * @param string $pdf_content
   *   Contenido binario del PDF firmado.
   *
   * @return bool
   *   TRUE si la firma es válida.
   */
  protected function validateSignature(string $pdf_content): bool
  {
    // Verificación básica: buscar estructura de firma en el PDF
    // En producción, usar una librería de validación como SetaPDF o pdfsig

    // Buscar diccionario de firma en el PDF
    if (
      strpos($pdf_content, '/Type /Sig') !== FALSE ||
      strpos($pdf_content, '/SubFilter /adbe.pkcs7') !== FALSE ||
      strpos($pdf_content, '/SubFilter /ETSI.CAdES') !== FALSE
    ) {
      return TRUE;
    }

    // Alternativa: usar pdfsig si está disponible
    $temp_file = $this->fileSystem->getTempDirectory() . '/validate_' . uniqid() . '.pdf';
    file_put_contents($temp_file, $pdf_content);

    $output = [];
    $return_var = 0;
    exec('pdfsig "' . escapeshellarg($temp_file) . '" 2>&1', $output, $return_var);

    unlink($temp_file);

    // pdfsig devuelve 0 si la firma es válida
    return $return_var === 0;
  }

  /**
   * Guarda el documento firmado.
   *
   * @param \Drupal\node\Entity\Node $node
   *   El nodo del documento.
   * @param string $signed_content
   *   Contenido del PDF firmado.
   * @param array $certificate_info
   *   Información del certificado.
   *
   * @return \Drupal\file\Entity\File|null
   *   El archivo guardado o NULL si falla.
   */
  protected function saveSignedDocument(Node $node, string $signed_content, array $certificate_info): ?File
  {
    // Preparar directorio
    $directory = 'private://documentos-firmados/' . date('Y/m');
    $this->fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
    );

    // Generar nombre de archivo
    $original_file = $node->get('field_documento_pdf')->entity;
    $original_name = $original_file ? pathinfo($original_file->getFilename(), PATHINFO_FILENAME) : 'documento';
    $filename = $original_name . '-firmado-' . date('Ymd-His') . '.pdf';

    // Guardar archivo
    $uri = $directory . '/' . $filename;
    $saved_uri = $this->fileSystem->saveData($signed_content, $uri, FileSystemInterface::EXISTS_RENAME);

    if (!$saved_uri) {
      return NULL;
    }

    // Crear entidad File
    $file = File::create([
      'uri' => $saved_uri,
      'uid' => $this->currentUser->id(),
      'status' => FILE_STATUS_PERMANENT,
      'filename' => $filename,
    ]);
    $file->save();

    // Registrar uso del archivo
    \Drupal::service('file.usage')->add($file, 'ecosistema_jaraba_core', 'node', $node->id());

    return $file;
  }

  /**
   * Registra la firma en el log de auditoría.
   *
   * @param \Drupal\node\Entity\Node $node
   *   El documento firmado.
   * @param array $certificate_info
   *   Información del certificado (sin datos sensibles).
   */
  protected function logSignatureAudit(Node $node, array $certificate_info): void
  {
    $audit_data = [
      'action' => 'document_signed',
      'document_id' => $node->id(),
      'document_title' => $node->getTitle(),
      'signer_uid' => $this->currentUser->id(),
      'signer_name' => $this->currentUser->getDisplayName(),
      'certificate_cn' => $certificate_info['cn'] ?? 'No disponible',
      'certificate_issuer' => $certificate_info['issuer'] ?? 'No disponible',
      'timestamp' => date('c'),
      'ip_address' => \Drupal::request()->getClientIp(),
      'user_agent' => \Drupal::request()->headers->get('User-Agent'),
    ];

    $this->getLogger('ecosistema_jaraba_core')->info(
      '📋 AUDITORÍA FIRMA: Usuario @user firmó documento @doc con certificado @cert',
      [
        '@user' => $audit_data['signer_name'],
        '@doc' => $audit_data['document_title'],
        '@cert' => $audit_data['certificate_cn'],
      ]
    );

    // Opcionalmente, guardar en tabla de auditoría dedicada
    // \Drupal::database()->insert('ecosistema_jaraba_audit_signatures')->fields($audit_data)->execute();
  }

}
