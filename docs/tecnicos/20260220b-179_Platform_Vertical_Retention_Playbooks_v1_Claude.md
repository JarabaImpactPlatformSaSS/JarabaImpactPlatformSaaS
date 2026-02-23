PLAYBOOKS DE RETENCIÓN VERTICALIZADOS
Estrategias de Retención Adaptadas por Vertical y Estacionalidad
Especificación Técnica para Implementación
JARABA IMPACT PLATFORM

Parámetro	Valor
Versión:	1.0
Fecha:	Febrero 2026
Código:	179_Platform_Vertical_Retention_Playbooks_v1
Estado:	Especificación para EDI Google Antigravity / Claude Code
Dependencias:	113_Customer_Success, 145_ActiveCampaign, jaraba_dunning, ECA, FOC
Prioridad:	ALTA - Reducción de churn verticalizado del 15-25%
 
1. Resumen Ejecutivo
Los playbooks genéricos de retención (Doc 113) tratan a todos los tenants igual, pero un productor agrícola de AgroConecta tiene patrones de estacionalidad radicalmente diferentes a un despacho profesional de ServiciosConecta. Este documento especifica playbooks de retención adaptados a cada vertical, incorporando estacionalidad económica, ciclos de uso específicos y señales de churn propias de cada sector.
La implementación extiende el sistema de Customer Success (jaraba_success) y el motor ECA existente, añadiendo reglas verticalizadas que se activan según el vertical_id del tenant.
1.1 Impacto Esperado por Vertical
Vertical	Churn Actual Estimado	Target Post-Playbook	Reducción Esperada	Revenue Protegido/año
AgroConecta	12-15% anual	< 8%	-35%	€18K-25K
ComercioConecta	10-12% anual	< 7%	-30%	€22K-30K
ServiciosConecta	8-10% anual	< 5%	-40%	€15K-20K
Empleabilidad	15-20% anual	< 10%	-35%	€20K-28K
Emprendimiento	18-25% anual	< 12%	-40%	€12K-18K
2. Modelo de Datos
2.1 Entidad: vertical_retention_profile
Configuración de retención específica por vertical, incluyendo estacionalidad, señales de churn y umbrales personalizados.
Campo	Tipo	Descripción
id	UUID	Identificador único
vertical_id	INT FK	Vertical (taxonomy_term business_verticals)
seasonality_calendar	JSON	Mapa mensual de actividad esperada (0-100 por mes)
churn_risk_signals	JSON	Señales de riesgo específicas del vertical con pesos
health_score_weights	JSON	Override de pesos del Health Score para este vertical
critical_features	JSON	Features cuyo desuso indica churn inminente
reengagement_triggers	JSON	Eventos que deben disparar reengagement
upsell_signals	JSON	Señales de oportunidad de expansión
seasonal_offers	JSON	Ofertas especiales por temporada
expected_usage_pattern	JSON	Patrón de uso típico semanal/mensual
max_inactivity_days	INT	Días sin actividad antes de alerta (varía por vertical)
playbook_overrides	JSON	Customizaciones de los playbooks genéricos
2.2 Entidad: seasonal_churn_prediction
Predicciones de churn ajustadas por estacionalidad vertical.
Campo	Tipo	Descripción
id	UUID	Identificador único
tenant_id	UUID FK	Tenant evaluado
vertical_id	INT FK	Vertical del tenant
prediction_month	DATE	Mes para el que se predice
base_churn_probability	DECIMAL(3,2)	Probabilidad base del modelo genérico
seasonal_adjustment	DECIMAL(3,2)	Ajuste estacional (-0.5 a +0.5)
adjusted_probability	DECIMAL(3,2)	Probabilidad final ajustada
seasonal_context	VARCHAR(64)	Contexto: post_harvest, holiday_season, back_to_school, etc.
recommended_playbook	VARCHAR(64)	ID del playbook recomendado
intervention_urgency	ENUM	low|medium|high|critical
 
