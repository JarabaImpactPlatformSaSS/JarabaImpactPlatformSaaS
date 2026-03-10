<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Orquestador de generación de documentos + solicitud de firma.
 *
 * Sprint 3 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * Mapea cada categoría de ExpedienteDocumento a su flujo de firma
 * (simple participante, simple técnico, dual, sello empresa)
 * y orquesta la generación + solicitud de firma automáticamente.
 *
 * Elimina la desconexión entre "generar PDF" y "firmar PDF" que
 * hacía que firmado=TRUE se pusiera por código sin firma real.
 */
class DocumentoFirmaOrchestrator {

  /**
   * Categorías que requieren firma simple del participante.
   */
  protected const FIRMA_SIMPLE_PARTICIPANTE = [
    'sto_acuerdo_participacion',
    'sto_daci',
    'sto_recibi_incentivo',
    'sto_renuncia_incentivo',
    'programa_contrato',
    'programa_consentimiento',
    'programa_compromiso',
  ];

  /**
   * Categorías que requieren firma dual (técnico primero, luego participante).
   */
  protected const FIRMA_DUAL = [
    'sto_recibo_actuaciones',
    'orientacion_hoja_servicio',
    'formacion_hoja_servicio',
    'mentoria_hoja_servicio',
  ];

  /**
   * Categorías que requieren sello de empresa (coordinador).
   */
  protected const FIRMA_SELLO_EMPRESA = [
    'cert_formacion',
    'cert_competencias',
    'cert_participacion',
    'justificacion_trimestral',
    'justificacion_final',
  ];

  /**
   * Categorías que no requieren firma (documentos subidos por participante).
   */
  protected const SIN_FIRMA = [
    'sto_dni',
    'sto_empadronamiento',
    'sto_vida_laboral',
    'sto_demanda_empleo',
    'sto_prestaciones',
    'sto_titulo_academico',
    'sto_otros',
    'tarea_diagnostico',
    'tarea_plan_empleo',
    'tarea_cv',
    'tarea_carta',
    'tarea_proyecto',
    'tarea_entregable',
    'insercion_contrato_laboral',
    'insercion_alta_ss',
    'insercion_ficha',
    'formacion_vobo_sae',
    'prospeccion_informe',
    'intermediacion_informe',
    'indicadores_fse_entrada',
    'indicadores_fse_salida',
    'indicadores_fse_6m',
  ];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?FirmaWorkflowService $firmaWorkflow = NULL,
  ) {}

  /**
   * Genera documento y solicita firma según categoría.
   *
   * Punto de integración para todos los servicios de generación.
   * Después de que un servicio cree el ExpedienteDocumento vía
   * ExpedienteService::createDocument(), debe llamar a este método
   * para iniciar el flujo de firma correspondiente.
   *
   * @param int $documentoId
   *   ID del ExpedienteDocumento recién creado.
   * @param string $categoria
   *   Categoría del documento.
   * @param int $participanteUid
   *   UID del participante (propietario del documento).
   * @param int|null $tecnicoUid
   *   UID del técnico/orientador (para firma dual).
   *
   * @return array{success: bool, message: string, estado: string, tipo_firma: string}
   */
  public function solicitarFirmaSegunCategoria(
    int $documentoId,
    string $categoria,
    int $participanteUid,
    ?int $tecnicoUid = NULL,
  ): array {
    if (!$this->firmaWorkflow) {
      $this->logger->warning('FirmaWorkflowService no disponible. Documento @id sin firma.', [
        '@id' => $documentoId,
      ]);
      return [
        'success' => FALSE,
        'message' => 'Servicio de firma no disponible.',
        'estado' => 'borrador',
        'tipo_firma' => 'ninguno',
      ];
    }

    $tipoFirma = $this->resolverTipoFirma($categoria);

    return match ($tipoFirma) {
      'simple_participante' => $this->solicitarFirmaSimple($documentoId, $participanteUid, $tipoFirma),
      'dual' => $this->solicitarFirmaDual($documentoId, $tecnicoUid ?? 0, $participanteUid, $tipoFirma),
      'sello_empresa' => $this->solicitarFirmaSello($documentoId, $tipoFirma),
      default => [
        'success' => TRUE,
        'message' => 'Documento no requiere firma.',
        'estado' => 'borrador',
        'tipo_firma' => 'ninguno',
      ],
    };
  }

  /**
   * Resuelve el tipo de firma requerido para una categoría.
   *
   * @param string $categoria
   *   Categoría del documento.
   *
   * @return string
   *   simple_participante, dual, sello_empresa, ninguno.
   */
  public function resolverTipoFirma(string $categoria): string {
    if (in_array($categoria, self::FIRMA_SIMPLE_PARTICIPANTE, TRUE)) {
      return 'simple_participante';
    }
    if (in_array($categoria, self::FIRMA_DUAL, TRUE)) {
      return 'dual';
    }
    if (in_array($categoria, self::FIRMA_SELLO_EMPRESA, TRUE)) {
      return 'sello_empresa';
    }
    return 'ninguno';
  }

  /**
   * Indica si una categoría requiere algún tipo de firma.
   */
  public function requiereFirma(string $categoria): bool {
    return $this->resolverTipoFirma($categoria) !== 'ninguno';
  }

  /**
   * Indica si una categoría requiere firma dual.
   */
  public function requiereFirmaDual(string $categoria): bool {
    return in_array($categoria, self::FIRMA_DUAL, TRUE);
  }

  /**
   * Obtiene info de firma para el frontend.
   *
   * @param string $categoria
   *   Categoría del documento.
   *
   * @return array{tipo: string, requiere_firma: bool, dual: bool, firmantes: string[]}
   */
  public function getInfoFirma(string $categoria): array {
    $tipo = $this->resolverTipoFirma($categoria);
    return [
      'tipo' => $tipo,
      'requiere_firma' => $tipo !== 'ninguno',
      'dual' => $tipo === 'dual',
      'firmantes' => match ($tipo) {
        'simple_participante' => ['participante'],
        'dual' => ['tecnico', 'participante'],
        'sello_empresa' => ['coordinador'],
        default => [],
      },
    ];
  }

  /**
   * Solicita firma simple.
   */
  protected function solicitarFirmaSimple(int $documentoId, int $firmanteUid, string $tipoFirma): array {
    $result = $this->firmaWorkflow->solicitarFirma($documentoId, $firmanteUid);
    $result['tipo_firma'] = $tipoFirma;
    return $result;
  }

  /**
   * Solicita firma dual (técnico primero, luego participante).
   */
  protected function solicitarFirmaDual(int $documentoId, int $tecnicoUid, int $participanteUid, string $tipoFirma): array {
    if ($tecnicoUid <= 0) {
      $this->logger->warning('Firma dual solicitada sin UID de técnico para doc @id.', [
        '@id' => $documentoId,
      ]);
      // Fallback: firma simple del participante.
      return $this->solicitarFirmaSimple($documentoId, $participanteUid, 'simple_participante');
    }

    $result = $this->firmaWorkflow->solicitarFirmaDual($documentoId, $tecnicoUid, $participanteUid);
    $result['tipo_firma'] = $tipoFirma;
    return $result;
  }

  /**
   * Solicita sello de empresa (firma automática con certificado PKCS#12).
   */
  protected function solicitarFirmaSello(int $documentoId, string $tipoFirma): array {
    $result = $this->firmaWorkflow->procesarFirmaSello($documentoId);
    $result['tipo_firma'] = $tipoFirma;
    return $result;
  }

}
