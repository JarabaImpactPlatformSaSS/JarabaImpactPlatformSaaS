# Aprendizajes: Zero-Region Template Pattern — Error 500 /tenant/export Resuelto

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-16 |

---

## Patron Principal

La pagina /tenant/export devolvio error 500 con "tenant" is an invalid render array key. La causa raiz fue el patron zero-region: el controller pasaba entity objects como non-# keys en el render array, y el template no renderiza {{ page.content }} por lo que #attached del controller nunca se procesa. La solucion requirio 3 cambios: (1) simplificar controller a minimal #markup, (2) hook_preprocess_page() para inyectar variables + drupalSettings, (3) hook_theme_suggestions_page_alter() para activar el template. Este patron ya existia en jaraba_verifactu y jaraba_facturae pero no estaba formalizado como directriz.

---

## Aprendizajes Clave

### 1. Zero-region templates NO procesan render arrays del controller

**Situacion:** TenantExportPageController retornaba ['tenant' => $entity, 'exports' => [...], '#attached' => [...]], causando InvalidArgumentException porque Drupal trata keys sin # como child render elements.

**Aprendizaje:** En templates zero-region (page--*.html.twig que no incluyen {{ page.content }}), el render array del controller NUNCA llega al template. Drupal intenta renderizar el array como contenido de pagina, y cualquier key sin # prefix que no sea un render array valido genera excepcion. La solucion es: controller retorna SOLO ['#type' => 'markup', '#markup' => ''], y todas las variables se inyectan en hook_preprocess_page().

**Regla:** ZERO-REGION-001: En paginas zero-region, TODAS las variables de template y drupalSettings DEBEN inyectarse via hook_preprocess_page(), NUNCA via render array del controller.

### 2. drupalSettings requiere attachment en preprocess, no en controller

**Situacion:** Tras mover las variables a hook_preprocess_page(), los drupalSettings seguian sin aparecer en el HTML de la pagina.

**Aprendizaje:** Cuando un template zero-region no renderiza {{ page.content }}, el pipeline de renderizado de Drupal no procesa los #attached del controller. Para inyectar drupalSettings, se debe usar $variables['#attached']['drupalSettings'] dentro de hook_preprocess_page(). Este es el mismo patron usado por jaraba_verifactu_preprocess_page().

**Regla:** ZERO-REGION-003: En templates zero-region, drupalSettings DEBEN adjuntarse via $variables['#attached']['drupalSettings'] en hook_preprocess_page(), no via #attached del controller.

### 3. hook_theme_suggestions_page_alter() es obligatorio para activar templates zero-region

**Situacion:** Definir hook_theme() con 'base hook' => 'page' no era suficiente para que Drupal usara el template page--tenant-export.html.twig.

**Aprendizaje:** El patron completo zero-region requiere 3 hooks en .module: (1) hook_theme() para registrar el template y sus variables, (2) hook_theme_suggestions_page_alter() para añadir la suggestion en la ruta correcta, (3) hook_preprocess_page() para inyectar variables y drupalSettings.

**Regla:** ZERO-REGION-002: Toda pagina zero-region DEBE implementar los 3 hooks: hook_theme(), hook_theme_suggestions_page_alter(), hook_preprocess_page(). No usar entity objects como non-# keys.

---

## Metricas de la Sesion

- Error resuelto: 500 → 200 en /tenant/export
- Patron formalizado: 3 reglas ZERO-REGION-001/002/003
- Modulos que usan el patron: jaraba_verifactu, jaraba_facturae, jaraba_funding, jaraba_tenant_export, jaraba_privacy, jaraba_legal, jaraba_dr (7+)
- Referencias: jaraba_verifactu.module (patron canonico), TenantExportPageController.php (fix aplicado)
