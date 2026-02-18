# Flujo de Trabajo del Asistente IA (Claude)

**Fecha de creacion:** 2026-02-18
**Version:** 8.0.0 (Unified & Stabilized SaaS Workflow)

---

## 3. Durante la Implementacion

...
- **Mocking PHPUnit 11:** 
  - Evitar `createMock(\stdClass::class)`. Usar interfaces explícitas.
  - Para clases `final`, inyectar como `object` en el constructor y usar `if (!interface_exists(...))` en los tests para definir interfaces de mock temporales.
  - Asegurar que los mocks de entidades implementen metadatos de caché (`getCacheContexts`, etc.) si se usan en AccessHandlers.
- **XML Robustness:** Usar XPath para aserciones en lugar de `str_contains`. Canonicalizar en documentos limpios antes de verificar firmas.

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

---

## 9. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-18 | **8.0.0** | **Unified & Stabilized Workflow**: Incorporación de patrones de testing masivo, estabilización de 17 módulos y gestión de clases final con DI flexible. |
| 2026-02-18 | 7.0.0 | **Living SaaS Workflow**: Incorporación de mentalidad adaptativa (Liquid UI) e inteligencia colectiva privada (ZKP). |
| ... | ... | ... |
