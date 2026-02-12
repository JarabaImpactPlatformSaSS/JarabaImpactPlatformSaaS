# Aprendizaje: Módulo jaraba_i18n Multi-Entidad

**Fecha**: 2026-02-02  
**Área**: i18n, AI Agents, Arquitectura, Drupal Controllers

---

## 📋 Resumen

Implementación del módulo `jaraba_i18n` con 13 archivos. API REST verificada.
Lecciones críticas sobre herencia de ControllerBase en Drupal.

---

## 🎯 Decisión Arquitectónica

### Módulo Separado jaraba_i18n
- ✅ Soporta múltiples entidades (PageContent, BlogPost, Course, etc.)
- ✅ Patrón adaptador extensible
- ✅ Separación de responsabilidades
- ✅ Reusable cross-módulo

---

## 🐛 Errores Encontrados y Soluciones

### 1. AgentFactoryInterface no existe

**Error**: `Use of unknown class: AgentFactoryInterface`

**Solución**: Usar `AgentOrchestrator` directamente:
```yaml
jaraba_i18n.ai_translation:
  arguments:
    - '@jaraba_ai_agents.orchestrator'
    - '@jaraba_ai_agents.model_router'
```

### 2. getChangedTime() en ContentEntityInterface

**Error**: `Call to unknown method: ContentEntityInterface::getChangedTime()`

**Solución**: Verificar interfaz:
```php
if ($original instanceof EntityChangedInterface) {
  $originalChanged = $original->getChangedTime();
}
```

### 3. ⚠️ CRÍTICO: Redefinición de propiedades en ControllerBase

**Error**: 
```
Fatal error: Type of TranslationApiController::$entityTypeManager 
must not be defined (as in class ControllerBase)
```

**Causa**: `ControllerBase` ya define `$entityTypeManager` como propiedad.
No se puede redeclarar con `protected EntityTypeManagerInterface $entityTypeManager`.

**Solución**: Usar el método heredado `entityTypeManager()`:
```php
// ❌ INCORRECTO - No redefinir propiedades heredadas
public function __construct(
  protected EntityTypeManagerInterface $entityTypeManager, // ERROR
) {}

// ✅ CORRECTO - Usar factory pattern
public static function create(ContainerInterface $container): static {
  $instance = new static();
  $instance->translationManager = $container->get('...');
  return $instance;
}

// Luego usar el método heredado
public function getStatus(): JsonResponse {
  $entity = $this->entityTypeManager()  // Método, no propiedad
    ->getStorage($entity_type)
    ->load($entity_id);
}
```

**Regla**: En controllers que extienden `ControllerBase`, nunca redefinir:
- `$entityTypeManager`
- `$moduleHandler`
- `$currentUser`

Usar siempre los métodos: `entityTypeManager()`, `moduleHandler()`, `currentUser()`.

---

## 📁 Estructura Final

```
web/modules/custom/jaraba_i18n/
├── jaraba_i18n.info.yml
├── jaraba_i18n.services.yml
├── jaraba_i18n.routing.yml
├── jaraba_i18n.permissions.yml
├── jaraba_i18n.libraries.yml
├── jaraba_i18n.module
├── js/i18n-selector.js
├── scss/_i18n-selector.scss
├── src/Controller/ (2 archivos)
├── src/Service/ (2 archivos)
└── templates/i18n-selector.html.twig

Total: 13 archivos
```

---

## 📚 Referencias

- [Plan Gap E](../planificacion/20260202-Gap_E_i18n_UI_v1.md)
- [Plan Elevación Clase Mundial](../planificacion/20260129-Plan_Elevacion_Clase_Mundial_v1.md)
- [Drupal ControllerBase](https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Controller%21ControllerBase.php)

---

*Gap E del Plan de Elevación a Clase Mundial*
