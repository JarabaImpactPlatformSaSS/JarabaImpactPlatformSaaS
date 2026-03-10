<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Puente entre participantes +ei y el sistema de matching jaraba_matching.
 *
 * Sprint 9 — Plan Maestro Andalucía +ei Clase Mundial.
 *
 * Matching bidireccional Participante↔Empresa: sincroniza perfiles candidato,
 * ejecuta matching semántico contra ofertas activas vía Qdrant y permite
 * a las empresas ver candidatos compatibles de forma anonimizada.
 */
class EiMatchingBridgeService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $matchingService = NULL,
    protected ?object $profileService = NULL,
    protected ?object $skillsService = NULL,
    protected ?object $tenantContext = NULL,
  ) {}

  /**
   * Crea o actualiza el perfil candidato desde datos del participante.
   *
   * Extrae competencias, formación, experiencia y preferencias del
   * ProgramaParticipanteEi y los sincroniza con CandidateProfile
   * del módulo jaraba_matching.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return bool
   *   TRUE si se sincronizó correctamente.
   */
  public function sincronizarPerfilCandidato(int $participanteId): bool {
    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        $this->logger->warning('Participante @id no encontrado para sincronizar perfil.', [
          '@id' => $participanteId,
        ]);
        return FALSE;
      }

      if (!$this->profileService) {
        $this->logger->info('Servicio de perfiles no disponible; sincronización omitida para @id.', [
          '@id' => $participanteId,
        ]);
        return FALSE;
      }

      // Extraer competencias del participante.
      $competencias = [];
      if ($this->skillsService) {
        try {
          $competencias = $this->skillsService->getCompetencias($participanteId);
        }
        catch (\Throwable $e) {
          $this->logger->warning('Error obteniendo competencias participante @id: @msg', [
            '@id' => $participanteId,
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      // Construir datos del perfil candidato.
      $datoPerfil = [
        'source_entity_type' => 'programa_participante_ei',
        'source_entity_id' => $participanteId,
        'uid' => $participante->getOwnerId(),
        'competencias' => $competencias,
        'formacion' => $participante->hasField('nivel_formativo')
          ? ($participante->get('nivel_formativo')->value ?? '')
          : '',
        'experiencia_sector' => $participante->hasField('experiencia_sector')
          ? ($participante->get('experiencia_sector')->value ?? '')
          : '',
        'disponibilidad' => $participante->hasField('disponibilidad')
          ? ($participante->get('disponibilidad')->value ?? 'completa')
          : 'completa',
        'ubicacion' => $participante->hasField('municipio')
          ? ($participante->get('municipio')->value ?? '')
          : '',
      ];

      // Resolver tenant.
      if ($participante->hasField('tenant_id') && !$participante->get('tenant_id')->isEmpty()) {
        $datoPerfil['tenant_id'] = (int) $participante->get('tenant_id')->target_id;
      }

      $this->profileService->syncCandidateProfile($datoPerfil);

      $this->logger->info('Perfil candidato sincronizado para participante @id.', [
        '@id' => $participanteId,
      ]);
      return TRUE;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error sincronizando perfil candidato @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Ejecuta matching semántico contra ofertas activas vía Qdrant.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array
   *   Lista de matches con score, job_posting_id, empresa (anonimizada).
   */
  public function ejecutarMatching(int $participanteId): array {
    try {
      if (!$this->matchingService) {
        $this->logger->info('Servicio de matching no disponible; ejecución omitida para @id.', [
          '@id' => $participanteId,
        ]);
        return [];
      }

      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return [];
      }

      // Asegurar perfil actualizado antes de ejecutar matching.
      $this->sincronizarPerfilCandidato($participanteId);

      $resultados = $this->matchingService->executeMatching([
        'candidate_source' => 'programa_participante_ei',
        'candidate_id' => $participanteId,
        'uid' => $participante->getOwnerId(),
        'strategy' => 'semantic_qdrant',
      ]);

      $this->logger->info('Matching ejecutado para participante @id: @count resultados.', [
        '@id' => $participanteId,
        '@count' => count($resultados),
      ]);

      return $resultados;
    }
    catch (\Throwable $e) {
      $this->logger->error('Error ejecutando matching participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene los matches de un participante ordenados por score.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   * @param int $limit
   *   Número máximo de resultados.
   *
   * @return array
   *   Lista de matches con score, empresa, puesto y fecha.
   */
  public function getMatchesPorParticipante(int $participanteId, int $limit = 5): array {
    try {
      if (!$this->matchingService) {
        return [];
      }

      return $this->matchingService->getMatchesForCandidate(
        'programa_participante_ei',
        $participanteId,
        $limit,
      );
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo matches participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene participantes compatibles anonimizados para una empresa.
   *
   * Devuelve datos sin PII: competencias, formación, experiencia y score.
   * La empresa no ve nombre ni datos personales hasta aceptación mutua.
   *
   * @param int $prospeccionId
   *   ID de la ProspeccionEmpresaEi.
   * @param int $limit
   *   Número máximo de resultados.
   *
   * @return array
   *   Lista de candidatos anonimizados con score de compatibilidad.
   */
  public function getMatchesPorEmpresa(int $prospeccionId, int $limit = 10): array {
    try {
      if (!$this->matchingService) {
        return [];
      }

      $prospeccion = $this->entityTypeManager
        ->getStorage('prospeccion_empresa_ei')
        ->load($prospeccionId);

      if (!$prospeccion) {
        $this->logger->warning('Prospección @id no encontrada para matching empresa.', [
          '@id' => $prospeccionId,
        ]);
        return [];
      }

      $candidatos = $this->matchingService->getMatchesForEmployer(
        'prospeccion_empresa_ei',
        $prospeccionId,
        $limit,
      );

      // Anonimizar resultados: eliminar PII.
      return array_map(static function (array $match): array {
        unset(
          $match['nombre'],
          $match['email'],
          $match['telefono'],
          $match['dni'],
          $match['direccion'],
        );
        return $match;
      }, $candidatos);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo matches empresa @id: @msg', [
        '@id' => $prospeccionId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Notifica a participante y orientador sobre un nuevo match.
   *
   * @param int $matchId
   *   ID del match a notificar.
   */
  public function notificarMatch(int $matchId): void {
    try {
      if (!$this->matchingService) {
        return;
      }

      $match = $this->matchingService->loadMatch($matchId);
      if (!$match) {
        $this->logger->warning('Match @id no encontrado para notificación.', [
          '@id' => $matchId,
        ]);
        return;
      }

      // Notificar al participante.
      $this->matchingService->notifyCandidate($matchId);

      // Notificar al orientador asignado si existe.
      $candidateId = $match['candidate_id'] ?? NULL;
      if ($candidateId) {
        $participante = $this->entityTypeManager
          ->getStorage('programa_participante_ei')
          ->load($candidateId);

        if ($participante && $participante->hasField('orientador_id') && !$participante->get('orientador_id')->isEmpty()) {
          $orientadorUid = (int) $participante->get('orientador_id')->target_id;
          $this->matchingService->notifyOrientador($matchId, $orientadorUid);
        }
      }

      $this->logger->info('Notificación de match @id enviada.', ['@id' => $matchId]);
    }
    catch (\Throwable $e) {
      $this->logger->error('Error notificando match @id: @msg', [
        '@id' => $matchId,
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