3. Vertical AgroConecta
Avatar: Marta (productora agrícola). Sector con alta estacionalidad vinculada a ciclos de cosecha, ferias agrícolas y campañas de venta estacionales.
3.1 Calendario de Estacionalidad
Mes	Actividad Esperada	Riesgo Churn	Acción Preventiva
Ene-Feb	BAJA (post-campaña navideña)	🟠 ALTO	Ofrecer planes reducidos de temporada baja. Formación en preparación de catálogo.
Mar-Abr	MEDIA (preparación primavera)	🟡 MEDIO	Activar features de planificación de cosecha y preventa.
May-Jul	ALTA (temporada principal)	🟢 BAJO	Maximizar features de venta. Upsell logística y promoción.
Ago-Sep	ALTA (segunda cosecha)	🟢 BAJO	Cross-sell trazabilidad. Preparar campaña otoño.
Oct-Nov	MEDIA-ALTA (aceite, vino, frutos secos)	🟡 MEDIO	Promover suscripciones de temporada. Features navideñas.
Dic	ALTA (campaña navideña)	🟢 BAJO	Cestas navideñas, packs regalo. Maximizar GMV.
3.2 Señales de Churn Específicas
Señal	Peso	Detección	Acción Inmediata
0 productos publicados en 30 días	ALTO (30%)	Query: product_catalog WHERE tenant AND updated < 30d	Email: Ayuda para actualizar catálogo + sesión 1:1
0 pedidos recibidos en 45 días	ALTO (25%)	Query: orders WHERE tenant AND created < 45d	Análisis de visibilidad + sugerencias SEO local
Descenso >50% en GMV MoM	MEDIO (20%)	FOC: gmv_trend < -50%	Llamada CSM + revisión de precios/promoción
No usa trazabilidad (si activa)	BAJO (10%)	Feature tracking: traceability_usage = 0	Training de trazabilidad + valor diferencial
Sin fotos de producto actualizadas	MEDIO (15%)	Query: product_images WHERE updated < 90d	Workshop fotografía de producto con móvil
3.3 Playbook: Retención Temporada Baja Agro
Día	Acción	Canal	Contenido
D+0	Detectar inicio temporada baja del productor	Sistema	Trigger: actividad < 30% del pico AND mes en [Ene,Feb]
D+1	Email educativo	Email	Prepara tu catálogo para la próxima temporada. Guía de fotografía.
D+5	Ofrecer plan estacional	Email + In-App	Plan Invernadero: 50% dto en meses de baja actividad. Sin compromiso.
D+10	Webinar grupal	Email	Invitación a webinar: Cómo maximizar ventas online para la próxima cosecha.
D+20	Check-in personalizado	Llamada/WhatsApp	CSM contacta para revisar plan de temporada.
D+30	Oferta de continuidad	Email	Mantente activo: 3 meses al 40% + setup gratuito de campaña primavera.
 
4. Vertical ComercioConecta
Avatar: Carlos (comerciante local). Sector con estacionalidad vinculada a rebajas, campañas festivas y eventos locales.
4.1 Calendario de Estacionalidad
Mes	Actividad Esperada	Riesgo Churn	Acción Preventiva
Ene	ALTA (rebajas de invierno)	🟢 BAJO	Maximizar Flash Offers y QR dinámico.
Feb-Mar	BAJA (post-rebajas)	🟠 ALTO	Training en fidelización. Activar cupones recurrentes.
Abr-May	MEDIA (primavera, Día de la Madre)	🟡 MEDIO	Templates de campañas estacionales precargados.
Jun-Jul	MEDIA-ALTA (rebajas verano + turismo)	🟢 BAJO	Activar features multiidioma para turistas.
Ago	BAJA (cierre estival zonas rurales)	🟠 ALTO	Plan vacacional: congelar suscripción sin penalización.
Sep-Oct	MEDIA (vuelta al cole, otoño)	🟡 MEDIO	Reactivación con nuevas features. Preparar Black Friday.
Nov	ALTA (Black Friday, pre-Navidad)	🟢 BAJO	Kit Black Friday preconfigurado. Upsell promoción premium.
Dic	MUY ALTA (Navidad)	🟢 BAJO	Maximizar todas las features. Soporte extendido.
4.2 Señales de Churn Específicas
Señal	Peso	Detección
0 Flash Offers creadas en 30 días	ALTO (25%)	Feature tracking: flash_offers_created = 0 (30d)
QR dinámico no escaneado en 30 días	ALTO (25%)	Analytics: qr_scans = 0 (30d)
Sin transacciones POS en 21 días	ALTO (30%)	Stripe/POS: transactions = 0 (21d)
No actualiza productos en 45 días	MEDIO (10%)	Query: products WHERE updated < 45d
No usa local SEO tools	BAJO (10%)	Feature tracking: seo_tools_usage = 0 (60d)
4.3 Playbook: Retención Comercio Post-Rebajas
Día	Acción	Canal	Contenido
D+0	Detectar caída post-rebajas	Sistema	GMV < 40% del pico de rebajas AND mes en [Feb,Mar]
D+2	Email de transición	Email	Las rebajas terminaron, tu tienda online no. 5 ideas para vender en febrero.
D+5	Activar programa fidelización	In-App	Configurar automáticamente cupones de fidelidad para clientes recurrentes.
D+10	Workshop grupal	Email	Webinar: Crea tu calendario comercial anual en 1 hora.
D+15	Ofrecer pausa inteligente	Email	Plan Siesta: reduce tu plan 2 meses sin perder datos ni posicionamiento.
D+25	Caso de éxito local	Email	Cómo [Comercio similar] factura €2K/mes en temporada baja.
 
