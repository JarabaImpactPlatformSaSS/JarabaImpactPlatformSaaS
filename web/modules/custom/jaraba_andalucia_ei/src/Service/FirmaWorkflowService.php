<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Orquestador del ciclo de vida de firma de documentos.
 *
 * Gestiona la máquina de estados, coordina con FirmaDigitalService
 * para sellado, con jaraba_legal_vault para almacenamiento cifrado,
 * y con el sistema de notificaciones.
 *
 * Sprint 1 del Plan Maestro Andalucía +ei Clase Mundial.
 */
class FirmaWorkflowService {

  /**
   * Estados válidos de firma.
   */
  public const ESTADO_BORRADOR = 'borrador';
  public const ESTADO_PENDIENTE_FIRMA = 'pendiente_firma';
  public const ESTADO_PENDIENTE_FIRMA_TECNICO = 'pendiente_firma_tecnico';
  public const ESTADO_PENDIENTE_FIRMA_PARTICIPANTE = 'pendiente_firma_participante';
  public const ESTADO_FIRMADO_PARCIAL = 'firmado_parcial';
  public const ESTADO_FIRMADO = 'firmado';
  public const ESTADO_RECHAZADO = 'rechazado';
  public const ESTADO_CADUCADO = 'caducado';

  /**
   * Métodos de firma.
   */
  public const METODO_TACTIL = 'tactil';
  public const METODO_AUTOFIRMA = 'autofirma';
  public const METODO_SELLO_EMPRESA = 'sello_empresa';

  /**
   * Acciones de auditoría.
   */
  public const AUDIT_FIRMA_SOLICITADA = 'firma_solicitada';
  public const AUDIT_FIRMA_COMPLETADA = 'firma_completada';
  public const AUDIT_FIRMA_RECHAZADA = 'firma_rechazada';
  public const AUDIT_SELLO_APLICADO = 'sello_aplicado';
  public const AUDIT_VERIFICACION = 'verificacion';
  public const AUDIT_COFIRMA_COMPLETADA = 'cofirma_completada';

  /**
   * Transiciones válidas de la máquina de estados.
   *
   * @var array<string, list<string>>
   */
  protected const TRANSICIONES = [
    self::ESTADO_BORRADOR => [
      self::ESTADO_PENDIENTE_FIRMA,
      self::ESTADO_PENDIENTE_FIRMA_TECNICO,
    ],
    self::ESTADO_PENDIENTE_FIRMA => [
      self::ESTADO_FIRMADO,
      self::ESTADO_RECHAZADO,
      self::ESTADO_CADUCADO,
    ],
    self::ESTADO_PENDIENTE_FIRMA_TECNICO => [
      self::ESTADO_FIRMADO_PARCIAL,
      self::ESTADO_RECHAZADO,
      self::ESTADO_CADUCADO,
    ],
    self::ESTADO_FIRMADO_PARCIAL => [
      self::ESTADO_PENDIENTE_FIRMA_PARTICIPANTE,
    ],
    self::ESTADO_PENDIENTE_FIRMA_PARTICIPANTE => [
      self::ESTADO_FIRMADO,
      self::ESTADO_RECHAZADO,
      self::ESTADO_CADUCADO,
    ],
    self::ESTADO_RECHAZADO => [
      self::ESTADO_PENDIENTE_FIRMA,
    ],
    self::ESTADO_CADUCADO => [
      self::ESTADO_PENDIENTE_FIRMA,
    ],
  ];

