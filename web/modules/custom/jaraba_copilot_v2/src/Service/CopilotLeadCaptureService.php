<?php

declare(strict_types=1);

namespace Drupal\jaraba_copilot_v2\Service;

use Psr\Log\LoggerInterface;

/**
 * Detecta intencion de compra en mensajes del copilot y crea leads CRM.
 *
 * Patron identico a VerticalQuizService::createCrmLead() y
 * PublicSubscribeController::createCrmLead() — LEAD-MAGNET-CRM-001.
 *
 * Usa clasificador por regex (NO LLM adicional) para mantener coste cero.
 * Forma parte del Nivel 2 (POR KEYWORD MATCH) de la cascada de busqueda IA.
 */
class CopilotLeadCaptureService {

  /**
   * Patrones de intencion por vertical (regex en espanol).
   *
   * Prioridad: las primeras verticales del array tienen mayor prioridad
   * cuando multiples patterns matchean (ej: "curso con incentivo" matchea
   * tanto formacion como andalucia_ei).
   */
  private const INTENT_PATTERNS = [
    'andalucia_ei' => [
      'patterns' => '/\b(inserci[oó]n\s+laboral|piil|andaluc[ií]a\s*\+?ei|fse\+?|incentivo\s+laboral|programa\s+gratuito|junta\s+de\s+andaluc|colectivos?\s+vulnerable)/iu',
      'keywords' => ['inserción', 'piil', 'andalucía', 'fse', 'incentivo laboral', 'colectivos'],
      'confidence' => 0.9,
    ],
    'formacion' => [
      'patterns' => '/\b(curso|formaci[oó]n|certificad|lecci[oó]n|instructor|capacitaci[oó]n|incentivo.{0,20}(curso|formaci)|aprender\s+(algo|un))/iu',
      'keywords' => ['curso', 'formación', 'certificado', 'lección', 'incentivo'],
      'confidence' => 0.8,
    ],
    'empleabilidad' => [
      'patterns' => '/\b(empleo|trabajo|curr[ií]cul|oferta\s+de\s+(empleo|trabajo)|busco\s+trabajo|orientaci[oó]n\s+profesional|riasec|candidate)/iu',
      'keywords' => ['empleo', 'trabajo', 'currículum', 'oferta'],
      'confidence' => 0.85,
    ],
    'emprendimiento' => [
      'patterns' => '/\b(negocio|empresa|emprender|startup|idea\s+de\s+negocio|canvas|validar\s+(mi\s+)?idea|mentor[ií]a)/iu',
      'keywords' => ['negocio', 'empresa', 'emprender', 'startup'],
      'confidence' => 0.8,
    ],
    'comercioconecta' => [
      'patterns' => '/\b(vender|tienda|comercio|ecommerce|marketplace|producto.{0,10}online|catalogo\s+digital)/iu',
      'keywords' => ['vender', 'tienda', 'comercio', 'ecommerce'],
      'confidence' => 0.8,
    ],
    'agroconecta' => [
      'patterns' => '/\b(productor|cosecha|agr[oí]col|campo|finca|bodega|trazabilidad|ecol[oó]gico|denominaci[oó]n\s+de\s+origen)/iu',
      'keywords' => ['productor', 'agrícola', 'campo', 'trazabilidad'],
      'confidence' => 0.8,
    ],
    'jarabalex' => [
      'patterns' => '/\b(ley|legal|abogad|normativa|contrato|jurisprudencia|legislaci[oó]n|bufete)/iu',
      'keywords' => ['legal', 'abogado', 'normativa', 'contrato'],
      'confidence' => 0.8,
    ],
    'serviciosconecta' => [
      'patterns' => '/\b(servicio\s+profesional|freelance|consultor[ií]a|reserva\s+online|agenda\s+digital)/iu',
      'keywords' => ['servicio', 'freelance', 'consultoría'],
      'confidence' => 0.75,
    ],
    'purchase_generic' => [
      'patterns' => '/\b(precio|plan|contratar|suscripci[oó]n|pagar|coste|tarifa|presupuesto|cu[aá]nto\s+cuesta)/iu',
      'keywords' => ['precio', 'plan', 'contratar', 'suscripción'],
      'confidence' => 0.7,
    ],
    'trial' => [
      'patterns' => '/\b(probar|gratis|demo|free|sin\s+compromiso|prueba\s+gratuita)/iu',
      'keywords' => ['probar', 'gratis', 'demo'],
      'confidence' => 0.6,
    ],
  ];

  /**
   * CRM Contact service (opcional).
   *
   * @var object|null
   */
  protected $contactService;