5. Vertical ServiciosConecta
Avatar: Elena (profesional de servicios - abogada, consultora, fisioterapeuta). Sector con menor estacionalidad pero alta sensibilidad a la relación calidad-precio y al ROI percibido.
5.1 Señales de Churn Específicas
Señal	Peso	Acción
0 reservas en 21 días (Booking Engine)	ALTO (30%)	Revisar configuración agenda + visibilidad SEO local
0 presupuestos enviados en 30 días	ALTO (25%)	Training del presupuestador automático + templates
Buzón de Confianza sin responder >7 días	MEDIO (15%)	Alerta: mensajes de clientes sin responder
Sin videoconsultas en 30 días (si activo)	MEDIO (15%)	Promover videoconsulta como canal alternativo
Firma digital no utilizada en 45 días	BAJO (10%)	Recordar beneficios de firma PAdES integrada
Dashboard profesional sin visitar en 14 días	BAJO (5%)	Email: Tus métricas del mes te esperan
5.2 Playbook: Retención Profesional ROI-Driven
Los profesionales de servicios cancelan cuando no perciben ROI. Este playbook demuestra valor con datos concretos.
Día	Acción	Canal	Contenido
D+0	Health Score < 60 detectado	Sistema	Trigger: combinación de señales verticales
D+1	Email de valor	Email	Tu informe de impacto: X reservas, X€ facturado, X horas ahorradas con Jaraba.
D+3	ROI Calculator	In-App	Mostrar widget: Sin Jaraba estarías perdiendo ~X€/mes en eficiencia.
D+7	Llamada CSM	Llamada	Revisar uso actual. Identificar features no adoptadas con alto impacto.
D+10	Feature spotlight	Email	Descubre: El presupuestador automático ahorra 3h/semana a profesionales como tú.
D+14	Oferta de extensión	Email	Prueba Plan Pro 30 días gratis para experimentar el valor completo.
D+21	Testimonial relevante	Email	Cómo Elena (abogada, Córdoba) triplica su agenda con Jaraba.
 
