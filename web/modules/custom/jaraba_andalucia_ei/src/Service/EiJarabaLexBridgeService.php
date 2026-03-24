<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Puente entre Andalucía +ei y jaraba_jarabalex.
 *
 * Módulo 3 (Trámites Administrativos): acceso a ayudas, calendario fiscal y
 * plantillas laborales del vertical JarabaLex.
 * Patrón OPTIONAL-CROSSMODULE-001: dependencia @? en services.yml.
 */
class EiJarabaLexBridgeService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ?object $legalService = NULL,
  ) {}

  /**
   * Comprueba si el servicio de JarabaLex está disponible.
   */
  public function isAvailable(): bool {
    return $this->legalService !== NULL;
  }

  /**
   * Obtiene las ayudas y subvenciones disponibles para un participante.
   *
   * Filtra por perfil del participante (sector, provincia, situación laboral)
   * para mostrar ayudas relevantes del catálogo de JarabaLex.
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array<int, array{id: int, titulo: string, organismo: string, plazo_fin: string|null, importe_maximo: float|null, requisitos_resumen: string}>
   *   Lista de ayudas disponibles, vacía si no hay datos o el servicio
   *   no está disponible.
   */
  public function getAyudasDisponibles(int $participanteId): array {
    if (!$this->legalService) {
      return [];
    }

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return [];
      }

      // Construir filtros según el perfil del participante.
      $filtros = [
        'tipo' => 'ayuda_subvencion',
        'activas' => TRUE,
      ];

      if ($participante->hasField('provincia') && !$participante->get('provincia')->isEmpty()) {
        $filtros['provincia'] = (string) $participante->get('provincia')->value;
      }

      if ($participante->hasField('sector_interes') && !$participante->get('sector_interes')->isEmpty()) {
        $filtros['sector'] = (string) $participante->get('sector_interes')->value;
      }

      // Delegar al legal service si soporta el método.
      if (method_exists($this->legalService, 'getAyudasFiltradas')) {
        $resultado = $this->legalService->getAyudasFiltradas($filtros);
        return is_array($resultado) ? $resultado : [];
      }

      // Fallback: consulta directa a normativa_legal entity.
      $storage = $this->entityTypeManager->getStorage('normativa_legal');
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tipo', 'ayuda_subvencion')
        ->condition('status', 1)
        ->sort('plazo_fin', 'ASC')
        ->range(0, 20);

      $ids = $query->execute();
      if (empty($ids)) {
        return [];
      }

      $normativas = $storage->loadMultiple($ids);
      $ayudas = [];

      foreach ($normativas as $normativa) {
        $ayudas[] = [
          'id' => (int) $normativa->id(),
          'titulo' => $normativa->label() ?? '',
          'organismo' => $normativa->hasField('organismo') && !$normativa->get('organismo')->isEmpty()
            ? (string) $normativa->get('organismo')->value
            : '',
          'plazo_fin' => $normativa->hasField('plazo_fin') && !$normativa->get('plazo_fin')->isEmpty()
            ? (string) $normativa->get('plazo_fin')->value
            : NULL,
          'importe_maximo' => $normativa->hasField('importe_maximo') && !$normativa->get('importe_maximo')->isEmpty()
            ? (float) $normativa->get('importe_maximo')->value
            : NULL,
          'requisitos_resumen' => $normativa->hasField('requisitos_resumen') && !$normativa->get('requisitos_resumen')->isEmpty()
            ? (string) $normativa->get('requisitos_resumen')->value
            : '',
        ];
      }

      return $ayudas;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo ayudas disponibles para participante @pid: @msg', [
        '@pid' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene el calendario fiscal relevante para un participante.
   *
   * Devuelve obligaciones fiscales próximas según el perfil profesional
   * del participante (autónomo, SL, cooperativa, etc.).
   *
   * @param int $participanteId
   *   ID del ProgramaParticipanteEi.
   *
   * @return array<int, array{id: int, obligacion: string, modelo: string, fecha_limite: string, periodicidad: string, descripcion: string}>
   *   Lista de obligaciones fiscales próximas, vacía si no hay datos.
   */
  public function getCalendarioFiscal(int $participanteId): array {
    if (!$this->legalService) {
      return [];
    }

    try {
      $participante = $this->entityTypeManager
        ->getStorage('programa_participante_ei')
        ->load($participanteId);

      if (!$participante) {
        return [];
      }

      // Determinar forma jurídica para filtrar obligaciones.
      $formaJuridica = 'autonomo';
      if ($participante->hasField('forma_juridica') && !$participante->get('forma_juridica')->isEmpty()) {
        $formaJuridica = (string) $participante->get('forma_juridica')->value;
      }

      // Delegar al legal service.
      if (method_exists($this->legalService, 'getCalendarioFiscal')) {
        $resultado = $this->legalService->getCalendarioFiscal($formaJuridica);
        return is_array($resultado) ? $resultado : [];
      }

      // Fallback: consulta directa a obligacion_fiscal entity.
      $storage = $this->entityTypeManager->getStorage('obligacion_fiscal');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('forma_juridica', [$formaJuridica, 'todas'], 'IN')
        ->sort('fecha_limite', 'ASC')
        ->range(0, 20)
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $obligaciones = $storage->loadMultiple($ids);
      $calendario = [];

      foreach ($obligaciones as $obligacion) {
        $calendario[] = [
          'id' => (int) $obligacion->id(),
          'obligacion' => $obligacion->label() ?? '',
          'modelo' => $obligacion->hasField('modelo') && !$obligacion->get('modelo')->isEmpty()
            ? (string) $obligacion->get('modelo')->value
            : '',
          'fecha_limite' => $obligacion->hasField('fecha_limite') && !$obligacion->get('fecha_limite')->isEmpty()
            ? (string) $obligacion->get('fecha_limite')->value
            : '',
          'periodicidad' => $obligacion->hasField('periodicidad') && !$obligacion->get('periodicidad')->isEmpty()
            ? (string) $obligacion->get('periodicidad')->value
            : 'trimestral',
          'descripcion' => $obligacion->hasField('descripcion') && !$obligacion->get('descripcion')->isEmpty()
            ? (string) $obligacion->get('descripcion')->value
            : '',
        ];
      }

      return $calendario;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo calendario fiscal para participante @pid: @msg', [
        '@pid' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Obtiene la lista de plantillas laborales disponibles.
   *
   * Plantillas de contratos, facturas, presupuestos y documentos legales
   * básicos que los participantes pueden usar en su actividad profesional.
   *
   * @return array<int, array{id: int, nombre: string, tipo: string, descripcion: string, formato: string}>
   *   Lista de plantillas disponibles, vacía si el servicio no está disponible.
   */
  public function getPlantillasLaborales(): array {
    if (!$this->legalService) {
      return [];
    }

    try {
      // Delegar al legal service.
      if (method_exists($this->legalService, 'getPlantillas')) {
        $resultado = $this->legalService->getPlantillas(['tipo' => 'laboral']);
        return is_array($resultado) ? $resultado : [];
      }

      // Fallback: consulta directa a plantilla_legal entity.
      $storage = $this->entityTypeManager->getStorage('plantilla_legal');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->condition('categoria', ['laboral', 'fiscal', 'mercantil'], 'IN')
        ->sort('label', 'ASC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      $plantillas = $storage->loadMultiple($ids);
      $resultado = [];

      foreach ($plantillas as $plantilla) {
        $resultado[] = [
          'id' => (int) $plantilla->id(),
          'nombre' => $plantilla->label() ?? '',
          'tipo' => $plantilla->hasField('categoria') && !$plantilla->get('categoria')->isEmpty()
            ? (string) $plantilla->get('categoria')->value
            : 'general',
          'descripcion' => $plantilla->hasField('descripcion') && !$plantilla->get('descripcion')->isEmpty()
            ? (string) $plantilla->get('descripcion')->value
            : '',
          'formato' => $plantilla->hasField('formato') && !$plantilla->get('formato')->isEmpty()
            ? (string) $plantilla->get('formato')->value
            : 'pdf',
        ];
      }

      return $resultado;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error obteniendo plantillas laborales: @msg', [
        '@msg' => $e->getMessage(),
      ]);
      return [];
    }
  }

}
