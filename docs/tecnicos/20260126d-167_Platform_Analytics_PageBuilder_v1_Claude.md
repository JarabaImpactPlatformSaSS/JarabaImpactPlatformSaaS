167
ESPECIFICACIÓN TÉCNICA
Analytics Page Builder
Tracking Bloques | Funnels | Heatmaps | Dashboard
Ecosistema Jaraba | EDI Google Antigravity
Versión:	1.0.0
Fecha:	26 Enero 2026
Horas:	40-50h
 
1. Resumen Ejecutivo
Sistema de analytics específico para páginas del Page Builder con tracking automático de interacciones por bloque y dashboard de rendimiento.
Integraciones
GA4 | Hotjar | Microsoft Clarity | API interna | BigQuery export
1.1 Métricas Clave
•	Views por bloque con tiempo visible
•	CTR de CTAs por posición
•	Scroll depth y puntos de abandono
•	Conversiones por template
•	Heatmaps de clicks
 
2. Eventos Automáticos
 block-events.js
const BLOCK_EVENTS = {
  hero: { view: 'hero_view', cta_click: 'hero_cta_click', video_play: 'hero_video_play' },
  pricing: { view: 'pricing_view', plan_click: 'pricing_plan_click', toggle_annual: 'pricing_toggle' },
  form: { view: 'form_view', submit_success: 'form_submit_success', field_error: 'form_field_error' },
  cta: { view: 'cta_view', button_click: 'cta_button_click' }
};
 
// Evento enviado a GA4
{
  event: 'page_builder_interaction',
  block_type: 'hero',
  block_id: 'hero-123',
  action: 'cta_click',
  page_template: 'landing_empleabilidad',
  tenant_id: 'tenant-456'
}

3. APIs Analytics
Método	Endpoint	Descripción
POST	/api/v1/analytics/events	Recibir eventos (beacon)
GET	/api/v1/analytics/pages/{id}/dashboard	Dashboard página
GET	/api/v1/analytics/pages/{id}/blocks	Rendimiento bloques
GET	/api/v1/analytics/pages/{id}/funnel	Análisis funnel
GET	/api/v1/analytics/pages/{id}/heatmap	Datos heatmap
 
4. Dashboard Response
 dashboard-response.json
{
  "page_id": "landing-main",
  "summary": {
    "total_views": 12450,
    "unique_visitors": 8320,
    "conversion_rate": 4.8,
    "bounce_rate": 32.5
  },
  "block_performance": [
    { "block_id": "hero-1", "views": 12450, "cta_clicks": 890, "ctr": 7.15 },
    { "block_id": "pricing-1", "views": 8900, "plan_clicks": 445 }
  ],
  "scroll_depth": { "25%": 92.3, "50%": 78.5, "75%": 56.2, "100%": 34.8 },
  "recommendations": [
    { "priority": "high", "message": "Bloque pricing tiene alta tasa de salida" }
  ]
}

5. Límites por Plan
Capacidad	Starter	Professional	Enterprise
Retención datos	7 días	90 días	2 años
Eventos/mes	10K	500K	Ilimitados
Heatmaps	—	✓	✓
Recomendaciones IA	—	—	✓
6. Roadmap
Sprint	Componente	Horas
1	Schema eventos + API beacon	18-22h
2	Dashboard UI + GA4 integration	15-18h
3	Heatmaps + Funnels	10-12h
Total: 40-50 horas (€3,200-€4,000)
Fin del documento.
