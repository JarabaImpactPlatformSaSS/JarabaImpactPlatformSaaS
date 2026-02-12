# Aprendizajes: AI Smart Router y RAG Semántico

**Fecha:** 2026-01-21
**Módulo:** jaraba_copilot_v2
**Autor:** IA Asistente

---

## Resumen

Esta sesión implementó un sistema de router inteligente multi-proveedor para el Copiloto del vertical Emprendimiento, junto con mejoras significativas en el sistema RAG y monitorización de costes IA.

---

## 1. ModeDetectorService - Router Inteligente

### Problema Resuelto
El modo del Copiloto se seleccionaba manualmente por el usuario, lo cual no era óptimo para la experiencia del emprendedor.

### Implementación
```php
// Archivo: jaraba_copilot_v2/src/Service/ModeDetectorService.php

// Sistema de scoring con +100 triggers ponderados
private const MODE_TRIGGERS = [
  'coach' => [
    'motivación' => 2.5,
    'ayuda' => 1.5,
    // ...
  ],
  // 7 modos en total
];

// Modificadores por "carril" del emprendedor
private const CONTEXT_MODIFIERS = [
  'IMPULSO' => ['coach' => 1.5, 'sparring' => 0.7],
  'LANZADERA' => ['sparring' => 1.3, 'cfo' => 1.2],
  'ACELERA' => ['cfo' => 1.5, 'fiscal' => 1.3],
];
```

### Lección Aprendida
> **El análisis emocional del mensaje es clave para potenciar el modo "coach"**. Palabras como "difícil", "frustrado", "no consigo" deben aumentar la probabilidad de respuesta empática.

---

## 2. NormativeRAGService - RAG Semántico

### Problema Resuelto
El `NormativeKnowledgeService` original usaba búsqueda por palabras clave (SQL LIKE), perdiendo contexto semántico.

### Implementación
```php
// Archivo: jaraba_copilot_v2/src/Service/NormativeRAGService.php

// Patrón correcto para obtener embeddings
public function getEmbedding(string $text): array {
  $provider = $this->aiProviderManager
    ->getDefaultProviderForOperationType('embeddings');
  $result = $provider->embeddings($text, 'text-embedding-3-small');
  return $result->getNormalized();
}
```

### Lección Aprendida
> **Siempre usar el patrón `getDefaultProviderForOperationType()` + `getNormalized()`** del módulo `ai` de Drupal. No intentar acceder directamente a la API del proveedor.

### Datos de Indexación
- **Colección Qdrant:** `normative_knowledge`
- **Documentos indexados:** 33
- **Dimensión vectorial:** 1536 (OpenAI text-embedding-3-small)

---

## 3. Multi-Proveedor con Google Gemini

### Problema Resuelto
El sistema solo tenía dos proveedores de IA (Anthropic, OpenAI), lo cual limitaba la resiliencia ante fallos de API.

### Implementación
```php
// Archivo: jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php

private const MODE_PROVIDERS = [
  'coach' => ['anthropic', 'openai', 'google_gemini'],
  'cfo' => ['openai', 'anthropic', 'google_gemini'],
  // ... todos los modos con 3 proveedores
];

// Mapeo automático de modelos para Gemini
private function getGeminiModelForContext(string $originalModel): string {
  $mapping = [
    'gpt-4o' => 'gemini-2.5-pro',
    'claude-sonnet-4-20250514' => 'gemini-2.5-flash',
    // ...
  ];
  return $mapping[$originalModel] ?? 'gemini-2.5-flash';
}
```

### Lección Aprendida
> **El failover debe incluir mapeo de modelos** para que el modelo Gemini seleccionado sea equivalente en capacidad al modelo original que falló.

---

## 4. AI Cost Tracking en FinOps Dashboard

### Problema Resuelto
No había visibilidad de los costes de IA en el centro de operaciones financieras.

### Implementación
```php
// Archivo: CopilotOrchestratorService.php

private function trackAiUsage(string $provider, string $model, array $usage): void {
  $state = \Drupal::state();
  $key = 'ai_usage_' . date('Y-m');
  $current = $state->get($key, []);
  
  $current[$provider]['tokens_in'] += $usage['input_tokens'] ?? 0;
  $current[$provider]['tokens_out'] += $usage['output_tokens'] ?? 0;
  $current[$provider]['cost'] += $this->calculateCost($model, $usage);
  $current[$provider]['calls']++;
  
  $state->set($key, $current);
}
```

### Lección Aprendida
> **Usar State API de Drupal para métricas de alto volumen** es eficiente porque evita consultas SQL frecuentes. Los datos se agregan en memoria y se persisten periódicamente.

---

## 5. Checklist de Integración

Para futuras integraciones de proveedores IA:

- [ ] Crear módulo `ai_provider_{name}` siguiendo el patrón de `ai_provider_google_gemini`
- [ ] Registrar plugin con anotación `@AiProvider`
- [ ] Implementar `chat()` y `embeddings()` como mínimo
- [ ] Añadir proveedor al array `MODE_PROVIDERS`
- [ ] Crear mapeo de modelos en `getXxxModelForContext()`
- [ ] Actualizar `calculateCost()` con precios del nuevo proveedor
- [ ] Indexar documentos en Qdrant si hay nueva colección

---

## Referencias

- [ModeDetectorService.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_copilot_v2/src/Service/ModeDetectorService.php)
- [NormativeRAGService.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_copilot_v2/src/Service/NormativeRAGService.php)
- [CopilotOrchestratorService.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/jaraba_copilot_v2/src/Service/CopilotOrchestratorService.php)
- [GoogleGeminiProvider.php](file:///z:/home/PED/JarabaImpactPlatformSaaS/web/modules/custom/ai_provider_google_gemini/src/Plugin/AiProvider/GoogleGeminiProvider.php)
- [KI: Jaraba SaaS AI Agent Architecture](file:///C:/Users/Pepe%20Jaraba/.gemini/antigravity/knowledge/jaraba_saas_ai_agent_architecture/artifacts/overview.md)
