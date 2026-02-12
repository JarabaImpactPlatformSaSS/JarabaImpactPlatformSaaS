# Bloque H: Sistema de Agentes IA Multi-Vertical - Documento de Implementación

**Fecha de creación:** 2026-01-28 17:00  
**Última actualización:** 2026-01-28 17:00  
**Autor:** IA Asistente (Claude)  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos

1. [Matriz de Especificaciones](#1-matriz-de-especificaciones)
2. [Checklist Multidisciplinar](#2-checklist-multidisciplinar)
3. [H.1 Módulo Base](#3-h1-módulo-base)
4. [H.2 BaseAgent Multi-Tenant](#4-h2-baseagent-multi-tenant)
5. [H.3 Agentes Generalizados](#5-h3-agentes-generalizados)
6. [H.4 APIs REST](#6-h4-apis-rest)
7. [H.5 UI Hub Agentes](#7-h5-ui-hub-agentes)
8. [Checklist Directrices](#8-checklist-directrices)
9. [Registro de Cambios](#9-registro-de-cambios)

---

## 1. Matriz de Especificaciones

### 1.1 Documentos de Referencia

| Doc | Archivo | Contenido |
|-----|---------|-----------|
| Auditoría | [20260128-Auditoria_Arquitectura_IA_SaaS_v1_Claude.md](20260128-Auditoria_Arquitectura_IA_SaaS_v1_Claude.md) | Análisis arquitectura IA |
| Workflow | [/ai-integration.md](../../.agent/workflows/ai-integration.md) | Directrices integración IA |
| Bloque G | [20260123g-Bloque_G_AI_Skills_Implementacion_Claude.md](20260123g-Bloque_G_AI_Skills_Implementacion_Claude.md) | Skills System |

### 1.2 Stack Tecnológico

| Componente | Tecnología | Justificación |
|------------|------------|---------------|
| AI Provider | Drupal AI Module (`@ai.provider`) | Estándar proyecto |
| Registro | ConfigEntity `AIAgent` | Zero-code admin |
| Multi-tenant | Group Module + TenantContext | Aislamiento |
| Frontend | JS + Design Tokens | Consistencia UX |

### 1.3 Origen Componentes (Reuso)

| Componente | Origen | Adaptación |
|------------|--------|------------|
| BaseAgent | AgroConecta | +TenantContext |
| MarketingAgent | AgroConecta | Generalizar prompts |
| StorytellingAgent | AgroConecta | Generalizar |
| AgentInterface | AgroConecta | Sin cambios |

---

## 2. Checklist Multidisciplinar

### 2.1 Arquitecto SaaS

| Verificación | Estado |
|--------------|--------|
| ¿Multi-tenancy implementado? | [ ] |
| ¿Servicios usan `@ai.provider`? | [ ] |
| ¿ConfigEntity `AIAgent` registrado? | [ ] |

### 2.2 Ingeniero Software

| Verificación | Estado |
|--------------|--------|
| ¿PHPUnit para BaseAgent? | [ ] |
| ¿Jest para UI Hub? | [ ] |
| ¿Handlers entities correctos? | [ ] |

### 2.3 Ingeniero IA

| Verificación | Estado |
|--------------|--------|
| ¿Prompts parametrizados por vertical? | [ ] |
| ¿Brand Voice por tenant? | [ ] |
| ¿Logging de generaciones? | [ ] |

---

## 3. H.1 Módulo Base

### 3.1 Estructura (Sprint H1: 8h)

```bash
web/modules/custom/jaraba_ai_agents/
├── jaraba_ai_agents.info.yml
├── jaraba_ai_agents.services.yml
├── jaraba_ai_agents.module
├── jaraba_ai_agents.permissions.yml
├── src/
│   ├── Agent/
│   │   ├── AgentInterface.php
│   │   └── BaseAgent.php
│   ├── Service/
│   │   ├── AgentOrchestrator.php
│   │   └── TenantBrandVoiceService.php
│   └── Controller/
│       └── AgentApiController.php
└── config/install/
    └── jaraba_ai_agents.settings.yml
```

### 3.2 jaraba_ai_agents.info.yml

```yaml
name: 'Jaraba AI Agents'
type: module
description: 'Sistema de agentes IA orientados a acciones para verticales SaaS.'
package: Jaraba
core_version_requirement: ^10 || ^11
dependencies:
  - drupal:ai
  - ecosistema_jaraba_core:ecosistema_jaraba_core
  - group:group
```

### 3.3 jaraba_ai_agents.services.yml

```yaml
services:
  jaraba_ai_agents.orchestrator:
    class: Drupal\jaraba_ai_agents\Service\AgentOrchestrator
    arguments:
      - '@ai.provider'
      - '@config.factory'
      - '@logger.factory'
      - '@jaraba_ai_agents.tenant_brand_voice'

  jaraba_ai_agents.tenant_brand_voice:
    class: Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService
    arguments:
      - '@config.factory'
      - '@group.membership_loader'

  jaraba_ai_agents.marketing_agent:
    class: Drupal\jaraba_ai_agents\Agent\MarketingAgent
    arguments:
      - '@ai.provider'
      - '@config.factory'
      - '@logger.factory'
      - '@jaraba_ai_agents.tenant_brand_voice'
```

---

## 4. H.2 BaseAgent Multi-Tenant

### 4.1 AgentInterface.php (Sin cambios)

```php
<?php

namespace Drupal\jaraba_ai_agents\Agent;

interface AgentInterface {
    public function execute(string $action, array $context): array;
    public function getAvailableActions(): array;
    public function getAgentId(): string;
}
```

### 4.2 BaseAgent.php (Sprint H2: 16h)

```php
<?php

namespace Drupal\jaraba_ai_agents\Agent;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jaraba_ai_agents\Service\TenantBrandVoiceService;
use Psr\Log\LoggerInterface;

abstract class BaseAgent implements AgentInterface {

    protected AiProviderPluginManager $aiProvider;
    protected ConfigFactoryInterface $configFactory;
    protected LoggerInterface $logger;
    protected TenantBrandVoiceService $brandVoice;
    protected ?string $tenantId = NULL;
    protected ?string $vertical = NULL;

    public function __construct(
        AiProviderPluginManager $aiProvider,
        ConfigFactoryInterface $configFactory,
        LoggerInterface $logger,
        TenantBrandVoiceService $brandVoice
    ) {
        $this->aiProvider = $aiProvider;
        $this->configFactory = $configFactory;
        $this->logger = $logger;
        $this->brandVoice = $brandVoice;
    }

    public function setTenantContext(string $tenantId, string $vertical): void {
        $this->tenantId = $tenantId;
        $this->vertical = $vertical;
    }

    protected function getBrandVoicePrompt(): string {
        if (!$this->tenantId) {
            return $this->getDefaultBrandVoice();
        }
        return $this->brandVoice->getPromptForTenant($this->tenantId);
    }

    protected function callAiApi(string $prompt, array $options = []): array {
        try {
            $defaults = $this->aiProvider->getDefaultProviderForOperationType('chat');
            if (empty($defaults)) {
                return ['success' => FALSE, 'error' => 'No hay proveedor IA configurado.'];
            }

            $provider = $this->aiProvider->createInstance($defaults['provider_id']);
            $chatInput = new ChatInput([
                new ChatMessage('system', $this->getBrandVoicePrompt()),
                new ChatMessage('user', $prompt),
            ]);

            $response = $provider->chat($chatInput, $defaults['model_id'], [
                'temperature' => $options['temperature'] ?? 0.7,
            ]);

            return [
                'success' => TRUE,
                'data' => ['text' => $response->getNormalized()->getText()],
                'tenant_id' => $this->tenantId,
                'vertical' => $this->vertical,
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error agente IA: @msg', ['@msg' => $e->getMessage()]);
            return ['success' => FALSE, 'error' => $e->getMessage()];
        }
    }

    protected function cleanJsonString(string $text): string {
        $text = preg_replace('/```(?:json)?\s*/is', '', $text);
        $text = preg_replace('/\s*```/is', '', $text);
        if (preg_match('/(\{[\s\S]*\})/m', $text, $matches)) {
            return trim($matches[1]);
        }
        return trim($text);
    }

    abstract protected function getDefaultBrandVoice(): string;
}
```

---

## 5. H.3 Agentes Generalizados

### 5.1 MarketingAgent (Sprint H3: 12h)

```php
<?php

namespace Drupal\jaraba_ai_agents\Agent;

class MarketingAgent extends BaseAgent {

    public function getAgentId(): string {
        return 'marketing_multi';
    }

    public function getAvailableActions(): array {
        return [
            'social_post' => [
                'label' => 'Crear Post Redes Sociales',
                'requires' => ['product_name', 'platform', 'objective'],
            ],
            'email_promo' => [
                'label' => 'Email Marketing',
                'requires' => ['product_name', 'objective', 'offer_details'],
            ],
            'ad_copy' => [
                'label' => 'Copy para Anuncios',
                'requires' => ['product_name', 'platform', 'audience'],
            ],
        ];
    }

    public function execute(string $action, array $context): array {
        return match ($action) {
            'social_post' => $this->generateSocialPost($context),
            'email_promo' => $this->generateEmailPromo($context),
            'ad_copy' => $this->generateAdCopy($context),
            default => ['success' => FALSE, 'error' => "Acción no soportada: $action"],
        };
    }

    private function generateSocialPost(array $context): array {
        $product = $context['product_name'] ?? 'producto';
        $platform = $context['platform'] ?? 'Instagram';
        $objective = $context['objective'] ?? 'Engagement';

        $prompt = "VERTICAL: {$this->vertical}\n";
        $prompt .= "TAREA: Crea post para {$platform}.\n";
        $prompt .= "PRODUCTO: {$product}\n";
        $prompt .= "OBJETIVO: {$objective}\n\n";
        $prompt .= "FORMATO JSON:\n";
        $prompt .= '{"content": "...", "hashtags": "...", "cta": "...", "visual_suggestion": "..."}';

        $response = $this->callAiApi($prompt);
        if ($response['success']) {
            $response['data'] = json_decode($this->cleanJsonString($response['data']['text']), TRUE) ?? $response['data'];
            $response['data']['content_type'] = 'social_post';
        }
        return $response;
    }

    // ... email_promo y ad_copy similares

    protected function getDefaultBrandVoice(): string {
        return "Eres un experto en marketing digital. Tono profesional pero cercano.";
    }
}
```

### 5.2 ConfigEntity Registration

```yaml
# ecosistema_jaraba_core.ai_agent.marketing_multi.yml
id: marketing_multi
label: 'Marketing Agent (Multi-Vertical)'
description: 'Genera contenido de marketing para todos los verticales.'
service_id: jaraba_ai_agents.marketing_agent
icon: bullhorn
color: '#e91e63'
autonomy_level: 1
requires_approval: true
allowed_actions: '["social_post", "email_promo", "ad_copy"]'
```

---

## 6. H.4 APIs REST

### 6.1 Endpoints (Sprint H4: 8h)

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/v1/agents` | Listar agentes |
| GET | `/api/v1/agents/{id}/actions` | Acciones disponibles |
| POST | `/api/v1/agents/{id}/execute` | Ejecutar acción |

### 6.2 AgentApiController.php

```php
<?php

namespace Drupal\jaraba_ai_agents\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AgentApiController extends ControllerBase {

    public function execute(string $agent_id, Request $request): JsonResponse {
        $data = json_decode($request->getContent(), TRUE);
        $action = $data['action'] ?? '';
        $context = $data['context'] ?? [];
        $tenantId = $request->headers->get('X-Tenant-ID');

        $agent = $this->container->get("jaraba_ai_agents.{$agent_id}_agent");
        $agent->setTenantContext($tenantId, $context['vertical'] ?? 'general');

        $result = $agent->execute($action, $context);
        return new JsonResponse($result);
    }
}
```

---

## 7. H.5 UI Hub Agentes

### 7.1 Migración UI (Sprint H5: 8h)

| Componente | Origen | Acción |
|------------|--------|--------|
| `agent-hub.js` | AgroConecta | Adaptar API endpoints |
| `agent-hub.scss` | AgroConecta | Migrar a Design Tokens |
| Dashboard | AgroConecta | Integrar Admin Center |

---

## 8. Checklist Directrices

### 8.1 Pre-Commit

| Verificación | Estado |
|--------------|--------|
| ¿Usa `@ai.provider`? | [ ] |
| ¿Textos traducibles (`$this->t()`)? | [ ] |
| ¿SCSS con `var(--ej-*)`? | [ ] |
| ¿PHPUnit tests? | [ ] |

---

## 9. Resumen de Inversión

| Sprint | Horas | Entregable |
|--------|-------|------------|
| H1 Módulo Base | 8h | Estructura, services |
| H2 BaseAgent | 16h | Multi-tenant, Brand Voice |
| H3 Agentes | 12h | Marketing, Storytelling |
| H4 APIs | 8h | REST endpoints |
| H5 UI | 8h | Hub migrado |
| **TOTAL** | **52h** | **Sistema completo** |

---

## 10. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-28 | 1.0.0 | Creación inicial - Bloque H |

---

**Jaraba Impact Platform | Bloque H: AI Agents Multi-Vertical | Enero 2026**
