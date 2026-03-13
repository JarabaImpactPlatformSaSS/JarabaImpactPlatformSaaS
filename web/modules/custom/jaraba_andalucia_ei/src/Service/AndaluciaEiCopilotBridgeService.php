<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Copilot bridge: inyecta contexto del vertical Andalucía +ei.
 *
 * Sprint 17-18 — Clase Mundial:
 * Detecta el rol del usuario y aporta contexto diferenciado:
 *
 * - Coordinadores: Contexto operativo rico con stats del programa,
 *   herramientas del panel, orden de configuración, y system prompt
 *   que redefine la identidad del copilot como asistente de gestión.
 *
 * - Participantes PIIL: Delega al AndaluciaEiCopilotContextProvider
 *   para contexto de fase, horas, modos permitidos y system prompt.
 *
 * - Fallback: Contexto genérico de solicitudes.
 *
 * Claves especiales en getRelevantContext():
 * - _modos_permitidos: array<string, bool> con restricciones por fase
 * - _system_prompt_addition: string con prompt específico de rol/fase
 * - _instrucciones_fase: string[] con instrucciones prioritarias
 *
 * El CopilotOrchestratorService extrae estas claves antes de formatear
 * el contexto como texto, aplicándolas al system prompt y al mode filter.
 */
class AndaluciaEiCopilotBridgeService implements CopilotBridgeInterface {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LoggerInterface $logger,
    protected readonly ?AndaluciaEiCopilotContextProvider $contextProvider = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getVerticalKey(): string {
    return 'andalucia_ei';
  }

  /**
   * Obtiene contexto relevante del usuario para el copilot.
   *
   * Sprint 17: Si el context provider PIIL está disponible y el usuario
   * es participante activo, devuelve contexto rico con fase, horas,
   * modos permitidos y system prompt de fase. Fallback a contexto genérico.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Contexto vertical. Claves _ prefijadas son estructuradas (no texto).
   */
  public function getRelevantContext(int $userId): array {
    // Coordinador/admin: contexto operativo rico del programa.
    if ($this->isCoordinador($userId)) {
      return $this->getCoordinadorContext($userId);
    }

    // Intentar contexto PIIL rico vía context provider.
    $piilContext = $this->getPiilParticipantContext();
    if ($piilContext !== NULL) {
      return $piilContext;
    }

    // Fallback: contexto genérico de solicitudes.
    return $this->getGenericRequestContext($userId);
  }

