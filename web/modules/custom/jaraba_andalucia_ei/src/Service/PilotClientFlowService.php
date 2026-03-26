<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Orquesta el flujo de cliente piloto desde prospección hasta cliente activo.
 *
 * Gestiona la conversión de negocios prospectados (NegocioProspectadoEi) a
 * clientes piloto (ClienteParticipanteEi) y su posterior activación con
 * facturación recurrente vía Stripe.
 *
 * PRESAVE-RESILIENCE-001: Todos los métodos usan try-catch \Throwable.
 * TENANT-001: Queries filtradas por tenant_id donde aplique.
 */
class PilotClientFlowService {

  /**
   * Constructs a PilotClientFlowService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   El gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   El canal de log para andalucia_ei.
   * @param \Drupal\jaraba_andalucia_ei\Service\FirmaWorkflowService|null $firmaWorkflow
   *   El servicio de flujo de firma (opcional, @?).
   * @param \Drupal\jaraba_andalucia_ei\Service\CatalogoPacksService|null $catalogoPacks
   *   El servicio de catálogo de packs (opcional, @?).
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ?FirmaWorkflowService $firmaWorkflow = NULL,
    protected readonly ?CatalogoPacksService $catalogoPacks = NULL,
  ) {}

  /**
   * Convierte un negocio prospectado en cliente piloto.
   *
   * Crea una entidad ClienteParticipanteEi con es_piloto=TRUE y estado='piloto',
   * copiando datos clave desde NegocioProspectadoEi (nombre, sector, contacto)
   * y asociando el pack confirmado del participante.
   *
   * @param int $negocioId
   *   ID del NegocioProspectadoEi a convertir.
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi asociado.
   *
   * @return int|null
   *   ID del nuevo ClienteParticipanteEi o NULL en error.
   */
  public function convertirProspeccionAClientePiloto(int $negocioId, int $participanteId): ?int {
    try {
      // Cargar negocio prospectado.
      $negocioStorage = $this->entityTypeManager->getStorage('negocio_prospectado_ei');
      /** @var \Drupal\jaraba_andalucia_ei\Entity\NegocioProspectadoEi|null $negocio */
      $negocio = $negocioStorage->load($negocioId);

      if ($negocio === NULL) {
        $this->logger->warning('NegocioProspectadoEi @id no encontrado para conversión a piloto.', [
          '@id' => $negocioId,
        ]);
        return NULL;
      }

      // Cargar participante para obtener pack_confirmado.
      $participanteStorage = $this->entityTypeManager->getStorage('programa_participante_ei');
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $participante */
      $participante = $participanteStorage->load($participanteId);

      if ($participante === NULL) {
        $this->logger->warning('ProgramaParticipanteEi @id no encontrado para conversión a piloto.', [
          '@id' => $participanteId,
        ]);
        return NULL;
      }

      // Resolver pack_contratado desde el participante.
      $packContratado = NULL;
      if ($participante->hasField('pack_confirmado')) {
        $packContratado = $participante->get('pack_confirmado')->target_id;
      }

      // Crear ClienteParticipanteEi.
      $clienteStorage = $this->entityTypeManager->getStorage('cliente_participante_ei');
      /** @var \Drupal\Core\Entity\ContentEntityInterface $cliente */
      $cliente = $clienteStorage->create([
        'es_piloto' => TRUE,
        'estado' => 'piloto',
        'nombre_negocio' => $negocio->getNombreNegocio(),
        'sector' => $negocio->getSector(),
        'email' => (string) ($negocio->get('email')->value ?? ''),
        'telefono' => (string) ($negocio->get('telefono')->value ?? ''),
        'participante_id' => $participanteId,
        'negocio_origen_id' => $negocioId,
        'pack_contratado' => $packContratado,
      ]);

      $cliente->save();
      $clienteId = (int) $cliente->id();

      $this->logger->info('Negocio @negocio convertido a cliente piloto @cliente (participante @part).', [
        '@negocio' => $negocioId,
        '@cliente' => $clienteId,
        '@part' => $participanteId,
      ]);

      return $clienteId;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error convirtiendo negocio @id a cliente piloto: @message', [
        '@id' => $negocioId,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Convierte un cliente piloto en cliente activo.
   *
   * Cambia el estado de 'piloto' a 'activo', establece la fecha de inicio
   * y desactiva la marca de piloto. Si PackBillingActivationService está
   * disponible, activa la facturación del pack asociado.
   *
   * @param int $clienteId
   *   ID del ClienteParticipanteEi.
   *
   * @return bool
   *   TRUE si la conversión fue exitosa, FALSE en error.
   */
  public function convertirPilotoAClienteActivo(int $clienteId): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('cliente_participante_ei');
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $cliente */
      $cliente = $storage->load($clienteId);

      if ($cliente === NULL) {
        $this->logger->warning('ClienteParticipanteEi @id no encontrado para conversión a activo.', [
          '@id' => $clienteId,
        ]);
        return FALSE;
      }

      // Cambiar estado y desactivar marca piloto.
      $cliente->set('estado', 'activo');
      $cliente->set('es_piloto', FALSE);

      if ($cliente->hasField('fecha_inicio')) {
        $cliente->set('fecha_inicio', date('Y-m-d\TH:i:s'));
      }

      $cliente->save();

      // Activar billing del pack si el servicio está disponible.
      $this->activarBillingPackSiDisponible($cliente);

      $this->logger->info('Cliente piloto @id convertido a activo.', [
        '@id' => $clienteId,
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error convirtiendo cliente piloto @id a activo: @message', [
        '@id' => $clienteId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Registra feedback del periodo piloto.
   *
   * Actualiza el campo feedback_piloto del ClienteParticipanteEi
   * con las observaciones del cliente tras el periodo de prueba.
   *
   * @param int $clienteId
   *   ID del ClienteParticipanteEi.
   * @param string $feedback
   *   Texto de feedback del cliente.
   *
   * @return bool
   *   TRUE si se registró correctamente, FALSE en error.
   */
  public function registrarFeedbackPiloto(int $clienteId, string $feedback): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('cliente_participante_ei');
      /** @var \Drupal\Core\Entity\ContentEntityInterface|null $cliente */
      $cliente = $storage->load($clienteId);

      if ($cliente === NULL) {
        $this->logger->warning('ClienteParticipanteEi @id no encontrado para registrar feedback.', [
          '@id' => $clienteId,
        ]);
        return FALSE;
      }

      if (!$cliente->hasField('feedback_piloto')) {
        $this->logger->warning('ClienteParticipanteEi @id no tiene campo feedback_piloto.', [
          '@id' => $clienteId,
        ]);
        return FALSE;
      }

      $cliente->set('feedback_piloto', $feedback);
      $cliente->save();

      $this->logger->info('Feedback piloto registrado para cliente @id.', [
        '@id' => $clienteId,
      ]);

      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error registrando feedback para cliente @id: @message', [
        '@id' => $clienteId,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene estadísticas de pilotos para un participante.
   *
   * Consulta ClienteParticipanteEi filtrado por participante_id y tenant_id
   * para calcular totales, activos, convertidos y tasa de conversión.
   *
   * TENANT-001: Filtra por tenant_id.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   * @param int $tenantId
   *   ID del tenant (grupo) para filtrar.
   *
   * @return array<string, mixed>
   *   Array con claves: total_pilotos, pilotos_activos, pilotos_convertidos,
   *   tasa_conversion (float 0.0-100.0).
   */
  public function getEstadisticasPiloto(int $participanteId, int $tenantId): array {
    $resultado = [
      'total_pilotos' => 0,
      'pilotos_activos' => 0,
      'pilotos_convertidos' => 0,
      'tasa_conversion' => 0.0,
    ];

    try {
      $storage = $this->entityTypeManager->getStorage('cliente_participante_ei');

      // Total pilotos (todos los que alguna vez fueron piloto).
      $totalQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('tenant_id', $tenantId);
      $totalIds = $totalQuery->execute();
      $resultado['total_pilotos'] = count($totalIds);

      if ($resultado['total_pilotos'] === 0) {
        return $resultado;
      }

      // Pilotos activos (estado = 'activo').
      $activosQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('tenant_id', $tenantId)
        ->condition('estado', 'activo');
      $activosIds = $activosQuery->execute();
      $resultado['pilotos_activos'] = count($activosIds);

      // Pilotos convertidos (es_piloto = FALSE y estado = 'activo').
      $convertidosQuery = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('tenant_id', $tenantId)
        ->condition('es_piloto', FALSE)
        ->condition('estado', 'activo');
      $convertidosIds = $convertidosQuery->execute();
      $resultado['pilotos_convertidos'] = count($convertidosIds);

      // Tasa de conversión.
      $resultado['tasa_conversion'] = round(
        ($resultado['pilotos_convertidos'] / $resultado['total_pilotos']) * 100,
        2,
      );

      return $resultado;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo estadísticas piloto para participante @id: @message', [
        '@id' => $participanteId,
        '@message' => $e->getMessage(),
      ]);
      return $resultado;
    }
  }

  /**
   * Activa billing del pack asociado al cliente si el servicio está disponible.
   *
   * Usa \Drupal::service() como lazy-load porque PackBillingActivationService
   * no es dependencia directa del constructor (evita dependencia circular).
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $cliente
   *   La entidad ClienteParticipanteEi.
   */
  protected function activarBillingPackSiDisponible(\Drupal\Core\Entity\ContentEntityInterface $cliente): void {
    try {
      if (!$cliente->hasField('pack_contratado')) {
        return;
      }

      $packId = $cliente->get('pack_contratado')->target_id;
      if ($packId === NULL) {
        return;
      }

      if (!\Drupal::hasService('jaraba_andalucia_ei.pack_billing_activation')) {
        return;
      }

      /** @var \Drupal\jaraba_andalucia_ei\Service\PackBillingActivationService $billingService */
      $billingService = \Drupal::service('jaraba_andalucia_ei.pack_billing_activation');
      $billingService->activarBillingPack((int) $packId);
    }
    catch (\Throwable $e) {
      $this->logger->warning('No se pudo activar billing para pack del cliente @id: @message', [
        '@id' => $cliente->id(),
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
