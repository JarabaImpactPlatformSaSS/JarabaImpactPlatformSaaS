<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Provides Andalucía +ei context to Copilot v2.
 *
 * Enriches the copilot conversation with participant-specific data:
 * phase, hours, documents, milestones, and cross-vertical bridges.
 *
 * AI-IDENTITY-001: Uses Jaraba identity in all prompts.
 */
class AndaluciaEiCopilotContextProvider {

  /**
   * Constructs an AndaluciaEiCopilotContextProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\jaraba_andalucia_ei\Service\ExpedienteService $expedienteService
   *   The document folder service.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\ecosistema_jaraba_core\Service\TenantContextService|null $tenantContext
   *   The tenant context service (optional — OPTIONAL-CROSSMODULE-001).
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
    protected ExpedienteService $expedienteService,
    protected LoggerInterface $logger,
    protected ?TenantContextService $tenantContext = NULL,
    protected ?CopilotPhaseConfigService $phaseConfig = NULL,
    protected ?CopilotHistorialService $copilotHistorial = NULL,
  ) {}

  /**
   * Gets context data for the copilot.
   *
   * @return array|null
   *   Context data or NULL if user is not a participant.
   */
  public function getContext(): ?array {
    $participante = $this->getParticipante();
    if (!$participante) {
      return NULL;
    }

    $horasOrientacion = $participante->getTotalHorasOrientacion();
    $horasFormacion = (float) ($participante->get('horas_formacion')->value ?? 0);
    $completitud = $this->expedienteService->getCompletuDocumental((int) $participante->id());

    $faseLabels = [
      'acogida' => 'Acogida',
      'diagnostico' => 'Diagnóstico',
      'atencion' => 'Atención',
      'insercion' => 'Inserción',
      'seguimiento' => 'Seguimiento',
      'baja' => 'Baja',
    ];

    $context = [
      'vertical' => 'andalucia_ei',
      'participante_id' => (int) $participante->id(),
      'fase_actual' => $participante->getFaseActual(),
      'fase_label' => $faseLabels[$participante->getFaseActual()] ?? $participante->getFaseActual(),
      'horas' => [
        'orientacion_total' => $horasOrientacion,
        'orientacion_meta' => 10.0,
        'orientacion_pct' => min(100, round(($horasOrientacion / 10) * 100)),
        'formacion' => $horasFormacion,
        'formacion_meta' => 50.0,
        'formacion_pct' => min(100, round(($horasFormacion / 50) * 100)),
        'mentoria_ia' => $participante->getHorasMentoriaIa(),
        'mentoria_humana' => $participante->getHorasMentoriaHumana(),
      ],
      'documentos' => [
        'sto_completados' => $completitud['completados'],
        'sto_total' => $completitud['total_requeridos'],
        'sto_porcentaje' => $completitud['porcentaje'],
        'pendientes_revision' => $this->countPendingDocs((int) $participante->id()),
      ],
      'milestones' => [
        'puede_transitar' => $participante->canTransitToInsercion(),
        'incentivo_recibido' => $participante->hasReceivedIncentivo(),
        'tipo_insercion' => $participante->get('tipo_insercion')->value ?? NULL,
      ],
      'modos_permitidos' => $this->getModosPermitidosPorFase($participante->getFaseActual()),
      'instrucciones_fase' => $this->getInstruccionesFase($participante->getFaseActual(), [
        'horas_orientacion' => $horasOrientacion,
        'horas_formacion' => $horasFormacion,
      ]),
      'system_prompt_addition' => $this->buildSystemPrompt($participante),
    ];

    // Sprint 7: Contexto de emprendimiento si tiene plan activo.
    $this->enriquecerConEmprendimiento($context, (int) $participante->id());

    // Sprint 8: Barreras de acceso para copilot sensible al colectivo.
    $this->enriquecerConBarreras($context, $participante);

    // Sprint F — SPEC-CAT-008: Contexto del pack confirmado del participante.
    $this->enriquecerConPack($context, $participante);

    return $context;
  }

