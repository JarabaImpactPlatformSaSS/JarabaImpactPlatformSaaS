# Aprendizaje: Infraestructura DID y Negociación Autónoma

**Fecha:** 2026-02-18  
**Contexto:** Implementación de la Fase 3 (Economía Agéntica)  
**Módulos afectados:** `jaraba_identity`, `jaraba_agent_market`, `jaraba_credentials`

---

## 📑 Patron Principal: "The Non-Repudiable Negotiation"

El sistema ahora permite que agentes autónomos cierren tratos con validez legal y técnica, gracias a que cada paso está firmado criptográficamente por una identidad soberana custodiada.

---

## 🧠 Aprendizajes Clave

### 1. Custodia Híbrida de Claves
- **Situación:** Los agentes necesitan firmar transacciones sin que el humano esté presente, pero guardar claves privadas en texto plano es un riesgo crítico.
- **Aprendizaje:** Implementar una **Wallet Custodial** donde la clave privada se guarda encriptada simétricamente con una clave derivada del sitio. Solo se desencripta en memoria RAM durante el milisegundo de la firma.
- **Regla:** **IDENTITY-001**: Las claves privadas nunca tocan la base de datos sin cifrado.

### 2. Protocolo JDTP (State Machine)
- **Situación:** Las negociaciones entre máquinas pueden ser caóticas si no hay un protocolo estricto.
- **Aprendizaje:** Modelar la negociación como una **Máquina de Estados Finita** (Offer -> Counter -> Accept) asegura que el flujo sea predecible y que el `ledger` sea coherente.
- **Regla:** **NEGOTIATION-001**: El rastro de negociación debe ser append-only y cada entrada debe estar firmada por el emisor.

### 3. Reutilización Criptográfica
- **Situación:** Se tendía a crear nuevos servicios de firma para cada módulo.
- **Aprendizaje:** Centralizar la primitiva criptográfica (`Ed25519`) en un módulo core (`jaraba_credentials`) y consumirla vía inyección de servicios garantiza que la seguridad sea uniforme.

---

## 🛠️ Resultado Técnico
- **Identidad**: DIDs (`did:jaraba:*`) operativos para todos los usuarios y agentes.
- **Mercado**: Motor de negociación capaz de procesar ofertas y contraofertas firmadas.
- **UX**: Dashboard futurista para monitorizar la economía autónoma.

**Estado del SaaS:** Golden Master Candidate (Phase 3 Active).
