# Flujo de Trabajo del Asistente IA (Claude)

**Fecha de creacion:** 2026-02-18
**Version:** 10.0.0 (Page Builder Template Consistency Workflow)

---

## 3. Durante la Implementacion

...
- **Mocking PHPUnit 11:** 
  - Evitar `createMock(\stdClass::class)`. Usar interfaces explícitas.
  - Para clases `final`, inyectar como `object` en el constructor y usar `if (!interface_exists(...))` en los tests para definir interfaces de mock temporales.
  - Asegurar que los mocks de entidades implementen metadatos de caché (`getCacheContexts`, etc.) si se usan en AccessHandlers.
- **XML Robustness:** Usar XPath para aserciones en lugar de `str_contains`. Canonicalizar en documentos limpios antes de verificar firmas.
- **CI/CD Config:**
  - **Trivy:** Las claves `skip-dirs`/`skip-files` van SIEMPRE bajo el bloque `scan:` en trivy.yaml. Verificar en los logs que el conteo de archivos escaneados es coherente.
  - **Deploy:** Todo smoke test con dependencia de secrets de URL debe tener fallback SSH/Drush. Nunca `exit 1` sin intentar alternativas.
- **Page Builder Templates (Config Entities YAML):**
  - Todo YAML de PageTemplate DEBE incluir `preview_image` con ruta al PNG. Convención: `{id_con_guiones}.png`.
  - Los `preview_data` verticales DEBEN incluir arrays ricos (features[], testimonials[], faqs[], stats[]) con 3+ items del dominio específico, no placeholders genéricos.
  - Al editar templates masivamente, validar YAML con Python (`yaml.safe_load()`) ya que Symfony YAML no está disponible desde CLI sin autoloader.
  - Crear update hook para resyncronizar configs en la BD activa tras modificar YAMLs en `config/install/`.
- **Drupal 10+ Entity Updates:**
  - `applyUpdates()` fue eliminado. Usar `installFieldStorageDefinition()` / `updateFieldStorageDefinition()` explícitamente.
  - Verificar tipo de campo instalado con `getFieldStorageDefinition()` antes de intentar actualizarlo.

---

## 5. Reglas de Oro (Actualizadas)

1. **No hardcodear:** Configuracion via Config Entities o State API.
2. **Inmutabilidad Financiera:** Registros append-only y encadenados por hash.
3. **Detección Proactiva:** El sistema debe avisar (Push/Email) antes de que el usuario lo pida.
4. **Tenant isolation:** `tenant_id` obligatorio.
5. **Mocking Seguro:** No mockear Value Objects `final`, usarlos directamente.
6. **DI Flexible:** Si una dependencia es `final` en contrib, usar type hint `object` en core para permitir el testeo.
7. **Documentar siempre:** Toda sesion genera actualizacion documental.
8. **Privacidad Diferencial:** Toda inteligencia colectiva debe pasar por el motor de ruido de Laplace.
9. **Verificar CI tras cambios de config:** Tras modificar archivos de configuracion de herramientas (trivy.yaml, workflows), monitorear el pipeline completo hasta verde. Las herramientas pueden ignorar claves invalidas sin warning.
10. **Update hooks para config resync:** Tras modificar YAMLs en `config/install/`, crear un update hook que reimporte los configs en la BD activa. Los YAMLs de `config/install/` solo se procesan durante la instalacion del modulo.

---

## 9. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-18 | **10.0.0** | **Page Builder Template Consistency Workflow**: Patrones para edicion masiva de templates YAML, validacion, preview_data rico por vertical, update hooks para resync de configs, y Drupal 10+ entity update patterns. Regla de oro #10. |
| 2026-02-18 | 9.0.0 | **CI/CD Hardening Workflow**: Reglas para config Trivy (`scan.skip-dirs`), deploy resiliente con fallback SSH, y regla de oro #9 (verificar CI tras cambios de config). |
| 2026-02-18 | 8.0.0 | **Unified & Stabilized Workflow**: Incorporación de patrones de testing masivo, estabilización de 17 módulos y gestión de clases final con DI flexible. |
| 2026-02-18 | 7.0.0 | **Living SaaS Workflow**: Incorporación de mentalidad adaptativa (Liquid UI) e inteligencia colectiva privada (ZKP). |
| ... | ... | ... |
