<?php

declare(strict_types=1);

namespace Drupal\jaraba_funding\Service\Intelligence;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Copilot de subvenciones que responde consultas en lenguaje natural.
 *
 * Detecta la intencion del usuario (buscar, elegibilidad, plazos,
 * documentacion, comparacion) y delega al handler correspondiente.
 * Devuelve respuestas estructuradas con sugerencias y matches.
 *
 * ARQUITECTURA:
 * - Deteccion de intents por keywords.
 * - Handlers especificos por tipo de consulta.
 * - Integracion con FundingMatchingEngine para busquedas.
 * - Respuestas con sugerencias de follow-up.
 *
 * RELACIONES:
 * - FundingCopilotService -> FundingMatchingEngine (matching)
 * - FundingCopilotService -> FundingCall entity (datos)
 * - FundingCopilotService -> FundingSubscription entity (perfil)
 * - FundingCopilotService -> jaraba_funding.settings (configuracion)
 */
class FundingCopilotService {

  /**
   * Keywords por intent para deteccion.
   *
   * @var array<string, array<string>>
   */
  protected const INTENT_KEYWORDS = [
    'search' => ['buscar', 'busco', 'encontrar', 'subvencion', 'ayuda', 'convocatoria', 'listar', 'mostrar', 'que hay', 'disponibles'],
    'eligibility' => ['elegible', 'puedo', 'cumplo', 'requisitos', 'aplica', 'calificar', 'cumplir', 'acceder'],
    'deadline' => ['plazo', 'fecha', 'vence', 'caduca', 'cierre', 'cuando', 'limite', 'dias'],
    'documentation' => ['documentacion', 'documentos', 'papeles', 'formulario', 'solicitud', 'presentar', 'necesito'],
    'comparison' => ['comparar', 'diferencia', 'mejor', 'cual', 'elegir', 'entre', 'versus', 'vs'],
  ];

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \Drupal\jaraba_funding\Service\Intelligence\FundingMatchingEngine $matchingEngine
   *   Motor de matching de subvenciones.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factory de configuracion.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger del modulo.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FundingMatchingEngine $matchingEngine,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Procesa un mensaje del usuario y genera respuesta.
   *
   * @param string $message
   *   Mensaje del usuario en lenguaje natural.
   * @param int $tenantId
   *   ID del tenant actual.
   * @param array $context
   *   Contexto adicional (e.g., conversacion previa).
   *
   * @return array
   *   Respuesta estructurada con claves:
   *   - response: (string) Respuesta en texto.
   *   - suggestions: (array) Sugerencias de follow-up.
   *   - matches: (array) Convocatorias relacionadas si aplica.
   */
  public function chat(string $message, int $tenantId, array $context = []): array {
    $config = $this->configFactory->get('jaraba_funding.settings');
    $enabled = (bool) ($config->get('copilot_enabled') ?? TRUE);

    if (!$enabled) {
      return [
        'response' => 'El copilot de subvenciones no esta habilitado actualmente.',
        'suggestions' => [],
        'matches' => [],
      ];
    }

    try {
      $intent = $this->detectIntent($message);

      $result = match ($intent) {
        'search' => $this->handleSearchIntent($message, $tenantId),
        'eligibility' => $this->handleEligibilityIntent($message, $tenantId),
        'deadline' => $this->handleDeadlineIntent($message, $tenantId),
        'documentation' => $this->handleDocumentationIntent($message, $tenantId),
        'comparison' => $this->handleComparisonIntent($message, $tenantId),
        default => $this->handleGeneralIntent($message, $tenantId),
      };

      $this->logger->info('Copilot: intent "@intent" para tenant @tenant.', [
        '@intent' => $intent,
        '@tenant' => $tenantId,
      ]);

      return $result;
    }
    catch (\Exception $e) {
      $this->logger->error('Error en copilot de subvenciones: @error', [
        '@error' => $e->getMessage(),
      ]);

      return [
        'response' => 'Se produjo un error al procesar su consulta. Intente de nuevo mas tarde.',
        'suggestions' => ['Buscar subvenciones disponibles', 'Ver plazos proximos'],
        'matches' => [],
      ];
    }
  }

