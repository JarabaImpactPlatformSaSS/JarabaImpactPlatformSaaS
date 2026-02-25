<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumento;
use Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface;
use Drupal\jaraba_andalucia_ei\Entity\ProgramaParticipanteEiInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de expediente documental.
 *
 * Gestiona la subida, consulta y validación de documentos
 * del expediente de participantes Andalucía +ei.
 * Integra con jaraba_legal_vault para almacenamiento encriptado
 * y ecosistema_jaraba_core.firma_digital para firmas.
 */
class ExpedienteService {

  /**
   * Constructs an ExpedienteService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param object|null $documentVault
   *   The document vault service (jaraba_legal_vault.document_vault).
   * @param object|null $firmaDigital
   *   The digital signature service (ecosistema_jaraba_core.firma_digital).
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected ?object $documentVault,
    protected ?object $firmaDigital,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Sube un documento al expediente de un participante.
   *
   * @param int $participanteId
   *   ID del participante.
   * @param string $categoria
   *   Categoría del documento.
   * @param string $titulo
   *   Título descriptivo.
   * @param string $contenido
   *   Contenido binario del archivo.
   * @param string $nombreArchivo
   *   Nombre original del archivo.
   * @param string $mimeType
   *   Tipo MIME.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface|null
   *   El documento creado o NULL si falla.
   */
  public function subirDocumento(
    int $participanteId,
    string $categoria,
    string $titulo,
    string $contenido,
    string $nombreArchivo,
    string $mimeType,
  ): ?ExpedienteDocumentoInterface {
    $storage = $this->entityTypeManager->getStorage('expediente_documento');

    // Store in vault if available.
    $vaultId = NULL;
    if ($this->documentVault) {
      try {
        $result = $this->documentVault->store(
          content: $contenido,
          title: $titulo,
          originalFilename: $nombreArchivo,
          mimeType: $mimeType,
          caseId: $participanteId,
        );
        if ($result['success']) {
          $vaultId = (string) $result['document']->id();
        }
      }
      catch (\Exception $e) {
        $this->logger->error('Error storing document in vault: @message', [
          '@message' => $e->getMessage(),
        ]);
      }
    }

    // Determine if document is STO-required.
    $requeridoSto = str_starts_with($categoria, 'sto_');

    /** @var \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface $documento */
    $documento = $storage->create([
      'participante_id' => $participanteId,
      'titulo' => $titulo,
      'categoria' => $categoria,
      'archivo_vault_id' => $vaultId,
      'archivo_nombre' => $nombreArchivo,
      'archivo_mime' => $mimeType,
      'archivo_tamano' => strlen($contenido),
      'estado_revision' => 'pendiente',
      'requerido_sto' => $requeridoSto,
      'uid' => $this->currentUser->id(),
    ]);

    // Inherit tenant from participante.
    $participante = $this->entityTypeManager->getStorage('programa_participante_ei')->load($participanteId);
    if ($participante && $participante->hasField('tenant_id') && !$participante->get('tenant_id')->isEmpty()) {
      $documento->set('tenant_id', $participante->get('tenant_id')->target_id);
    }

    try {
      $documento->save();
      $this->logger->info('Documento expediente creado: @titulo (participante @id)', [
        '@titulo' => $titulo,
        '@id' => $participanteId,
      ]);
      return $documento;
    }
    catch (\Exception $e) {
      $this->logger->error('Error creating expediente documento: @message', [
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Lista documentos de un participante, opcionalmente filtrados por categoría.
   *
   * @param int $participanteId
   *   ID del participante.
   * @param string|null $categoria
   *   Categoría a filtrar (NULL para todas).
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface[]
   *   Documentos encontrados.
   */
  public function listarDocumentos(int $participanteId, ?string $categoria = NULL): array {
    $storage = $this->entityTypeManager->getStorage('expediente_documento');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('participante_id', $participanteId)
      ->condition('status', 1)
      ->sort('created', 'DESC');

    if ($categoria !== NULL) {
      $query->condition('categoria', $categoria);
    }

    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Obtiene el porcentaje de completitud documental por categoría.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return array
   *   Array con claves: total_requeridos, completados, porcentaje, por_categoria.
   */
  public function getCompletuDocumental(int $participanteId): array {
    $stoCategories = array_filter(
      array_keys(ExpedienteDocumento::CATEGORIAS),
      fn(string $key) => str_starts_with($key, 'sto_') && $key !== 'sto_otros',
    );

    $documentos = $this->listarDocumentos($participanteId);
    $aprobadosPorCategoria = [];
    foreach ($documentos as $doc) {
      if ($doc->getEstadoRevision() === 'aprobado') {
        $aprobadosPorCategoria[$doc->getCategoria()] = TRUE;
      }
    }

    $porCategoria = [];
    $completados = 0;
    foreach ($stoCategories as $cat) {
      $ok = isset($aprobadosPorCategoria[$cat]);
      $porCategoria[$cat] = [
        'label' => ExpedienteDocumento::CATEGORIAS[$cat] ?? $cat,
        'completo' => $ok,
      ];
      if ($ok) {
        $completados++;
      }
    }

    $total = count($stoCategories);

    return [
      'total_requeridos' => $total,
      'completados' => $completados,
      'porcentaje' => $total > 0 ? round(($completados / $total) * 100) : 0,
      'por_categoria' => $porCategoria,
    ];
  }

  /**
   * Firma un documento digitalmente.
   *
   * @param int $documentoId
   *   ID del documento.
   *
   * @return bool
   *   TRUE si la firma fue exitosa.
   */
  public function firmarDocumento(int $documentoId): bool {
    if (!$this->firmaDigital) {
      $this->logger->warning('FirmaDigitalService not available.');
      return FALSE;
    }

    $documento = $this->entityTypeManager->getStorage('expediente_documento')->load($documentoId);
    if (!$documento) {
      return FALSE;
    }

    // Only sign if vault reference exists and document is approved.
    if (!$documento->getArchivoVaultId() || $documento->getEstadoRevision() !== 'aprobado') {
      return FALSE;
    }

    try {
      $certInfo = $this->firmaDigital->getCertificateInfo();
      $documento->set('firmado', TRUE);
      $documento->set('firma_fecha', date('Y-m-d\TH:i:s'));
      if ($certInfo) {
        $documento->set('firma_certificado_info', json_encode($certInfo, JSON_THROW_ON_ERROR));
      }
      $documento->save();

      $this->logger->info('Documento @id firmado digitalmente.', ['@id' => $documentoId]);
      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error signing document @id: @message', [
        '@id' => $documentoId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Marca un documento como revisado.
   *
   * @param int $documentoId
   *   ID del documento.
   * @param string $estado
   *   Nuevo estado (aprobado, rechazado, requiere_cambios).
   * @param string|null $notas
   *   Notas del revisor.
   */
  public function marcarRevisado(int $documentoId, string $estado, ?string $notas = NULL): void {
    $documento = $this->entityTypeManager->getStorage('expediente_documento')->load($documentoId);
    if (!$documento) {
      return;
    }

    $documento->setEstadoRevision($estado);
    $documento->set('revisor_id', $this->currentUser->id());
    if ($notas !== NULL) {
      $documento->set('revision_humana_notas', $notas);
    }
    $documento->save();

    $this->logger->info('Documento @id marcado como @estado por usuario @uid.', [
      '@id' => $documentoId,
      '@estado' => $estado,
      '@uid' => $this->currentUser->id(),
    ]);
  }

  /**
   * Obtiene documentos requeridos para STO de un participante.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return \Drupal\jaraba_andalucia_ei\Entity\ExpedienteDocumentoInterface[]
   *   Documentos STO.
   */
  public function getDocumentosParaSto(int $participanteId): array {
    $storage = $this->entityTypeManager->getStorage('expediente_documento');
    $ids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('participante_id', $participanteId)
      ->condition('requerido_sto', TRUE)
      ->condition('status', 1)
      ->sort('categoria')
      ->execute();

    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * Verifica si todos los documentos STO requeridos están completos.
   *
   * @param int $participanteId
   *   ID del participante.
   *
   * @return bool
   *   TRUE si todos los documentos STO requeridos están aprobados.
   */
  public function verificarDocumentosCompletos(int $participanteId): bool {
    $completitud = $this->getCompletuDocumental($participanteId);
    return $completitud['porcentaje'] === 100.0 || $completitud['porcentaje'] === 100;
  }

}
