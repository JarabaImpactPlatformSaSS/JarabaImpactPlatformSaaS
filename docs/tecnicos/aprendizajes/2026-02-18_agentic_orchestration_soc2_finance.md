# Aprendizaje: Orquestación Agentic, Wallet SOC2 y Movilidad Proactiva

**Fecha:** 2026-02-18  
**Contexto:** Fase final de elevación a Clase Mundial (Golden Master Candidate)  
**Módulos afectados:** `jaraba_ai_agents`, `jaraba_billing`, `jaraba_pwa`, `jaraba_predictive`, `jaraba_content_hub`

---

## 📑 Patron Principal: "The Autonomous Sovereign Platform"

El SaaS ha evolucionado de ser una herramienta pasiva a un organismo activo que predice riesgos, actúa mediante agentes y se asegura de su propia rentabilidad y cumplimiento normativo.

---

## 🧠 Aprendizajes Clave

### 1. Orquestación de Agentes (Agentic Workflows)
- **Situación:** Los agentes de IA solo conversaban, no podían realizar acciones reales en el sistema.
- **Aprendizaje:** Implementar un **Tool Registry** basado en Atributos PHP 8.4 permite a los servicios backend "anunciarse" a la IA sin acoplamiento.
- **Regla:** **AI-TOOL-001**: Marcar métodos ejecutables con `#[AgentTool]` y usar el patrón Compiler Pass para registro automático.

### 2. Integridad Financiera SOC2
- **Situación:** El sistema de créditos era una simple columna de suma/resta, vulnerable a manipulaciones y sin auditoría.
- **Aprendizaje:** Un SaaS Enterprise requiere un **Ledger Inmutable**. Cada transacción debe heredar un hash de la anterior (Hash Chain), garantizando que el rastro de dinero sea auditable y a prueba de manipulaciones.
- **Regla:** **FIN-LEDGER-001**: Inmutabilidad obligatoria para registros de saldo y uso.

### 3. Movilidad y Notificaciones Push (VAPID)
- **Situación:** La plataforma dependía de que el usuario estuviera logueado en escritorio para ver novedades logísticas o de seguridad.
- **Aprendizaje:** El uso de **Web Push nativo (VAPID)** permite notificaciones proactivas sin depender de Firebase o Google, manteniendo la soberanía del dato y cerrando el ciclo de respuesta inmediata (ej: picos de tokens IA detectados).
- **Regla:** **MOB-PWA-001**: Manifiesto dinámico y notificaciones push para eventos de severidad "Critical" o "High".

### 4. Agnosticismo Vertical con Inyección
- **Situación:** Se tendía a crear motores de fraude o predicción específicos para un vertical (Agro).
- **Aprendizaje:** El motor debe estar en el Core y ser "ciego". Los verticales inyectan sus reglas vía servicios tagueados.
- **Regla:** **BIZ-AGNOSTIC-001**: La inteligencia central procesa abstracciones; los verticales proveen los detectores.

---

## 🛠️ Resultado Técnico
- **Seguridad**: Sondas automáticas de compliance SOC2 (MFA, Backups).
- **IA**: Orquestación autónoma de envíos y campañas.
- **UX**: PWA instalable con diseño glassmorphic y guía visual para iOS.
- **Finanzas**: Wallet prepago con trazabilidad bancaria.

**Estado del SaaS:** Golden Master Ready.
