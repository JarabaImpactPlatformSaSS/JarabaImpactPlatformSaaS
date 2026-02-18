<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jaraba_institutional\Service\FseReporterService;
use Drupal\jaraba_institutional\Service\FundaeReporterService;
use Drupal\jaraba_institutional\Service\ParticipantTrackerService;
use Drupal\jaraba_institutional\Service\ProgramManagerService;
use Drupal\jaraba_institutional\Service\StoFichaGeneratorService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller de la API REST de Programas Institucionales.
 *
 * ESTRUCTURA: 10 endpoints JSON para programas, participantes,
 *   fichas STO y reportes FUNDAE/FSE+. Sigue el patron del
 *   ecosistema con envelope estandar {data}/{data,meta}/{error}.
 *
 * LOGICA: Cada endpoint retorna JsonResponse. Los metodos de
 *   escritura usan store()/enroll() en lugar de create()
 *   (API-NAMING-001). Los metodos de lectura soportan paginacion
 *   via query params limit/offset.
 */
class InstitutionalApiController extends ControllerBase {

  /**
   * Constructor con inyeccion de dependencias (PHP 8.3 promotion).
   */
  public function __construct(
    protected ProgramManagerService $programManager,
    protected ParticipantTrackerService $participantTracker,
    protected StoFichaGeneratorService $stoGenerator,
    protected FundaeReporterService $fundaeReporter,
    protected FseReporterService $fseReporter,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_institutional.program_manager'),
      $container->get('jaraba_institutional.participant_tracker'),
      $container->get('jaraba_institutional.sto_generator'),
      $container->get('jaraba_institutional.fundae_reporter'),
      $container->get('jaraba_institutional.fse_reporter'),
    );
  }

  // ---------------------------------------------------------------
  // Programas
  // ---------------------------------------------------------------

  /**
   * Lista programas institucionales con filtros y paginacion.
   *
   * Query params: status, program_type, funding_entity, limit, offset.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP entrante.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data, meta}.
   */
  public function listPrograms(Request $request): JsonResponse {
    $filters = [
      'status' => $request->query->get('status'),
      'program_type' => $request->query->get('program_type'),
      'funding_entity' => $request->query->get('funding_entity'),
    ];
    $filters = array_filter($filters);

    $limit = (int) $request->query->get('limit', 25);
    $offset = (int) $request->query->get('offset', 0);

    try {
      $result = $this->programManager->list($filters, $limit, $offset);
      $programs = array_map(
        fn(object $p) => $this->serializeProgram($p),
        $result['items'],
      );

      return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => TRUE, 'data' => $programs, 'meta' => [
          'total' => $result['total'],
          'limit' => $limit,
          'offset' => $offset,
        ]]);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  /**
   * Crea un nuevo programa institucional (API-NAMING-001: store).
   *
   * Campos obligatorios: name, program_type, program_code,
   * funding_entity, start_date.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP entrante con el body JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data} 201 o {error} 422/500.
   */
  public function storeProgram(Request $request): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];

    // Validar campos obligatorios.
    $required = ['name', 'program_type', 'program_code', 'funding_entity', 'start_date'];
    $missing = array_filter($required, fn(string $field) => empty($body[$field]));

    if (!empty($missing)) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('Campos obligatorios faltantes: @fields', [
          '@fields' => implode(', ', $missing),
        ]),
      ], 422);
    }

    try {
      $program = $this->programManager->store($body);

      return new JsonResponse(['success' => TRUE, 'data' => $this->serializeProgram($program),
      ], 201);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  /**
   * Obtiene un programa institucional por su ID.
   *
   * @param int $institutional_program
   *   ID del programa institucional.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data} o {error} 404.
   */
  public function showProgram(int $institutional_program): JsonResponse {
    try {
      $program = $this->programManager->load($institutional_program);

      if ($program === NULL) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Programa @id no encontrado.', [
            '@id' => $institutional_program,
          ]),
        ], 404);
      }

      return new JsonResponse([
        'data' => $this->serializeProgram($program),
      ]);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  /**
   * Actualiza un programa institucional existente (PATCH).
   *
   * Campos actualizables: name, funding_entity, start_date, end_date,
   * budget_total, budget_executed, participants_target, notes.
   * Los cambios de status se gestionan via programManager->updateStatus().
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP entrante con el body JSON parcial.
   * @param int $institutional_program
   *   ID del programa institucional.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data} o {error} 404/500.
   */
  public function updateProgram(Request $request, int $institutional_program): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];

    try {
      $program = $this->programManager->load($institutional_program);

      if ($program === NULL) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Programa @id no encontrado.', [
            '@id' => $institutional_program,
          ]),
        ], 404);
      }

      // Campos actualizables directamente.
      $updatable = [
        'name', 'funding_entity', 'start_date', 'end_date',
        'budget_total', 'budget_executed', 'participants_target', 'notes',
      ];

      foreach ($updatable as $field) {
        if (array_key_exists($field, $body)) {
          $program->set($field, $body[$field]);
        }
      }

      // Cambio de estado via metodo dedicado.
      if (isset($body['status'])) {
        $this->programManager->updateStatus($program, $body['status']);
      }

      $program->save();

      return new JsonResponse([
        'data' => $this->serializeProgram($program),
      ]);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  // ---------------------------------------------------------------
  // Participantes
  // ---------------------------------------------------------------

  /**
   * Lista participantes de un programa con paginacion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP entrante.
   * @param int $institutional_program
   *   ID del programa institucional.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data, meta}.
   */
  public function listParticipants(Request $request, int $institutional_program): JsonResponse {
    $limit = (int) $request->query->get('limit', 25);
    $offset = (int) $request->query->get('offset', 0);

    try {
      $result = $this->participantTracker->listByProgram(
        $institutional_program,
        $limit,
        $offset,
      );

      $participants = array_map(
        fn(object $p) => $this->serializeParticipant($p),
        $result['items'],
      );

      return new JsonResponse([
        'data' => $participants, 'meta' => [
          'total' => $result['total'],
          'limit' => $limit,
          'offset' => $offset,
        ]]);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  /**
   * Inscribe un participante en un programa (API-NAMING-001: enroll).
   *
   * Campo obligatorio: user_id.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP entrante con el body JSON.
   * @param int $institutional_program
   *   ID del programa institucional.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data} 201 o {error} 422/500.
   */
  public function enrollParticipant(Request $request, int $institutional_program): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];

    if (empty($body['user_id'])) {
      return new JsonResponse([
        'error' => (string) new TranslatableMarkup('El campo user_id es obligatorio.'),
      ], 422);
    }

    try {
      $participant = $this->participantTracker->enroll(
        $institutional_program,
        (int) $body['user_id'],
        $body,
      );

      return new JsonResponse([
        'data' => $this->serializeParticipant($participant),
      ], 201);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  /**
   * Actualiza datos de seguimiento de un participante (PATCH).
   *
   * Campos actualizables: hours_orientation, hours_training,
   * certifications_obtained, employment_outcome, employment_date,
   * exit_date, exit_reason, notes.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP entrante con el body JSON parcial.
   * @param int $program_participant
   *   ID del participante en el programa.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data} o {error} 404/500.
   */
  public function updateParticipant(Request $request, int $program_participant): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];

    try {
      $participant = $this->participantTracker->load($program_participant);

      if ($participant === NULL) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Participante @id no encontrado.', [
            '@id' => $program_participant,
          ]),
        ], 404);
      }

      $updatable = [
        'hours_orientation', 'hours_training', 'certifications_obtained',
        'employment_outcome', 'employment_date', 'exit_date', 'exit_reason',
        'notes',
      ];

      foreach ($updatable as $field) {
        if (array_key_exists($field, $body)) {
          $participant->set($field, $body[$field]);
        }
      }

      $participant->save();

      return new JsonResponse([
        'data' => $this->serializeParticipant($participant),
      ]);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  // ---------------------------------------------------------------
  // Fichas STO
  // ---------------------------------------------------------------

  /**
   * Genera una ficha STO para un participante.
   *
   * Campos opcionales en body: ficha_type (default 'initial'),
   * use_ai (default FALSE).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   La peticion HTTP entrante con el body JSON opcional.
   * @param int $program_participant
   *   ID del participante en el programa.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data} 201 o {error} 404/500.
   */
  public function generateFicha(Request $request, int $program_participant): JsonResponse {
    $body = json_decode($request->getContent(), TRUE) ?? [];
    $fichaType = $body['ficha_type'] ?? 'initial';
    $useAi = (bool) ($body['use_ai'] ?? FALSE);

    try {
      $participant = $this->participantTracker->load($program_participant);

      if ($participant === NULL) {
        return new JsonResponse([
          'error' => (string) new TranslatableMarkup('Participante @id no encontrado.', [
            '@id' => $program_participant,
          ]),
        ], 404);
      }

      $ficha = $this->stoGenerator->generate($participant, $fichaType, $useAi);

      return new JsonResponse([
        'data' => [
          'id' => (int) $ficha->id(),
          'participant_id' => (int) $ficha->get('participant_id')->target_id,
          'ficha_type' => $ficha->get('ficha_type')->value,
          'ficha_number' => $ficha->get('ficha_number')->value,
          'ai_generated' => (bool) $ficha->get('ai_generated')->value,
          'created' => $ficha->get('created')->value,
        ],
      ], 201);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  // ---------------------------------------------------------------
  // Reportes
  // ---------------------------------------------------------------

  /**
   * Genera el reporte FUNDAE de un programa institucional.
   *
   * @param int $institutional_program
   *   ID del programa institucional.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data} con los datos del reporte FUNDAE.
   */
  public function reportFundae(int $institutional_program): JsonResponse {
    try {
      $report = $this->fundaeReporter->generate($institutional_program);

      return new JsonResponse([
        'data' => $report,
      ]);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  /**
   * Genera el reporte FSE+ de un programa institucional.
   *
   * @param int $institutional_program
   *   ID del programa institucional.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Envelope {data} con los datos del reporte FSE+.
   */
  public function reportFse(int $institutional_program): JsonResponse {
    try {
      $report = $this->fseReporter->generate($institutional_program);

      return new JsonResponse([
        'data' => $report,
      ]);
    }
    catch (\Throwable $e) {
      \Drupal::logger('jaraba_institutional')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
      return new JsonResponse([
        'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
      ], 500);
    }
  }

  // ---------------------------------------------------------------
  // Serializadores
  // ---------------------------------------------------------------

  /**
   * Serializa un programa institucional para la respuesta JSON.
   *
   * @param object $program
   *   La entidad programa institucional.
   *
   * @return array
   *   Array asociativo con los campos del programa.
   */
  protected function serializeProgram(object $program): array {
    return [
      'id' => (int) $program->id(),
      'name' => $program->get('name')->value,
      'program_type' => $program->get('program_type')->value,
      'program_code' => $program->get('program_code')->value,
      'funding_entity' => $program->get('funding_entity')->value,
      'start_date' => $program->get('start_date')->value,
      'end_date' => $program->get('end_date')->value,
      'budget_total' => $program->get('budget_total')->value,
      'budget_executed' => $program->get('budget_executed')->value,
      'participants_target' => $program->get('participants_target')->value,
      'participants_actual' => $program->get('participants_actual')->value,
      'status' => $program->get('status')->value,
      'created' => $program->get('created')->value,
      'changed' => $program->get('changed')->value,
    ];
  }

  /**
   * Serializa un participante para la respuesta JSON.
   *
   * @param object $participant
   *   La entidad participante del programa.
   *
   * @return array
   *   Array asociativo con los campos del participante.
   */
  protected function serializeParticipant(object $participant): array {
    return [
      'id' => (int) $participant->id(),
      'program_id' => (int) $participant->get('program_id')->target_id,
      'user_id' => (int) $participant->get('user_id')->target_id,
      'enrollment_date' => $participant->get('enrollment_date')->value,
      'exit_date' => $participant->get('exit_date')->value,
      'exit_reason' => $participant->get('exit_reason')->value,
      'sto_ficha_id' => $participant->get('sto_ficha_id')->value,
      'employment_outcome' => $participant->get('employment_outcome')->value,
      'employment_date' => $participant->get('employment_date')->value,
      'hours_orientation' => $participant->get('hours_orientation')->value,
      'hours_training' => $participant->get('hours_training')->value,
      'status' => $participant->get('status')->value,
      'created' => $participant->get('created')->value,
      'changed' => $participant->get('changed')->value,
    ];
  }

}
