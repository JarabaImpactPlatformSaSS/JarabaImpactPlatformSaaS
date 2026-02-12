# Agentic Workflows & Marketing AI Stack - Implementación

**Fecha:** 2026-02-06  
**Módulos:** jaraba_ai_agents, jaraba_social, jaraba_crm, jaraba_email

## 1. Agentic Workflows Framework

### Patrón CoreEngine
```php
// Tool Registry dinámico con auto-registro
services:
  jaraba_ai_agents.tool_registry:
    class: Drupal\jaraba_ai_agents\Service\ToolRegistryService
    arguments:
      - '@entity_type.manager'
      - '@class_resolver'
```

### Entidades Implementadas
| Entidad | Propósito |
|---------|-----------|
| `AgentTool` | Wrapper de Tool Use para LLMs |
| `AiAgentExecution` | Tracking de ejecuciones de agentes |
| `AiToolExecution` | Log de cada tool invocada |

### Lección: Tool Use Nativo
- Usar formato JSON Schema nativo del LLM (function_call para OpenAI, tool_use para Anthropic)
- NO inventar formatos propios
- AgentOrchestrator traduce automáticamente entre proveedores

## 2. AI Social Manager

### Patrón Multi-Plataforma
```php
// SocialPostService con enum de plataformas
protected function getContentForPlatform(string $platform, string $content): string {
    return match ($platform) {
        'twitter' => $this->truncate($content, 280),
        'linkedin' => $content, // Sin límite práctico
        'instagram' => $this->addHashtags($content),
        default => $content,
    };
}
```

### Lección: Scheduling con Colas
- Usar Queue API de Drupal para scheduling
- Cron procesa la cola cada minuto
- Retry automático con backoff exponencial

## 3. CRM Pipeline Kanban

### Patrón: Herencia de ControllerBase
```php
// ❌ Incorrecto - conflicto con propiedad de padre
public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
) {}

// ✅ Correcto - usar método heredado
public function kanban(): array {
    $storage = $this->entityTypeManager()->getStorage('crm_opportunity');
}
```

### Lección: PHP 8.1 Promoted Properties
- ControllerBase ya tiene $entityTypeManager como propiedad
- Las propiedades promoted en constructor hijo causan conflicto de tipos
- Usar los métodos de acceso heredados en su lugar

## 4. Email AI Service

### Patrón: Generación con AgentOrchestrator
```php
$result = $this->orchestrator->execute('marketing', 'generate_email_copy', [
    'topic' => $topic,
    'style' => 'newsletter',
]);
```

### Lección: Personalización Segura
- Usar whitelist de placeholders: `{{nombre}}`, `{{empresa}}`
- NO permitir evaluación de código en templates
- Sanitizar inputs antes de interpolar en prompts

## 5. Directrices Aplicadas

| Directriz | Cumplimiento |
|-----------|--------------|
| SCSS Variables Inyectables | ✅ `var(--ej-*)` |
| Dart Sass (npx) | ✅ Compilación en contenedor |
| Templates Twig Limpios | ✅ Sin regiones Drupal |
| hook_preprocess_html() | ✅ Body classes per-page |
| Textos t() | ✅ Traducibles |
| Field UI + /admin/content | ✅ Rutas estándar |
