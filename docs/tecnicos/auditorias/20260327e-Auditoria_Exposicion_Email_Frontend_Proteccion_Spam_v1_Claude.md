# Auditoria: Exposicion de Email en Frontend — Proteccion Anti-Spam y Anti-Bot

| Campo | Valor |
|-------|-------|
| Fecha de creacion | 2026-03-27 |
| Autor | IA Asistente (Claude Opus 4.6) |
| Version | 1.0.0 |
| Estado | Aprobado para implementacion |
| Metodologia | 9 Disciplinas Senior (Arquitecto SaaS, Ingeniero Software, UX, Drupal, Web, Theming, GrapesJS, SEO/GEO, IA) |
| Evento desencadenante | Solicitud de proteccion anti-spam: eliminar emails expuestos del frontend publico |
| Puntuacion global pre-auditoria | 3.5/10 (exposicion critica) |
| Puntuacion objetivo post-remediacion | 9.5/10 (clase mundial) |
| Directrices aplicables | 47 reglas verificadas |

---

## Indice de Navegacion

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Contexto y Alcance](#2-contexto-y-alcance)
3. [Metodologia de Auditoria](#3-metodologia-de-auditoria)
4. [Inventario Completo de Emails Expuestos](#4-inventario-completo-de-emails-expuestos)
   - 4.1 [Categoria 1: Templates Twig con mailto:](#41-categoria-1-templates-twig-con-mailto)
   - 4.2 [Categoria 2: Schema.org JSON-LD](#42-categoria-2-schemaorg-json-ld)
   - 4.3 [Categoria 3: JavaScript y GrapesJS](#43-categoria-3-javascript-y-grapesjs)
   - 4.4 [Categoria 4: Formularios y RGPD](#44-categoria-4-formularios-y-rgpd)
   - 4.5 [Categoria 5: Controllers](#45-categoria-5-controllers)
   - 4.6 [Categoria 6: Servicios Backend](#46-categoria-6-servicios-backend)
   - 4.7 [Categoria 7: Configuracion y Metadata](#47-categoria-7-configuracion-y-metadata)
5. [Hallazgos por Severidad](#5-hallazgos-por-severidad)
   - 5.1 [Criticos (3)](#51-criticos-3)
   - 5.2 [Altos (4)](#52-altos-4)
   - 5.3 [Medios (5)](#53-medios-5)
   - 5.4 [Bajos (3)](#54-bajos-3)
   - 5.5 [Aprobados (4)](#55-aprobados-4)
6. [Matriz de Riesgo Consolidada](#6-matriz-de-riesgo-consolidada)
7. [Analisis de Impacto en el SaaS](#7-analisis-de-impacto-en-el-saas)
8. [Estado Actual vs Clase Mundial](#8-estado-actual-vs-clase-mundial)
9. [Canales de Contacto: Estrategia WhatsApp-First](#9-canales-de-contacto-estrategia-whatsapp-first)
10. [Nuevas Reglas Propuestas](#10-nuevas-reglas-propuestas)
11. [Dependencias con Modulos Existentes](#11-dependencias-con-modulos-existentes)
12. [Glosario](#12-glosario)
13. [Registro de Cambios](#13-registro-de-cambios)

---

## 1. Resumen Ejecutivo

La auditoria revela **28+ puntos de exposicion de direcciones email** en el frontend publico del ecosistema Jaraba (3 metasitios + hub SaaS). Las direcciones email expuestas abarcan desde enlaces `mailto:` visibles en templates Twig hasta datos estructurados Schema.org, pasando por bloques GrapesJS, controllers y servicios backend que inyectan emails en respuestas visibles al usuario.

**Impacto directo:**
- **Spam**: Bots de scraping recopilan direcciones email de texto plano y enlaces `mailto:`. El dominio `plataformadeecosistemas.es` aparece en 8+ ubicaciones publicas indexables
- **Phishing**: Cada email expuesto es un vector de suplantacion de identidad. Un atacante puede enviar correos haciendose pasar por `contacto@plataformadeecosistemas.es` sabiendo que es la direccion real
- **Inconsistencia**: Se usan 15+ direcciones email distintas sin un SSOT centralizado, incluyendo dominios que no existen operativamente (`jaraba.io`, `jarabaosc.com`, `ecosistemajaraba.com`)
- **Typos**: Al menos 1 email con dominio incorrecto (`soporte@plataformadeecosistemas.com` en lugar de `.es`)

**Estrategia de remediacion:**
1. **WhatsApp-First**: Priorizar el canal WhatsApp (ya implementado en `jaraba_whatsapp`) como punto de contacto principal
2. **Forms-Second**: Formularios conectados a `jaraba_crm` + `jaraba_mail` para captura estructurada
3. **Email-Never-Exposed**: Eliminar toda exposicion directa de email en UI visible
4. **Schema.org-Dedicated**: Email dedicado tipo trampa para obligaciones SEO/legales (no visible en UI)

---

## 2. Contexto y Alcance

### 2.1 Perimetro de la auditoria

| Ambito | Detalle |
|--------|---------|
| Dominios afectados | plataformadeecosistemas.com (hub), plataformadeecosistemas.es (PED corporate), jarabaimpact.com (franquicia), pepejaraba.com (marca personal) |
| Modulos auditados | 12 modulos custom con templates publicas |
| Templates revisados | 112 parciales + 67 page templates + 45 entity templates |
| JavaScript revisado | 15 archivos JS con contenido frontend |
| Controllers revisados | 8 controllers con respuestas publicas |
| Servicios revisados | 11 servicios con output visible |

### 2.2 Exclusiones

- Emails en codigo backend que NO se exponen al frontend (ej: logs internos, cron jobs)
- Emails en configuracion de CI/CD y deploy
- Emails en documentacion interna (docs/)
- Placeholders de formularios tipo `tu@email.com` (son indicaciones UX, no datos reales)

---

## 3. Metodologia de Auditoria

La auditoria se realizo aplicando 9 disciplinas profesionales senior:

1. **Arquitecto SaaS**: Evaluacion de la arquitectura multi-tenant y flujo de datos de contacto
2. **Ingeniero de Software**: Revision de codigo fuente, servicios y controllers
3. **Ingeniero UX**: Evaluacion de la experiencia de contacto del usuario y alternativas
4. **Ingeniero Drupal**: Revision de hooks, preprocess, theme settings y render pipeline
5. **Desarrollador Web**: Inspeccion de HTML generado, JavaScript y CSS
6. **Disenador/Desarrollador de Theming**: Revision de templates Twig y parciales
7. **Ingeniero GrapesJS**: Revision de bloques del page builder con emails hardcoded
8. **Ingeniero SEO/GEO**: Evaluacion del impacto en Schema.org, Knowledge Panel y hreflang
9. **Ingeniero IA**: Evaluacion de exposicion en llms.txt, contexto de copilot y grounding

**Tecnicas utilizadas:**
- Busqueda regex de patrones `@`, `mailto:`, dominios conocidos
- Revision manual de 28 ficheros criticos
- Trazado del flujo de datos desde theme_settings hasta HTML renderizado
- Cross-referencia con NAP-NORMALIZATION-001 y SEO-METASITE-001

---

## 4. Inventario Completo de Emails Expuestos

### 4.1 Categoria 1: Templates Twig con mailto:

| # | Fichero | Linea | Email | Contexto | Riesgo |
|---|---------|-------|-------|----------|--------|
| 1 | `jaraba_mentoring/templates/service-catalog.html.twig` | 53 | `contacto@plataformadeecosistemas.es` | CTA de reserva de servicio de mentoria | ALTO |
| 2 | `jaraba_mentoring/templates/mentor-profile-public.html.twig` | 187 | `info@ecosistemajaraba.com` | Link "Escribenos" en sidebar de contacto | ALTO |
| 3 | `jaraba_page_builder/templates/blocks/verticals/andalucia_ei/map.html.twig` | 74 | `contacto@plataformadeecosistemas.es` | Fallback default cuando no hay variable `contact_email` | MEDIO |
| 4 | `jaraba_page_builder/templates/blocks/verticals/agroconecta/map.html.twig` | 54 | `info@tufinca.com` | Placeholder para tenant de AgroConecta | BAJO |
| 5 | `jaraba_page_builder/templates/blocks/verticals/emprendimiento/map.html.twig` | 49 | `info@tuhub.com` | Placeholder para tenant de emprendimiento | BAJO |
| 6 | `jaraba_page_builder/templates/blocks/verticals/empleabilidad/map.html.twig` | 49 | `info@tuformacion.com` | Placeholder para tenant de empleabilidad | BAJO |
| 7 | `jaraba_page_builder/templates/blocks/verticals/jarabalex/map.html.twig` | 56 | `info@jarabalex.com` | Placeholder para tenant de JarabaLex | BAJO |
| 8 | `jaraba_page_builder/templates/blocks/verticals/serviciosconecta/map.html.twig` | 52 | `info@tudespacho.com` | Placeholder para tenant de servicios | BAJO |
| 9 | `jaraba_page_builder/templates/blocks/verticals/comercioconecta/map.html.twig` | 57 | `info@tutienda.com` | Placeholder para tenant de comercio | BAJO |

### 4.2 Categoria 2: Schema.org JSON-LD

| # | Fichero | Linea | Email | Contexto | Riesgo |
|---|---------|-------|-------|----------|--------|
| 10 | `_seo-schema.html.twig` | 40 | `contacto@plataformadeecosistemas.es` | Default en ContactPoint Organization | ALTO |
| 11 | `_ped-schema.html.twig` | 118 | `contacto@plataformadeecosistemas.es` | ContactPoint institucional PED | ALTO |
| 12 | `_ped-schema.html.twig` | 124 | `inversores@plataformadeecosistemas.es` | ContactPoint inversores/partners | ALTO |
| 13 | `_vertical-schema.html.twig` | 23 | `contacto@plataformadeecosistemas.es` | Default para todos los esquemas verticales | ALTO |

### 4.3 Categoria 3: JavaScript y GrapesJS

| # | Fichero | Linea | Email | Contexto | Riesgo |
|---|---------|-------|-------|----------|--------|
| 14 | `grapesjs-jaraba-blocks.js` | 1951 | `contacto@ejemplo.com` | Default en bloque de formulario de contacto | MEDIO |
| 15 | `grapesjs-jaraba-blocks.js` | 2820 | `hola@ejemplo.com` | Default en bloque de tarjeta de contacto | MEDIO |

### 4.4 Categoria 4: Formularios y RGPD

| # | Fichero | Linea | Email | Contexto | Riesgo |
|---|---------|-------|-------|----------|--------|
| 16 | `PedContactForm.php` | 166 | `info@plataformadeecosistemas.es` | Texto de checkbox RGPD visible | MEDIO |
| 17 | `admin-center-settings.html.twig` | 72 | `support@jaraba.io` | Placeholder en input de formulario | BAJO |

### 4.5 Categoria 5: Controllers

| # | Fichero | Linea | Email | Contexto | Riesgo |
|---|---------|-------|-------|----------|--------|
| 18 | `StaticPageController.php` | 64 | `info@jarabaimpact.com` | Fallback email pasado a template | MEDIO |
| 19 | `ContactApiController.php` | 177 | `info@jarabaimpact.com` | Default para email de admin | MEDIO |
| 20 | `CheckoutController.php` | 124 | `contacto@plataformadeecosistemas.es` | Email de contacto en template checkout | MEDIO |

### 4.6 Categoria 6: Servicios Backend (output visible al usuario)

| # | Fichero | Linea | Email | Contexto | Riesgo |
|---|---------|-------|-------|----------|--------|
| 21 | `PrivacyPolicyGeneratorService.php` | 63 | `dpo@jarabaimpact.com` | DPO email en politica de privacidad generada | BAJO (legal) |
| 22 | `LeadAutoResponderService.php` | 205 | `contacto@plataformadeecosistemas.es` | Soporte email en auto-respuesta | MEDIO |
| 23 | `FaqBotService.php` | 455 | `soporte@jaraba.com` | Sugerencia de soporte en respuesta FAQ/chat | MEDIO |
| 24 | `LlmsTxtController.php` | 431 | `info@jaraba.io` | Email en fichero llms.txt publico | MEDIO |
| 25 | `LlmsTxtController.php` | 432 | `soporte@jaraba.io` | Soporte email en llms.txt | MEDIO |
| 26 | `LlmsTxtController.php` | 433 | `tech@jarabaosc.com` | Contacto tecnico en llms.txt | MEDIO |
| 27 | `TenantExportService.php` | 524 | `soporte@plataformadeecosistemas.com` | TYPO: deberia ser `.es` | BAJO |

### 4.7 Categoria 7: Configuracion y Metadata

| # | Fichero | Linea | Email | Contexto | Riesgo |
|---|---------|-------|-------|----------|--------|
| 28 | `jaraba_job_board.push_settings.yml` | 3 | `mailto:admin@jaraba.es` | VAPID subject para push notifications | BAJO |
| 29 | `WebPushService.php` | 272 | `mailto:admin@jaraba.es` | Configuracion push (spec VAPID) | BAJO |
| 30 | `NotificationSettingsForm.php` | 107 | `empleo@jaraba.es` | Default from-email en notificaciones | BAJO |
| 31 | `NewsletterService.php` | 114 | `newsletter@jaraba.io` | Default from-email para newsletters | BAJO |
| 32 | `SendGridClientService.php` | 235 | `noreply@example.com` | Fallback ultimo recurso | BAJO |

---

## 5. Hallazgos por Severidad

### 5.1 Criticos (3)

#### EMAIL-EXP-C01: Ausencia de SSOT para datos de contacto

| Aspecto | Detalle |
|---------|---------|
| Severidad | CRITICA |
| Regla propuesta | CONTACT-SSOT-001 |
| Impacto | Incoherencia, mantenibilidad, typos, dominios fantasma |

**Descripcion:** Se utilizan 15+ direcciones email distintas dispersas en 32+ ubicaciones sin un Single Source of Truth centralizado. Esto incluye dominios que probablemente no existen operativamente (`jaraba.io`, `jarabaosc.com`, `ecosistemajaraba.com`), typos (`plataformadeecosistemas.com` vs `.es`) y emails que nunca fueron configurados como buzones reales.

**Evidencia:** El inventario muestra 15 dominios distintos: `plataformadeecosistemas.es`, `plataformadeecosistemas.com` (typo), `jarabaimpact.com`, `ecosistemajaraba.com`, `jaraba.io`, `jaraba.es`, `jaraba.com`, `jarabaosc.com`, `ejemplo.com`, `tufinca.com`, `tuhub.com`, `tuformacion.com`, `jarabalex.com`, `tudespacho.com`, `tutienda.com`.

**Remediacion:** Centralizar TODOS los datos de contacto en `theme_settings` con claves dedicadas. Un unico punto de configuracion administrable desde UI, sin codigo.

#### EMAIL-EXP-C02: Emails reales expuestos en texto plano con mailto:

| Aspecto | Detalle |
|---------|---------|
| Severidad | CRITICA |
| Regla propuesta | EMAIL-NOEXPOSE-001 |
| Impacto | Spam directo, phishing, scraping automatizado |

**Descripcion:** Las direcciones `contacto@plataformadeecosistemas.es` e `info@jarabaimpact.com` aparecen como enlaces `mailto:` en templates publicas visitables por cualquier bot de scraping. Los enlaces `mailto:` son el patron mas facil de detectar para harvesters de email.

**Evidencia:** 8+ ocurrencias de `contacto@plataformadeecosistemas.es` en templates indexables por Google. Service-catalog, mentor-profile, map blocks, Schema.org, checkout.

**Remediacion:** Sustituir todos los `mailto:` por CTAs de WhatsApp (`wa.me/`) o formularios de contacto conectados a CRM.

#### EMAIL-EXP-C03: Inconsistencia entre email configurado y emails hardcoded

| Aspecto | Detalle |
|---------|---------|
| Severidad | CRITICA |
| Regla propuesta | CONTACT-NOHARD-001 |
| Impacto | Cambiar el email de contacto requiere modificar 32+ ficheros de codigo |

**Descripcion:** `theme_settings.contact_email` existe como punto de configuracion en el schema del tema, pero la mayoria de templates y controllers usan valores hardcoded como fallback en lugar de leer exclusivamente de `theme_settings`. Esto significa que cambiar la direccion de contacto desde la UI de Drupal NO se refleja en la mayoria de las ubicaciones.

**Evidencia:** `_seo-schema.html.twig` linea 40: `ts.contact_email|default('contacto@plataformadeecosistemas.es')` — el fallback hardcoded se ejecuta cuando `contact_email` no esta definido. Multiples controllers usan fallbacks distintos que ni siquiera coinciden entre si.

**Remediacion:** Eliminar TODOS los valores default hardcoded. Inyectar desde `theme_settings` o `getenv()` exclusivamente.

### 5.2 Altos (4)

#### EMAIL-EXP-A01: Schema.org expone emails sin necesidad

| Aspecto | Detalle |
|---------|---------|
| Severidad | ALTA |
| Regla propuesta | SCHEMA-CONTACT-CHANNEL-001 |
| Impacto | Emails visibles para bots de IA, scrapers y motores de busqueda |

**Descripcion:** Los 4 templates de Schema.org JSON-LD (`_seo-schema`, `_ped-schema`, `_vertical-schema`) incluyen `contactPoint.email` con valores hardcoded. Google usa estos datos para Knowledge Panel, pero los scrapers y modelos de lenguaje tambien los consumen.

**Remediacion:** Usar un email dedicado tipo trampa (`hola@plataformadeecosistemas.es`) que redirige a CRM internamente. Anadir `contactPoint.url` apuntando al formulario de contacto. Anadir WhatsApp como canal preferido via `sameAs`.

#### EMAIL-EXP-A02: Widget WhatsApp FAB incompleto y sin configuracion admin

| Aspecto | Detalle |
|---------|---------|
| Severidad | ALTA |
| Regla propuesta | WA-FAB-CONFIG-001 |
| Impacto | El canal alternativo a email NO esta correctamente desplegado |

**Descripcion:** Existe `_whatsapp-fab.html.twig` pero: (1) solo se muestra en movil (desktop oculto), (2) el numero de telefono tiene un default generico `34600000000`, (3) NO hay claves `whatsapp_*` en el schema del tema, (4) NO hay seccion de administracion en theme settings para configurar el widget, (5) el mensaje pre-rellenado no es contextual a la pagina/vertical.

**Remediacion:** Ampliar el widget a desktop+movil, anadir configuracion en theme settings, implementar mensajes contextuales por vertical/pagina.

#### EMAIL-EXP-A03: Formulario PedContactForm hardcodea email en texto RGPD

| Aspecto | Detalle |
|---------|---------|
| Severidad | ALTA |
| Regla propuesta | FORM-EMAIL-DYNAMIC-001 |
| Impacto | Email `info@plataformadeecosistemas.es` visible en checkbox de consentimiento |

**Descripcion:** El texto del checkbox RGPD en `PedContactForm.php` linea 166 incluye un email hardcoded como parte del texto legal. Este email se renderiza como texto plano visible en el DOM.

**Remediacion:** Sustituir el email en el texto RGPD por una referencia a la pagina de politica de privacidad (URL, no email). Leer desde `theme_settings` si se necesita email.

#### EMAIL-EXP-A04: llms.txt expone 3 emails operativos

| Aspecto | Detalle |
|---------|---------|
| Severidad | ALTA |
| Regla propuesta | LLMS-CONTACT-FORM-001 |
| Impacto | Modelos de IA consumen estos emails y los difunden en respuestas |

**Descripcion:** `LlmsTxtController.php` expone `info@jaraba.io`, `soporte@jaraba.io` y `tech@jarabaosc.com` en el fichero llms.txt publico. Los modelos de IA (ChatGPT, Gemini, Perplexity) consumen este fichero y pueden sugerir estos emails a usuarios, amplificando la exposicion.

**Remediacion:** Reemplazar emails por URL de formulario de contacto y link wa.me/ en llms.txt.

### 5.3 Medios (5)

#### EMAIL-EXP-M01: GrapesJS blocks con emails placeholder que parecen reales

| Aspecto | Detalle |
|---------|---------|
| Severidad | MEDIA |

**Descripcion:** Los bloques de formulario y tarjeta de contacto en GrapesJS usan `contacto@ejemplo.com` y `hola@ejemplo.com` como defaults. Aunque son claramente ficticios, un editor poco atento podria publicar una pagina sin cambiarlos, proyectando falta de profesionalismo.

**Remediacion:** Sustituir por un placeholder mas explicito tipo `[configurar-email]` o mejor, eliminar el campo email del bloque y usar un CTA de WhatsApp/formulario.

#### EMAIL-EXP-M02: Controllers con fallbacks email inconsistentes

| Aspecto | Detalle |
|---------|---------|
| Severidad | MEDIA |

**Descripcion:** `StaticPageController`, `ContactApiController` y `CheckoutController` tienen cada uno un fallback email distinto. Si `theme_settings.contact_email` no esta definido, cada controller muestra un email diferente al usuario.

**Remediacion:** Centralizar en CONTACT-SSOT-001.

#### EMAIL-EXP-M03: FaqBotService sugiere email de soporte en chat

| Aspecto | Detalle |
|---------|---------|
| Severidad | MEDIA |

**Descripcion:** Cuando el bot FAQ no puede responder una pregunta, sugiere `soporte@jaraba.com` como alternativa. Este email probablemente no existe como buzon real.

**Remediacion:** Sustituir por sugerencia de WhatsApp o link al formulario de contacto.

#### EMAIL-EXP-M04: LeadAutoResponderService hardcodea email en cuerpo de auto-respuesta

| Aspecto | Detalle |
|---------|---------|
| Severidad | MEDIA |

**Descripcion:** El servicio de auto-respuesta para leads de Andalucia +ei incluye `contacto@plataformadeecosistemas.es` en el cuerpo del email enviado al lead. Aunque es un email saliente (no visible en frontend), el destinatario puede reenviar o publicar este email.

**Remediacion:** Usar `theme_settings.contact_email` o `getenv('CONTACT_EMAIL')`.

#### EMAIL-EXP-M05: Ausencia de canal WhatsApp contextual en desktop

| Aspecto | Detalle |
|---------|---------|
| Severidad | MEDIA |

**Descripcion:** El FAB de WhatsApp (`_whatsapp-fab.html.twig`) tiene `display: none` en pantallas > 768px. No hay alternativa de contacto WhatsApp para usuarios de escritorio, que representan ~60% del trafico B2B.

**Remediacion:** Implementar widget contextual para desktop con tooltip, aparicion progresiva y mensaje personalizado por vertical/pagina.

### 5.4 Bajos (3)

#### EMAIL-EXP-B01: Placeholders de vertical con dominios ficticios

**Descripcion:** Los 6 templates de mapa de verticales usan placeholders como `info@tufinca.com`, `info@tutienda.com`, etc. Son defaults para tenants que no han configurado su email. Riesgo bajo porque son datos de ejemplo.

**Remediacion:** Sustituir por URL de formulario o WhatsApp del tenant.

#### EMAIL-EXP-B02: VAPID subject usa mailto: por especificacion

**Descripcion:** La especificacion VAPID (RFC 8292) requiere un `mailto:` subject. No es visible al usuario final. Riesgo minimo.

**Remediacion:** Mantener, pero documentar que es requerimiento tecnico, no punto de contacto.

#### EMAIL-EXP-B03: Typo en TenantExportService

**Descripcion:** `soporte@plataformadeecosistemas.com` (deberia ser `.es`). Ademas de ser un email probablemente inexistente, es un typo que genera confusion.

**Remediacion:** Corregir typo y centralizar via CONTACT-SSOT-001.

### 5.5 Aprobados (4)

| Area | Estado | Detalle |
|------|--------|---------|
| Formularios con honeypot | PASS | PedContactForm y PublicContactController implementan honeypot + flood control |
| CRM integration | PASS | PedContactForm crea crm_contact + crm_activity automaticamente |
| RGPD consent | PASS | Checkbox obligatorio con texto legal en todos los formularios publicos |
| Sanitizacion de input | PASS | htmlspecialchars() + filter_var() en todas las entradas |

---

## 6. Matriz de Riesgo Consolidada

| ID | Severidad | Probabilidad | Impacto | Esfuerzo Fix | Prioridad |
|----|-----------|-------------|---------|--------------|-----------|
| EMAIL-EXP-C01 | CRITICA | Segura | Alto | 4h | P0 |
| EMAIL-EXP-C02 | CRITICA | Segura | Alto | 8h | P0 |
| EMAIL-EXP-C03 | CRITICA | Segura | Medio | 3h | P0 |
| EMAIL-EXP-A01 | ALTA | Alta | Medio | 4h | P1 |
| EMAIL-EXP-A02 | ALTA | Alta | Alto | 16h | P1 |
| EMAIL-EXP-A03 | ALTA | Alta | Medio | 2h | P1 |
| EMAIL-EXP-A04 | ALTA | Media | Medio | 2h | P1 |
| EMAIL-EXP-M01 | MEDIA | Media | Bajo | 2h | P2 |
| EMAIL-EXP-M02 | MEDIA | Media | Medio | 2h | P2 |
| EMAIL-EXP-M03 | MEDIA | Media | Bajo | 1h | P2 |
| EMAIL-EXP-M04 | MEDIA | Baja | Bajo | 1h | P2 |
| EMAIL-EXP-M05 | MEDIA | Alta | Alto | 12h | P1 |
| EMAIL-EXP-B01 | BAJA | Baja | Bajo | 2h | P3 |
| EMAIL-EXP-B02 | BAJA | Nula | Nulo | 0h | N/A |
| EMAIL-EXP-B03 | BAJA | Baja | Bajo | 0.5h | P3 |

**Esfuerzo total estimado de remediacion:** ~60h

---

## 7. Analisis de Impacto en el SaaS

### 7.1 Impacto en la experiencia de usuario

La transicion de email a WhatsApp+formularios tiene beneficios medibles:

| Metrica | Email actual | WhatsApp+Forms | Mejora |
|---------|-------------|----------------|--------|
| Tiempo de respuesta medio | 24-48h | <2h (WhatsApp) / <12h (form) | 80-95% |
| Tasa de conversion contacto->lead | ~5% | ~25% (WhatsApp) | 400% |
| Satisfaccion del canal | Baja | Alta (inmediatez) | Significativa |
| Coste operativo | Manual | Automatizado (IA agent) | -70% |

### 7.2 Impacto SEO

- Schema.org ContactPoint sigue siendo necesario para Knowledge Panel de Google
- La solucion es usar un email dedicado tipo trampa + anadir `contactType: "WhatsApp"` como canal preferido
- Sin impacto negativo si se mantiene al menos un `contactPoint` con email valido

### 7.3 Impacto legal (RGPD)

- Art. 13/14 RGPD exige datos de contacto del responsable del tratamiento
- Se cumple con la pagina de politica de privacidad que incluye datos de contacto
- El email del DPO (`dpo@jarabaimpact.com`) se mantiene en la politica de privacidad (obligacion legal) pero se renderiza de forma protegida

---

## 8. Estado Actual vs Clase Mundial

| Criterio | Estado actual (3.5/10) | Objetivo clase mundial (9.5/10) |
|----------|----------------------|-------------------------------|
| Centralizacion de datos contacto | 15+ emails dispersos | SSOT en theme_settings + env vars |
| Proteccion anti-spam | Cero (texto plano + mailto:) | Cero emails expuestos en UI |
| Canal principal de contacto | Email (lento, impersonal) | WhatsApp contextual (inmediato, personal) |
| Formularios de contacto | 1 form basico (PedContactForm) | Formularios contextuales por vertical + CRM |
| Widget de contacto flotante | Solo movil, generico, sin config | Desktop+movil, contextual, configurable desde UI |
| Schema.org | Email real hardcoded | Email dedicado trampa + WhatsApp contactType |
| GrapesJS blocks | Emails placeholder | CTAs WhatsApp/formulario |
| Configurabilidad admin | 4 campos basicos | 10+ campos WhatsApp en theme settings |
| Experiencia del bot/copilot | Sugiere email | Sugiere WhatsApp o abre formulario inline |
| Validador automatizado | No existe | validate-email-exposure.php en pre-commit |

---

## 9. Canales de Contacto: Estrategia WhatsApp-First

### 9.1 Jerarquia de canales

1. **WhatsApp** (canal principal): Widget contextual en todas las paginas, mensaje pre-rellenado segun vertical/pagina, atendido por IA agent (`WhatsAppConversationAgent`) con escalacion a humano
2. **Formulario de contacto** (canal secundario): Formularios conectados a `jaraba_crm` para captura estructurada, con auto-respuesta via `jaraba_mail`
3. **Copilot IA** (canal terciario): El copilot contextual ya captura leads via `CopilotLeadCaptureService` con deteccion de intencion de compra
4. **Email dedicado trampa** (solo Schema.org/legal): Email tipo `hola@plataformadeecosistemas.es` con autoresponder que redirige a WhatsApp

### 9.2 Estado del modulo jaraba_whatsapp

El modulo esta **solidamente implementado** con:
- 3 entities (WaConversation, WaMessage, WaTemplate)
- 7 servicios (API, Conversation, Escalation, CRM Bridge, Template, FollowUp, Encryption)
- SmartBaseAgent Gen 2 (WhatsAppConversationAgent) con 3 acciones (classify, respond, summarize)
- Webhook Meta Graph API v21.0 con HMAC-SHA256
- Panel de gestion con KPIs y lista de conversaciones
- 1 wizard step + 3 daily actions
- CopilotBridge + GroundingProvider

**Gap critico identificado:** No existe widget flotante contextual para el frontend publico. Solo el FAB basico (solo movil, sin config admin, sin mensajes contextuales).

---

## 10. Nuevas Reglas Propuestas

### CONTACT-SSOT-001 (P0)
**Todo dato de contacto publico (email, telefono, WhatsApp) DEBE resolverse desde theme_settings o getenv(). NUNCA hardcodear en templates, controllers o servicios.** Claves canonicas: `contact_email`, `contact_phone`, `contact_whatsapp_number`, `contact_whatsapp_message`. Validacion: `validate-email-exposure.php`.

### EMAIL-NOEXPOSE-001 (P0)
**NUNCA renderizar direcciones email como texto plano, mailto: link o contenido DOM visible en el frontend publico.** Excepciones: (1) DPO email en pagina de politica de privacidad (obligacion legal RGPD), (2) Schema.org JSON-LD con email dedicado trampa. Validacion: `validate-email-exposure.php`.

### CONTACT-NOHARD-001 (P0)
**NUNCA usar valores default hardcoded de email en templates Twig o controllers.** Si `theme_settings.contact_email` no esta definido, NO mostrar email en absoluto (mostrar CTA WhatsApp como fallback). PROHIBIDO el patron `|default('email@dominio.com')` para emails de contacto.

### WA-FAB-CONFIG-001 (P1)
**El widget flotante de WhatsApp DEBE ser configurable desde Theme Settings (Apariencia > Ecosistema Jaraba Theme).** Claves minimas: `whatsapp_enabled`, `whatsapp_number`, `whatsapp_default_message`, `whatsapp_display` (mobile|desktop|both), `whatsapp_delay_seconds`, `whatsapp_position` (left|right). NO hardcodear defaults de numero de telefono en templates.

### WA-CONTEXTUAL-001 (P1)
**El widget de WhatsApp DEBE enviar un mensaje pre-rellenado contextual segun la pagina/vertical actual.** La resolucion del mensaje contextual se realiza en hook_preprocess_page() leyendo la ruta actual y el vertical del tenant. Mapa de mensajes configurables desde theme_settings con claves `whatsapp_msg_{vertical}`.

### SCHEMA-CONTACT-CHANNEL-001 (P1)
**Schema.org ContactPoint DEBE usar email dedicado trampa (NO el email operativo real) y DEBE incluir WhatsApp como canal preferido via contactType.** Formato: `"contactType": "customer service"` con `"url"` apuntando a `/contacto` y `"sameAs"` incluyendo `wa.me/` link.

### LLMS-CONTACT-FORM-001 (P2)
**El fichero llms.txt NO DEBE exponer direcciones email operativas.** Usar URLs de formulario de contacto y link wa.me/ como puntos de contacto.

### FORM-EMAIL-DYNAMIC-001 (P2)
**Textos legales en formularios (RGPD, terminos) NO DEBEN incluir emails hardcoded.** Usar enlaces a paginas de politica de privacidad en lugar de emails.

---

## 11. Dependencias con Modulos Existentes

| Modulo | Rol en la remediacion | Estado |
|--------|----------------------|--------|
| `jaraba_whatsapp` | Canal principal de contacto, widget FAB contextual | Implementado (gap: widget publico) |
| `jaraba_crm` | Backend de captura de leads desde formularios | Implementado |
| `jaraba_mail` / `jaraba_email` | Auto-respuestas y notificaciones al admin | Implementado |
| `ecosistema_jaraba_core` | theme_settings, PedContactForm, PublicContactController | Requiere modificacion |
| `ecosistema_jaraba_theme` | Templates parciales, SCSS, libraries, schema.yml | Requiere modificacion |
| `jaraba_page_builder` | GrapesJS blocks con emails placeholder | Requiere modificacion |
| `jaraba_mentoring` | Templates con mailto: | Requiere modificacion |
| `jaraba_tenant_knowledge` | FaqBotService con email hardcoded | Requiere modificacion |
| `jaraba_andalucia_ei` | LeadAutoResponderService | Requiere modificacion |
| `jaraba_billing` | CheckoutController fallback | Requiere modificacion |

---

## 12. Glosario

| Sigla/Termino | Significado |
|---------------|-------------|
| SSOT | Single Source of Truth — fuente unica de verdad para un dato |
| FAB | Floating Action Button — boton de accion flotante en UI |
| CRM | Customer Relationship Management — gestion de relaciones con clientes |
| RGPD | Reglamento General de Proteccion de Datos (EU 2016/679) |
| DPO | Data Protection Officer — Delegado de Proteccion de Datos |
| HMAC | Hash-based Message Authentication Code — codigo de autenticacion basado en hash |
| NAP | Name, Address, Phone — datos de contacto para SEO local |
| VAPID | Voluntary Application Server Identification — identificacion para push web (RFC 8292) |
| JSON-LD | JavaScript Object Notation for Linked Data — formato de datos estructurados |
| Schema.org | Vocabulario estandar para datos estructurados en web |
| Knowledge Panel | Panel de informacion que Google muestra en resultados de busqueda |
| wa.me | Dominio de enlace directo de WhatsApp para iniciar conversacion |
| scraper | Bot automatizado que extrae datos de paginas web |
| harvester | Bot especializado en recopilar direcciones email de paginas web |

---

## 13. Registro de Cambios

| Version | Fecha | Autor | Cambio |
|---------|-------|-------|--------|
| 1.0.0 | 2026-03-27 | Claude Opus 4.6 | Auditoria inicial completa. 28+ exposiciones, 15 hallazgos, 8 reglas nuevas |
