<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller de API para el flujo de firma electrónica.
 *
 * Adapter entre ExpedienteDocumento y el contrato esperado por AutoFirma JS.
 * Sprint 1 del Plan Maestro Andalucía +ei Clase Mundial.
 *
 * CSRF-API-001: Todas las rutas API usan _csrf_request_header_token.
 * API-WHITELIST-001: Campos de entrada filtrados por ALLOWED_FIELDS.
 * CONTROLLER-READONLY-001: No usar readonly en propiedades heredadas.
 */
class FirmaDocumentoController extends ControllerBase {

  /**
   * Campos permitidos en request de firma táctil.
   */
  protected const ALLOWED_FIELDS_TACTIL = [
    'documento_id',
    'firma_base64',
  ];

  /**
   * Campos permitidos en request de firma AutoFirma.
   */
  protected const ALLOWED_FIELDS_AUTOFIRMA = [
    'documento_id',
    'signed_content',
    'certificate_info',
  ];

  /**
   * Campos permitidos en request de sello.
   */
  protected const ALLOWED_FIELDS_SELLO = [
    'documento_ids',
  ];

  /**
   * Campos permitidos en request de rechazo.
   */
  protected const ALLOWED_FIELDS_RECHAZAR = [
    'documento_id',
    'motivo',
  ];