6. Vertical Empleabilidad
Avatares: Lucía (buscadora de empleo), Empleadores (empresas que publican vacantes). Alto churn natural: los buscadores exitosos se van porque encontraron empleo. Los empleadores se van tras cubrir vacantes.
6.1 Estacionalidad del Mercado Laboral
Período	Actividad	Tipo de Churn	Estrategia
Ene-Mar	ALTA (nuevos propósitos, presupuestos)	Bajo churn nuevos, alto churn post-colocación Q4	Reactivar buscadores. Captar nuevos empleadores con presupuesto nuevo.
Abr-Jun	MEDIA-ALTA	Moderado	Upsell formación complementaria a buscadores activos.
Jul-Ago	BAJA (vacaciones)	Alto churn empleadores	Plan Verano: congelar publicaciones. Formación online para buscadores.
Sep-Oct	MUY ALTA (vuelta contratación)	Bajo	Maximizar matching. Captar nuevos empleadores.
Nov-Dic	ALTA (contratos temporales navideños)	Bajo empleadores, alto buscadores post-colocación	Ofrecer upskilling post-contratación. Retener con formación.
6.2 Señales de Churn por Avatar
Job Seeker (Lucía)
Señal	Interpretación	Acción
Completa perfil al 100% pero deja de aplicar	Puede haber encontrado empleo fuera de plataforma	Encuesta: ¿Has encontrado empleo? Si sí: celebrar + ofrecer upskilling
0 logins en 14 días	Abandono o empleo encontrado	Email: Nuevas ofertas matching tu perfil (personalizado)
Rechaza >5 ofertas sugeridas	Matching desalineado	Recalibrar matching engine. Ofrecer revisión de perfil.
Completa curso pero no aplica a vacantes	Gap de confianza	Ofrecer sesión mentoría 1:1 + simulación entrevista
Employer
Señal	Interpretación	Acción
0 vacantes publicadas en 30 días	No necesita contratar actualmente	Ofrecer modo standby con acceso a base de candidatos
Vacante cubierta + no publica nueva	Necesidad puntual satisfecha	Ofrecer plan talent pipeline para mantener reserva de candidatos
Rechaza >10 candidatos consecutivos	Quality mismatch	Revisar criterios de matching. Ofrecer screening avanzado.
No usa dashboard de empleador	No percibe valor analítico	Email: Tu panel de talento tiene insights nuevos
6.3 Playbook: Retención Post-Colocación
El desafío único de Empleabilidad: el éxito del usuario (encontrar empleo) causa churn. La estrategia es transformar al job seeker exitoso en un usuario de upskilling.
Día	Acción	Canal	Contenido
D+0	Detectar colocación exitosa	Sistema	Trigger: application.status = hired OR encuesta confirma empleo
D+1	Celebración	Email + In-App	¡Enhorabuena! Tu esfuerzo ha dado fruto. Certificado de completitud.
D+3	Transición a upskilling	Email	Tu nuevo trabajo es el inicio. Cursos de desarrollo profesional para crecer.
D+7	Oferta alumni	Email	Plan Alumni: acceso a formación continua al 60% + red de contactos.
D+14	Referral incentive	Email	¿Conoces a alguien buscando empleo? Reférelo y ambos obtenéis 1 mes gratis.
D+30	Check-in post-empleo	Email	¿Cómo va tu primer mes? Recursos para superar el período de prueba.
 
7. Vertical Emprendimiento
Avatar: Javier (emprendedor en fase early-stage). Vertical con mayor churn natural: muchos emprendimientos pivotan, abandonan o simplemente dejan de usar herramientas digitales cuando se quedan sin financiación.
7.1 Señales de Churn Específicas
Señal	Peso	Acción
Business Model Canvas no actualizado en 60 días	ALTO (25%)	Recordar importancia de iterar. Ofrecer sesión de pivoteo.
0 sesiones de mentoría en 30 días	ALTO (20%)	Sugerir mentores específicos basados en fase actual.
Proyecciones financieras no completadas	MEDIO (15%)	Workshop: Cómo hacer proyecciones realistas en 1 hora.
Diagnostic no completado o score < 30	ALTO (20%)	Ofrecer acompañamiento personalizado para mejorar score.
0 participación en grupos de colaboración	MEDIO (10%)	Invitar a grupo temático específico de su sector.
Sin avance en milestones en 45 días	MEDIO (10%)	Email: Roadmap simplificado para tu fase actual.
7.2 Playbook: Retención por Fase de Emprendimiento
Fase	Duración Típica	Riesgo Churn	Estrategia de Retención
Ideación	1-3 meses	MUY ALTO (40%)	Acelerar al Aha! moment: completar BMC + primera validación.
Validación	2-4 meses	ALTO (25%)	Mostrar progreso tangible. Conectar con mentores de validación.
MVP	3-6 meses	MEDIO (15%)	Soporte técnico activo. Digital Kits para prototipado rápido.
Tracción	6-12 meses	BAJO (8%)	Upsell herramientas de crecimiento. Networking con inversores.
Escalado	12+ meses	MUY BAJO (3%)	Cross-sell a otras verticales. Oferta de membresía premium.
 
8. Implementación Técnica
8.1 Servicio de Retención Verticalizado
<?php
namespace Drupal\jaraba_success\Service;

class VerticalRetentionService {

