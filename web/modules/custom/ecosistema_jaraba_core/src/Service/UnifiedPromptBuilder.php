<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_skills\Service\SkillManager;
use Drupal\jaraba_tenant_knowledge\Service\TenantKnowledgeManager;
use Drupal\jaraba_tenant_knowledge\Service\KnowledgeIndexerService;
use Psr\Log\LoggerInterface;

/**
 * SERVICIO CONSTRUCTOR DE PROMPT UNIFICADO
 *
 * PROPÓSITO:
 * Combina el conocimiento del tenant con las habilidades IA en un
 * prompt XML estructurado para el sistema de agentes.
 *
 * ESTRUCTURA:
 * El prompt unificado sigue esta estructura XML:
 * <jaraba_context>
 *   <skills>...</skills>           <- Del SkillManager
 *   <business_context>...</business_context>  <- Del TenantKnowledgeManager
 *   <corrections>...</corrections> <- Correcciones aplicadas
 *   <relevant_knowledge>...</relevant_knowledge> <- RAG results
 * </jaraba_context>
 *
 * LÓGICA DE NEGOCIO:
 * - Skills definen CÓMO actuar (procedural)
 * - Knowledge define QUÉ sabe el negocio (factual)
 * - Corrections previenen errores repetidos
 * - Relevant Knowledge es contexto RAG per-query
 *
 * @see SkillManager Para la resolución jerárquica de skills
 * @see TenantKnowledgeManager Para el contexto del negocio
 */
class UnifiedPromptBuilder
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected SkillManager $skillManager,
        protected TenantKnowledgeManager $knowledgeManager,
        protected KnowledgeIndexerService $knowledgeIndexer,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Construye el prompt completo para un agente IA.
     *
     * PROPÓSITO:
     * Genera el system prompt que contextualiza al agente con todo
     * el conocimiento disponible para el tenant actual.
     *
     * @param array $context
     *   Contexto de ejecución:
     *   - 'vertical': ID de vertical (empleabilidad, emprendimiento, etc.)
     *   - 'agent_type': Tipo de agente (copilot, assistant, etc.)
     *   - 'tenant_id': ID del tenant (se obtiene automáticamente si NULL)
     * @param string|null $query
     *   Query del usuario para búsqueda RAG (opcional).
     *
     * @return string
     *   Prompt XML estructurado listo para inyección.
     */
    public function buildPrompt(array $context = [], ?string $query = NULL): string
    {
        $output = "<jaraba_context>\n";

        // 1. Skills (CÓMO actuar).
        $skillsSection = $this->skillManager->generatePromptSection($context);
        if (!empty($skillsSection)) {
            $output .= $skillsSection . "\n";
        }

        // 2. Business Context (QUÉ sabe el negocio).
        $businessContext = $this->knowledgeManager->generatePromptContext();
        if (!empty($businessContext)) {
            $output .= $businessContext . "\n";
        }

        // 3. Correcciones aplicadas (prevención de errores).
        $correctionsSection = $this->getActiveCorrections($context['tenant_id'] ?? NULL);
        if (!empty($correctionsSection)) {
            $output .= $correctionsSection . "\n";
        }

        // 4. Conocimiento relevante (RAG per-query).
        if (!empty($query)) {
            $ragSection = $this->getRelevantKnowledge($query, $context['tenant_id'] ?? NULL);
            if (!empty($ragSection)) {
                $output .= $ragSection . "\n";
            }
        }

        $output .= "</jaraba_context>";

        $this->logger->debug('Built unified prompt with context: @context', [
            '@context' => json_encode($context),
        ]);

        return $output;
    }

    /**
     * Obtiene las correcciones de IA activas para prevenir errores.
     *
     * @param int|null $tenantId
     *   ID del tenant (usa actual si NULL).
     *
     * @return string
     *   Sección XML de correcciones.
     */
    protected function getActiveCorrections(?int $tenantId): string
    {
        if (!$tenantId) {
            $tenantId = $this->getCurrentTenantId();
        }

        if (!$tenantId) {
            return '';
        }

        try {
            $storage = $this->entityTypeManager->getStorage('tenant_ai_correction');
            $corrections = $storage->loadByProperties([
                'tenant_id' => $tenantId,
                'status' => 'applied',
            ]);

            if (empty($corrections)) {
                return '';
            }

            $output = "<corrections>\n";
            $output .= "<!-- Las siguientes reglas DEBEN aplicarse para evitar errores repetidos -->\n";

            foreach ($corrections as $correction) {
                /** @var \Drupal\jaraba_tenant_knowledge\Entity\TenantAiCorrection $correction */
                $rule = $correction->get('generated_rule')->value;
                if (!empty($rule)) {
                    $type = $correction->get('correction_type')->value;
                    $output .= "<rule type=\"{$type}\">{$rule}</rule>\n";
                }
            }

            $output .= "</corrections>";

            return $output;
        } catch (\Exception $e) {
            $this->logger->warning('Error loading corrections: @error', [
                '@error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Obtiene conocimiento relevante mediante búsqueda RAG.
     *
     * @param string $query
     *   Query del usuario.
     * @param int|null $tenantId
     *   ID del tenant.
     *
     * @return string
     *   Sección XML con conocimiento relevante.
     */
    protected function getRelevantKnowledge(string $query, ?int $tenantId): string
    {
        if (!$tenantId) {
            $tenantId = $this->getCurrentTenantId();
        }

        if (!$tenantId) {
            return '';
        }

        try {
            $results = $this->knowledgeIndexer->searchKnowledge($query, $tenantId, [
                'limit' => 5,
                'threshold' => 0.65,
            ]);

            if (empty($results)) {
                return '';
            }

            $output = "<relevant_knowledge>\n";
            $output .= "<!-- Información relevante encontrada en la base de conocimiento -->\n";

            foreach ($results as $result) {
                $type = $result['type'] ?? 'unknown';
                $content = $result['content'] ?? '';
                $score = round($result['score'] ?? 0, 2);

                $output .= "<knowledge type=\"{$type}\" score=\"{$score}\">\n";
                $output .= trim($content) . "\n";
                $output .= "</knowledge>\n";
            }

            $output .= "</relevant_knowledge>";

            return $output;
        } catch (\Exception $e) {
            $this->logger->warning('Error searching knowledge: @error', [
                '@error' => $e->getMessage(),
            ]);
            return '';
        }
    }

    /**
     * Genera un prompt específico para el Copiloto del tenant.
     *
     * PROPÓSITO:
     * Shortcut para el caso más común: el copiloto del tenant
     * respondiendo a una pregunta del usuario.
     *
     * @param string $userMessage
     *   Mensaje del usuario.
     * @param int|null $tenantId
     *   ID del tenant (usa actual si NULL).
     *
     * @return string
     *   Prompt completo para el copiloto.
     */
    public function buildCopilotPrompt(string $userMessage, ?int $tenantId = NULL): string
    {
        $context = [
            'agent_type' => 'copilot',
            'tenant_id' => $tenantId ?? $this->getCurrentTenantId(),
        ];

        return $this->buildPrompt($context, $userMessage);
    }

    /**
     * Obtiene el ID del tenant actual.
     */
    protected function getCurrentTenantId(): ?int
    {
        if (\Drupal::hasService('jaraba_multitenancy.tenant_context')) {
            $tenantContext = \Drupal::service('jaraba_multitenancy.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }
        return NULL;
    }

}
