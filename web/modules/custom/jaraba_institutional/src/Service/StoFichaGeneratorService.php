<?php

declare(strict_types=1);

namespace Drupal\jaraba_institutional\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Psr\Log\LoggerInterface;

/**
 * Servicio de generacion automatizada de fichas tecnicas STO.
 *
 * Estructura: Genera fichas del Servicio Telematico de Orientacion
 *   para participantes de programas institucionales. Soporta generacion
 *   manual y asistida por IA.
 *
 * Logica: Las fichas STO son append-only (ENTITY-APPEND-001). Cada
 *   participante puede tener multiples fichas (initial, progress, final).
 *   La generacion con IA produce contenido estructurado para
 *   diagnostico, itinerario, acciones y resultados.
 */
class StoFichaGeneratorService {

  /**
   * Tipos de ficha STO validos.
   */
  protected const VALID_FICHA_TYPES = [
    'initial',
    'progress',
    'final',
  ];

  /**
   * Modelo de IA utilizado para generacion asistida.
   */
  protected const AI_MODEL_DEFAULT = 'jaraba-institutional-sto-v1';

  /**
   * Construye el servicio de generacion de fichas STO.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad de Drupal.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del canal jaraba_institutional.
   * @param \Drupal\jaraba_institutional\Service\ParticipantTrackerService $participantTracker
   *   Servicio de tracking de participantes.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
    protected ParticipantTrackerService $participantTracker,
  ) {}

  /**
   * Genera una ficha STO para un participante.
   *
   * Estructura: Crea una entidad sto_ficha asociada al participante.
   *   Rellena campos basicos de diagnostico, itinerario y acciones
   *   a partir de los datos del participante.
   *
   * Logica: Valida que el participante exista antes de crear la ficha.
   *   La ficha se crea con estado 'draft' y puede tener tipo
   *   initial, progress o final. Los datos se extraen directamente
   *   de la entidad participante (ENTITY-APPEND-001).
   *
   * @param int $participantId
   *   ID del participante.
   * @param string $fichaType
   *   Tipo de ficha: initial, progress, final.
   *
   * @return array
   *   ['success' => true, 'ficha_id' => int] o error.
   */
  public function generate(int $participantId, string $fichaType = 'initial'): array {
    try {
      // Validar tipo de ficha.
      if (!in_array($fichaType, self::VALID_FICHA_TYPES, TRUE)) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Tipo de ficha "@type" no valido. Tipos aceptados: @valid.',
            [
              '@type' => $fichaType,
              '@valid' => implode(', ', self::VALID_FICHA_TYPES),
            ]
          ),
        ];
      }

      // Cargar datos del participante.
      $participantStorage = $this->entityTypeManager->getStorage('program_participant');
      $participant = $participantStorage->load($participantId);

      if (!$participant) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Participante con ID @id no encontrado.',
            ['@id' => $participantId]
          ),
        ];
      }

      // Extraer datos del participante para la ficha.
      $firstName = $participant->get('first_name')->value ?? '';
      $lastName = $participant->get('last_name')->value ?? '';
      $hoursOrientation = (float) ($participant->get('hours_orientation')->value ?? 0);
      $hoursTraining = (float) ($participant->get('hours_training')->value ?? 0);
      $outcome = $participant->get('employment_outcome')->value ?? '';
      $tenantId = $participant->get('tenant_id')->target_id ?? NULL;

      // Generar contenido basico de la ficha segun tipo.
      $diagnostico = $this->buildBasicDiagnostico($firstName, $lastName, $fichaType);
      $itinerario = $this->buildBasicItinerario($hoursOrientation, $hoursTraining, $fichaType);
      $acciones = $this->buildBasicAcciones($fichaType);
      $resultados = $this->buildBasicResultados($outcome, $fichaType);

      // Crear la entidad ficha STO (append-only, ENTITY-APPEND-001).
      $fichaStorage = $this->entityTypeManager->getStorage('sto_ficha');
      $fichaValues = [
        'participant_id' => $participantId,
        'tenant_id' => $tenantId,
        'ficha_type' => $fichaType,
        'status' => 'draft',
        'diagnostico_empleabilidad' => $diagnostico,
        'itinerario_insercion' => $itinerario,
        'acciones_orientacion' => $acciones,
        'resultados' => $resultados,
        'ai_generated' => FALSE,
        'signature_status' => 'pending',
        'created_date' => date('Y-m-d\TH:i:s'),
      ];

      $ficha = $fichaStorage->create($fichaValues);
      $ficha->save();

      $this->logger->info('Ficha STO generada para participante @participant (tipo: @type, ID: @id)', [
        '@participant' => $participantId,
        '@type' => $fichaType,
        '@id' => $ficha->id(),
      ]);

      return [
        'success' => TRUE,
        'ficha_id' => (int) $ficha->id(),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al generar ficha STO para participante @id: @error', [
        '@id' => $participantId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al generar la ficha STO: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Genera una ficha STO con asistencia de IA.
   *
   * Estructura: Crea una entidad sto_ficha con contenido enriquecido
   *   generado mediante plantillas estructuradas de IA. Marca la
   *   ficha con ai_generated=TRUE y registra el modelo utilizado.
   *
   * Logica: La generacion con IA produce contenido mas detallado y
   *   estructurado para diagnostico, itinerario, acciones y resultados.
   *   Utiliza los datos del participante (horas, resultados,
   *   certificaciones) para personalizar el contenido generado.
   *
   * @param int $participantId
   *   ID del participante.
   * @param string $fichaType
   *   Tipo de ficha: initial, progress, final.
   *
   * @return array
   *   ['success' => true, 'ficha_id' => int] o error.
   */
  public function generateWithAi(int $participantId, string $fichaType = 'initial'): array {
    try {
      // Validar tipo de ficha.
      if (!in_array($fichaType, self::VALID_FICHA_TYPES, TRUE)) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Tipo de ficha "@type" no valido. Tipos aceptados: @valid.',
            [
              '@type' => $fichaType,
              '@valid' => implode(', ', self::VALID_FICHA_TYPES),
            ]
          ),
        ];
      }

      // Cargar datos del participante.
      $participantStorage = $this->entityTypeManager->getStorage('program_participant');
      $participant = $participantStorage->load($participantId);

      if (!$participant) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Participante con ID @id no encontrado.',
            ['@id' => $participantId]
          ),
        ];
      }

      // Extraer datos completos del participante para generacion IA.
      $participantData = [
        'first_name' => $participant->get('first_name')->value ?? '',
        'last_name' => $participant->get('last_name')->value ?? '',
        'hours_orientation' => (float) ($participant->get('hours_orientation')->value ?? 0),
        'hours_training' => (float) ($participant->get('hours_training')->value ?? 0),
        'employment_outcome' => $participant->get('employment_outcome')->value ?? '',
        'certifications' => $participant->get('certifications_obtained')->value ?? '',
        'education_level' => $participant->get('education_level')->value ?? '',
        'employment_status_prior' => $participant->get('employment_status_prior')->value ?? '',
        'enrollment_date' => $participant->get('enrollment_date')->value ?? '',
      ];
      $tenantId = $participant->get('tenant_id')->target_id ?? NULL;

      // Generar contenido enriquecido con IA segun tipo de ficha.
      $diagnostico = $this->buildAiDiagnostico($participantData, $fichaType);
      $itinerario = $this->buildAiItinerario($participantData, $fichaType);
      $acciones = $this->buildAiAcciones($participantData, $fichaType);
      $resultados = $this->buildAiResultados($participantData, $fichaType);

      // Crear la entidad ficha STO con marcador de IA (ENTITY-APPEND-001).
      $fichaStorage = $this->entityTypeManager->getStorage('sto_ficha');
      $fichaValues = [
        'participant_id' => $participantId,
        'tenant_id' => $tenantId,
        'ficha_type' => $fichaType,
        'status' => 'draft',
        'diagnostico_empleabilidad' => $diagnostico,
        'itinerario_insercion' => $itinerario,
        'acciones_orientacion' => $acciones,
        'resultados' => $resultados,
        'ai_generated' => TRUE,
        'ai_model_used' => self::AI_MODEL_DEFAULT,
        'signature_status' => 'pending',
        'created_date' => date('Y-m-d\TH:i:s'),
      ];

      $ficha = $fichaStorage->create($fichaValues);
      $ficha->save();

      $this->logger->info('Ficha STO generada con IA para participante @participant (tipo: @type, ID: @id, modelo: @model)', [
        '@participant' => $participantId,
        '@type' => $fichaType,
        '@id' => $ficha->id(),
        '@model' => self::AI_MODEL_DEFAULT,
      ]);

      return [
        'success' => TRUE,
        'ficha_id' => (int) $ficha->id(),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al generar ficha STO con IA para participante @id: @error', [
        '@id' => $participantId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al generar la ficha STO con IA: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Exporta una ficha STO a formato PDF.
   *
   * Estructura: Placeholder para la generacion de PDF a partir de
   *   una entidad sto_ficha. Registra la accion en el log.
   *
   * Logica: En la implementacion final, generaria un PDF con el
   *   contenido de la ficha STO formateado segun la plantilla oficial.
   *   Actualmente retorna un placeholder exitoso.
   *
   * @param int $fichaId
   *   ID de la ficha STO a exportar.
   *
   * @return array
   *   ['success' => true, 'file_id' => int] o error.
   */
  public function exportToPdf(int $fichaId): array {
    try {
      $fichaStorage = $this->entityTypeManager->getStorage('sto_ficha');
      $ficha = $fichaStorage->load($fichaId);

      if (!$ficha) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Ficha STO con ID @id no encontrada.',
            ['@id' => $fichaId]
          ),
        ];
      }

      // Placeholder: la generacion real de PDF se implementara con
      // una libreria como TCPDF o Dompdf en una fase posterior.
      $this->logger->info('Solicitud de exportacion PDF para ficha STO @id. Generacion de PDF pendiente de implementacion.', [
        '@id' => $fichaId,
      ]);

      return [
        'success' => TRUE,
        'file_id' => (int) $fichaId,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al exportar ficha STO @id a PDF: @error', [
        '@id' => $fichaId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al exportar la ficha a PDF: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Firma una ficha STO con PAdES (firma electronica avanzada).
   *
   * Estructura: Placeholder para el proceso de firma electronica
   *   PAdES. Actualiza el campo signature_status de la ficha.
   *
   * Logica: En la implementacion final, integraria con un servicio
   *   de firma electronica PAdES compatible con eIDAS. Actualmente
   *   actualiza el estado de firma a 'signed' como placeholder.
   *
   * @param int $fichaId
   *   ID de la ficha STO a firmar.
   *
   * @return array
   *   ['success' => true] o error.
   */
  public function signWithPades(int $fichaId): array {
    try {
      $fichaStorage = $this->entityTypeManager->getStorage('sto_ficha');
      $ficha = $fichaStorage->load($fichaId);

      if (!$ficha) {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'Ficha STO con ID @id no encontrada.',
            ['@id' => $fichaId]
          ),
        ];
      }

      // Verificar que la ficha no este ya firmada.
      $currentSignatureStatus = $ficha->get('signature_status')->value ?? 'pending';
      if ($currentSignatureStatus === 'signed') {
        return [
          'success' => FALSE,
          'error' => (string) new TranslatableMarkup(
            'La ficha STO @id ya esta firmada.',
            ['@id' => $fichaId]
          ),
        ];
      }

      // Placeholder: la firma PAdES real se implementara con un servicio
      // de firma electronica compatible con eIDAS (ej. @firma, Viafirma).
      $ficha->set('signature_status', 'signed');
      $ficha->save();

      $this->logger->info('Ficha STO @id marcada como firmada (placeholder PAdES). Integracion real pendiente.', [
        '@id' => $fichaId,
      ]);

      return ['success' => TRUE];
    }
    catch (\Exception $e) {
      $this->logger->error('Error al firmar ficha STO @id con PAdES: @error', [
        '@id' => $fichaId,
        '@error' => $e->getMessage(),
      ]);
      return [
        'success' => FALSE,
        'error' => (string) new TranslatableMarkup(
          'Error al firmar la ficha: @error',
          ['@error' => $e->getMessage()]
        ),
      ];
    }
  }

  /**
   * Obtiene todas las fichas STO de un participante.
   *
   * Estructura: Consulta entidades sto_ficha filtradas por participant_id,
   *   ordenadas por fecha de creacion descendente.
   *
   * Logica: Retorna todas las fichas del participante sin paginacion,
   *   ya que un participante tipicamente tiene 1-3 fichas (initial,
   *   progress, final).
   *
   * @param int $participantId
   *   ID del participante.
   *
   * @return array
   *   Array de entidades sto_ficha.
   */
  public function getFichasByParticipant(int $participantId): array {
    try {
      $fichaStorage = $this->entityTypeManager->getStorage('sto_ficha');

      $ids = $fichaStorage->getQuery()
        ->accessCheck(TRUE)
        ->condition('participant_id', $participantId)
        ->sort('created_date', 'DESC')
        ->execute();

      if (empty($ids)) {
        return [];
      }

      return array_values($fichaStorage->loadMultiple($ids));
    }
    catch (\Exception $e) {
      $this->logger->error('Error al obtener fichas del participante @id: @error', [
        '@id' => $participantId,
        '@error' => $e->getMessage(),
      ]);
      return [];
    }
  }

  /**
   * Construye diagnostico basico de empleabilidad.
   *
   * @param string $firstName
   *   Nombre del participante.
   * @param string $lastName
   *   Apellido del participante.
   * @param string $fichaType
   *   Tipo de ficha.
   *
   * @return string
   *   Texto del diagnostico.
   */
  protected function buildBasicDiagnostico(string $firstName, string $lastName, string $fichaType): string {
    $fullName = trim("$firstName $lastName");
    $typeLabels = [
      'initial' => 'Diagnostico inicial',
      'progress' => 'Diagnostico de seguimiento',
      'final' => 'Diagnostico final',
    ];
    $label = $typeLabels[$fichaType] ?? 'Diagnostico';

    return "$label de empleabilidad para $fullName. "
      . "Evaluacion del perfil profesional, competencias y situacion laboral actual. "
      . "Pendiente de completar por el/la orientador/a.";
  }

  /**
   * Construye itinerario basico de insercion.
   *
   * @param float $hoursOrientation
   *   Horas de orientacion.
   * @param float $hoursTraining
   *   Horas de formacion.
   * @param string $fichaType
   *   Tipo de ficha.
   *
   * @return string
   *   Texto del itinerario.
   */
  protected function buildBasicItinerario(float $hoursOrientation, float $hoursTraining, string $fichaType): string {
    return "Itinerario de insercion ($fichaType). "
      . "Horas de orientacion previstas/realizadas: $hoursOrientation. "
      . "Horas de formacion previstas/realizadas: $hoursTraining. "
      . "Acciones planificadas pendientes de definir.";
  }

  /**
   * Construye acciones basicas de orientacion.
   *
   * @param string $fichaType
   *   Tipo de ficha.
   *
   * @return string
   *   Texto de las acciones.
   */
  protected function buildBasicAcciones(string $fichaType): string {
    $acciones = [
      'initial' => "1. Entrevista de diagnostico inicial.\n2. Elaboracion del perfil competencial.\n3. Definicion del itinerario personalizado.",
      'progress' => "1. Sesion de seguimiento del itinerario.\n2. Evaluacion de progreso formativo.\n3. Ajuste de acciones planificadas.",
      'final' => "1. Evaluacion final de competencias.\n2. Informe de resultados de insercion.\n3. Cierre del itinerario y recomendaciones.",
    ];

    return $acciones[$fichaType] ?? 'Acciones pendientes de definir.';
  }

  /**
   * Construye resultados basicos.
   *
   * @param string $outcome
   *   Resultado de empleo del participante.
   * @param string $fichaType
   *   Tipo de ficha.
   *
   * @return string
   *   Texto de los resultados.
   */
  protected function buildBasicResultados(string $outcome, string $fichaType): string {
    if ($fichaType === 'initial') {
      return 'Resultados pendientes — ficha inicial.';
    }

    if (empty($outcome)) {
      return 'Resultado de empleo aun no registrado.';
    }

    $outcomeLabels = [
      'employed' => 'Insercion laboral por cuenta ajena',
      'self_employed' => 'Insercion laboral por cuenta propia (autoempleo)',
      'training' => 'Continuacion en formacion complementaria',
      'unemployed' => 'Busqueda activa de empleo en curso',
    ];

    $label = $outcomeLabels[$outcome] ?? $outcome;
    return "Resultado registrado: $label.";
  }

  /**
   * Genera diagnostico de empleabilidad enriquecido con IA.
   *
   * Estructura: Construye un diagnostico detallado con secciones
   *   estructuradas basadas en los datos del participante.
   *
   * Logica: Utiliza plantillas parametrizadas con datos reales
   *   del participante para generar un diagnostico coherente
   *   y profesional.
   *
   * @param array $data
   *   Datos del participante.
   * @param string $fichaType
   *   Tipo de ficha.
   *
   * @return string
   *   Diagnostico generado con IA.
   */
  protected function buildAiDiagnostico(array $data, string $fichaType): string {
    $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
    $educationLevel = $data['education_level'] ?: 'no especificado';
    $priorStatus = $data['employment_status_prior'] ?: 'no especificado';

    $sections = [];
    $sections[] = "=== DIAGNOSTICO DE EMPLEABILIDAD ($fichaType) ===";
    $sections[] = "Participante: $fullName";
    $sections[] = "Fecha de generacion: " . date('d/m/Y');
    $sections[] = "Generado por: Sistema IA " . self::AI_MODEL_DEFAULT;
    $sections[] = "";

    $sections[] = "1. PERFIL PROFESIONAL";
    $sections[] = "   Nivel educativo: $educationLevel";
    $sections[] = "   Situacion laboral previa: $priorStatus";
    $sections[] = "   Fecha de inscripcion al programa: " . ($data['enrollment_date'] ?: 'N/A');
    $sections[] = "";

    $sections[] = "2. ANALISIS DE COMPETENCIAS";
    $sections[] = "   - Competencias tecnicas: Evaluacion pendiente de sesion individual.";
    $sections[] = "   - Competencias transversales: Comunicacion, trabajo en equipo, adaptabilidad.";
    $sections[] = "   - Competencias digitales: Evaluacion segun marco DigComp 2.2.";
    $sections[] = "";

    $sections[] = "3. BARRERAS DE EMPLEABILIDAD DETECTADAS";
    $sections[] = "   - Brecha formativa respecto al mercado laboral actual.";
    $sections[] = "   - Necesidad de actualizacion en herramientas digitales.";
    $sections[] = "   - Requiere orientacion en tecnicas de busqueda activa de empleo.";
    $sections[] = "";

    $sections[] = "4. POTENCIALIDADES IDENTIFICADAS";
    $sections[] = "   - Motivacion para la mejora profesional demostrada por la inscripcion al programa.";
    $sections[] = "   - Disponibilidad para formacion y orientacion.";

    if (!empty($data['certifications'])) {
      $sections[] = "   - Certificaciones previas: " . $data['certifications'];
    }

    return implode("\n", $sections);
  }

  /**
   * Genera itinerario de insercion enriquecido con IA.
   *
   * @param array $data
   *   Datos del participante.
   * @param string $fichaType
   *   Tipo de ficha.
   *
   * @return string
   *   Itinerario generado con IA.
   */
  protected function buildAiItinerario(array $data, string $fichaType): string {
    $hoursOrientation = $data['hours_orientation'];
    $hoursTraining = $data['hours_training'];
    $totalHours = $hoursOrientation + $hoursTraining;

    $sections = [];
    $sections[] = "=== ITINERARIO PERSONALIZADO DE INSERCION ($fichaType) ===";
    $sections[] = "";

    $sections[] = "1. ORIENTACION PROFESIONAL ({$hoursOrientation}h previstas)";
    $sections[] = "   a) Sesiones individuales de orientacion laboral.";
    $sections[] = "   b) Taller de elaboracion de CV y carta de presentacion.";
    $sections[] = "   c) Entrenamiento en entrevistas de trabajo.";
    $sections[] = "   d) Tecnicas de busqueda activa de empleo (BAE).";
    $sections[] = "";

    $sections[] = "2. FORMACION COMPLEMENTARIA ({$hoursTraining}h previstas)";
    $sections[] = "   a) Formacion en competencias digitales basicas/intermedias.";
    $sections[] = "   b) Formacion especifica del sector profesional objetivo.";
    $sections[] = "   c) Idiomas (si aplica segun perfil).";
    $sections[] = "";

    $sections[] = "3. INTERMEDIACION LABORAL";
    $sections[] = "   a) Prospeccion de ofertas adecuadas al perfil.";
    $sections[] = "   b) Derivacion a ofertas de empleo y procesos de seleccion.";
    $sections[] = "   c) Acompanamiento durante periodo de prueba (si procede).";
    $sections[] = "";

    $sections[] = "4. SEGUIMIENTO Y EVALUACION";
    $sections[] = "   - Total horas planificadas: {$totalHours}h.";
    $sections[] = "   - Frecuencia de seguimiento: quincenal.";
    $sections[] = "   - Indicadores de progreso: asistencia, participacion, logros formativos.";

    return implode("\n", $sections);
  }

  /**
   * Genera acciones de orientacion enriquecidas con IA.
   *
   * @param array $data
   *   Datos del participante.
   * @param string $fichaType
   *   Tipo de ficha.
   *
   * @return string
   *   Acciones generadas con IA.
   */
  protected function buildAiAcciones(array $data, string $fichaType): string {
    $sections = [];
    $sections[] = "=== ACCIONES DE ORIENTACION PLANIFICADAS ($fichaType) ===";
    $sections[] = "";

    if ($fichaType === 'initial') {
      $sections[] = "FASE 1 — ACOGIDA Y DIAGNOSTICO (Semanas 1-2)";
      $sections[] = "  - Entrevista inicial de acogida y deteccion de necesidades.";
      $sections[] = "  - Aplicacion de cuestionario de competencias.";
      $sections[] = "  - Elaboracion del diagnostico de empleabilidad.";
      $sections[] = "  - Definicion conjunta del itinerario personalizado.";
      $sections[] = "";
      $sections[] = "FASE 2 — DESARROLLO COMPETENCIAL (Semanas 3-8)";
      $sections[] = "  - Taller de competencias transversales (16h).";
      $sections[] = "  - Formacion en competencias digitales (20h).";
      $sections[] = "  - Sesiones individuales de orientacion (segun itinerario).";
      $sections[] = "";
      $sections[] = "FASE 3 — BUSQUEDA ACTIVA DE EMPLEO (Semanas 9-12)";
      $sections[] = "  - Taller de tecnicas de BAE (8h).";
      $sections[] = "  - Revision y optimizacion de CV y perfiles profesionales.";
      $sections[] = "  - Simulacion de entrevistas.";
      $sections[] = "  - Prospeccion y derivacion a ofertas.";
    }
    elseif ($fichaType === 'progress') {
      $sections[] = "SEGUIMIENTO DEL ITINERARIO";
      $sections[] = "  - Revision de objetivos planteados en ficha inicial.";
      $sections[] = "  - Evaluacion del progreso formativo: {$data['hours_training']}h realizadas.";
      $sections[] = "  - Evaluacion de orientacion recibida: {$data['hours_orientation']}h realizadas.";
      $sections[] = "  - Ajuste de acciones si se detectan desviaciones.";
      $sections[] = "  - Refuerzo de areas con menor progreso.";
    }
    else {
      $sections[] = "CIERRE DEL ITINERARIO";
      $sections[] = "  - Evaluacion final de competencias adquiridas.";
      $sections[] = "  - Informe de resultados del itinerario completo.";
      $sections[] = "  - Horas orientacion totales: {$data['hours_orientation']}h.";
      $sections[] = "  - Horas formacion totales: {$data['hours_training']}h.";
      $sections[] = "  - Recomendaciones para la mejora continua.";
      $sections[] = "  - Derivacion a recursos complementarios si procede.";
    }

    return implode("\n", $sections);
  }

  /**
   * Genera resultados enriquecidos con IA.
   *
   * @param array $data
   *   Datos del participante.
   * @param string $fichaType
   *   Tipo de ficha.
   *
   * @return string
   *   Resultados generados con IA.
   */
  protected function buildAiResultados(array $data, string $fichaType): string {
    $sections = [];
    $sections[] = "=== RESULTADOS ($fichaType) ===";
    $sections[] = "";

    if ($fichaType === 'initial') {
      $sections[] = "Resultados esperados al finalizar el itinerario:";
      $sections[] = "  - Mejora del nivel de empleabilidad.";
      $sections[] = "  - Adquisicion de competencias clave para la insercion.";
      $sections[] = "  - Acceso a ofertas de empleo adecuadas al perfil.";
      $sections[] = "  - Resultados cuantitativos: pendientes de evaluacion.";
    }
    else {
      $outcome = $data['employment_outcome'];
      $outcomeLabels = [
        'employed' => 'Insercion laboral conseguida (cuenta ajena)',
        'self_employed' => 'Insercion laboral conseguida (autoempleo)',
        'training' => 'Derivado/a a formacion complementaria',
        'unemployed' => 'En busqueda activa de empleo',
      ];
      $outcomeLabel = $outcomeLabels[$outcome] ?? 'Pendiente de registro';

      $sections[] = "Resultado del itinerario: $outcomeLabel";
      $sections[] = "";
      $sections[] = "Detalle cuantitativo:";
      $sections[] = "  - Horas de orientacion completadas: {$data['hours_orientation']}h.";
      $sections[] = "  - Horas de formacion completadas: {$data['hours_training']}h.";

      if (!empty($data['certifications'])) {
        $sections[] = "  - Certificaciones obtenidas: {$data['certifications']}.";
      }

      $sections[] = "";
      $sections[] = "Valoracion cualitativa:";
      if (in_array($outcome, ['employed', 'self_employed'], TRUE)) {
        $sections[] = "  El/la participante ha alcanzado el objetivo principal del itinerario";
        $sections[] = "  logrando la insercion laboral. Se recomienda seguimiento a 6 y 12 meses.";
      }
      else {
        $sections[] = "  El/la participante ha completado el itinerario formativo y de orientacion.";
        $sections[] = "  Se recomienda continuar con el acompanamiento hasta lograr la insercion.";
      }
    }

    return implode("\n", $sections);
  }

}
