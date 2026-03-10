<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Analiza barreras de participantes y genera adaptaciones de itinerario.
 *
 * Sprint 8 — Plan Maestro Andalucía +ei Clase Mundial.
 * Evalúa 8 tipos de barreras de acceso y propone adaptaciones,
 * derivaciones urgentes, intensidad de acompañamiento y recursos
 * específicos según el colectivo del participante.
 *
 * PRESAVE-RESILIENCE-001: Servicios opcionales con try-catch + \Throwable.
 * TENANT-001: Queries filtradas por tenant cuando aplique.
 */
class AdaptacionItinerarioService {

  /**
   * Tipos de barreras reconocidas con su peso base para complejidad.
   */
  private const BARRIER_WEIGHTS = [
    'idioma' => 12,
    'brecha_digital' => 10,
    'carga_cuidados' => 10,
    'situacion_administrativa' => 15,
    'vivienda' => 18,
    'salud_mental' => 15,
    'violencia_genero' => 18,
    'movilidad_geografica' => 8,
  ];

  /**
   * Multiplicador de nivel sobre el peso base.
   */
  private const LEVEL_MULTIPLIERS = [
    'bajo' => 0.5,
    'medio' => 1.0,
    'alto' => 1.5,
    'critico' => 2.0,
  ];

  /**
   * Construye el servicio de adaptación de itinerario.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Psr\Log\LoggerInterface $logger
   *   Canal de log.
   * @param object|null $riesgoService
   *   Servicio de riesgo de abandono (opcional, @?).
   * @param object|null $tenantContext
   *   Contexto de tenant (opcional, @?).
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ?object $riesgoService = NULL,
    protected readonly ?object $tenantContext = NULL,
  ) {}

  /**
   * Evalúa las barreras de acceso de un participante.
   *
   * Lee el campo barreras_acceso (JSON) de ProgramaParticipanteEi
   * y calcula una puntuación de complejidad (0-100).
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return array{barreras: array, complejidad: int, nivel_complejidad: string}
   *   Barreras activas con puntuación de complejidad.
   */
  public function evaluarBarreras(int $participanteId): array {
    $result = [
      'barreras' => [],
      'complejidad' => 0,
      'nivel_complejidad' => 'baja',
    ];

    try {
      $participante = $this->loadParticipante($participanteId);
      if (!$participante) {
        return $result;
      }

      $barrerasRaw = $this->parseBarrerasJson($participante);
      if (empty($barrerasRaw)) {
        return $result;
      }

      $barrerasActivas = [];
      $scoreTotal = 0;

      foreach (self::BARRIER_WEIGHTS as $tipo => $pesoBase) {
        if (!isset($barrerasRaw[$tipo])) {
          continue;
        }

        $barrera = $barrerasRaw[$tipo];
        $activa = !empty($barrera['activa']);

        if (!$activa) {
          continue;
        }

        $nivel = $barrera['nivel'] ?? 'bajo';
        $multiplier = self::LEVEL_MULTIPLIERS[$nivel] ?? 1.0;
        $puntos = (int) round($pesoBase * $multiplier);
        $scoreTotal += $puntos;

        $barrerasActivas[] = [
          'tipo' => $tipo,
          'activa' => TRUE,
          'nivel' => $nivel,
          'puntos' => $puntos,
          'detalle' => $barrera,
        ];
      }

      // Limitar complejidad a 100.
      $complejidad = min($scoreTotal, 100);

      $result['barreras'] = $barrerasActivas;
      $result['complejidad'] = $complejidad;
      $result['nivel_complejidad'] = $this->getNivelComplejidad($complejidad);

    }
    catch (\Throwable $e) {
      $this->logger->error('Error evaluando barreras del participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Devuelve las adaptaciones recomendadas según barreras activas.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return array<array{barrera: string, nivel: string, adaptacion: string, prioridad: string}>
   *   Lista de adaptaciones recomendadas.
   */
  public function getAdaptaciones(int $participanteId): array {
    $adaptaciones = [];

    try {
      $evaluacion = $this->evaluarBarreras($participanteId);

      foreach ($evaluacion['barreras'] as $barrera) {
        $tipo = $barrera['tipo'];
        $nivel = $barrera['nivel'];
        $detalle = $barrera['detalle'] ?? [];

        $nuevas = $this->generarAdaptacionesPorBarrera($tipo, $nivel, $detalle);
        $adaptaciones = array_merge($adaptaciones, $nuevas);
      }

    }
    catch (\Throwable $e) {
      $this->logger->error('Error generando adaptaciones para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $adaptaciones;
  }

  /**
   * Devuelve derivaciones urgentes necesarias.
   *
   * Evalúa situaciones críticas: vivienda sin_hogar, violencia_genero,
   * salud_mental crítico. Estas requieren intervención inmediata ANTES
   * de continuar con la orientación laboral.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return array<array{tipo: string, prioridad: string, mensaje: string, recurso_externo: string, accion: string}>
   *   Lista de derivaciones urgentes.
   */
  public function getDerivacionesNecesarias(int $participanteId): array {
    $derivaciones = [];

    try {
      $participante = $this->loadParticipante($participanteId);
      if (!$participante) {
        return $derivaciones;
      }

      $barrerasRaw = $this->parseBarrerasJson($participante);
      if (empty($barrerasRaw)) {
        return $derivaciones;
      }

      // Vivienda: sin_hogar = derivación crítica.
      if (!empty($barrerasRaw['vivienda']['activa'])) {
        $sinHogar = !empty($barrerasRaw['vivienda']['sin_hogar']);
        $nivel = $barrerasRaw['vivienda']['nivel'] ?? 'bajo';

        if ($sinHogar || $nivel === 'critico') {
          $derivaciones[] = [
            'tipo' => 'derivacion_urgente',
            'prioridad' => 'critica',
            'mensaje' => 'Participante en situación de sinhogarismo. Derivar a servicios sociales de forma prioritaria ANTES de iniciar orientación laboral.',
            'recurso_externo' => 'Servicios Sociales Comunitarios / Centro de Acogida Municipal',
            'accion' => 'Contactar trabajador/a social de zona y coordinar intervención de emergencia habitacional.',
          ];
        }
      }

      // Violencia de género: siempre derivación urgente.
      if (!empty($barrerasRaw['violencia_genero']['activa'])) {
        $derivaciones[] = [
          'tipo' => 'derivacion_urgente',
          'prioridad' => 'critica',
          'mensaje' => 'Participante víctima de violencia de género. Activar protocolo de protección y derivar al Instituto de la Mujer.',
          'recurso_externo' => 'Instituto Andaluz de la Mujer / Teléfono 016 / Centro Municipal de la Mujer',
          'accion' => 'Derivar a recurso especializado. Orientación en espacio seguro. No revelar ubicación en documentación.',
        ];
      }

      // Salud mental: nivel crítico = derivación alta.
      if (!empty($barrerasRaw['salud_mental']['activa'])) {
        $nivel = $barrerasRaw['salud_mental']['nivel'] ?? 'bajo';

        if ($nivel === 'critico' || $nivel === 'alto') {
          $derivaciones[] = [
            'tipo' => 'derivacion_urgente',
            'prioridad' => $nivel === 'critico' ? 'critica' : 'alta',
            'mensaje' => 'Participante con problemática de salud mental de nivel ' . $nivel . '. Derivar a recurso de atención psicológica.',
            'recurso_externo' => 'Unidad de Salud Mental del SAS / Atención Primaria de Salud',
            'accion' => 'Coordinar con centro de salud de referencia. Reducir intensidad de acompañamiento hasta estabilización.',
          ];
        }
      }

    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando derivaciones para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $derivaciones;
  }

  /**
   * Determina la intensidad de acompañamiento recomendada.
   *
   * Basada en la puntuación de complejidad de las barreras:
   * - alta: complejidad >= 50 (múltiples barreras o barreras críticas)
   * - media: complejidad >= 25
   * - baja: complejidad < 25
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return string
   *   'alta'|'media'|'baja'.
   */
  public function getIntensidadAcompanamiento(int $participanteId): string {
    try {
      $evaluacion = $this->evaluarBarreras($participanteId);
      $complejidad = $evaluacion['complejidad'];

      // Enriquecer con datos de riesgo de abandono si está disponible.
      if ($this->riesgoService !== NULL) {
        try {
          /** @var \Drupal\jaraba_andalucia_ei\Service\RiesgoAbandonoService $riesgoService */
          $riesgoService = $this->riesgoService;
          $riesgo = $riesgoService->evaluarRiesgo($participanteId);
          // Si el riesgo de abandono es alto/crítico, subir intensidad.
          if (in_array($riesgo['nivel'] ?? '', ['alto', 'critico'], TRUE)) {
            $complejidad = max($complejidad, 50);
          }
        }
        catch (\Throwable) {
          // PRESAVE-RESILIENCE-001: servicio opcional, continuar sin él.
        }
      }

      if ($complejidad >= 50) {
        return 'alta';
      }
      if ($complejidad >= 25) {
        return 'media';
      }

      return 'baja';

    }
    catch (\Throwable $e) {
      $this->logger->error('Error calculando intensidad para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
      return 'media';
    }
  }

  /**
   * Devuelve recursos específicos adaptados al colectivo del participante.
   *
   * @param int $participanteId
   *   ID de la entidad programa_participante_ei.
   *
   * @return array<array{recurso: string, tipo: string, colectivo: string, descripcion: string}>
   *   Lista de recursos específicos.
   */
  public function getRecursosEspecificos(int $participanteId): array {
    $recursos = [];

    try {
      $participante = $this->loadParticipante($participanteId);
      if (!$participante) {
        return $recursos;
      }

      $colectivo = $participante->get('colectivo')->value ?? '';
      $barrerasRaw = $this->parseBarrerasJson($participante);

      // Recursos genéricos por colectivo.
      $recursos = array_merge($recursos, $this->getRecursosPorColectivo($colectivo));

      // Recursos adicionales por barreras activas.
      foreach (self::BARRIER_WEIGHTS as $tipo => $peso) {
        if (!empty($barrerasRaw[$tipo]['activa'])) {
          $recursos = array_merge(
            $recursos,
            $this->getRecursosPorBarrera($tipo, $barrerasRaw[$tipo], $colectivo),
          );
        }
      }

    }
    catch (\Throwable $e) {
      $this->logger->error('Error obteniendo recursos para participante @id: @msg', [
        '@id' => $participanteId,
        '@msg' => $e->getMessage(),
      ]);
    }

    return $recursos;
  }

  /**
   * Carga una entidad ProgramaParticipanteEi por ID.
   *
   * @param int $participanteId
   *   ID de la entidad.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   La entidad cargada o NULL.
   */
  protected function loadParticipante(int $participanteId): ?object {
    return $this->entityTypeManager
      ->getStorage('programa_participante_ei')
      ->load($participanteId);
  }

  /**
   * Parsea el JSON del campo barreras_acceso.
   *
   * @param object $participante
   *   Entidad ProgramaParticipanteEi.
   *
   * @return array
   *   Barreras parseadas o array vacío si no hay datos válidos.
   */
  protected function parseBarrerasJson(object $participante): array {
    if (!$participante->hasField('barreras_acceso')) {
      return [];
    }

    $raw = $participante->get('barreras_acceso')->value ?? '';
    if (empty($raw)) {
      return [];
    }

    try {
      $decoded = json_decode($raw, TRUE, 512, JSON_THROW_ON_ERROR);
      return is_array($decoded) ? $decoded : [];
    }
    catch (\Throwable) {
      $this->logger->warning('JSON inválido en barreras_acceso del participante @id', [
        '@id' => $participante->id(),
      ]);
      return [];
    }
  }

  /**
   * Calcula el nivel de complejidad textual a partir de la puntuación.
   *
   * @param int $complejidad
   *   Puntuación de complejidad (0-100).
   *
   * @return string
   *   'baja'|'media'|'alta'|'critica'.
   */
  protected function getNivelComplejidad(int $complejidad): string {
    if ($complejidad >= 60) {
      return 'critica';
    }
    if ($complejidad >= 40) {
      return 'alta';
    }
    if ($complejidad >= 20) {
      return 'media';
    }
    return 'baja';
  }

  /**
   * Genera adaptaciones específicas según tipo y nivel de barrera.
   *
   * @param string $tipo
   *   Tipo de barrera.
   * @param string $nivel
   *   Nivel de la barrera (bajo/medio/alto/critico).
   * @param array $detalle
   *   Datos adicionales de la barrera.
   *
   * @return array<array{barrera: string, nivel: string, adaptacion: string, prioridad: string}>
   *   Adaptaciones generadas.
   */
  protected function generarAdaptacionesPorBarrera(string $tipo, string $nivel, array $detalle): array {
    $adaptaciones = [];

    switch ($tipo) {
      case 'idioma':
        $castellanoNivel = $detalle['castellano_nivel'] ?? '';
        // Si castellano por debajo de B1, activar adaptaciones de idioma.
        if (in_array($castellanoNivel, ['A1', 'A2', ''], TRUE)) {
          $adaptaciones[] = [
            'barrera' => 'idioma',
            'nivel' => $nivel,
            'adaptacion' => 'Sesiones con intérprete en idioma nativo del participante.',
            'prioridad' => $nivel === 'alto' || $nivel === 'critico' ? 'alta' : 'media',
          ];
          $adaptaciones[] = [
            'barrera' => 'idioma',
            'nivel' => $nivel,
            'adaptacion' => 'Material bilingüe adaptado (fichas, guías, documentación).',
            'prioridad' => 'media',
          ];
          $adaptaciones[] = [
            'barrera' => 'idioma',
            'nivel' => $nivel,
            'adaptacion' => 'Copilot IA configurado en idioma nativo del participante.',
            'prioridad' => 'alta',
          ];
        }
        break;

      case 'brecha_digital':
        $sinDispositivo = !empty($detalle['sin_dispositivo']);
        $sinInternet = !empty($detalle['sin_internet']);

        if ($sinDispositivo) {
          $adaptaciones[] = [
            'barrera' => 'brecha_digital',
            'nivel' => $nivel,
            'adaptacion' => 'Préstamo de tablet/dispositivo del programa durante el itinerario.',
            'prioridad' => 'alta',
          ];
          $adaptaciones[] = [
            'barrera' => 'brecha_digital',
            'nivel' => $nivel,
            'adaptacion' => 'Formación básica en competencias digitales (módulo introductorio).',
            'prioridad' => 'alta',
          ];
        }
        if ($sinInternet) {
          $adaptaciones[] = [
            'barrera' => 'brecha_digital',
            'nivel' => $nivel,
            'adaptacion' => 'Sesiones presenciales en oficina STO o espacio comunitario con wifi.',
            'prioridad' => 'alta',
          ];
          $adaptaciones[] = [
            'barrera' => 'brecha_digital',
            'nivel' => $nivel,
            'adaptacion' => 'Material offline descargable (PWA modo sin conexión).',
            'prioridad' => 'media',
          ];
        }
        break;

      case 'carga_cuidados':
        if ($nivel === 'alto' || $nivel === 'critico') {
          $adaptaciones[] = [
            'barrera' => 'carga_cuidados',
            'nivel' => $nivel,
            'adaptacion' => 'Horario de sesiones compatible con responsabilidades de cuidados (mañanas escolares).',
            'prioridad' => 'alta',
          ];
          $adaptaciones[] = [
            'barrera' => 'carga_cuidados',
            'nivel' => $nivel,
            'adaptacion' => 'Formación online asíncrona para flexibilidad horaria.',
            'prioridad' => 'alta',
          ];
          $adaptaciones[] = [
            'barrera' => 'carga_cuidados',
            'nivel' => $nivel,
            'adaptacion' => 'Mentoría asíncrona (mensajería, no videollamada obligatoria).',
            'prioridad' => 'media',
          ];
        }
        else {
          $adaptaciones[] = [
            'barrera' => 'carga_cuidados',
            'nivel' => $nivel,
            'adaptacion' => 'Flexibilidad horaria en sesiones individuales.',
            'prioridad' => 'media',
          ];
        }
        break;

      case 'situacion_administrativa':
        $adaptaciones[] = [
          'barrera' => 'situacion_administrativa',
          'nivel' => $nivel,
          'adaptacion' => 'Orientación jurídica sobre permisos de trabajo y residencia.',
          'prioridad' => $nivel === 'critico' ? 'alta' : 'media',
        ];
        $adaptaciones[] = [
          'barrera' => 'situacion_administrativa',
          'nivel' => $nivel,
          'adaptacion' => 'Itinerario formativo compatible con situación administrativa actual.',
          'prioridad' => 'media',
        ];
        break;

      case 'vivienda':
        $sinHogar = !empty($detalle['sin_hogar']);
        if ($sinHogar) {
          $adaptaciones[] = [
            'barrera' => 'vivienda',
            'nivel' => 'critico',
            'adaptacion' => 'URGENTE: Derivación a servicios sociales ANTES de orientación laboral.',
            'prioridad' => 'critica',
          ];
        }
        $adaptaciones[] = [
          'barrera' => 'vivienda',
          'nivel' => $nivel,
          'adaptacion' => 'Sesiones en espacio seguro y accesible. Coordinar con recursos de alojamiento.',
          'prioridad' => $sinHogar ? 'critica' : 'alta',
        ];
        break;

      case 'salud_mental':
        $adaptaciones[] = [
          'barrera' => 'salud_mental',
          'nivel' => $nivel,
          'adaptacion' => 'Derivación a recurso de atención psicológica (SAS / asociación especializada).',
          'prioridad' => $nivel === 'critico' ? 'critica' : 'alta',
        ];
        $adaptaciones[] = [
          'barrera' => 'salud_mental',
          'nivel' => $nivel,
          'adaptacion' => 'Reducir intensidad de acompañamiento. Sesiones más cortas y espaciadas.',
          'prioridad' => 'alta',
        ];
        break;

      case 'violencia_genero':
        $adaptaciones[] = [
          'barrera' => 'violencia_genero',
          'nivel' => $nivel,
          'adaptacion' => 'Derivación al Instituto Andaluz de la Mujer / recurso especializado.',
          'prioridad' => 'critica',
        ];
        $adaptaciones[] = [
          'barrera' => 'violencia_genero',
          'nivel' => $nivel,
          'adaptacion' => 'Orientación en espacio seguro. No compartir datos de ubicación.',
          'prioridad' => 'critica',
        ];
        $adaptaciones[] = [
          'barrera' => 'violencia_genero',
          'nivel' => $nivel,
          'adaptacion' => 'Itinerario con prospección de empleadores sensibilizados en igualdad.',
          'prioridad' => 'alta',
        ];
        break;

      case 'movilidad_geografica':
        $sinTransporte = !empty($detalle['sin_transporte']);
        if ($sinTransporte) {
          $adaptaciones[] = [
            'barrera' => 'movilidad_geografica',
            'nivel' => $nivel,
            'adaptacion' => 'Sesiones de orientación online (videollamada).',
            'prioridad' => 'alta',
          ];
          $adaptaciones[] = [
            'barrera' => 'movilidad_geografica',
            'nivel' => $nivel,
            'adaptacion' => 'Prospección de empleadores cercanos al domicilio del participante.',
            'prioridad' => 'alta',
          ];
        }
        else {
          $adaptaciones[] = [
            'barrera' => 'movilidad_geografica',
            'nivel' => $nivel,
            'adaptacion' => 'Sesiones híbridas (presencial + online) según disponibilidad de transporte.',
            'prioridad' => 'media',
          ];
        }
        break;
    }

    return $adaptaciones;
  }

  /**
   * Devuelve recursos genéricos según el colectivo del participante.
   *
   * @param string $colectivo
   *   Código del colectivo (larga_duracion, mayores_45, migrantes, etc.).
   *
   * @return array<array{recurso: string, tipo: string, colectivo: string, descripcion: string}>
   *   Recursos del colectivo.
   */
  protected function getRecursosPorColectivo(string $colectivo): array {
    $recursos = [];

    switch ($colectivo) {
      case 'larga_duracion':
        $recursos[] = [
          'recurso' => 'Programa de reactivación profesional',
          'tipo' => 'formacion',
          'colectivo' => $colectivo,
          'descripcion' => 'Módulo de actualización de competencias y reciclaje profesional para personas desempleadas de larga duración.',
        ];
        $recursos[] = [
          'recurso' => 'Taller de autoestima y motivación laboral',
          'tipo' => 'apoyo_psicosocial',
          'colectivo' => $colectivo,
          'descripcion' => 'Sesiones grupales para recuperar la confianza en el mercado laboral.',
        ];
        break;

      case 'mayores_45':
        $recursos[] = [
          'recurso' => 'Programa de digitalización senior',
          'tipo' => 'formacion',
          'colectivo' => $colectivo,
          'descripcion' => 'Formación en competencias digitales adaptada a mayores de 45 años.',
        ];
        $recursos[] = [
          'recurso' => 'Red de empresas age-friendly',
          'tipo' => 'intermediacion',
          'colectivo' => $colectivo,
          'descripcion' => 'Base de datos de empleadores comprometidos con la diversidad generacional.',
        ];
        break;

      case 'migrantes':
        $recursos[] = [
          'recurso' => 'Servicio de homologación de títulos',
          'tipo' => 'orientacion',
          'colectivo' => $colectivo,
          'descripcion' => 'Asesoramiento para convalidación de titulaciones extranjeras (NARIC/ENIC).',
        ];
        $recursos[] = [
          'recurso' => 'Curso de castellano para el empleo',
          'tipo' => 'formacion',
          'colectivo' => $colectivo,
          'descripcion' => 'Formación en castellano con enfoque laboral y administrativo.',
        ];
        $recursos[] = [
          'recurso' => 'Asesoría jurídica de extranjería',
          'tipo' => 'orientacion',
          'colectivo' => $colectivo,
          'descripcion' => 'Consulta gratuita sobre permisos de trabajo, residencia y reagrupación.',
        ];
        break;

      case 'perceptores_prestaciones':
        $recursos[] = [
          'recurso' => 'Simulador de compatibilidad prestaciones-empleo',
          'tipo' => 'orientacion',
          'colectivo' => $colectivo,
          'descripcion' => 'Herramienta para evaluar impacto de inserción laboral en prestaciones actuales.',
        ];
        $recursos[] = [
          'recurso' => 'Asesoramiento sobre Ingreso Mínimo Vital',
          'tipo' => 'orientacion',
          'colectivo' => $colectivo,
          'descripcion' => 'Información actualizada sobre compatibilidad IMV con actividad laboral.',
        ];
        break;
    }

    return $recursos;
  }

  /**
   * Devuelve recursos específicos por barrera activa.
   *
   * @param string $tipo
   *   Tipo de barrera.
   * @param array $detalle
   *   Datos de la barrera.
   * @param string $colectivo
   *   Colectivo del participante.
   *
   * @return array<array{recurso: string, tipo: string, colectivo: string, descripcion: string}>
   *   Recursos específicos.
   */
  protected function getRecursosPorBarrera(string $tipo, array $detalle, string $colectivo): array {
    $recursos = [];

    switch ($tipo) {
      case 'idioma':
        $recursos[] = [
          'recurso' => 'Servicio de interpretación',
          'tipo' => 'apoyo_linguistico',
          'colectivo' => $colectivo,
          'descripcion' => 'Intérprete disponible para sesiones de orientación y entrevistas.',
        ];
        break;

      case 'brecha_digital':
        $recursos[] = [
          'recurso' => 'Punto de acceso digital comunitario',
          'tipo' => 'infraestructura',
          'colectivo' => $colectivo,
          'descripcion' => 'Espacios con wifi y equipos disponibles en bibliotecas y centros cívicos.',
        ];
        break;

      case 'carga_cuidados':
        $recursos[] = [
          'recurso' => 'Servicio de conciliación del programa',
          'tipo' => 'apoyo_social',
          'colectivo' => $colectivo,
          'descripcion' => 'Coordinación con recursos de conciliación municipales durante sesiones presenciales.',
        ];
        break;

      case 'vivienda':
        $recursos[] = [
          'recurso' => 'Red de alojamiento temporal',
          'tipo' => 'emergencia_social',
          'colectivo' => $colectivo,
          'descripcion' => 'Derivación a centros de acogida y programas de vivienda social.',
        ];
        break;

      case 'salud_mental':
        $recursos[] = [
          'recurso' => 'Atención psicológica SAS',
          'tipo' => 'salud',
          'colectivo' => $colectivo,
          'descripcion' => 'Derivación a Unidad de Salud Mental del Servicio Andaluz de Salud.',
        ];
        break;

      case 'violencia_genero':
        $recursos[] = [
          'recurso' => 'Instituto Andaluz de la Mujer',
          'tipo' => 'proteccion',
          'colectivo' => $colectivo,
          'descripcion' => 'Atención integral: jurídica, psicológica y de inserción laboral.',
        ];
        $recursos[] = [
          'recurso' => 'Teléfono 016',
          'tipo' => 'emergencia',
          'colectivo' => $colectivo,
          'descripcion' => 'Línea de atención 24h para víctimas de violencia de género.',
        ];
        break;

      case 'movilidad_geografica':
        $recursos[] = [
          'recurso' => 'Bono transporte social',
          'tipo' => 'movilidad',
          'colectivo' => $colectivo,
          'descripcion' => 'Información sobre ayudas al transporte del ayuntamiento/Junta de Andalucía.',
        ];
        break;
    }

    return $recursos;
  }

}