  /**
   * CRM Opportunity service (opcional).
   *
   * @var object|null
   */
  protected $opportunityService;

  /**
   * CRM Activity service (opcional).
   *
   * @var object|null
   */
  protected $activityService;

  public function __construct(
    protected LoggerInterface $logger,
    ?object $contactService = NULL,
    ?object $opportunityService = NULL,
    ?object $activityService = NULL,
  ) {
    $this->contactService = $contactService;
    $this->opportunityService = $opportunityService;
    $this->activityService = $activityService;
  }

  /**
   * Analiza mensaje para detectar intencion de compra o interes vertical.
   *
   * NO llama a un LLM adicional — usa clasificacion por regex
   * para mantener el coste por interaccion en cero.
   *
   * @param string $message
   *   Mensaje del visitante.
   *
   * @return array{
   *   has_intent: bool,
   *   intent_type: string,
   *   vertical: ?string,
   *   confidence: float,
   *   keywords_matched: array<string>,
   * }
   */
  public function detectPurchaseIntent(string $message): array {
    $result = [
      'has_intent' => FALSE,
      'intent_type' => 'none',
      'vertical' => NULL,
      'confidence' => 0.0,
      'keywords_matched' => [],
    ];

    $messageLower = mb_strtolower($message);

    foreach (self::INTENT_PATTERNS as $vertical => $config) {
      if (preg_match($config['patterns'], $messageLower, $matches)) {
        $result = [
          'has_intent' => TRUE,
          'intent_type' => in_array($vertical, ['purchase_generic', 'trial'], TRUE) ? $vertical : 'vertical_interest',
          'vertical' => in_array($vertical, ['purchase_generic', 'trial'], TRUE) ? NULL : $vertical,
          'confidence' => $config['confidence'],
          'keywords_matched' => [$matches[0]],
        ];
        // Primer match con mayor prioridad gana.
        break;
      }
    }

    return $result;
  }

  /**
   * Crea lead CRM desde interaccion copilot.
   *
   * LEAD-MAGNET-CRM-001: Patron identico a VerticalQuizService::createCrmLead().
   *
   * @param string $email
   *   Email del visitante.
   * @param string $verticalKey
   *   Clave canonica del vertical detectado.
   * @param array<string, mixed> $context
   *   Contexto: ip_hash, session_id, message_summary, utm_*.
   *
   * @return array{contact_id: ?int, opportunity_id: ?int, created: bool}
   */
  public function createCrmLead(string $email, string $verticalKey, array $context = []): array {
    $result = ['contact_id' => NULL, 'opportunity_id' => NULL, 'created' => FALSE];

    if (!$this->contactService || !$this->opportunityService) {
      $this->logger->info('CRM services not available — skipping lead creation for @email', [
        '@email' => $email,
      ]);
      return $result;
    }

    try {
      // Crear Contact (o encontrar existente por email).
      $contact = $this->contactService->create([
        'email' => $email,
        'source' => 'copilot_public',
        'engagement_score' => 25,
        'lead_status' => 'new',
      ]);

      $result['contact_id'] = (int) $contact->id();

      // Crear Opportunity.
      $opportunity = $this->opportunityService->create([
        'title' => 'Copilot lead — ' . $verticalKey,
        'contact_id' => $contact->id(),
        'stage' => 'lead',
        'source' => 'copilot_public',
        'probability' => 15,
        'description' => $context['message_summary'] ?? '',
      ]);

      $result['opportunity_id'] = (int) $opportunity->id();
      $result['created'] = TRUE;

      $this->logger->info('CRM lead created from copilot: contact=@cid, opportunity=@oid, vertical=@v', [
        '@cid' => $contact->id(),
        '@oid' => $opportunity->id(),
        '@v' => $verticalKey,
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->error('CRM lead creation failed: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }

    return $result;
  }

  /**
   * Registra actividad CRM desde interaccion copilot.
   *
   * @param int $contactId
   *   ID del contacto CRM.
   * @param string $activityType
   *   Tipo de actividad.
   * @param string $subject
   *   Asunto de la actividad.
   * @param array<string, mixed> $metadata
   *   Metadatos adicionales.
   */
  public function logCrmActivity(int $contactId, string $activityType, string $subject, array $metadata = []): void {
    if (!$this->activityService) {
      return;
    }

    try {
      $this->activityService->create([
        'activity_type' => $activityType,
        'subject' => $subject,
        'contact_id' => $contactId,
        'status' => 'completed',
        'outcome' => 'positive',
      ]);
    }
    catch (\Throwable $e) {
      $this->logger->warning('CRM activity logging failed: @msg', [
        '@msg' => $e->getMessage(),
      ]);
    }
  }

}
