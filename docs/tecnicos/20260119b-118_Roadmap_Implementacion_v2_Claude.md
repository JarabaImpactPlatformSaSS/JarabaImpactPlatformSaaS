
ROADMAP DE IMPLEMENTACIÓN
Plan de Desarrollo 18 Meses - Actualizado con Marketing AI Stack
Versión:	2.0 (Actualización Marketing AI Stack)
Fecha:	Enero 2026
Código:	118_Roadmap_Implementacion_v2
Duración Total:	18 meses (Q1 2026 - Q2 2027)
Cambios v2:	Añadida Fase 4: Marketing AI Stack (Sprints 14-18)
 
1. Visión General del Roadmap
Fase	Nombre	Timeline	Sprints
Fase 1	Core Platform	Q1 2026 (Ene-Mar)	Sprints 1-6
Fase 2	Verticales Comerciales	Q2 2026 (Abr-Jun)	Sprints 7-13
Fase 3	Platform Features	Q3 2026 (Jul-Sep)	Sprints 14-20
Fase 4	Marketing AI Stack (NUEVO)	Q3-Q4 2026 (Ago-Nov)	Sprints 14-18
Fase 5	Escalado & Optimización	Q1-Q2 2027	Sprints 21-26
 
2. Fase 1: Core Platform (Q1 2026)
Objetivo: Establecer la infraestructura base que habilita todas las verticales.
2.1 Sprints 1-2: Fundamentos (Semanas 1-4)
Módulo	Doc	Horas	Dependencias
01_Core_Entidades_Esquema_BD	01	40-50h	Drupal 11
02_Core_Modulos_Personalizados	02	60-80h	01
04_Core_Permisos_RBAC	04	30-40h	02
07_Core_Configuracion_MultiTenant	07	50-60h	Group Module
2.2 Sprints 3-4: APIs y Theming (Semanas 5-8)
Módulo	Doc	Horas	Dependencias
03_Core_APIs_Contratos	03	50-60h	02
05_Core_Theming_jaraba_theme	05	40-50h	Tailwind
06_Core_Flujos_ECA	06	40-50h	ECA Module
100_Frontend_Architecture	100	128-176h	05
2.3 Sprints 5-6: Pagos y FOC (Semanas 9-12)
Módulo	Doc	Horas	Dependencias
134_Stripe_Billing_Integration	134	80-100h	Stripe Account
jaraba_foc (FOC completo)	FOC	120-150h	134
 
5. Fase 4: Marketing AI Stack (Q3-Q4 2026) — NUEVO
✓ NUEVA FASE añadida según documento 149_Marketing_AI_Stack_Completo_v1
Objetivo: Completar el departamento de marketing impulsado por IA con cobertura del 95%+

5.1 Sprint 14: Email Marketing Nativo (Semanas 27-28)
Componente	Horas	Prioridad	Dependencias
jaraba_email - Core entities	60-80h	🔴 Crítico	SendGrid API
ECA Automation Rules	30-40h	🔴 Crítico	jaraba_email core
MJML Templates (30)	25-35h	🟡 Alto	Brand guidelines
5.2 Sprint 15: CRM Pipeline B2B (Semanas 29-30)
Componente	Horas	Prioridad	Dependencias
jaraba_crm - Entities & Services	40-50h	🔴 Crítico	jaraba_core
Pipeline Kanban UI	Incluido	🔴 Crítico	React DnD
FOC Integration (forecasting)	Incluido	🟡 Alto	jaraba_foc
5.3 Sprint 16: Paid Ads & Retargeting (Semanas 31-32)
Componente	Horas	Prioridad	Dependencias
Paid Ads Integration (ext. analytics)	15-20h	🟡 Alto	Meta/Google APIs
Retargeting Pixel Manager	10-15h	🟡 Alto	jaraba_analytics
5.4 Sprint 17: Social Media Automation (Semanas 33-34)
Componente	Horas	Prioridad	Dependencias
jaraba_social - Core & Calendar	30-40h	🟡 Alto	jaraba_content_hub
Make.com Scenarios (4 platforms)	20-30h	🟡 Alto	Make.com account
Events & Webinars (ext. content_hub)	15-20h	🟢 Medio	Calendly/Zoom
5.5 Sprint 18: Experimentación & Referidos (Semanas 35-36)
Componente	Horas	Prioridad	Dependencias
A/B Testing Framework	12-18h	🟢 Medio	jaraba_analytics
Referral Program Universal	8-12h	🟢 Medio	jaraba_onboarding
 
6. Resumen de Inversión Total
Fase	Horas	Costo €	% Total
Fase 1: Core Platform	600-800h	30,000-40,000€	18%
Fase 2: Verticales Comerciales	1,500-2,000h	75,000-100,000€	42%
Fase 3: Platform Features	800-1,100h	40,000-55,000€	23%
Fase 4: Marketing AI Stack (NUEVO)	265-360h	13,250-18,000€	8%
Fase 5: Escalado & Optimización	400-500h	20,000-25,000€	9%
TOTAL PROYECTO	3,565-4,760h	178,250-238,000€	100%

Changelog v2.0
•	AÑADIDA: Fase 4 completa - Marketing AI Stack (Sprints 14-18)
•	AÑADIDO: jaraba_email en Sprint 14 (Email Marketing Nativo)
•	AÑADIDO: jaraba_crm en Sprint 15 (CRM Pipeline B2B)
•	AÑADIDO: Paid Ads Integration y Retargeting en Sprint 16
•	AÑADIDO: jaraba_social y Events & Webinars en Sprint 17
•	AÑADIDO: A/B Testing y Referral Program en Sprint 18
•	ACTUALIZADO: Presupuesto total incluye €13,250-18,000 adicionales para Marketing AI
•	REFERENCIA: Documento 149_Marketing_AI_Stack_Completo_v1 para especificaciones detalladas

— Fin del Documento —
Jaraba Impact Platform | Roadmap v2.0 | Enero 2026
