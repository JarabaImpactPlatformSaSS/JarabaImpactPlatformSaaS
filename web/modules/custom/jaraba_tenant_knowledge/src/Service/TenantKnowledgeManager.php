<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\jaraba_rag\Client\QdrantDirectClient;
use Drupal\ai\AiProviderPluginManager;
use Drupal\jaraba_tenant_knowledge\Entity\TenantKnowledgeConfig;
use Psr\Log\LoggerInterface;

/**
 * SERVICIO MANAGER DE CONOCIMIENTO DEL TENANT
 *
 * PROPÓSITO:
 * Gestiona el acceso y creación de la configuración de conocimiento
 * del tenant actual. Proporciona métodos de conteo para estadísticas.
 *
 * ESTRUCTURA:
 * Actúa como facade para todas las operaciones de Knowledge Training,
 * incluyendo creación lazy de TenantKnowledgeConfig.
 *
 * MULTI-TENANCY:
 * Obtiene el tenant actual mediante jaraba_multitenancy.tenant_context.
 * Todas las operaciones están filtradas por tenant.
 */
class TenantKnowledgeManager
{

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected QdrantDirectClient $qdrantClient,
        protected AiProviderPluginManager $aiProvider,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Obtiene o crea la configuración de conocimiento del tenant actual.
     *
     * LÓGICA:
     * 1. Obtiene el tenant actual via TenantContextService
     * 2. Busca TenantKnowledgeConfig existente para ese tenant
     * 3. Si no existe, crea una nueva instancia vacía
     *
     * @return \Drupal\jaraba_tenant_knowledge\Entity\TenantKnowledgeConfig|null
     *   La configuración o NULL si no hay tenant activo.
     */
    public function getOrCreateConfig(): ?TenantKnowledgeConfig
    {
        $tenantId = $this->getCurrentTenantId();

        if (!$tenantId) {
            $this->logger->warning('No se pudo determinar el tenant actual.');
            return NULL;
        }

        $storage = $this->entityTypeManager->getStorage('tenant_knowledge_config');

        // Buscar configuración existente.
        $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);

        if (!empty($configs)) {
            return reset($configs);
        }

        // Crear nueva configuración vacía.
        $config = $storage->create([
            'tenant_id' => $tenantId,
            'business_name' => '',
        ]);
        $config->save();

        $this->logger->info('Configuración de conocimiento creada para tenant @id.', [
            '@id' => $tenantId,
        ]);

        return $config;
    }

    /**
     * Obtiene la configuración sin crear si no existe.
     *
     * @return \Drupal\jaraba_tenant_knowledge\Entity\TenantKnowledgeConfig|null
     *   La configuración existente o NULL.
     */
    public function getConfig(): ?TenantKnowledgeConfig
    {
        $tenantId = $this->getCurrentTenantId();

        if (!$tenantId) {
            return NULL;
        }

        $storage = $this->entityTypeManager->getStorage('tenant_knowledge_config');
        $configs = $storage->loadByProperties(['tenant_id' => $tenantId]);

        return !empty($configs) ? reset($configs) : NULL;
    }

    /**
     * Cuenta el número de FAQs del tenant actual.
     *
     * @return int
     *   Número de FAQs.
     */
    public function countFaqs(): int
    {
        return $this->countEntities('tenant_faq');
    }

    /**
     * Cuenta el número de políticas del tenant actual.
     *
     * @return int
     *   Número de políticas.
     */
    public function countPolicies(): int
    {
        return $this->countEntities('tenant_policy');
    }

    /**
     * Cuenta el número de documentos del tenant actual.
     *
     * @return int
     *   Número de documentos.
     */
    public function countDocuments(): int
    {
        return $this->countEntities('tenant_document');
    }

    /**
     * Genera el contexto de conocimiento para inyectar en el prompt.
     *
     * PROPÓSITO:
     * Combina toda la información del tenant en un texto estructurado
     * que se inyecta en el system prompt del agente IA.
     *
     * @return string
     *   Texto XML con el contexto del negocio.
     */
    public function generatePromptContext(): string
    {
        $config = $this->getConfig();

        if (!$config) {
            return '';
        }

        $output = "<business_context>\n";

        // Información básica.
        if ($name = $config->getBusinessName()) {
            $output .= "<business_name>{$name}</business_name>\n";
        }

        if ($desc = $config->get('business_description')->value) {
            $output .= "<business_description>{$desc}</business_description>\n";
        }

        if ($industry = $config->get('industry')->value) {
            $allowedValues = $config->getFieldDefinition('industry')
                ->getSetting('allowed_values');
            $industryLabel = $allowedValues[$industry] ?? $industry;
            $output .= "<industry>{$industryLabel}</industry>\n";
        }

        // Tono de comunicación.
        if ($tone = $config->get('communication_tone')->value) {
            $allowedValues = $config->getFieldDefinition('communication_tone')
                ->getSetting('allowed_values');
            $toneLabel = $allowedValues[$tone] ?? $tone;
            $output .= "<communication_tone>{$toneLabel}</communication_tone>\n";
        }

        if ($toneInst = $config->get('tone_instructions')->value) {
            $output .= "<tone_instructions>{$toneInst}</tone_instructions>\n";
        }

        // Información de contacto.
        if ($hours = $config->get('business_hours')->value) {
            $output .= "<business_hours>{$hours}</business_hours>\n";
        }

        if ($location = $config->get('location')->value) {
            $output .= "<location>{$location}</location>\n";
        }

        // Competidores a evitar.
        if ($competitors = $config->get('competitors_to_avoid')->value) {
            $output .= "<do_not_mention>{$competitors}</do_not_mention>\n";
        }

        $output .= "</business_context>";

        return $output;
    }

    /**
     * Cuenta entidades de un tipo para el tenant actual.
     *
     * @param string $entityType
     *   ID del tipo de entidad.
     *
     * @return int
     *   Número de entidades.
     */
    protected function countEntities(string $entityType): int
    {
        $tenantId = $this->getCurrentTenantId();

        if (!$tenantId) {
            return 0;
        }

        // Verificar si el tipo de entidad existe.
        if (!$this->entityTypeManager->hasDefinition($entityType)) {
            return 0;
        }

        try {
            $storage = $this->entityTypeManager->getStorage($entityType);
            $query = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('tenant_id', $tenantId)
                ->count();

            return (int) $query->execute();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtiene el ID del tenant actual.
     *
     * @return int|null
     *   ID del tenant o NULL.
     */
    protected function getCurrentTenantId(): ?int
    {
        if (\Drupal::hasService('jaraba_multitenancy.tenant_context')) {
            /** @var \Drupal\jaraba_multitenancy\Service\TenantContextService $tenantContext */
            $tenantContext = \Drupal::service('jaraba_multitenancy.tenant_context');
            $tenant = $tenantContext->getCurrentTenant();
            return $tenant ? (int) $tenant->id() : NULL;
        }

        return NULL;
    }

}