  public function __construct(
    protected FirmaWorkflowService $firmaWorkflow,
    protected LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_andalucia_ei.firma_workflow'),
      $container->get('logger.channel.jaraba_andalucia_ei'),
    );
  }

  /**
   * GET: Obtiene datos de un documento para firma.
   *
   * Adapta ExpedienteDocumento al contrato de AutoFirma JS:
   * document_id, title, content (base64), filename, hash, mime_type,
   * sign_format (PAdES), sign_algorithm (SHA256withRSA).
   *
   * @param int $expediente_documento
   *   ID del ExpedienteDocumento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON con datos del documento o error.
   */
  public function getDocumentoParaFirma(int $expediente_documento): JsonResponse {
    $storage = $this->entityTypeManager()->getStorage('expediente_documento');
    $documento = $storage->load($expediente_documento);

    if (!$documento) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }

    $estado = $this->firmaWorkflow->getEstadoFirma($expediente_documento);

    return new JsonResponse([
      'document_id' => (int) $documento->id(),
      'title' => $documento->label() ?? '',
      'categoria' => $documento->get('categoria')->value ?? '',
      'estado_firma' => $estado['estado'],
      'firmado' => $estado['firmado'],
      'firmantes' => $estado['firmantes'],
      'archivo_nombre' => $documento->get('archivo_nombre')->value ?? '',
      'mime_type' => 'application/pdf',
      'sign_format' => 'PAdES',
      'sign_algorithm' => 'SHA256withRSA',
      'verificacion_hash' => $estado['verificacion_hash'] ?? NULL,
    ]);
  }

  /**
   * POST: Procesa firma táctil (manuscrita sobre canvas).
   *
   * Espera JSON: { documento_id: int, firma_base64: string }
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado de la firma.
   */
  public function firmarTactil(Request $request): JsonResponse {
    $data = $this->parseAndValidateRequest($request, self::ALLOWED_FIELDS_TACTIL);
    if ($data instanceof JsonResponse) {
      return $data;
    }

    $documentoId = (int) ($data['documento_id'] ?? 0);
    $firmaBase64 = (string) ($data['firma_base64'] ?? '');

    if ($documentoId <= 0 || empty($firmaBase64)) {
      return new JsonResponse(['error' => 'documento_id y firma_base64 son obligatorios.'], 400);
    }

    $uid = (int) $this->currentUser()->id();
    $result = $this->firmaWorkflow->procesarFirmaTactil($documentoId, $firmaBase64, $uid);

    return new JsonResponse($result, $result['success'] ? 200 : 400);
  }

  /**
   * POST: Procesa firma vía AutoFirma (PAdES).
   *
   * Espera JSON: { documento_id: int, signed_content: string, certificate_info: object }
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado de la firma.
   */
  public function firmarAutofirma(Request $request): JsonResponse {
    $data = $this->parseAndValidateRequest($request, self::ALLOWED_FIELDS_AUTOFIRMA);
    if ($data instanceof JsonResponse) {
      return $data;
    }

    $documentoId = (int) ($data['documento_id'] ?? 0);
    $signedContent = (string) ($data['signed_content'] ?? '');
    $certInfo = $data['certificate_info'] ?? [];

    if ($documentoId <= 0 || empty($signedContent) || !is_array($certInfo)) {
      return new JsonResponse(['error' => 'Datos de firma AutoFirma incompletos.'], 400);
    }

    $uid = (int) $this->currentUser()->id();
    $result = $this->firmaWorkflow->procesarFirmaAutofirma($documentoId, $signedContent, $certInfo, $uid);

    return new JsonResponse($result, $result['success'] ? 200 : 400);
  }

  /**
   * POST: Aplica sello de empresa (firma masiva).
   *
   * Espera JSON: { documento_ids: int[] }
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado con conteo de firmas exitosas/fallidas.
   */
  public function firmarSello(Request $request): JsonResponse {
    $data = $this->parseAndValidateRequest($request, self::ALLOWED_FIELDS_SELLO);
    if ($data instanceof JsonResponse) {
      return $data;
    }

    $documentoIds = $data['documento_ids'] ?? [];
    if (!is_array($documentoIds) || empty($documentoIds)) {
      return new JsonResponse(['error' => 'documento_ids debe ser un array no vacío.'], 400);
    }

    $exitos = 0;
    $errores = [];
    foreach ($documentoIds as $docId) {
      $id = (int) $docId;
      if ($id <= 0) {
        continue;
      }
      $result = $this->firmaWorkflow->procesarFirmaSello($id);
      if ($result['success']) {
        $exitos++;
      }
      else {
        $errores[] = ['documento_id' => $id, 'message' => $result['message']];
      }
    }

    return new JsonResponse([
      'success' => $exitos > 0,
      'message' => "$exitos documentos firmados con sello de empresa.",
      'exitos' => $exitos,
      'errores' => $errores,
    ]);
  }

  /**
   * POST: Rechaza una firma con motivo.
   *
   * Espera JSON: { documento_id: int, motivo: string }
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Resultado del rechazo.
   */
  public function rechazar(Request $request): JsonResponse {
    $data = $this->parseAndValidateRequest($request, self::ALLOWED_FIELDS_RECHAZAR);
    if ($data instanceof JsonResponse) {
      return $data;
    }

    $documentoId = (int) ($data['documento_id'] ?? 0);
    $motivo = trim((string) ($data['motivo'] ?? ''));

    if ($documentoId <= 0 || empty($motivo)) {
      return new JsonResponse(['error' => 'documento_id y motivo son obligatorios.'], 400);
    }

    $uid = (int) $this->currentUser()->id();
    $result = $this->firmaWorkflow->rechazarFirma($documentoId, $uid, $motivo);

    return new JsonResponse($result, $result['success'] ? 200 : 400);
  }

  /**
   * GET: Estado de firma de un documento.
   *
   * @param int $expediente_documento
   *   ID del ExpedienteDocumento.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Estado completo con firmantes e historial.
   */
  public function getEstado(int $expediente_documento): JsonResponse {
    $estado = $this->firmaWorkflow->getEstadoFirma($expediente_documento);

    if (empty($estado['estado'])) {
      return new JsonResponse(['error' => 'Documento no encontrado.'], 404);
    }

    return new JsonResponse($estado);
  }

  /**
   * GET: Documentos pendientes de firma del usuario actual.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Lista de documentos pendientes.
   */
  public function getPendientes(): JsonResponse {
    $uid = (int) $this->currentUser()->id();

    $tenantId = NULL;
    if (\Drupal::hasService('ecosistema_jaraba_core.tenant_context')) {
      try {
        $tenantId = (int) \Drupal::service('ecosistema_jaraba_core.tenant_context')->getCurrentTenantId();
      }
      catch (\Throwable) {
        // PRESAVE-RESILIENCE-001.
      }
    }

    $pendientes = $this->firmaWorkflow->getDocumentosPendientes($uid, $tenantId);

    return new JsonResponse([
      'count' => count($pendientes),
      'documentos' => $pendientes,
    ]);
  }

  /**
   * GET: Verificación pública de documento por hash (para QR).
   *
   * @param string $hash
   *   Hash de verificación de 64 caracteres.
   *
   * @return array
   *   Render array para la página de verificación.
   */
  public function verificarPublico(string $hash): array {
    $resultado = $this->firmaWorkflow->verificarDocumento($hash);

    return [
      '#theme' => 'andalucia_ei_verificacion_documento',
      '#valido' => $resultado['valido'],
      '#documento' => $resultado['documento'],
      '#hash' => $hash,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Parsea y valida el request JSON con whitelist de campos.
   *
   * API-WHITELIST-001: Solo campos permitidos pasan.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   El request HTTP.
   * @param array $allowedFields
   *   Lista de campos permitidos.
   *
   * @return array|\Symfony\Component\HttpFoundation\JsonResponse
   *   Datos filtrados o JsonResponse de error.
   */
  protected function parseAndValidateRequest(Request $request, array $allowedFields): array|JsonResponse {
    $content = $request->getContent();
    if (empty($content)) {
      return new JsonResponse(['error' => 'Request body vacío.'], 400);
    }

    try {
      $data = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      return new JsonResponse(['error' => 'JSON inválido.'], 400);
    }

    if (!is_array($data)) {
      return new JsonResponse(['error' => 'Se esperaba un objeto JSON.'], 400);
    }

    // Filtrar solo campos permitidos (API-WHITELIST-001).
    return array_intersect_key($data, array_flip($allowedFields));
  }

}