  /**
   * Detecta la intencion del usuario a partir del mensaje.
   *
   * @param string $message
   *   Mensaje del usuario.
   *
   * @return string
   *   Intent detectado: 'search', 'eligibility', 'deadline',
   *   'documentation', 'comparison', 'general'.
   */
  public function detectIntent(string $message): string {
    $lowerMessage = mb_strtolower($message);
    $scores = [];

    foreach (self::INTENT_KEYWORDS as $intent => $keywords) {
      $score = 0;
      foreach ($keywords as $keyword) {
        if (str_contains($lowerMessage, $keyword)) {
          $score++;
        }
      }
      $scores[$intent] = $score;
    }

    // Obtener el intent con mayor puntuacion.
    arsort($scores);
    $bestIntent = array_key_first($scores);
    $bestScore = $scores[$bestIntent] ?? 0;

    // Si ninguna keyword coincide, intent general.
    if ($bestScore === 0) {
      return 'general';
    }

    return $bestIntent;
  }

  /**
   * Maneja consultas de busqueda de subvenciones.
   *
   * @param string $message
   *   Mensaje del usuario.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Respuesta con convocatorias encontradas.
   */
  public function handleSearchIntent(string $message, int $tenantId): array {
    try {
      $callStorage = $this->entityTypeManager->getStorage('funding_call');
      $query = $callStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'abierta')
        ->sort('deadline', 'ASC')
        ->range(0, 10);

      $ids = $query->execute();

      if (empty($ids)) {
        return [
          'response' => 'No se encontraron convocatorias abiertas en este momento. Puede configurar alertas para ser notificado cuando haya nuevas convocatorias.',
          'suggestions' => ['Configurar alertas de subvenciones', 'Ver convocatorias cerradas'],
          'matches' => [],
        ];
      }

      $calls = $callStorage->loadMultiple($ids);
      $summaries = [];
      $matchesData = [];

      foreach ($calls as $call) {
        $summaries[] = $this->formatCallSummary($call);
        $matchesData[] = [
          'id' => (int) $call->id(),
          'title' => $call->get('title')->value ?? '',
          'deadline' => $call->get('deadline')->value ?? NULL,
          'amount_max' => (float) ($call->get('amount_max')->value ?? 0),
        ];
      }

      $response = "He encontrado " . count($calls) . " convocatorias abiertas:\n\n" . implode("\n\n", $summaries);

      return [
        'response' => $response,
        'suggestions' => [
          'Ver mi elegibilidad para estas convocatorias',
          'Comparar las mejores opciones',
          'Filtrar por sector',
        ],
        'matches' => $matchesData,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error en handleSearchIntent: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'response' => 'Error al buscar convocatorias. Intente de nuevo.',
        'suggestions' => [],
        'matches' => [],
      ];
    }
  }

  /**
   * Maneja consultas de elegibilidad.
   *
   * @param string $message
   *   Mensaje del usuario.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Respuesta con analisis de elegibilidad.
   */
  public function handleEligibilityIntent(string $message, int $tenantId): array {
    try {
      // Buscar suscripcion del tenant.
      $subStorage = $this->entityTypeManager->getStorage('funding_subscription');
      $subIds = $subStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'active')
        ->range(0, 1)
        ->execute();

      if (empty($subIds)) {
        return [
          'response' => 'No tiene un perfil de subvenciones configurado. Configure su perfil con datos de su empresa (tipo de beneficiario, sector, empleados, facturacion) para que pueda analizar su elegibilidad automaticamente.',
          'suggestions' => ['Configurar perfil de subvenciones', 'Buscar subvenciones disponibles'],
          'matches' => [],
        ];
      }

      $subscription = $subStorage->load(reset($subIds));

      // Ejecutar matching.
      $matchResults = $this->matchingEngine->runMatchingForSubscription((int) $subscription->id());

      if (empty($matchResults)) {
        return [
          'response' => 'Actualmente no hay convocatorias que coincidan con su perfil por encima del umbral minimo. Puede revisar su perfil para ampliar criterios o esperar a nuevas convocatorias.',
          'suggestions' => ['Actualizar perfil', 'Bajar umbral de coincidencia', 'Ver todas las convocatorias'],
          'matches' => [],
        ];
      }

      $count = count($matchResults);
      $response = "He encontrado {$count} convocatorias que coinciden con su perfil:\n\n";

      $matchesData = [];
      foreach (array_slice($matchResults, 0, 5) as $match) {
        $callId = (int) $match->get('call_id')->value;
        $score = (float) $match->get('score')->value;
        $call = $this->entityTypeManager->getStorage('funding_call')->load($callId);

        if ($call) {
          $response .= "- " . ($call->get('title')->value ?? 'Sin titulo') . " (coincidencia: {$score}%)\n";
          $matchesData[] = [
            'id' => $callId,
            'title' => $call->get('title')->value ?? '',
            'score' => $score,
          ];
        }
      }

      return [
        'response' => $response,
        'suggestions' => [
          'Ver detalle de la mejor opcion',
          'Verificar requisitos completos',
          'Ver documentacion necesaria',
        ],
        'matches' => $matchesData,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error en handleEligibilityIntent: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'response' => 'Error al analizar elegibilidad. Intente de nuevo.',
        'suggestions' => [],
        'matches' => [],
      ];
    }
  }

  /**
   * Maneja consultas sobre plazos y fechas.
   *
   * @param string $message
   *   Mensaje del usuario.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Respuesta con plazos proximos.
   */
  public function handleDeadlineIntent(string $message, int $tenantId): array {
    try {
      $callStorage = $this->entityTypeManager->getStorage('funding_call');
      $today = date('Y-m-d');

      $ids = $callStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 'abierta')
        ->condition('deadline', $today, '>=')
        ->sort('deadline', 'ASC')
        ->range(0, 10)
        ->execute();

      if (empty($ids)) {
        return [
          'response' => 'No hay convocatorias con plazos proximos.',
          'suggestions' => ['Buscar convocatorias', 'Configurar alertas de plazos'],
          'matches' => [],
        ];
      }

      $calls = $callStorage->loadMultiple($ids);
      $response = "Plazos proximos de convocatorias abiertas:\n\n";
      $matchesData = [];

      foreach ($calls as $call) {
        $title = $call->get('title')->value ?? 'Sin titulo';
        $deadline = $call->get('deadline')->value ?? '';

        $daysRemaining = '';
        if (!empty($deadline)) {
          $deadlineDate = new \DateTime($deadline);
          $now = new \DateTime();
          $diff = $now->diff($deadlineDate);
          $daysRemaining = " ({$diff->days} dias)";
          $deadline = (new \DateTime($deadline))->format('d/m/Y');
        }

        $response .= "- {$title}: {$deadline}{$daysRemaining}\n";
        $matchesData[] = [
          'id' => (int) $call->id(),
          'title' => $title,
          'deadline' => $call->get('deadline')->value ?? '',
        ];
      }

      return [
        'response' => $response,
        'suggestions' => [
          'Ver mi elegibilidad',
          'Configurar recordatorios',
          'Ver documentacion necesaria',
        ],
        'matches' => $matchesData,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error en handleDeadlineIntent: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'response' => 'Error al consultar plazos. Intente de nuevo.',
        'suggestions' => [],
        'matches' => [],
      ];
    }
  }

  /**
   * Maneja consultas sobre documentacion requerida.
   *
   * @param string $message
   *   Mensaje del usuario.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Respuesta con informacion de documentacion.
   */
  public function handleDocumentationIntent(string $message, int $tenantId): array {
    $response = "La documentacion habitual para solicitar subvenciones incluye:\n\n";
    $response .= "1. Escritura de constitucion y estatutos de la empresa.\n";
    $response .= "2. CIF/NIF de la empresa y del representante legal.\n";
    $response .= "3. Certificado de estar al corriente con Hacienda (AEAT).\n";
    $response .= "4. Certificado de estar al corriente con la Seguridad Social (TGSS).\n";
    $response .= "5. Memoria descriptiva del proyecto o actividad.\n";
    $response .= "6. Presupuesto detallado.\n";
    $response .= "7. Declaracion de otras ayudas solicitadas o recibidas (minimis).\n";
    $response .= "8. Cuenta bancaria para el ingreso de la ayuda.\n\n";
    $response .= "Nota: Cada convocatoria puede requerir documentacion adicional especifica. Consulte las bases de la convocatoria para conocer los requisitos exactos.";

    return [
      'response' => $response,
      'suggestions' => [
        'Buscar convocatorias abiertas',
        'Ver plazos proximos',
        'Verificar mi elegibilidad',
      ],
      'matches' => [],
    ];
  }

  /**
   * Maneja consultas de comparacion entre convocatorias.
   *
   * @param string $message
   *   Mensaje del usuario.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Respuesta con comparacion de convocatorias.
   */
  public function handleComparisonIntent(string $message, int $tenantId): array {
    try {
      // Buscar matches del tenant para comparar.
      $subStorage = $this->entityTypeManager->getStorage('funding_subscription');
      $subIds = $subStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenantId)
        ->condition('status', 'active')
        ->range(0, 1)
        ->execute();

      if (empty($subIds)) {
        return [
          'response' => 'Configure su perfil de subvenciones para poder comparar convocatorias segun su situacion.',
          'suggestions' => ['Configurar perfil'],
          'matches' => [],
        ];
      }

      $subscriptionId = (int) reset($subIds);

      $matchStorage = $this->entityTypeManager->getStorage('funding_match');
      $matchIds = $matchStorage->getQuery()
        ->accessCheck(FALSE)
        ->condition('subscription_id', $subscriptionId)
        ->sort('score', 'DESC')
        ->range(0, 5)
        ->execute();

      if (empty($matchIds)) {
        return [
          'response' => 'No hay convocatorias matcheadas para comparar. Ejecute primero el matching.',
          'suggestions' => ['Ejecutar matching', 'Buscar subvenciones'],
          'matches' => [],
        ];
      }

      $matchEntities = $matchStorage->loadMultiple($matchIds);
      $callStorage = $this->entityTypeManager->getStorage('funding_call');

      $response = "Comparacion de sus mejores opciones:\n\n";
      $matchesData = [];
      $num = 1;

      foreach ($matchEntities as $match) {
        $callId = (int) $match->get('call_id')->value;
        $score = (float) $match->get('score')->value;
        $call = $callStorage->load($callId);

        if ($call) {
          $response .= "{$num}. " . $this->formatCallSummary($call);
          $response .= "   Coincidencia: {$score}%\n\n";
          $matchesData[] = [
            'id' => $callId,
            'title' => $call->get('title')->value ?? '',
            'score' => $score,
          ];
          $num++;
        }
      }

      return [
        'response' => $response,
        'suggestions' => [
          'Ver requisitos de la mejor opcion',
          'Ver documentacion necesaria',
          'Configurar alerta para la mejor opcion',
        ],
        'matches' => $matchesData,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error en handleComparisonIntent: @error', [
        '@error' => $e->getMessage(),
      ]);
      return [
        'response' => 'Error al comparar convocatorias. Intente de nuevo.',
        'suggestions' => [],
        'matches' => [],
      ];
    }
  }

  /**
   * Formatea el resumen de una convocatoria para mostrar.
   *
   * @param \Drupal\Core\Entity\EntityInterface $call
   *   Entidad FundingCall.
   *
   * @return string
   *   Resumen formateado en texto plano.
   */
  public function formatCallSummary(EntityInterface $call): string {
    $title = $call->get('title')->value ?? 'Sin titulo';
    $organism = $call->get('organism')->value ?? '';
    $region = $call->get('region')->value ?? '';
    $amountMax = (float) ($call->get('amount_max')->value ?? 0);
    $deadline = $call->get('deadline')->value ?? '';

    $summary = "** {$title} **\n";

    if (!empty($organism)) {
      $summary .= "   Organo: {$organism}\n";
    }
    if (!empty($region)) {
      $summary .= "   Region: {$region}\n";
    }
    if ($amountMax > 0) {
      $formattedAmount = number_format($amountMax, 2, ',', '.');
      $summary .= "   Importe: hasta {$formattedAmount} EUR\n";
    }
    if (!empty($deadline)) {
      $deadlineFormatted = (new \DateTime($deadline))->format('d/m/Y');
      $summary .= "   Plazo: {$deadlineFormatted}\n";
    }

    return $summary;
  }

  /**
   * Maneja consultas generales que no coinciden con un intent especifico.
   *
   * @param string $message
   *   Mensaje del usuario.
   * @param int $tenantId
   *   ID del tenant.
   *
   * @return array
   *   Respuesta generica con sugerencias.
   */
  protected function handleGeneralIntent(string $message, int $tenantId): array {
    return [
      'response' => "Soy el copilot de subvenciones. Puedo ayudarle con:\n\n"
        . "- **Buscar subvenciones** disponibles para su empresa.\n"
        . "- **Verificar elegibilidad** para convocatorias concretas.\n"
        . "- **Consultar plazos** de solicitud.\n"
        . "- **Documentacion** necesaria para solicitar ayudas.\n"
        . "- **Comparar** convocatorias.\n\n"
        . "Que le gustaria hacer?",
      'suggestions' => [
        'Buscar subvenciones para mi empresa',
        'Ver plazos proximos',
        'Verificar mi elegibilidad',
        'Que documentos necesito',
      ],
      'matches' => [],
    ];
  }

}
