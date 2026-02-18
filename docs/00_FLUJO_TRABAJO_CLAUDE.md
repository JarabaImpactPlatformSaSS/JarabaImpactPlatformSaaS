# Flujo de Trabajo del Asistente IA (Claude)

**Fecha de creacion:** 2026-02-18
**Version:** 7.0.0 (Living SaaS Adaptive Workflow)

---

## 1. Inicio de Sesion

Al comenzar o reanudar una conversacion, leer en este orden:

1. **DIRECTRICES** (`docs/00_DIRECTRICES_PROYECTO.md`) — Reglas, convenciones, principios de desarrollo
2. **ARQUITECTURA** (`docs/00_DOCUMENTO_MAESTRO_ARQUITECTURA.md`) — Modulos, stack, modelo de datos
3. **INDICE** (`docs/00_INDICE_GENERAL.md`) — Estado actual, ultimos cambios, aprendizajes recientes

Esto garantiza contexto completo antes de cualquier implementacion.

---

## 2. Antes de Implementar

- **Auditoría de Integridad Global**: Antes de añadir una nueva funcionalidad, verificar si ya existe en **algún** vertical o en el core para evitar duplicidades.
- **Mentalidad Living SaaS**: Preguntarse: "¿Cómo se adapta este componente al estado de salud del Tenant? ¿Puede este dato alimentar el oráculo ZKP sin romper la privacidad diferencial?".
- **Mentalidad SOC2**: Preguntarse: "¿Este cambio genera logs de auditoría? ¿Es inmutable si es financiero? ¿Respeta el aislamiento multi-tenant?".
- **Verificar aprendizajes previos:** Consultar `docs/tecnicos/aprendizajes/`.

---

## 3. Durante la Implementacion

Respetar las directrices del proyecto, incluyendo:

- **Liquid UI Mutation:** Inyectar clases de estado (`theme--mode-crisis`, `theme--mode-growth`) vía `hook_preprocess_html`.
- **ZKP Signal Emission:** Emitir señales agregables al `ZkOracleService` para inteligencia de mercado ciega.
- **Agentic Tools:** Marcar métodos con `#[AgentTool]` para que la IA pueda operarlos.
- **Ledger Integridad:** Usar `WalletService` para transacciones con Hash Chain.
- **PWA Mobility:** Notificaciones push proactivas vía `PlatformPushService`.
- **Zero-region:** Variables via `hook_preprocess_page()`.
- **Hooks:** Hooks nativos PHP en `.module`.
- **API REST:** Envelope `{success, data, error, message}`, prefijo `/api/v1/`.

---

## 5. Reglas de Oro (Actualizadas)

1. **No hardcodear:** Configuracion via Config Entities o State API.
2. **Inmutabilidad Financiera:** Registros append-only y encadenados por hash.
3. **Detección Proactiva:** El sistema debe avisar (Push/Email) antes de que el usuario lo pida.
4. **Tenant isolation:** `tenant_id` obligatorio.
5. **Rate limiting:** En toda operacion costosa.
6. **Patron zero-region:** 3 hooks obligatorios.
7. **Documentar siempre:** Toda sesion genera actualizacion documental.
8. **Privacidad Diferencial:** Toda inteligencia colectiva debe pasar por el motor de ruido de Laplace.

---

## 9. Registro de Cambios

| Fecha | Version | Descripcion |
|-------|---------|-------------|
| 2026-02-18 | **7.0.0** | **Living SaaS Workflow**: Incorporación de mentalidad adaptativa (Liquid UI) e inteligencia colectiva privada (ZKP). |
| 2026-02-18 | 6.0.0 | Golden Master Workflow: Mentalidad SOC2 y orquestación agéntica. |
| ... | ... | ... |
