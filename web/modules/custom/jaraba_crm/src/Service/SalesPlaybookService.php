<?php

declare(strict_types=1);

namespace Drupal\jaraba_crm\Service;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_crm\Entity\Opportunity;
use Psr\Log\LoggerInterface;

/**
 * Sales Playbook — acciones recomendadas por etapa + BANT (Doc 186 §4).
 *
 * Determina la siguiente accion comercial para una oportunidad
 * basandose en la etapa actual del pipeline y el score BANT.
 */
class SalesPlaybookService {

  use StringTranslationTrait;

  /**
   * Constructor.
   */
  public function __construct(
    protected OpportunityService $opportunityService,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Obtiene la accion recomendada para una oportunidad.
   *
   * @param \Drupal\jaraba_crm\Entity\Opportunity $opportunity
   *   La oportunidad a evaluar.
   *
   * @return array
   *   Array con keys: action, priority, details, bant_score, stage.
   */
  public function getNextAction(Opportunity $opportunity): array {
    $stage = $opportunity->getStage();
    $bant = $opportunity->getBantScore();

    $recommendation = match ($stage) {
      'lead' => $this->buildAction(
        $this->t('Enviar secuencia email nurturing (5 emails / 30 días)'),
        'medium',
        [
          $this->t('Incluir caso de éxito relevante para el sector'),
          $this->t('Programar follow-up automático al día 7'),
          $this->t('Objetivo: mover a MQL con engagement > 30%'),
        ],
      ),
      'mql' => $this->buildAction(
        $this->t('Programar llamada de descubrimiento (15-20 min, framework SPIN)'),
        'high',
        [
          $this->t('Preparar preguntas SPIN: Situación, Problema, Implicación, Necesidad'),
          $this->t('Identificar criterios BANT durante la llamada'),
          $this->t('Documentar pain points y stakeholders'),
        ],
      ),
      'sql' => $bant >= 3
        ? $this->buildAction(
            $this->t('Programar demo personalizada'),
            'high',
            [
              $this->t('Personalizar demo para el caso de uso del prospecto'),
              $this->t('Incluir ROI estimado y comparativa de planes'),
              $this->t('Invitar al decision maker y champion'),
            ],
          )
        : $this->buildAction(
            $this->t('Re-cualificar: BANT incompleto (@score/4)', ['@score' => $bant]),
            'critical',
            [
              $this->t('Revisar campos BANT pendientes'),
              $this->t('Programar reunión de cualificación adicional'),
              $this->t('Considerar devolver a MQL si no cualifica'),
            ],
          ),
      'demo' => $this->buildAction(
        $this->t('Enviar propuesta personalizada con 3 opciones de plan'),
        'high',
        [
          $this->t('Incluir plan básico, profesional y enterprise'),
          $this->t('Destacar ROI y período de recuperación'),
          $this->t('Añadir testimonios de clientes similares'),
        ],
      ),
      'proposal' => $this->buildAction(
        $this->t('Follow-up a los 3 días si no hay respuesta'),
        'medium',
        [
          $this->t('Ofrecer resolución de dudas por videollamada'),
          $this->t('Compartir caso de éxito adicional'),
          $this->t('Evaluar objeciones potenciales: precio, timing, competencia'),
        ],
      ),
      'negotiation' => $this->buildAction(
        $this->t('Preparar contrato + onboarding plan'),
        'high',
        [
          $this->t('Definir condiciones finales y descuento si aplica'),
          $this->t('Preparar plan de onboarding con hitos a 30/60/90 días'),
          $this->t('Coordinar firma digital y facturación'),
        ],
      ),
      'closed_won' => $this->buildAction(
        $this->t('Iniciar onboarding y programar kickoff'),
        'medium',
        [
          $this->t('Enviar email de bienvenida personalizado'),
          $this->t('Asignar customer success manager'),
          $this->t('Programar revisión a los 30 días'),
        ],
      ),
      'closed_lost' => $this->buildAction(
        $this->t('Registrar motivos de pérdida y programar re-engagement'),
        'low',
        [
          $this->t('Documentar razones de pérdida para análisis'),
          $this->t('Programar re-engagement a los 6 meses'),
          $this->t('Agregar a nurturing de largo plazo'),
        ],
      ),
      default => $this->buildAction(
        $this->t('Revisar oportunidad y actualizar etapa'),
        'low',
        [],
      ),
    };

    return $recommendation + [
      'bant_score' => $bant,
      'stage' => $stage,
      'bant_details' => $this->getBantDetails($opportunity),
    ];
  }

  /**
   * Construye un array de accion recomendada.
   */
  protected function buildAction(string|\Stringable $action, string $priority, array $details): array {
    return [
      'action' => (string) $action,
      'priority' => $priority,
      'details' => array_map('strval', $details),
    ];
  }

  /**
   * Obtiene el detalle de cada criterio BANT.
   */
  protected function getBantDetails(Opportunity $opportunity): array {
    $fields = [
      'budget' => 'bant_budget',
      'authority' => 'bant_authority',
      'need' => 'bant_need',
      'timeline' => 'bant_timeline',
    ];

    $details = [];
    foreach ($fields as $key => $fieldName) {
      $value = $opportunity->hasField($fieldName)
        ? ($opportunity->get($fieldName)->value ?? 'none')
        : 'none';

      $details[$key] = [
        'value' => $value,
        'qualified' => $this->isQualified($fieldName, $value),
      ];
    }

    return $details;
  }

  /**
   * Determina si un criterio BANT esta en nivel maximo.
   */
  protected function isQualified(string $field, string $value): bool {
    $maxValues = [
      'bant_budget' => 'approved',
      'bant_authority' => 'champion',
      'bant_need' => 'critical',
      'bant_timeline' => 'immediate',
    ];
    return ($maxValues[$field] ?? '') === $value;
  }

}