  public function evaluateTenantRisk(int $tenantId): array {
    $tenant = $this->tenantManager->load($tenantId);
    $vertical = $tenant->getVertical();
    $profile = $this->getRetentionProfile($vertical->id());

    // 1. Obtener health score genérico
    $baseScore = $this->healthScoreService->calculate($tenantId);

    // 2. Aplicar pesos verticalizados
    $verticalScore = $this->applyVerticalWeights(
      $baseScore, $profile->getHealthScoreWeights()
    );

    // 3. Ajustar por estacionalidad
    $currentMonth = (int) date('n');
    $seasonality = $profile->getSeasonalityCalendar();
    $expectedActivity = $seasonality[$currentMonth] ?? 50;
    $actualActivity = $this->getActivityLevel($tenantId);

    // Si la actividad baja es esperada estacionalmente, reducir riesgo
    $seasonalAdjustment = 0;
    if ($expectedActivity < 40 && $actualActivity < 30) {
      $seasonalAdjustment = +15; // Es normal, menos riesgo
    }

    // 4. Evaluar señales específicas del vertical
    $verticalSignals = $this->evaluateVerticalSignals(
      $tenantId, $profile->getChurnRiskSignals()
    );

    $finalScore = min(100, max(0,
      $verticalScore + $seasonalAdjustment - $verticalSignals['risk_penalty']
    ));

    return [
      'score' => $finalScore,
      'vertical' => $vertical->id(),
      'seasonal_context' => $this->getSeasonalContext($vertical->id(), $currentMonth),
      'signals' => $verticalSignals,
      'recommended_playbook' => $this->selectPlaybook($finalScore, $vertical->id(), $verticalSignals),
    ];
  }
}
8.2 ECA Flow: Retención Verticalizada
# config/eca/eca.model.vertical_retention.yml
id: vertical_retention_check
label: 'Vertical Retention: Daily Risk Assessment'
status: true
events:
  - plugin: 'eca_cron:cron'
    settings:
      frequency: 'daily'
      time: '03:00'
actions:
  - plugin: 'jaraba_success:evaluate_all_tenants'
    result_key: 'evaluations'
  - plugin: 'eca:foreach'
    settings:
      items: '[evaluations:at_risk]'
      item_key: 'eval'
      actions:
        - plugin: 'jaraba_success:execute_vertical_playbook'
          settings:
            tenant_id: '[eval:tenant_id]'
            playbook_id: '[eval:recommended_playbook]'
            context:
              vertical: '[eval:vertical]'
              seasonal_context: '[eval:seasonal_context]'
              signals: '[eval:signals]'
 
9. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/retention/profiles	Listar perfiles de retención por vertical
GET	/api/v1/retention/profiles/{vertical_id}	Perfil de retención de vertical específico
PUT	/api/v1/retention/profiles/{vertical_id}	Actualizar configuración de retención
GET	/api/v1/retention/risk-assessment/{tenant_id}	Evaluación de riesgo verticalizada del tenant
GET	/api/v1/retention/seasonal-predictions	Predicciones de churn estacional por vertical
GET	/api/v1/retention/playbook-executions	Historial de ejecuciones de playbooks
POST	/api/v1/retention/playbook-executions/{id}/override	Override manual de playbook por CSM
10. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidades BD. Vertical retention profiles para las 5 verticales. Servicio base.
Sprint 2	Semana 3-4	Health Score verticalizado con pesos por vertical. Ajuste estacional.
Sprint 3	Semana 5-6	Playbooks AgroConecta y ComercioConecta completos. Templates email.
Sprint 4	Semana 7-8	Playbooks ServiciosConecta y Empleabilidad. Integración ActiveCampaign.
Sprint 5	Semana 9-10	Playbook Emprendimiento por fases. Seasonal churn predictions.
Sprint 6	Semana 11-12	ECA flows verticalizados. Dashboard de retención por vertical en FOC. Go-live.
10.1 Estimación de Esfuerzo
Componente	Horas Estimadas
Entidades BD y perfiles verticales	25-35h
Health Score verticalizado + estacionalidad	35-50h
Playbook AgroConecta (templates + lógica)	20-30h
Playbook ComercioConecta	20-30h
Playbook ServiciosConecta	20-30h
Playbook Empleabilidad (2 avatares)	30-40h
Playbook Emprendimiento (por fases)	25-35h
ECA flows verticalizados	25-35h
Integración ActiveCampaign (5 secuencias)	20-30h
Dashboard FOC retención vertical	20-30h
Testing & QA	25-35h
TOTAL	265-380h
--- Fin del Documento ---
