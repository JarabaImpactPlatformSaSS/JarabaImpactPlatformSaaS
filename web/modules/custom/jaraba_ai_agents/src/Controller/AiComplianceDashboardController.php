<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_ai_agents\Service\AiAuditTrailService;
use Drupal\jaraba_ai_agents\Service\AiRiskClassificationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * EU AI Act compliance dashboard controller.
 *
 * Renders the compliance overview page with:
 * - Risk classification matrix for all registered agents.
 * - Audit trail statistics per tenant.
 * - Compliance status indicators.
 * - Links to risk assessments and audit entries.
 */
class AiComplianceDashboardController extends ControllerBase {

  public function __construct(
    protected readonly AiRiskClassificationService $riskClassification,
    protected readonly AiAuditTrailService $auditTrail,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_ai_agents.risk_classification'),
      $container->get('jaraba_ai_agents.audit_trail'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Renders the compliance dashboard.
   */
  public function dashboard(): array {
    $riskMatrix = $this->buildRiskMatrix();
    $riskAssessments = $this->loadRiskAssessments();

    return [
      '#theme' => 'ai_compliance_dashboard',
      '#risk_matrix' => $riskMatrix,
      '#risk_assessments' => $riskAssessments,
      '#high_risk_actions' => $this->riskClassification->getHighRiskActions(),
      '#limited_risk_actions' => $this->riskClassification->getLimitedRiskActions(),
      '#eu_ai_act_deadline' => '2026-08-02',
      '#attached' => [
        'library' => ['ecosistema_jaraba_theme/bundle-ai-compliance'],
      ],
    ];
  }

  /**
   * Builds the risk classification matrix for known agents.
   */
  protected function buildRiskMatrix(): array {
    $knownAgents = [
      'smart_marketing' => ['generate_copy', 'social_media_post', 'email_draft', 'seo_optimization'],
      'storytelling' => ['storytelling', 'blog_article', 'content_generation'],
      'customer_experience' => ['customer_support', 'chatbot', 'copilot_chat'],
      'support' => ['customer_support', 'chatbot'],
      'producer_copilot' => ['product_description', 'pricing_suggestion', 'invoice_generation'],
      'sales' => ['pricing_suggestion', 'product_description'],
      'merchant_copilot' => ['product_description', 'pricing_suggestion'],
      'legal_copilot' => ['legal_analysis', 'legal_search', 'case_assistant', 'contract_generation'],
    ];

    $matrix = [];
    foreach ($knownAgents as $agentId => $actions) {
      $classifications = $this->riskClassification->classifyAll($agentId, $actions);
      $highestRisk = $this->riskClassification->getHighestRisk($classifications);

      $matrix[$agentId] = [
        'agent_id' => $agentId,
        'actions' => $classifications,
        'highest_risk' => $highestRisk,
        'action_count' => count($actions),
        'high_risk_count' => count(array_filter($classifications, fn($c) => $c['risk_level'] === 'high')),
      ];
    }

    return $matrix;
  }

  /**
   * Loads all active risk assessments.
   */
  protected function loadRiskAssessments(): array {
    try {
      $storage = $this->entityTypeManager->getStorage('ai_risk_assessment');
      $entities = $storage->loadMultiple();

      $assessments = [];
      foreach ($entities as $entity) {
        $assessments[] = [
          'id' => $entity->id(),
          'label' => $entity->label(),
          'agent_id' => $entity->getAgentId(),
          'risk_level' => $entity->getRiskLevel(),
          'status' => $entity->getAssessmentStatus(),
          'is_high_risk' => $entity->isHighRisk(),
        ];
      }
      return $assessments;
    }
    catch (\Throwable $e) {
      return [];
    }
  }

}