  /**
   * Enriquece contexto con datos de plan de emprendimiento.
   *
   * Sprint 7 — Si el participante tiene plan activo, activa modos
   * adicionales del copilot de emprendimiento.
   */
  protected function enriquecerConEmprendimiento(array &$context, int $participanteId): void {
    if (!\Drupal::hasService('jaraba_andalucia_ei.ei_emprendimiento_bridge')) {
      return;
    }

    try {
      $bridge = \Drupal::service('jaraba_andalucia_ei.ei_emprendimiento_bridge');
      $plan = $bridge->getPlanActivo($participanteId);
      if ($plan) {
        $context['plan_emprendimiento'] = $bridge->getResumenPlan((int) $plan->id());
        $context['modos_adicionales'] = ['business_strategist', 'financial_advisor'];
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('Error enriqueciendo contexto emprendimiento: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Enriquece contexto con barreras de acceso del participante.
   *
   * Sprint 8 — Copilot sensible al colectivo: adapta comunicación
   * según barreras activas del participante.
   */
  protected function enriquecerConBarreras(array &$context, mixed $participante): void {
    if (!$participante->hasField('barreras_acceso')) {
      return;
    }

    $barrerasJson = $participante->get('barreras_acceso')->value ?? NULL;
    if (!$barrerasJson) {
      return;
    }

    try {
      $barreras = json_decode($barrerasJson, TRUE, 512, JSON_THROW_ON_ERROR);
      $activas = [];
      $instrucciones = [];

      foreach ($barreras as $tipo => $datos) {
        if (!empty($datos['activa'])) {
          $activas[] = $tipo;
          $instrucciones[] = match ($tipo) {
            'idioma' => 'Adapta tu lenguaje: usa frases simples y vocabulario básico. Nivel castellano del participante: ' . ($datos['castellano_nivel'] ?? 'desconocido') . '.',
            'brecha_digital' => 'El participante tiene brecha digital. Explica paso a paso cómo navegar la plataforma.',
            'carga_cuidados' => 'El participante tiene cargas de cuidados. Prioriza flexibilidad horaria y formación online/async.',
            'salud_mental' => 'Sensibilidad máxima. No presionar. Derivar a profesionales si detectas malestar.',
            'violencia_genero' => 'Enfoque empoderador. NUNCA preguntar por la situación personal. Derivar a profesionales especializados.',
            default => '',
          };
        }
      }

      if (!empty($activas)) {
        $context['barreras_activas'] = $activas;
        $context['instrucciones_barreras'] = array_filter($instrucciones);
      }
    }
    catch (\Throwable) {
      // JSON inválido — ignorar.
    }
  }

  /**
   * Builds a system prompt addition for the copilot.
   *
   * @param mixed $participante
   *   The participant entity.
   *
   * @return string
   *   System prompt text.
   */
  protected function buildSystemPrompt($participante): string {
    $fase = $participante->getFaseActual();
    $horas = $participante->getTotalHorasOrientacion();
    $formacion = (float) ($participante->get('horas_formacion')->value ?? 0);

    $prompt = sprintf(
      'El usuario es participante del programa Andalucía +ei de la Fundación Jaraba. ' .
      'Está en fase "%s". Lleva %.1fh de orientación (meta: 10h) y %.1fh de formación (meta: 50h). ',
      $fase,
      $horas,
      $formacion,
    );

    if ($fase === 'acogida') {
      $prompt .= 'El participante está en fase de acogida. Prioridad: explicar el programa, facilitar la firma del Acuerdo de Participación (Acuerdo_participacion_ICV25) Y del DACI — Documento de Aceptación de Compromisos e Información (Anexo_DACI_ICV25), y recoger indicadores FSE+ de entrada. Son DOS documentos distintos y obligatorios. NO ofrecer orientación vocacional ni formación aún. ';
    }
    elseif ($fase === 'diagnostico') {
      $prompt .= 'El participante está en fase de diagnóstico. Prioridad: completar cuestionario DIME para asignar itinerario (Impulso Digital o Acelera Pro). Puede explorar intereses pero NO iniciar formación formal. ';
    }
    elseif ($fase === 'atencion') {
      if ($horas < 5) {
        $prompt .= 'Prioridad: aumentar horas de orientación. Sugiere sesiones y actividades. ';
      }
      elseif ($formacion < 25) {
        $prompt .= 'Prioridad: avanzar en formación. Recomienda módulos del LMS. ';
      }
      elseif ($participante->canTransitToInsercion()) {
        $prompt .= 'El participante cumple requisitos para transitar a inserción. Orienta sobre el proceso. ';
      }
    }
    elseif ($fase === 'insercion') {
      $tipo = $participante->get('tipo_insercion')->value ?? '';
      if (empty($tipo)) {
        $prompt .= 'Prioridad: definir vía de inserción (empleo, autoempleo, formación complementaria). ';
      }
      else {
        $prompt .= sprintf('Vía de inserción: %s. Apoya en la búsqueda activa y preparación. ', $tipo);
      }
    }
    elseif ($fase === 'seguimiento') {
      $prompt .= 'El participante está en fase de seguimiento post-inserción. Apoya en la consolidación del empleo y gestiona indicadores FSE+ de salida. ';
    }

    return $prompt;
  }

  /**
   * Obtiene los modos de copilot permitidos según la fase.
   *
   * Cada fase tiene restricciones sobre qué puede hacer el copilot.
   *
   * @param string $fase
   *   Fase actual del participante.
   *
   * @return array<string, bool>
   *   Modos habilitados/deshabilitados.
   */
  public function getModosPermitidosPorFase(string $fase): array {
    return match ($fase) {
      'acogida' => [
        'orientacion_vocacional' => FALSE,
        'formacion' => FALSE,
        'insercion' => FALSE,
        'informacion_programa' => TRUE,
        'documentacion' => TRUE,
        'fse_entrada' => TRUE,
      ],
      'diagnostico' => [
        'orientacion_vocacional' => TRUE,
        'formacion' => FALSE,
        'insercion' => FALSE,
        'informacion_programa' => TRUE,
        'documentacion' => TRUE,
        'fse_entrada' => TRUE,
      ],
      'atencion' => [
        'orientacion_vocacional' => TRUE,
        'formacion' => TRUE,
        'insercion' => FALSE,
        'informacion_programa' => TRUE,
        'documentacion' => TRUE,
        'fse_entrada' => FALSE,
      ],
      'insercion' => [
        'orientacion_vocacional' => TRUE,
        'formacion' => TRUE,
        'insercion' => TRUE,
        'informacion_programa' => TRUE,
        'documentacion' => TRUE,
        'fse_entrada' => FALSE,
      ],
      'seguimiento' => [
        'orientacion_vocacional' => FALSE,
        'formacion' => FALSE,
        'insercion' => TRUE,
        'informacion_programa' => TRUE,
        'documentacion' => TRUE,
        'fse_salida' => TRUE,
      ],
      default => [
        'informacion_programa' => TRUE,
      ],
    };
  }

  /**
   * Obtiene instrucciones específicas por fase para el copilot.
   *
   * @param string $fase
   *   Fase actual.
   * @param array $contexto
   *   Datos adicionales (horas, inserción, etc.).
   *
   * @return string[]
   *   Lista de instrucciones prioritarias.
   */
  public function getInstruccionesFase(string $fase, array $contexto = []): array {
    $instrucciones = match ($fase) {
      'acogida' => [
        'Explica el programa Andalucía +ei y su duración (12 meses).',
        'Facilita la firma del Acuerdo de Participación (Acuerdo_participacion_ICV25).',
        'Facilita la firma del DACI (Anexo_DACI_ICV25) — documento separado del Acuerdo.',
        'Recoge indicadores FSE+ de entrada.',
        'NO ofrezcas orientación vocacional ni formación en esta fase.',
      ],
      'diagnostico' => [
        'Guía al participante en el cuestionario DIME.',
        'Explica los dos itinerarios: Impulso Digital y Acelera Pro.',
        'El carril se asigna según el resultado DIME.',
        'NO inicies formación formal hasta asignar carril.',
      ],
      'atencion' => [
        'Meta: 10h orientación + 50h formación.',
        'Sugiere sesiones de orientación individual y grupal.',
        'Recomienda módulos formativos del LMS según el carril.',
        'Monitoriza progreso hacia requisitos de inserción.',
      ],
      'insercion' => [
        'Apoya la búsqueda activa de empleo.',
        'Orienta sobre tipos de inserción: cuenta ajena, cuenta propia, agrario.',
        'Facilita conexión con empresas colaboradoras.',
        'Prepara para entrevistas y procesos de selección.',
      ],
      'seguimiento' => [
        'Apoya la consolidación del puesto de trabajo.',
        'Recoge indicadores FSE+ de salida.',
        'Evalúa satisfacción y calidad del empleo.',
        'Ofrece información sobre el Club Alumni.',
      ],
      default => ['Proporciona información general del programa.'],
    };

    // Instrucciones condicionales por contexto.
    $horasOrient = $contexto['horas_orientacion'] ?? 0;
    $horasForm = $contexto['horas_formacion'] ?? 0;

    if ($fase === 'atencion') {
      if ($horasOrient < 5) {
        array_unshift($instrucciones, 'PRIORIDAD: Aumentar horas de orientación (actualmente ' . $horasOrient . 'h de 10h).');
      }
      if ($horasForm < 25) {
        array_unshift($instrucciones, 'PRIORIDAD: Avanzar en formación (actualmente ' . $horasForm . 'h de 50h).');
      }
    }

    return $instrucciones;
  }

  /**
   * Gets the current user's participant entity.
   *
   * TENANT-001: Filters by uid AND tenant_id when available.
   */
  protected function getParticipante() {
    $uid = $this->currentUser->id();
    $storage = $this->entityTypeManager->getStorage('programa_participante_ei');
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $uid)
      ->condition('fase_actual', 'baja', '<>')
      ->range(0, 1);

    // TENANT-001: filtrar por tenant del usuario actual.
    $tenantId = $this->resolveCurrentTenantId();
    if ($tenantId) {
      $query->condition('tenant_id', $tenantId);
    }

    $ids = $query->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * Resolves the current tenant Group ID.
   *
   * @return int|null
   *   The group ID, or NULL if unavailable.
   */
  protected function resolveCurrentTenantId(): ?int {
    if (!$this->tenantContext) {
      return NULL;
    }

    try {
      $tenant = $this->tenantContext->getCurrentTenant();
      return $tenant ? (int) $tenant->id() : NULL;
    }
    catch (\Throwable) {
      return NULL;
    }
  }

  /**
   * Counts documents pending review for a participant.
   */
  protected function countPendingDocs(int $participanteId): int {
    try {
      $storage = $this->entityTypeManager->getStorage('expediente_documento');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participanteId)
        ->condition('estado_revision', ['pendiente', 'en_revision'], 'IN')
        ->condition('status', 1)
        ->execute();
      return count($ids);
    }
    catch (\Throwable) {
      return 0;
    }
  }

  /**
   * Enriquece contexto con datos del pack confirmado.
   *
   * Sprint F — SPEC-CAT-008: Inyecta info del pack del participante
   * para que el copilot adapte respuestas al contexto de negocio.
   *
   * @param array<string, mixed> $context
   *   Contexto a enriquecer (por referencia).
   * @param \Drupal\Core\Entity\ContentEntityInterface $participante
   *   Participante activo.
   */
  protected function enriquecerConPack(array &$context, $participante): void {
    try {
      $packConfirmado = $participante->get('pack_confirmado')->value ?? NULL;
      if ($packConfirmado === NULL || $packConfirmado === '') {
        return;
      }

      $packLabels = [
        'contenido_digital' => 'Contenido Digital',
        'asistente_virtual' => 'Asistente Virtual',
        'presencia_online' => 'Presencia Online',
        'tienda_digital' => 'Tienda Digital',
        'community_manager' => 'Community Manager',
      ];

      $packLabel = $packLabels[$packConfirmado] ?? $packConfirmado;
      $context['pack_confirmado'] = $packConfirmado;
      $context['pack_label'] = $packLabel;

      // Buscar pack publicado.
      $storage = $this->entityTypeManager->getStorage('pack_servicio_ei');
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('participante_id', $participante->id())
        ->condition('pack_tipo', $packConfirmado)
        ->range(0, 1)
        ->execute();

      if ($ids !== []) {
        $pack = $storage->load(reset($ids));
        if ($pack !== NULL) {
          $context['pack_publicado'] = (bool) ($pack->get('publicado')->value ?? FALSE);
          $context['pack_modalidad'] = $pack->get('modalidad')->value ?? '';
          $context['pack_precio'] = (float) ($pack->get('precio_mensual')->value ?? 0);
        }
      }

      // Enriquecer system prompt con contexto del pack.
      $addition = "\n\nEl participante tiene el Pack $packLabel confirmado.";
      if (isset($context['pack_publicado']) && $context['pack_publicado']) {
        $addition .= ' Su pack ya está publicado en el catálogo.';
      }
      else {
        $addition .= ' Su pack aún no está publicado.';
      }
      $addition .= ' Adapta tus respuestas a este contexto de negocio.';

      $context['_system_prompt_addition'] = ($context['_system_prompt_addition'] ?? '') . $addition;
    }
    catch (\Throwable) {
      // PRESAVE-RESILIENCE-001.
    }
  }

}
