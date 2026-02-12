# F8 — Merchant Copilot (Doc 184) — Plan de Implementacion

**Fecha:** 2026-02-12
**Fase:** F8 de 12
**Modulo:** `jaraba_ai_agents` (extension)
**Estimacion:** 20-28h
**Dependencias:** F6 (admin patterns), jaraba_ai_agents architecture

---

## 1. Objetivo

Completar el Merchant Copilot para ComercioConecta: system prompt especializado,
generacion de ofertas flash y posts de redes sociales con IA.

## 2. Estado Actual

### 2.1 Agentes existentes

| Agente | Vertical | Acciones |
|--------|----------|----------|
| ProducerCopilotAgent | AgroConecta | 4: description, price, review, chat |
| SalesAgent | AgroConecta | 6: recommend, search, cart, faq, chat, recover_cart |
| MarketingAgent | General | 4: social_post, email_promo, blog_post, product_copy |
| SmartMarketingAgent | General | 4: (same with model routing) |

### 2.2 Patron establecido

- Hereda de `SmartBaseAgent` (model routing inteligente)
- Constructor con: AiProviderPluginManager, ConfigFactory, Logger, BrandVoice, Observability, ModelRouter, UnifiedPromptBuilder
- `doExecute()` con match expression
- Cada accion retorna JSON parseado con `content_type` metadata
- `getDefaultBrandVoice()` define personalidad del agente

### 2.3 Gap

| Gap | Tipo |
|-----|------|
| MerchantCopilotAgent | Nueva clase agente |
| System prompt comercio local | Nuevo |
| flash_offer action | Nuevo (stock + descuento optimo) |
| social_post merchant-specific | Nuevo (local hashtags) |
| email_promo merchant-specific | Nuevo (campana comercio) |

## 3. Arquitectura

### 3.1 MerchantCopilotAgent

Extiende SmartBaseAgent con 6 acciones:

| Accion | Tier | Requiere |
|--------|------|----------|
| generate_description | balanced | product_name |
| suggest_price | fast | product_name, current_price |
| social_post | balanced | product_name, platform |
| flash_offer | balanced | product_name, current_price, stock_level |
| respond_review | fast | review_rating, review_comment |
| email_promo | balanced | product_name, objective, offer_details |

### 3.2 Flash Offer Logic

Analiza stock de los ultimos 30 dias para sugerir descuento optimo:
- Stock alto + ventas bajas → descuento agresivo (30-50%)
- Stock medio + ventas medias → descuento moderado (15-25%)
- Stock bajo → no recomendar flash offer

## 4. Verificacion

- [ ] Service `jaraba_ai_agents.merchant_copilot_agent` registrado
- [ ] Agent ID: `merchant_copilot`
- [ ] 6/6 acciones disponibles
- [ ] `drush cr` exitoso