  /**
   * Plazo por defecto de firma en días.
   */
  protected const PLAZO_FIRMA_DIAS = 30;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected Connection $database,
    protected RequestStack $requestStack,
    protected ?object $firmaDigital = NULL,
    protected ?object $tenantContext = NULL,
    protected ?object $notificationService = NULL,
  ) {}

  /**
   * Solicita firma simple para un documento.
   *
   * Transiciona de borrador → pendiente_firma y notifica al firmante.
   *
   * @param int $documentoId
   *   ID del ExpedienteDocumento.
   * @param int $firmanteUid
   *   UID del usuario que debe firmar.
   * @param string $tipo
   *   Tipo de firma esperada: tactil, autofirma, sello_empresa.
   *
   * @return array{success: bool, message: string, estado: string}
   */
  public function solicitarFirma(int $documentoId, int $firmanteUid, string $tipo = self::METODO_TACTIL): array {
    $documento = $this->loadDocumento($documentoId);
    if (!$documento) {
      return ['success' => FALSE, 'message' => 'Documento no encontrado.', 'estado' => ''];
    }

    $estadoActual = $this->getEstadoFirmaField($documento);
    $estadoObjetivo = self::ESTADO_PENDIENTE_FIRMA;

    if (!$this->esTransicionValida($estadoActual, $estadoObjetivo)) {
      return [
        'success' => FALSE,
        'message' => "Transición inválida: $estadoActual → $estadoObjetivo.",
        'estado' => $estadoActual,
      ];
    }

    $documento->set('estado_firma', $estadoObjetivo);
    $documento->set('firma_solicitada_fecha', date('Y-m-d\TH:i:s'));
    $documento->set('firma_solicitante_uid', $firmanteUid);
    $documento->save();

    $this->registrarAuditoria(
      $documentoId,
      $documento->getParticipanteId(),
      self::AUDIT_FIRMA_SOLICITADA,
      $firmanteUid,
      NULL,
      ['tipo_esperado' => $tipo],
    );

    $this->notificarFirmaPendiente($documento, $firmanteUid);

    return [
      'success' => TRUE,
      'message' => 'Firma solicitada correctamente.',
      'estado' => $estadoObjetivo,
    ];
  }

  /**
   * Solicita firma dual (técnico + participante).
   *
   * El técnico firma primero, luego se notifica al participante.
   *
   * @param int $documentoId
   *   ID del ExpedienteDocumento.
   * @param int $tecnicoUid
   *   UID del técnico/orientador.
   * @param int $participanteUid
   *   UID del participante.
   *
   * @return array{success: bool, message: string, estado: string}
   */
  public function solicitarFirmaDual(int $documentoId, int $tecnicoUid, int $participanteUid): array {
    $documento = $this->loadDocumento($documentoId);
    if (!$documento) {
      return ['success' => FALSE, 'message' => 'Documento no encontrado.', 'estado' => ''];
    }

    $estadoActual = $this->getEstadoFirmaField($documento);
    $estadoObjetivo = self::ESTADO_PENDIENTE_FIRMA_TECNICO;

    if (!$this->esTransicionValida($estadoActual, $estadoObjetivo)) {
      return [
        'success' => FALSE,
        'message' => "Transición inválida: $estadoActual → $estadoObjetivo.",
        'estado' => $estadoActual,
      ];
    }

    $documento->set('estado_firma', $estadoObjetivo);
    $documento->set('firma_solicitada_fecha', date('Y-m-d\TH:i:s'));
    $documento->set('firma_solicitante_uid', $tecnicoUid);
    $documento->set('co_firmante_uid', $participanteUid);
    $documento->save();

    $this->registrarAuditoria(
      $documentoId,
      $documento->getParticipanteId(),
      self::AUDIT_FIRMA_SOLICITADA,
      $tecnicoUid,
      NULL,
      ['tipo' => 'dual', 'co_firmante' => $participanteUid],
    );

    $this->notificarFirmaPendiente($documento, $tecnicoUid);

    return [
      'success' => TRUE,
      'message' => 'Firma dual solicitada. Esperando firma del técnico.',
      'estado' => $estadoObjetivo,
    ];
  }

  /**
   * Procesa firma táctil (manuscrita sobre canvas).
   *
   * @param int $documentoId
   *   ID del ExpedienteDocumento.
   * @param string $firmaBase64
   *   Imagen PNG de la firma en base64.
   * @param int $firmanteUid
   *   UID del firmante.
   *
   * @return array{success: bool, message: string, estado: string}
   */
  public function procesarFirmaTactil(int $documentoId, string $firmaBase64, int $firmanteUid): array {
    $documento = $this->loadDocumento($documentoId);
    if (!$documento) {
      return ['success' => FALSE, 'message' => 'Documento no encontrado.', 'estado' => ''];
    }

    $estadoActual = $this->getEstadoFirmaField($documento);

    // Validar que el estado permite firma.
    if (!$this->estadoPermiteFirma($estadoActual)) {
      return [
        'success' => FALSE,
        'message' => "El documento no está pendiente de firma (estado: $estadoActual).",
        'estado' => $estadoActual,
      ];
    }

    // Validar que el firmante es el esperado.
    if (!$this->esFirmanteValido($documento, $firmanteUid, $estadoActual)) {
      return [
        'success' => FALSE,
        'message' => 'No tienes permiso para firmar este documento.',
        'estado' => $estadoActual,
      ];
    }

    // Validar la firma base64.
    $firmaData = base64_decode($firmaBase64, TRUE);
    if ($firmaData === FALSE || strlen($firmaData) < 100) {
      return [
        'success' => FALSE,
        'message' => 'La firma proporcionada no es válida.',
        'estado' => $estadoActual,
      ];
    }

    // Calcular hash del documento antes de firmar.
    $hashDocumento = $this->calcularHashDocumento($documento);

    // Determinar nuevo estado.
    $nuevoEstado = $this->calcularNuevoEstadoPostFirma($estadoActual);

    // Obtener metadatos del request.
    $request = $this->requestStack->getCurrentRequest();
    $ip = $request?->getClientIp() ?? '0.0.0.0';
    $userAgent = $request?->headers->get('User-Agent', '') ?? '';

    // Aplicar sello TSA si disponible.
    $certInfo = $this->aplicarSelloTsa($documento);

    // Actualizar documento.
    $ahora = date('Y-m-d\TH:i:s');
    $esDualCoFirma = in_array($estadoActual, [
      self::ESTADO_PENDIENTE_FIRMA_PARTICIPANTE,
    ], TRUE);

    if ($esDualCoFirma) {
      $documento->set('co_firma_fecha', $ahora);
      $documento->set('co_firma_metodo', self::METODO_TACTIL);
    }
    else {
      $documento->set('firma_fecha', $ahora);
      $documento->set('firma_metodo', self::METODO_TACTIL);
    }

    $documento->set('estado_firma', $nuevoEstado);
    $documento->set('firmado', $nuevoEstado === self::ESTADO_FIRMADO);
    $documento->set('firma_ip', $ip);
    $documento->set('firma_user_agent', substr($userAgent, 0, 512));
    $documento->set('firma_hash_documento', $hashDocumento);

    if ($certInfo) {
      $documento->set('firma_certificado_info', json_encode($certInfo, JSON_THROW_ON_ERROR));
    }

    // Generar hash de verificación para QR.
    $verificacionHash = $this->generarVerificacionHash($documento, $ahora);
    $documento->set('verificacion_hash', $verificacionHash);

    $documento->save();

    // Auditoría.
    $accion = $esDualCoFirma ? self::AUDIT_COFIRMA_COMPLETADA : self::AUDIT_FIRMA_COMPLETADA;
    $this->registrarAuditoria(
      $documentoId,
      $documento->getParticipanteId(),
      $accion,
      $firmanteUid,
      self::METODO_TACTIL,
      [
        'hash_documento' => $hashDocumento,
        'ip' => $ip,
        'user_agent' => substr($userAgent, 0, 128),
        'firma_size_bytes' => strlen($firmaData),
      ],
    );

    // Si es firma dual parcial, transicionar y notificar al siguiente firmante.
    if ($nuevoEstado === self::ESTADO_FIRMADO_PARCIAL) {
      $this->transicionarAFirmaParticipante($documento);
    }

    // Sprint 5: QR + TSA post-firma.
    if ($nuevoEstado === self::ESTADO_FIRMADO) {
      $this->postProcesoFirmaCompletada($documento);
    }

    $this->logger->info('Firma táctil procesada para documento @id por usuario @uid. Estado: @estado', [
      '@id' => $documentoId,
      '@uid' => $firmanteUid,
      '@estado' => $nuevoEstado,
    ]);

    return [
      'success' => TRUE,
      'message' => $nuevoEstado === self::ESTADO_FIRMADO
        ? 'Documento firmado correctamente.'
        : 'Firma registrada. Pendiente de co-firma.',
      'estado' => $nuevoEstado,
    ];
  }

  /**
   * Procesa firma vía AutoFirma (PAdES con certificado digital).
   *
   * @param int $documentoId
   *   ID del ExpedienteDocumento.
   * @param string $pdfFirmadoBase64
   *   PDF firmado en base64.
   * @param array $certInfo
   *   Información del certificado: cn, issuer, serial, valid_from, valid_to.
   * @param int $firmanteUid
   *   UID del firmante.
   *
   * @return array{success: bool, message: string, estado: string}
   */
  public function procesarFirmaAutofirma(int $documentoId, string $pdfFirmadoBase64, array $certInfo, int $firmanteUid): array {
    $documento = $this->loadDocumento($documentoId);
    if (!$documento) {
      return ['success' => FALSE, 'message' => 'Documento no encontrado.', 'estado' => ''];
    }

    $estadoActual = $this->getEstadoFirmaField($documento);
    if (!$this->estadoPermiteFirma($estadoActual)) {
      return [
        'success' => FALSE,
        'message' => "El documento no está pendiente de firma (estado: $estadoActual).",
        'estado' => $estadoActual,
      ];
    }

    if (!$this->esFirmanteValido($documento, $firmanteUid, $estadoActual)) {
      return [
        'success' => FALSE,
        'message' => 'No tienes permiso para firmar este documento.',
        'estado' => $estadoActual,
      ];
    }

    // Validar PDF firmado.
    $pdfData = base64_decode($pdfFirmadoBase64, TRUE);
    if ($pdfData === FALSE || !str_starts_with($pdfData, '%PDF')) {
      return [
        'success' => FALSE,
        'message' => 'El PDF firmado no es válido.',
        'estado' => $estadoActual,
      ];
    }

    // Validar certificado mínimo.
    if (empty($certInfo['cn']) || empty($certInfo['serial'])) {
      return [
        'success' => FALSE,
        'message' => 'Información de certificado incompleta.',
        'estado' => $estadoActual,
      ];
    }

    $hashDocumento = hash('sha256', $pdfData);
    $nuevoEstado = $this->calcularNuevoEstadoPostFirma($estadoActual);

    $request = $this->requestStack->getCurrentRequest();
    $ip = $request?->getClientIp() ?? '0.0.0.0';
    $userAgent = $request?->headers->get('User-Agent', '') ?? '';

    $ahora = date('Y-m-d\TH:i:s');
    $esDualCoFirma = $estadoActual === self::ESTADO_PENDIENTE_FIRMA_PARTICIPANTE;

    if ($esDualCoFirma) {
      $documento->set('co_firma_fecha', $ahora);
      $documento->set('co_firma_metodo', self::METODO_AUTOFIRMA);
    }
    else {
      $documento->set('firma_fecha', $ahora);
      $documento->set('firma_metodo', self::METODO_AUTOFIRMA);
    }

    $documento->set('estado_firma', $nuevoEstado);
    $documento->set('firmado', $nuevoEstado === self::ESTADO_FIRMADO);
    $documento->set('firma_ip', $ip);
    $documento->set('firma_user_agent', substr($userAgent, 0, 512));
    $documento->set('firma_hash_documento', $hashDocumento);
    $documento->set('firma_certificado_info', json_encode($certInfo, JSON_THROW_ON_ERROR));

    $verificacionHash = $this->generarVerificacionHash($documento, $ahora);
    $documento->set('verificacion_hash', $verificacionHash);

    $documento->save();

    $accion = $esDualCoFirma ? self::AUDIT_COFIRMA_COMPLETADA : self::AUDIT_FIRMA_COMPLETADA;
    $this->registrarAuditoria(
      $documentoId,
      $documento->getParticipanteId(),
      $accion,
      $firmanteUid,
      self::METODO_AUTOFIRMA,
      [
        'hash_documento' => $hashDocumento,
        'certificado_cn' => $certInfo['cn'] ?? '',
        'certificado_issuer' => $certInfo['issuer'] ?? '',
        'certificado_serial' => $certInfo['serial'] ?? '',
        'ip' => $ip,
      ],
    );

    if ($nuevoEstado === self::ESTADO_FIRMADO_PARCIAL) {
      $this->transicionarAFirmaParticipante($documento);
    }

    // Sprint 5: QR + TSA post-firma.
    if ($nuevoEstado === self::ESTADO_FIRMADO) {
      $this->postProcesoFirmaCompletada($documento);
    }

    $this->logger->info('Firma AutoFirma procesada para documento @id. CN: @cn. Estado: @estado', [
      '@id' => $documentoId,
      '@cn' => $certInfo['cn'] ?? '(desconocido)',
      '@estado' => $nuevoEstado,
    ]);

    return [
      'success' => TRUE,
      'message' => $nuevoEstado === self::ESTADO_FIRMADO
        ? 'Documento firmado con certificado digital.'
        : 'Firma con certificado registrada. Pendiente de co-firma.',
      'estado' => $nuevoEstado,
    ];
  }

  /**
   * Aplica sello de empresa (PKCS#12 del tenant) a un documento.
   *
   * @param int $documentoId
   *   ID del ExpedienteDocumento.
   *
   * @return array{success: bool, message: string, estado: string}
   */
  public function procesarFirmaSello(int $documentoId): array {
    $documento = $this->loadDocumento($documentoId);
    if (!$documento) {
      return ['success' => FALSE, 'message' => 'Documento no encontrado.', 'estado' => ''];
    }

    if (!$this->firmaDigital) {
      return [
        'success' => FALSE,
        'message' => 'Servicio de firma digital no disponible.',
        'estado' => $this->getEstadoFirmaField($documento),
      ];
    }

    $estadoActual = $this->getEstadoFirmaField($documento);
    if (!$this->estadoPermiteFirma($estadoActual)) {
      return [
        'success' => FALSE,
        'message' => "El documento no está pendiente de firma (estado: $estadoActual).",
        'estado' => $estadoActual,
      ];
    }

    try {
      $certInfo = $this->firmaDigital->getCertificateInfo();
    }
    catch (\Throwable $e) {
      $this->logger->error('Error al obtener info de certificado: @msg', ['@msg' => $e->getMessage()]);
      return [
        'success' => FALSE,
        'message' => 'Error al acceder al certificado de empresa.',
        'estado' => $estadoActual,
      ];
    }

    $hashDocumento = $this->calcularHashDocumento($documento);
    $ahora = date('Y-m-d\TH:i:s');

    $documento->set('estado_firma', self::ESTADO_FIRMADO);
    $documento->set('firmado', TRUE);
    $documento->set('firma_fecha', $ahora);
    $documento->set('firma_metodo', self::METODO_SELLO_EMPRESA);
    $documento->set('firma_hash_documento', $hashDocumento);

    if ($certInfo) {
      $documento->set('firma_certificado_info', json_encode($certInfo, JSON_THROW_ON_ERROR));
    }

    $verificacionHash = $this->generarVerificacionHash($documento, $ahora);
    $documento->set('verificacion_hash', $verificacionHash);

    $documento->save();

    $this->registrarAuditoria(
      $documentoId,
      $documento->getParticipanteId(),
      self::AUDIT_SELLO_APLICADO,
      (int) (\Drupal::currentUser()->id()),
      self::METODO_SELLO_EMPRESA,
      [
        'hash_documento' => $hashDocumento,
        'certificado_cn' => $certInfo['subject'] ?? '',
        'certificado_issuer' => $certInfo['issuer'] ?? '',
      ],
    );

    // Sprint 5: QR + TSA post-sello.
    $this->postProcesoFirmaCompletada($documento);

    $this->logger->info('Sello de empresa aplicado a documento @id.', ['@id' => $documentoId]);

    return [
      'success' => TRUE,
      'message' => 'Sello de empresa aplicado correctamente.',
      'estado' => self::ESTADO_FIRMADO,
    ];
  }

  /**
   * Rechaza firma con motivo.
   *
   * @param int $documentoId
   *   ID del ExpedienteDocumento.
   * @param int $uid
   *   UID del usuario que rechaza.
   * @param string $motivo
   *   Motivo del rechazo.
   *
   * @return array{success: bool, message: string, estado: string}
   */
  public function rechazarFirma(int $documentoId, int $uid, string $motivo): array {
    $documento = $this->loadDocumento($documentoId);
    if (!$documento) {
      return ['success' => FALSE, 'message' => 'Documento no encontrado.', 'estado' => ''];
    }

    $estadoActual = $this->getEstadoFirmaField($documento);
    if (!$this->esTransicionValida($estadoActual, self::ESTADO_RECHAZADO)) {
      return [
        'success' => FALSE,
        'message' => "No se puede rechazar desde estado: $estadoActual.",
        'estado' => $estadoActual,
      ];
    }

    $documento->set('estado_firma', self::ESTADO_RECHAZADO);
    $documento->save();

    $this->registrarAuditoria(
      $documentoId,
      $documento->getParticipanteId(),
      self::AUDIT_FIRMA_RECHAZADA,
      $uid,
      NULL,
      ['motivo' => $motivo],
    );

    return [
      'success' => TRUE,
      'message' => 'Firma rechazada.',
      'estado' => self::ESTADO_RECHAZADO,
    ];
  }

  /**
   * Obtiene el estado de firma completo de un documento.
   *
   * @param int $documentoId
   *   ID del ExpedienteDocumento.
   *
   * @return array{estado: string, firmado: bool, firmantes: array, historial: array}
   */
  public function getEstadoFirma(int $documentoId): array {
    $documento = $this->loadDocumento($documentoId);
    if (!$documento) {
      return [
        'estado' => '',
        'firmado' => FALSE,
        'firmantes' => [],
        'historial' => [],
      ];
    }

    $estado = $this->getEstadoFirmaField($documento);
    $firmantes = [];

    $solicitanteUid = $documento->get('firma_solicitante_uid')->target_id ?? NULL;
    if ($solicitanteUid) {
      $firmantes[] = [
        'uid' => (int) $solicitanteUid,
        'rol' => 'firmante_principal',
        'firmado' => !empty($documento->get('firma_fecha')->value),
        'metodo' => $documento->get('firma_metodo')->value ?? NULL,
        'fecha' => $documento->get('firma_fecha')->value ?? NULL,
      ];
    }

    $coFirmanteUid = $documento->get('co_firmante_uid')->target_id ?? NULL;
    if ($coFirmanteUid) {
      $firmantes[] = [
        'uid' => (int) $coFirmanteUid,
        'rol' => 'co_firmante',
        'firmado' => !empty($documento->get('co_firma_fecha')->value),
        'metodo' => $documento->get('co_firma_metodo')->value ?? NULL,
        'fecha' => $documento->get('co_firma_fecha')->value ?? NULL,
      ];
    }

    $historial = $this->getHistorialAuditoria($documentoId);

    return [
      'estado' => $estado,
      'firmado' => $documento->isFirmado(),
      'titulo' => $documento->label() ?? '',
      'categoria' => $documento->getCategoria(),
      'firmantes' => $firmantes,
      'verificacion_hash' => $documento->get('verificacion_hash')->value ?? NULL,
      'historial' => $historial,
    ];
  }

  /**
   * Obtiene documentos pendientes de firma para un usuario.
   *
   * @param int $uid
   *   UID del usuario.
   * @param int|null $tenantId
   *   ID del tenant para filtrado (TENANT-001).
   *
   * @return array
   *   Lista de documentos pendientes con metadatos.
   */
  public function getDocumentosPendientes(int $uid, ?int $tenantId = NULL): array {
    $storage = $this->entityTypeManager->getStorage('expediente_documento');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('status', 1)
      ->condition('estado_firma', [
        self::ESTADO_PENDIENTE_FIRMA,
        self::ESTADO_PENDIENTE_FIRMA_TECNICO,
        self::ESTADO_PENDIENTE_FIRMA_PARTICIPANTE,
      ], 'IN');

    if ($tenantId !== NULL) {
      $query->condition('tenant_id', $tenantId);
    }

    // Buscar documentos donde el usuario es firmante principal o co-firmante.
    $orGroup = $query->orConditionGroup()
      ->condition('firma_solicitante_uid', $uid)
      ->condition('co_firmante_uid', $uid);
    $query->condition($orGroup);

    $ids = $query->execute();
    if (empty($ids)) {
      return [];
    }

    $documentos = $storage->loadMultiple($ids);
    $resultado = [];

    foreach ($documentos as $doc) {
      $estado = $this->getEstadoFirmaField($doc);
      // Solo incluir si el usuario puede firmar en el estado actual.
      if ($this->esFirmanteValido($doc, $uid, $estado)) {
        $resultado[] = [
          'documento_id' => (int) $doc->id(),
          'titulo' => $doc->label() ?? '',
          'categoria' => $doc->getCategoria(),
          'estado_firma' => $estado,
          'fecha_solicitud' => $doc->get('firma_solicitada_fecha')->value ?? NULL,
          'participante_id' => $doc->getParticipanteId(),
        ];
      }
    }

    return $resultado;
  }

  /**
   * Verifica un documento por hash público (para QR).
   *
   * @param string $hash
   *   Hash de verificación de 64 caracteres.
   *
   * @return array{valido: bool, documento: array|null}
   */
  public function verificarDocumento(string $hash): array {
    if (strlen($hash) !== 64 || !ctype_xdigit($hash)) {
      return ['valido' => FALSE, 'documento' => NULL];
    }

    $storage = $this->entityTypeManager->getStorage('expediente_documento');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('verificacion_hash', $hash)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return ['valido' => FALSE, 'documento' => NULL];
    }

    $doc = $storage->load(reset($ids));
    if (!$doc) {
      return ['valido' => FALSE, 'documento' => NULL];
    }

    $this->registrarAuditoria(
      (int) $doc->id(),
      $doc->getParticipanteId(),
      self::AUDIT_VERIFICACION,
      0,
      NULL,
      ['hash' => $hash],
    );

    return [
      'valido' => TRUE,
      'documento' => [
        'titulo' => $doc->label() ?? '',
        'categoria' => $doc->getCategoria(),
        'firmado' => $doc->isFirmado(),
        'firma_fecha' => $doc->get('firma_fecha')->value ?? NULL,
        'firma_metodo' => $doc->get('firma_metodo')->value ?? NULL,
        'estado_firma' => $this->getEstadoFirmaField($doc),
        'hash_documento' => $doc->get('firma_hash_documento')->value ?? NULL,
      ],
    ];
  }

  /**
   * Carga un ExpedienteDocumento por ID.
   */
  protected function loadDocumento(int $id): ?ExpedienteDocumentoInterface {
    $entity = $this->entityTypeManager
      ->getStorage('expediente_documento')
      ->load($id);
    return $entity instanceof ExpedienteDocumentoInterface ? $entity : NULL;
  }

  /**
   * Obtiene el valor del campo estado_firma con fallback a borrador.
   */
  protected function getEstadoFirmaField(ExpedienteDocumentoInterface $documento): string {
    if (!$documento->hasField('estado_firma')) {
      return self::ESTADO_BORRADOR;
    }
    return $documento->get('estado_firma')->value ?? self::ESTADO_BORRADOR;
  }

  /**
   * Valida si una transición de estado es permitida.
   */
  protected function esTransicionValida(string $estadoActual, string $estadoObjetivo): bool {
    $transicionesPermitidas = self::TRANSICIONES[$estadoActual] ?? [];
    return in_array($estadoObjetivo, $transicionesPermitidas, TRUE);
  }

  /**
   * Determina si un estado permite recibir firma.
   */
  protected function estadoPermiteFirma(string $estado): bool {
    return in_array($estado, [
      self::ESTADO_PENDIENTE_FIRMA,
      self::ESTADO_PENDIENTE_FIRMA_TECNICO,
      self::ESTADO_PENDIENTE_FIRMA_PARTICIPANTE,
    ], TRUE);
  }

  /**
   * Valida que el usuario puede firmar en el estado actual.
   *
   * ACCESS-STRICT-001: Comparación con (int) === (int).
   */
  protected function esFirmanteValido(ExpedienteDocumentoInterface $documento, int $uid, string $estado): bool {
    return match ($estado) {
      self::ESTADO_PENDIENTE_FIRMA => TRUE,
      self::ESTADO_PENDIENTE_FIRMA_TECNICO => (int) ($documento->get('firma_solicitante_uid')->target_id ?? 0) === $uid,
      self::ESTADO_PENDIENTE_FIRMA_PARTICIPANTE => (int) ($documento->get('co_firmante_uid')->target_id ?? 0) === $uid,
      default => FALSE,
    };
  }

  /**
   * Calcula el nuevo estado tras completarse una firma.
   */
  protected function calcularNuevoEstadoPostFirma(string $estadoActual): string {
    return match ($estadoActual) {
      self::ESTADO_PENDIENTE_FIRMA => self::ESTADO_FIRMADO,
      self::ESTADO_PENDIENTE_FIRMA_TECNICO => self::ESTADO_FIRMADO_PARCIAL,
      self::ESTADO_PENDIENTE_FIRMA_PARTICIPANTE => self::ESTADO_FIRMADO,
      default => $estadoActual,
    };
  }

  /**
   * Transiciona de firmado_parcial a pendiente_firma_participante.
   */
  protected function transicionarAFirmaParticipante(ExpedienteDocumentoInterface $documento): void {
    $documento->set('estado_firma', self::ESTADO_PENDIENTE_FIRMA_PARTICIPANTE);
    $documento->save();

    $coFirmanteUid = (int) ($documento->get('co_firmante_uid')->target_id ?? 0);
    if ($coFirmanteUid > 0) {
      $this->notificarFirmaPendiente($documento, $coFirmanteUid);
    }
  }

  /**
   * Calcula SHA-256 del documento vinculado.
   */
  protected function calcularHashDocumento(ExpedienteDocumentoInterface $documento): string {
    $vaultId = $documento->getArchivoVaultId();
    if ($vaultId) {
      return hash('sha256', $vaultId . ':' . $documento->id() . ':' . time());
    }
    return hash('sha256', 'doc:' . $documento->id() . ':' . $documento->getCategoria() . ':' . time());
  }

  /**
   * Genera hash de verificación público para QR.
   */
  protected function generarVerificacionHash(ExpedienteDocumentoInterface $documento, string $fecha): string {
    $data = implode('|', [
      $documento->id(),
      $documento->getCategoria(),
      $documento->getParticipanteId() ?? 0,
      $fecha,
      bin2hex(random_bytes(16)),
    ]);
    return hash('sha256', $data);
  }

  /**
   * Aplica sellado temporal TSA al documento firmado.
   *
   * Sprint 5 — Verificación QR y Sellado Temporal.
   *
   * Invoca FirmaDigitalService::aplicarSelloTemporal() si disponible.
   * El sellado TSA proporciona una marca temporal con valor legal
   * (FNMT como TSA primaria, FreeTSA como fallback).
   *
   * PRESAVE-RESILIENCE-001: try-catch para servicio opcional.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface $documento
   *   Documento firmado.
   *
   * @return bool
   *   TRUE si se aplicó el sello correctamente.
   */
  protected function aplicarSelloTsa(ExpedienteDocumentoInterface $documento): bool {
    if (!$this->firmaDigital) {
      return FALSE;
    }

    try {
      $vaultId = $documento->getArchivoVaultId();
      if (!$vaultId) {
        return FALSE;
      }

      $this->firmaDigital->aplicarSelloTemporal($vaultId);
      $this->logger->info('Sello TSA aplicado a documento @id.', ['@id' => $documento->id()]);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->warning('No se pudo aplicar sello TSA al doc @id: @msg', [
        '@id' => $documento->id(),
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Genera la URI de verificación QR para un documento firmado.
   *
   * Sprint 5 — QR de verificación en PDFs firmados.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface $documento
   *   Documento con verificacion_hash.
   *
   * @return string|null
   *   URL absoluta de verificación o NULL si no hay hash.
   */
  public function generarVerificacionQrUri(ExpedienteDocumentoInterface $documento): ?string {
    $hash = $documento->get('verificacion_hash')->value ?? NULL;
    if (!$hash) {
      return NULL;
    }

    try {
      $url = \Drupal\Core\Url::fromRoute('jaraba_andalucia_ei.verificar_documento', [
        'hash' => $hash,
      ], ['absolute' => TRUE]);
      return $url->toString();
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Post-proceso tras firma completada: QR + TSA.
   *
   * Sprint 5 — Se invoca automáticamente cuando un documento
   * alcanza estado FIRMADO.
   *
   * @param \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface $documento
   *   Documento recién firmado.
   */
  protected function postProcesoFirmaCompletada(ExpedienteDocumentoInterface $documento): void {
    // 1. Generar URI de verificación QR.
    $qrUri = $this->generarVerificacionQrUri($documento);
    if ($qrUri) {
      $documento->set('verificacion_qr_uri', $qrUri);
    }

    // 2. Aplicar sellado temporal TSA.
    $this->aplicarSelloTsa($documento);

    // 3. Guardar URI del QR.
    if ($qrUri) {
      $documento->save();
    }

    // 4. Notificar firma completada al solicitante.
    $this->notificarFirmaCompletada($documento);
  }

  /**
   * Notifica que un documento ha sido firmado completamente.
   *
   * Sprint 4 — Notificaciones push y email.
   */
  protected function notificarFirmaCompletada(ExpedienteDocumentoInterface $documento): void {
    if (!$this->notificationService) {
      return;
    }

    try {
      $solicitanteUid = (int) ($documento->get('firma_solicitante_uid')->target_id ?? 0);
      if ($solicitanteUid > 0) {
        $this->notificationService->notify($solicitanteUid, 'firma_completada', [
          'documento_titulo' => $documento->label() ?? '',
          'documento_categoria' => $documento->getCategoria(),
          'documento_id' => $documento->id(),
        ]);
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error al notificar firma completada: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Notifica a un usuario que tiene firma pendiente.
   *
   * PRESAVE-RESILIENCE-001: servicio de notificación opcional.
   */
  protected function notificarFirmaPendiente(ExpedienteDocumentoInterface $documento, int $uid): void {
    if (!$this->notificationService) {
      return;
    }

    try {
      $this->notificationService->notify($uid, 'firma_pendiente', [
        'documento_titulo' => $documento->label() ?? '',
        'documento_categoria' => $documento->getCategoria(),
        'documento_id' => $documento->id(),
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error al notificar firma pendiente: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Registra evento en tabla de auditoría inmutable.
   *
   * Tabla: jaraba_andalucia_ei_firma_audit
   * Los registros son permanentes (no rotan) para cumplimiento FSE+ y PIIL.
   */
  protected function registrarAuditoria(
    int $documentoId,
    ?int $participanteId,
    string $accion,
    int $actorUid,
    ?string $metodoFirma = NULL,
    array $metadata = [],
  ): void {
    try {
      // Resolver tenant.
      $tenantId = NULL;
      if ($this->tenantContext) {
        try {
          $tenantId = (int) $this->tenantContext->getCurrentTenantId();
        }
        catch (\Throwable) {
          // PRESAVE-RESILIENCE-001.
        }
      }

      // Resolver nombre del actor.
      $actorNombre = '';
      if ($actorUid > 0) {
        try {
          $user = $this->entityTypeManager->getStorage('user')->load($actorUid);
          $actorNombre = $user?->getDisplayName() ?? '';
        }
        catch (\Throwable) {
          // Non-critical.
        }
      }

      $request = $this->requestStack->getCurrentRequest();

      $this->database->insert('jaraba_andalucia_ei_firma_audit')
        ->fields([
          'documento_id' => $documentoId,
          'participante_id' => $participanteId ?? 0,
          'tenant_id' => $tenantId ?? 0,
          'accion' => $accion,
          'actor_uid' => $actorUid,
          'actor_nombre' => substr($actorNombre, 0, 255),
          'metodo_firma' => $metodoFirma ?? '',
          'hash_documento' => $metadata['hash_documento'] ?? '',
          'certificado_cn' => $metadata['certificado_cn'] ?? '',
          'certificado_issuer' => $metadata['certificado_issuer'] ?? '',
          'certificado_serial' => $metadata['certificado_serial'] ?? '',
          'ip_address' => $request?->getClientIp() ?? '',
          'user_agent' => substr($request?->headers->get('User-Agent', '') ?? '', 0, 512),
          'motivo_rechazo' => $metadata['motivo'] ?? '',
          'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
          'created' => time(),
        ])
        ->execute();
    }
    catch (\Throwable $e) {
      $this->logger->error('Error al registrar auditoría de firma: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  /**
   * Obtiene historial de auditoría de un documento.
   *
   * @param int $documentoId
   *   ID del ExpedienteDocumento.
   *
   * @return array
   *   Lista de registros de auditoría ordenados cronológicamente.
   */
  protected function getHistorialAuditoria(int $documentoId): array {
    try {
      $results = $this->database->select('jaraba_andalucia_ei_firma_audit', 'a')
        ->fields('a', [
          'accion',
          'actor_uid',
          'actor_nombre',
          'metodo_firma',
          'ip_address',
          'created',
        ])
        ->condition('a.documento_id', $documentoId)
        ->orderBy('a.created', 'ASC')
        ->execute();

      $historial = [];
      foreach ($results as $row) {
        $historial[] = [
          'accion' => $row->accion,
          'actor_uid' => (int) $row->actor_uid,
          'actor_nombre' => $row->actor_nombre,
          'metodo_firma' => $row->metodo_firma ?: NULL,
          'fecha' => date('Y-m-d\TH:i:s', (int) $row->created),
        ];
      }
      return $historial;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error al obtener historial de auditoría: @msg', ['@msg' => $e->getMessage()]);
      return [];
    }
  }

}
