<?php

declare(strict_types=1);

namespace Drupal\jaraba_andalucia_ei\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_copilot_v2\Service\CopilotBridgeInterface;
use Psr\Log\LoggerInterface;

/**
 * Copilot bridge: inyecta contexto PIIL del vertical Andalucía +ei.
 *
 * Sprint 17 — Clase Mundial:
 * Delega al AndaluciaEiCopilotContextProvider para obtener contexto
 * rico del participante (fase, horas, modos permitidos, system prompt).
 *
 * Claves especiales en getRelevantContext():
 * - _modos_permitidos: array<string, bool> con restricciones por fase
 * - _system_prompt_addition: string con prompt específico de fase
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
      'active_requests' => 0,
      'pending_documents' => 0,
      'approved_requests' => 0,
      'active_programs' => 0,
    ];

    try {
      if ($this->entityTypeManager->hasDefinition('ei_request')) {
        $storage = $this->entityTypeManager->getStorage('ei_request');

        $context['active_requests'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->condition('status', ['pending', 'in_review', 'documentation'], 'IN')
          ->count()
          ->execute();

        $context['approved_requests'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->condition('status', 'approved')
          ->count()
          ->execute();

        $context['pending_documents'] = (int) $storage
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('uid', $userId)
          ->condition('status', 'documentation')
          ->count()
          ->execute();
      }

      if ($this->entityTypeManager->hasDefinition('ei_program')) {
        $context['active_programs'] = (int) $this->entityTypeManager
          ->getStorage('ei_program')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('status', TRUE)
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

      // Sugerencias PIIL basadas en fase.
      $fase = $context['_fase_raw'] ?? NULL;
      if ($fase !== NULL) {
        return $this->getPiilSuggestion($fase, $context);
      }

      // Fallback: sugerencias genéricas.
      if (($context['pending_documents'] ?? 0) > 0) {
        return [
          'message' => 'Tienes ' . $context['pending_documents'] . ' solicitud(es) pendiente(s) de documentación.',
          'cta' => ['label' => 'Ver solicitudes', 'route' => 'jaraba_andalucia_ei.my_requests'],
          'trigger' => 'pending_docs',
        ];
      }

      if (($context['active_requests'] ?? 0) === 0 && ($context['active_programs'] ?? 0) > 0) {
        return [
          'message' => 'Hay ' . $context['active_programs'] . ' programa(s) activo(s). Consulta las convocatorias abiertas.',
          'cta' => ['label' => 'Ver programas', 'route' => 'jaraba_andalucia_ei.programs'],
          'trigger' => 'no_requests',
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
    catch (\Exception $e) {
      $this->logger->warning('AndaluciaEiCopilotBridge insights error: @error', [
        '@error' => $e->getMessage(),
      ]);
    }

    return $insights;
  }

}