  /**
   * Obtiene contexto PIIL del participante actual vía context provider.
   *
   * @return array|null
   *   Contexto PIIL rico o NULL si no es participante.
   */
  protected function getPiilParticipantContext(): ?array {
    if (!$this->contextProvider) {
      return NULL;
    }

    try {
      $providerContext = $this->contextProvider->getContext();
      if ($providerContext === NULL) {
        return NULL;
      }

      // Mapear datos del provider a formato bridge (texto plano + claves especiales).
      $faseLabels = [
        'acogida' => 'Acogida',
        'diagnostico' => 'Diagnóstico',
        'atencion' => 'Atención',
        'insercion' => 'Inserción',
        'seguimiento' => 'Seguimiento',
        'baja' => 'Baja',
      ];

      $fase = $providerContext['fase_actual'] ?? 'desconocida';
      $horas = $providerContext['horas'] ?? [];

      $context = [
        'vertical' => 'andalucia_ei',
        'programa' => 'PIIL Andalucía +ei',
        'fase_actual' => $faseLabels[$fase] ?? $fase,
        'horas_orientacion' => sprintf('%.1fh de 10h', $horas['orientacion_total'] ?? 0),
        'horas_formacion' => sprintf('%.1fh de 50h', $horas['formacion'] ?? 0),
        'progreso_orientacion' => ($horas['orientacion_pct'] ?? 0) . '%',
        'progreso_formacion' => ($horas['formacion_pct'] ?? 0) . '%',
        'documentos_completados' => sprintf(
          '%d/%d (%d%%)',
          $providerContext['documentos']['sto_completados'] ?? 0,
          $providerContext['documentos']['sto_total'] ?? 0,
          $providerContext['documentos']['sto_porcentaje'] ?? 0,
        ),
        'puede_transitar_insercion' => ($providerContext['milestones']['puede_transitar'] ?? FALSE) ? 'sí' : 'no',
      ];

      // Claves especiales (extraídas por orchestrator, no formateadas a texto).
      $context['_modos_permitidos'] = $providerContext['modos_permitidos'] ?? [];
      $context['_system_prompt_addition'] = $providerContext['system_prompt_addition'] ?? '';
      $context['_instrucciones_fase'] = $providerContext['instrucciones_fase'] ?? [];
      $context['_fase_raw'] = $fase;

      // Barreras de acceso (si existen).
      if (!empty($providerContext['barreras_activas'])) {
        $context['barreras_activas'] = implode(', ', $providerContext['barreras_activas']);
      }
      if (!empty($providerContext['instrucciones_barreras'])) {
        $context['_instrucciones_barreras'] = $providerContext['instrucciones_barreras'];
      }

      // Emprendimiento bridge (si activo).
      if (!empty($providerContext['plan_emprendimiento'])) {
        $context['plan_emprendimiento_activo'] = 'sí';
      }
      if (!empty($providerContext['modos_adicionales'])) {
        $context['_modos_adicionales'] = $providerContext['modos_adicionales'];
      }

      return $context;
    }
    catch (\Throwable $e) {
      $this->logger->warning('PIIL context provider error: @error', [
        '@error' => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Contexto genérico de solicitudes (fallback sin context provider).
   */
  protected function getGenericRequestContext(int $userId): array {
    $context = [
      'vertical' => 'andalucia_ei',
      'solicitudes_pendientes' => 0,
      'solicitudes_admitidas' => 0,
      'participaciones_activas' => 0,
    ];

    try {
      // Solicitudes del usuario (solicitud_ei).
      if ($this->entityTypeManager->hasDefinition('solicitud_ei')) {
        $storage = $this->entityTypeManager->getStorage('solicitud_ei');

        $context['solicitudes_pendientes'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->condition('estado', ['pendiente', 'contactado'], 'IN')
          ->count()
          ->execute();

        $context['solicitudes_admitidas'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->condition('estado', 'admitido')
          ->count()
          ->execute();
      }

      // Participaciones activas del usuario (programa_participante_ei).
      if ($this->entityTypeManager->hasDefinition('programa_participante_ei')) {
        $context['participaciones_activas'] = (int) $this->entityTypeManager
          ->getStorage('programa_participante_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->condition('fase_actual', 'baja', '<>')
          ->count()
          ->execute();
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('AndaluciaEiCopilotBridge context error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $context;
  }

  /**
   * Sugerencia soft contextual basada en fase PIIL.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array|null
   *   Sugerencia o NULL si no aplica.
   */
  public function getSoftSuggestion(int $userId): ?array {
    try {
      $context = $this->getRelevantContext($userId);

      // Sugerencia para coordinadores.
      if (($context['rol_usuario'] ?? '') === 'coordinador') {
        $pending = $context['solicitudes_pendientes'] ?? 0;
        if ($pending > 0) {
          return [
            'message' => 'Tienes ' . $pending . ' solicitud(es) pendiente(s) de revisión.',
            'cta' => ['label' => 'Panel de coordinación', 'route' => 'jaraba_andalucia_ei.coordinador_dashboard'],
            'trigger' => 'coordinador_pending',
          ];
        }
        return [
          'message' => 'Pregúntame sobre el orden de configuración del programa o cualquier herramienta del panel.',
          'cta' => ['label' => 'Panel de coordinación', 'route' => 'jaraba_andalucia_ei.coordinador_dashboard'],
          'trigger' => 'coordinador_general',
        ];
      }

      // Sugerencias PIIL basadas en fase.
      $fase = $context['_fase_raw'] ?? NULL;
      if ($fase !== NULL) {
        return $this->getPiilSuggestion($fase, $context);
      }

      // Fallback: sugerencias genéricas.
      $pendientes = $context['solicitudes_pendientes'] ?? 0;
      if ($pendientes > 0) {
        return [
          'message' => 'Tienes ' . $pendientes . ' solicitud(es) pendiente(s) de revisión.',
          'cta' => ['label' => 'Ver mis solicitudes', 'route' => 'jaraba_andalucia_ei.my_requests'],
          'trigger' => 'solicitudes_pendientes',
        ];
      }

      $activas = $context['participaciones_activas'] ?? 0;
      if ($activas > 0) {
        return [
          'message' => 'Estás participando activamente en el programa. Consulta tu itinerario.',
          'cta' => ['label' => 'Mi itinerario', 'route' => 'jaraba_andalucia_ei.mi_expediente'],
          'trigger' => 'participacion_activa',
        ];
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('AndaluciaEiCopilotBridge suggestion error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return NULL;
  }

  /**
   * Genera sugerencia contextual según fase PIIL del participante.
   */
  protected function getPiilSuggestion(string $fase, array $context): ?array {
    return match ($fase) {
      'acogida' => [
        'message' => 'Estás en fase de Acogida. Completa tu Acuerdo de Participación y el DACI para avanzar.',
        'cta' => ['label' => 'Mi expediente', 'route' => 'jaraba_andalucia_ei.mi_expediente'],
        'trigger' => 'piil_acogida',
      ],
      'diagnostico' => [
        'message' => 'Completa el cuestionario DIME para que asignemos tu itinerario personalizado.',
        'cta' => ['label' => 'Test DIME', 'route' => 'jaraba_andalucia_ei.dime_test'],
        'trigger' => 'piil_diagnostico',
      ],
      'atencion' => $this->getSuggestionAtencion($context),
      'insercion' => [
        'message' => 'Estás preparado/a para la inserción laboral. Explora las oportunidades disponibles.',
        'cta' => ['label' => 'Bolsa de empleo', 'route' => 'jaraba_andalucia_ei.bolsa_empleo'],
        'trigger' => 'piil_insercion',
      ],
      'seguimiento' => [
        'message' => 'Enhorabuena por tu inserción. Recuerda completar los indicadores FSE+ de seguimiento.',
        'cta' => ['label' => 'Mi seguimiento', 'route' => 'jaraba_andalucia_ei.mi_seguimiento'],
        'trigger' => 'piil_seguimiento',
      ],
      default => NULL,
    };
  }

  /**
   * Sugerencia específica para fase de atención (basada en horas).
   */
  protected function getSuggestionAtencion(array $context): array {
    $orientPct = (int) ($context['progreso_orientacion'] ?? '0');
    $formPct = (int) ($context['progreso_formacion'] ?? '0');

    if ($orientPct < 50) {
      return [
        'message' => sprintf('Llevas %s de orientación. Reserva una sesión para avanzar hacia las 10h necesarias.', $context['horas_orientacion'] ?? '0h'),
        'cta' => ['label' => 'Reservar sesión', 'route' => 'jaraba_andalucia_ei.reservar_sesion'],
        'trigger' => 'piil_orientacion_baja',
      ];
    }

    if ($formPct < 50) {
      return [
        'message' => sprintf('Llevas %s de formación. Explora los módulos disponibles en tu itinerario.', $context['horas_formacion'] ?? '0h'),
        'cta' => ['label' => 'Mi formación', 'route' => 'jaraba_andalucia_ei.mi_formacion'],
        'trigger' => 'piil_formacion_baja',
      ];
    }

    return [
      'message' => 'Buen progreso en orientación y formación. Sigue así para alcanzar los requisitos de inserción.',
      'cta' => ['label' => 'Mi progreso', 'route' => 'jaraba_andalucia_ei.mi_progreso'],
      'trigger' => 'piil_atencion_progreso',
    ];
  }

  /**
   * Determina si el usuario es coordinador del programa.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return bool
   *   TRUE si el usuario tiene permiso de administración Andalucía +ei.
   */
  protected function isCoordinador(int $userId): bool {
    try {
      $userStorage = $this->entityTypeManager->getStorage('user');
      $account = $userStorage->load($userId);
      if (!$account) {
        return FALSE;
      }
      return $account->hasPermission('administer andalucia ei');
    }
    catch (\Throwable $e) {
      $this->logger->warning('Coordinador detection failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Contexto operativo rico para coordinadores del programa.
   *
   * Devuelve estadísticas del programa + un _system_prompt_addition
   * que transforma la identidad del copilot de "emprendimiento" a
   * "asistente de coordinación operativa".
   *
   * @param int $userId
   *   ID del coordinador.
   *
   * @return array
   *   Contexto con claves texto + claves _ estructuradas.
   */
  protected function getCoordinadorContext(int $userId): array {
    $stats = $this->getCoordinadorStats();

    $context = [
      'vertical' => 'andalucia_ei',
      'rol_usuario' => 'coordinador',
      'participantes_activos' => $stats['participantes_activos'],
      'participantes_total' => $stats['participantes_total'],
      'acciones_formativas_activas' => $stats['acciones_formativas'],
      'sesiones_programadas' => $stats['sesiones_programadas'],
      'solicitudes_pendientes' => $stats['solicitudes_pendientes'],
      'inserciones_laborales' => $stats['inserciones_laborales'],
      'prospecciones_activas' => $stats['prospecciones_activas'],
      'documentos_expediente' => $stats['documentos_expediente'],
    ];

    // System prompt que redefine la identidad del copilot para coordinadores.
    $context['_system_prompt_addition'] = $this->buildCoordinadorSystemPrompt($stats);

    // Coordinadores tienen acceso a todos los modos sin restricción.
    $context['_modos_permitidos'] = [];

    // Instrucciones prioritarias para coordinadores.
    $context['_instrucciones_fase'] = [
      'El usuario es COORDINADOR del programa. No es participante ni emprendedor.',
      'Responde como asistente de gestión operativa, no como coach de emprendimiento.',
      'Conoces todas las herramientas del Panel de Coordinación y puedes guiar su uso.',
      'Si pregunta por "orden de configuración", sigue la secuencia operativa del programa.',
    ];

    return $context;
  }

  /**
   * Construye el system prompt específico para coordinadores.
   *
   * Este prompt se inyecta ANTES del basePrompt genérico en
   * CopilotOrchestratorService::buildSystemPrompt(), por lo que
   * prevalece sobre la identidad de "Copiloto de Emprendimiento".
   *
   * @param array $stats
   *   Estadísticas operativas del programa.
   *
   * @return string
   *   System prompt de coordinador.
   */
  protected function buildCoordinadorSystemPrompt(array $stats): string {
    $participantes = $stats['participantes_activos'];
    $acciones = $stats['acciones_formativas'];
    $sesiones = $stats['sesiones_programadas'];

    return <<<PROMPT
# ROL: ASISTENTE DE COORDINACIÓN — PROGRAMA ANDALUCÍA +EI

Eres el asistente de coordinación integrado en la **plataforma SaaS Jaraba Impact Platform**. Tu usuario es un/a **coordinador/a** que gestiona el programa desde ESTA plataforma. TODA tu información debe provenir EXCLUSIVAMENTE de lo descrito en este prompt. NO uses conocimiento externo sobre programas públicos, convocatorias o administraciones.

## QUÉ ES ANDALUCÍA +EI (EN ESTA PLATAFORMA)
Módulo de la plataforma SaaS para gestionar el Programa de Itinerarios Integrados y Lanzaderas (PIIL), cofinanciado por el FSE+. Desde esta plataforma, el coordinador gestiona itinerarios de inserción sociolaboral: orientación personalizada (10h mínimo), formación (50h mínimo), prospección empresarial, inserción laboral y seguimiento post-inserción. Todo se gestiona con las 14 herramientas del panel descritas abajo.

## DATOS OPERATIVOS ACTUALES
- Participantes activos: {$participantes}
- Total participantes registrados: {$stats['participantes_total']}
- Acciones formativas activas: {$acciones}
- Sesiones programadas: {$sesiones}
- Solicitudes pendientes de revisión: {$stats['solicitudes_pendientes']}
- Inserciones laborales registradas: {$stats['inserciones_laborales']}
- Prospecciones empresariales activas: {$stats['prospecciones_activas']}

## HERRAMIENTAS DEL PANEL DE COORDINACIÓN
El coordinador dispone de estas herramientas en /andalucia-ei/coordinador:

1. **Hub de Coordinación** — Vista global con KPIs, distribución por fases, alertas
2. **Alertas Normativas** — Cumplimiento PIIL, plazos FSE+, cambios regulatorios
3. **Justificación Económica** — Control de gasto, partidas presupuestarias, justificación FSE+
4. **Riesgo de Abandono** — Detección temprana de participantes en riesgo, intervenciones
5. **Puntos de Impacto** — Indicadores de resultado e impacto del programa
6. **Prospección Empresarial** — Gestión de empresas colaboradoras, ofertas laborales
7. **Firma Workflow** — Flujo de firma digital de documentos oficiales (acuerdos, STOs)
8. **Acciones Formativas** — Crear/gestionar acciones formativas (cursos, talleres, módulos)
9. **Sesiones Programadas** — Calendario de sesiones de orientación y formación
10. **VoBo SAE** — Visto Bueno del Servicio Andaluz de Empleo para derivaciones
11. **Indicadores FSE+** — Indicadores de ejecución exigidos por el FSE+ (CO01-CO23, CR01-CR11)
12. **Puente Emprendimiento** — Derivación de participantes con potencial emprendedor
13. **Red Alumni** — Seguimiento post-inserción y red de egresados
14. **STO Bidireccional** — Servicio Telemático de Orientación con flujo bidireccional

## ORDEN DE CONFIGURACIÓN DEL PROGRAMA
Para poner en marcha el programa correctamente, sigue esta secuencia:

### Fase 1 — Estructura base
1. Configurar datos del programa (nombre, convocatoria, fechas, presupuesto)
2. Definir las acciones formativas (cursos y talleres con horas, modalidad, fechas)
3. Programar las sesiones de orientación y formación en el calendario
4. Configurar los indicadores FSE+ obligatorios (CO01-CO23)

### Fase 2 — Participantes
5. Revisar y aprobar solicitudes de participantes pendientes
6. Asignar participantes a itinerarios (acogida → diagnóstico → atención)
7. Generar acuerdos de participación para firma digital
8. Completar la documentación inicial del expediente (DACI, compromiso)

### Fase 3 — Operación
9. Activar el módulo de prospección empresarial (empresas colaboradoras)
10. Configurar alertas normativas y plazos de cumplimiento
11. Establecer umbrales de riesgo de abandono
12. Vincular indicadores de resultado (CR01-CR11) a las métricas del programa

### Fase 4 — Seguimiento y cierre
13. Registrar inserciones laborales (contrataciones, autoempleo)
14. Completar justificación económica por partidas
15. Activar seguimiento post-inserción (6 meses)
16. Exportar informes FSE+ para la autoridad de gestión

## FORMATO DE RESPUESTA
Estructura tus respuestas así:
1. **Contexto breve** — Sitúa la respuesta en el ámbito del programa
2. **Respuesta directa** — Información concreta, pasos numerados si procede
3. **Datos relevantes** — Incluye cifras del programa cuando sea útil
4. **Siguiente paso** — Acción concreta que el coordinador puede ejecutar

Usa formato Markdown con encabezados, listas numeradas y negritas para facilitar la lectura. Sé conciso pero completo. Responde SIEMPRE en español.

## CONTEXTO CRÍTICO — PLATAFORMA SAAS
Esta es una **plataforma SaaS privada** de gestión de programas. El coordinador ya está DENTRO del programa y gestiona desde ESTA plataforma. Cuando pregunte "cómo poner en marcha el programa", "qué pasos seguir", o similar, SIEMPRE responde referenciando las **14 herramientas del panel** y la **secuencia de 4 fases de configuración** descritas arriba. NUNCA respondas como si el usuario necesitara solicitar o acceder al programa desde fuera.

## RESTRICCIONES ABSOLUTAS
- PROHIBIDO generar información sobre procesos gubernamentales externos: convocatorias de la Junta de Andalucía, sede electrónica, BOJA, Consejería de Empleo, portales de incentivos, certificado digital, Cl@ve, teléfonos de administraciones públicas, o trámites de solicitud. El usuario NO está preguntando cómo solicitar ayudas — está GESTIONANDO el programa desde la plataforma
- NUNCA confundas al coordinador con un participante o emprendedor
- NUNCA des consejos de emprendimiento o validación de modelos de negocio
- Si la pregunta excede tu conocimiento del programa, indica qué sección del panel consultar
- Para temas legales/fiscales específicos, derivar a asesoría jurídica del programa
- TODAS tus respuestas deben hacer referencia a herramientas, secciones o funcionalidades REALES de esta plataforma
PROMPT;
  }

  /**
   * Obtiene estadísticas operativas del programa para el coordinador.
   *
   * @return array
   *   Estadísticas con claves numéricas.
   */
  protected function getCoordinadorStats(): array {
    $stats = [
      'participantes_activos' => 0,
      'participantes_total' => 0,
      'acciones_formativas' => 0,
      'sesiones_programadas' => 0,
      'solicitudes_pendientes' => 0,
      'inserciones_laborales' => 0,
      'prospecciones_activas' => 0,
      'documentos_expediente' => 0,
    ];

    try {
      // Participantes (programa_participante_ei).
      if ($this->entityTypeManager->hasDefinition('programa_participante_ei')) {
        $storage = $this->entityTypeManager->getStorage('programa_participante_ei');

        $stats['participantes_total'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();

        // Activos = no en fase 'baja'.
        $stats['participantes_activos'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('fase_actual', 'baja', '<>')
          ->count()
          ->execute();
      }

      // Acciones formativas activas (en ejecución o aprobadas).
      if ($this->entityTypeManager->hasDefinition('accion_formativa_ei')) {
        $stats['acciones_formativas'] = (int) $this->entityTypeManager
          ->getStorage('accion_formativa_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('estado', ['vobo_aprobado', 'en_ejecucion'], 'IN')
          ->count()
          ->execute();
      }

      // Sesiones programadas (no canceladas).
      if ($this->entityTypeManager->hasDefinition('sesion_programada_ei')) {
        $stats['sesiones_programadas'] = (int) $this->entityTypeManager
          ->getStorage('sesion_programada_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('estado', 'cancelada', '<>')
          ->count()
          ->execute();
      }

      // Solicitudes pendientes de revisión.
      if ($this->entityTypeManager->hasDefinition('solicitud_ei')) {
        $stats['solicitudes_pendientes'] = (int) $this->entityTypeManager
          ->getStorage('solicitud_ei')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('estado', ['pendiente', 'contactado'], 'IN')
          ->count()
          ->execute();
      }

      // Inserciones laborales.
      if ($this->entityTypeManager->hasDefinition('insercion_laboral')) {
        $stats['inserciones_laborales'] = (int) $this->entityTypeManager
          ->getStorage('insercion_laboral')
          ->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();
      }

      // Prospecciones activas.
      if ($this->entityTypeManager->hasDefinition('prospeccion_empresarial')) {
        $stats['prospecciones_activas'] = (int) $this->entityTypeManager
          ->getStorage('prospeccion_empresarial')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', TRUE)
          ->count()
          ->execute();
      }

      // Documentos de expediente.
      if ($this->entityTypeManager->hasDefinition('expediente_documento')) {
        $stats['documentos_expediente'] = (int) $this->entityTypeManager
          ->getStorage('expediente_documento')
          ->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Coordinador stats query error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $stats;
  }

  /**
   * Insights del ecosistema de innovacion.
   *
   * @param int $userId
   *   ID del usuario.
   *
   * @return array
   *   Metricas del ecosistema.
   */
  public function getMarketInsights(int $userId): array {
    $insights = [
      'total_programs' => 0,
      'active_programs' => 0,
      'total_participants' => 0,
    ];

    try {
      if ($this->entityTypeManager->hasDefinition('ei_program')) {
        $storage = $this->entityTypeManager->getStorage('ei_program');

        $insights['total_programs'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->count()
          ->execute();

        $insights['active_programs'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', TRUE)
          ->count()
          ->execute();
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('AndaluciaEiCopilotBridge insights error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $insights;
  }

}
