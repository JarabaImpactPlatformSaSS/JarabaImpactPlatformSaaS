DASHBOARD ADMINISTRADOR
Centro de Control y Analytics del Despacho
KPIs Agregados + Rendimiento por Profesional + Business Intelligence
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	95_ServiciosConecta_Dashboard_Admin
Dependencias:	94_Dashboard_Profesional, todos los módulos anteriores
Usuario:	Gerente/Socio del despacho, Administrador
Prioridad:	ALTA - Visibilidad de negocio para toma de decisiones
 
1. Resumen Ejecutivo
El Dashboard Admin proporciona a los gerentes y socios del despacho una visión agregada del rendimiento del negocio. A diferencia del Dashboard Profesional (doc 94) que se centra en el día a día individual, este dashboard ofrece métricas de negocio, comparativas entre profesionales, análisis de tendencias y herramientas de business intelligence para la toma de decisiones estratégicas.
Este dashboard responde a preguntas críticas: ¿Cuánto estamos facturando? ¿Quién está sobrecargado? ¿Qué tipo de casos son más rentables? ¿Cuál es nuestra tasa de conversión? ¿Cómo evolucionan las métricas mes a mes? Con esta información, el gerente puede optimizar recursos, identificar cuellos de botella y planificar el crecimiento.
1.1 Preguntas Clave del Negocio
Pregunta	Métrica	Visualización
¿Cuánto estamos facturando?	MRR, ingresos por período	Gráfico de línea + KPI card
¿Quién está sobrecargado?	Casos activos por profesional	Heatmap de carga de trabajo
¿Qué casos son más rentables?	Ingresos por categoría	Gráfico de barras + tabla
¿Cuál es nuestra conversión?	Consultas → Casos	Funnel + tasa %
¿Cómo vamos vs mes anterior?	Comparativa MoM	Sparklines + % cambio
¿Cuánto tardamos en responder?	Tiempo medio respuesta	Gauge + tendencia
¿Qué clientes son más valiosos?	LTV por cliente	Top 10 + distribución
¿Dónde perdemos clientes?	Churn, presupuestos rechazados	Análisis de motivos

1.2 Usuarios del Dashboard Admin
Rol	Necesidades	Permisos
Socio/Gerente	Visión 360° del negocio, rentabilidad, estrategia	Acceso completo a todos los datos
Director de área	Rendimiento de su equipo, distribución de carga	Solo datos de su departamento
Responsable admin	Facturación, cobros, gestión operativa	Métricas financieras y operativas
Tenant Owner	Supervisión multi-despacho (si aplica)	Datos agregados de todos los tenants

 
2. Estructura del Dashboard
2.1 Layout General
┌─────────────────────────────────────────────────────────────────────────────────┐
│  📊 Dashboard Admin    👥 Equipo    💰 Facturación    📈 Informes    ⚙️ Config  │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  Despacho García & Asociados                    [Enero 2026 ▼] [Exportar PDF]  │
│                                                                                 │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐                │
│  │ INGRESOS   │  │ CASOS      │  │ CONVERSIÓN │  │ RESP.TIME  │                │
│  │            │  │            │  │            │  │            │                │
│  │  €42,350   │  │    127     │  │   38.5%    │  │   1.8h     │                │
│  │  ▲ +12%    │  │  ▲ +8      │  │  ▲ +2.1%   │  │  ▼ -15min  │                │
│  └────────────┘  └────────────┘  └────────────┘  └────────────┘                │
│                                                                                 │
│  ┌─────────────────────────────────────┐  ┌───────────────────────────────────┐│
│  │  📈 EVOLUCIÓN DE INGRESOS           │  │  👥 CARGA POR PROFESIONAL         ││
│  │                                     │  │                                   ││
│  │     ▄▄▄                             │  │  María G.  ████████████░░ 85%     ││
│  │    ▄████▄      ▄▄                   │  │  Pedro L.  ██████████░░░░ 72%     ││
│  │   ▄██████▄   ▄████▄    ▄▄▄         │  │  Ana M.    ██████░░░░░░░░ 45%     ││
│  │  ▄████████▄▄████████▄▄█████▄       │  │  Carlos R. ████████████████ 98%!  ││
│  │  ───────────────────────────        │  │                                   ││
│  │  Sep  Oct  Nov  Dic  Ene            │  │  ⚠️ Carlos necesita redistribución││
│  └─────────────────────────────────────┘  └───────────────────────────────────┘│
│                                                                                 │
│  ┌─────────────────────────────────────┐  ┌───────────────────────────────────┐│
│  │  🎯 FUNNEL DE CONVERSIÓN            │  │  📊 INGRESOS POR CATEGORÍA        ││
│  │                                     │  │                                   ││
│  │  Consultas    ████████████ 245      │  │  Civil      ████████ €18,200      ││
│  │  Triaje OK    ██████████░░ 198      │  │  Fiscal     ██████░ €12,400       ││
│  │  Presupuesto  ████████░░░░ 156      │  │  Laboral    ████░░░ €8,200        ││
│  │  Aceptado     █████░░░░░░░  94      │  │  Mercantil  ███░░░░ €3,550        ││
│  │                                     │  │                                   ││
│  │  Conversión final: 38.5%            │  │  Total: €42,350                   ││
│  └─────────────────────────────────────┘  └───────────────────────────────────┘│
└─────────────────────────────────────────────────────────────────────────────────┘
 
2.2 Secciones del Dashboard
Sección	Contenido	Frecuencia Actualización
KPIs Principales	Ingresos, casos activos, conversión, tiempo respuesta	Cada hora
Evolución Temporal	Gráficos de línea: ingresos, casos, consultas por período	Diaria
Carga por Profesional	Heatmap de casos activos, alertas de sobrecarga	Cada 15 minutos
Funnel de Conversión	Consulta → Triaje → Presupuesto → Caso	Diaria
Ingresos por Categoría	Distribución de facturación por tipo de servicio	Diaria
Rendimiento Individual	Tabla comparativa de profesionales con métricas	Diaria
Clientes Top	Top 10 por facturación, frecuencia, LTV	Semanal
Alertas Operativas	Casos estancados, presupuestos sin respuesta, cobros	Tiempo real

3. Catálogo de Métricas
3.1 Métricas de Negocio
Métrica	Definición	Fórmula
MRR (Recurrente)	Ingresos mensuales recurrentes (suscripciones)	SUM(suscripciones_activas * precio)
Ingresos Período	Total facturado en el período seleccionado	SUM(facturas.total) WHERE fecha IN período
Ticket Medio	Valor promedio por caso cerrado	Ingresos / Casos cerrados
LTV Cliente	Valor total histórico por cliente	SUM(facturas) por cliente
Tasa Conversión	% de consultas que se convierten en casos	Casos / Consultas * 100
Tasa Aceptación	% de presupuestos aceptados	Aceptados / Enviados * 100
Churn Rate	% de clientes que no vuelven en 12 meses	Clientes inactivos / Total

3.2 Métricas Operativas
Métrica	Definición	Objetivo
Tiempo Primera Respuesta	Tiempo desde consulta hasta primera respuesta	< 2 horas
Duración Media Caso	Días desde apertura hasta cierre	Variable por categoría
Casos Activos/Profesional	Número de casos abiertos por persona	15-25 (óptimo)
Ocupación	% de capacidad utilizada	70-85%
Tasa No-Show	% de citas donde cliente no aparece	< 5%
Docs Pendientes	Documentos solicitados sin recibir > 7 días	< 10%
SLA Cumplimiento	% de plazos internos cumplidos	> 95%

 
4. Modelo de Datos
4.1 Entidad: analytics_snapshot (Snapshots Diarios)
Para optimizar rendimiento, se calculan métricas diarias y se almacenan como snapshots:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
snapshot_date	DATE	Fecha del snapshot	NOT NULL, INDEX
scope	VARCHAR(16)	Alcance	ENUM: tenant|provider|category
scope_id	INT	ID del alcance (provider/category)	NULLABLE
metrics	JSON	Todas las métricas del día	NOT NULL
created	DATETIME	Fecha creación	NOT NULL

4.2 Estructura JSON de Métricas
{
  "business": {
    "revenue_total": 4235000,
    "revenue_invoiced": 3890000,
    "revenue_pending": 345000,
    "mrr": 850000,
    "ticket_average": 125000,
    "quotes_sent": 45,
    "quotes_accepted": 18,
    "quotes_rejected": 12,
    "quotes_pending": 15
  },
  "operations": {
    "cases_active": 127,
    "cases_opened": 23,
    "cases_closed": 15,
    "inquiries_received": 45,
    "inquiries_converted": 18,
    "avg_response_time_minutes": 108,
    "avg_case_duration_days": 32,
    "bookings_scheduled": 34,
    "bookings_completed": 28,
    "bookings_no_show": 2
  },
  "documents": {
    "uploaded_by_client": 67,
    "delivered_to_client": 43,
    "pending_upload": 12,
    "pending_signature": 8
  },
  "clients": {
    "total_active": 89,
    "new_this_period": 12,
    "returning": 6
  }
}

 
5. Servicios Principales
5.1 AdminDashboardService
<?php namespace Drupal\jaraba_admin_dashboard\Service;

class AdminDashboardService {
  
  public function getDashboardData(
    int $tenantId,
    DateRange $period,
    ?int $providerId = null // Filtro opcional por profesional
  ): AdminDashboardData {
    return new AdminDashboardData([
      'kpis' => $this->getKPIs($tenantId, $period, $providerId),
      'revenue_chart' => $this->getRevenueEvolution($tenantId, $period),
      'workload_heatmap' => $this->getWorkloadByProvider($tenantId),
      'conversion_funnel' => $this->getConversionFunnel($tenantId, $period),
      'revenue_by_category' => $this->getRevenueByCategory($tenantId, $period),
      'provider_comparison' => $this->getProviderComparison($tenantId, $period),
      'top_clients' => $this->getTopClients($tenantId, 10),
      'operational_alerts' => $this->getOperationalAlerts($tenantId),
    ]);
  }
  
  private function getKPIs(int $tenantId, DateRange $period, ?int $providerId): array {
    $current = $this->metricsService->calculate($tenantId, $period, $providerId);
    $previous = $this->metricsService->calculate($tenantId, $period->previous(), $providerId);
    
    return [
      'revenue' => [
        'value' => $current['business']['revenue_total'],
        'change' => $this->percentChange(
          $previous['business']['revenue_total'],
          $current['business']['revenue_total']
        ),
        'trend' => 'up',
      ],
      'active_cases' => [
        'value' => $current['operations']['cases_active'],
        'change' => $current['operations']['cases_opened'] - $current['operations']['cases_closed'],
      ],
      'conversion_rate' => [
        'value' => $this->calculateConversionRate($current),
        'change' => $this->calculateConversionRate($current) - $this->calculateConversionRate($previous),
      ],
      'avg_response_time' => [
        'value' => $current['operations']['avg_response_time_minutes'],
        'change' => $previous['operations']['avg_response_time_minutes'] - $current['operations']['avg_response_time_minutes'],
      ],
    ];
  }
}

5.2 AnalyticsSnapshotService (Cron Diario)
<?php namespace Drupal\jaraba_admin_dashboard\Service;

class AnalyticsSnapshotService {
  
  /**
   * Ejecutado por cron cada noche a las 02:00
   */
  public function generateDailySnapshots(): void {
    $yesterday = new \DateTime('yesterday');
    $tenants = $this->tenantRepository->getAllActive();
    
    foreach ($tenants as $tenant) {
      // 1. Snapshot a nivel tenant
      $this->createSnapshot(
        $tenant->id(),
        $yesterday,
        'tenant',
        null,
        $this->calculateMetrics($tenant->id(), $yesterday)
      );
      
      // 2. Snapshot por profesional
      $providers = $this->providerRepository->getByTenant($tenant->id());
      foreach ($providers as $provider) {
        $this->createSnapshot(
          $tenant->id(),
          $yesterday,
          'provider',
          $provider->id(),
          $this->calculateMetrics($tenant->id(), $yesterday, $provider->id())
        );
      }
      
      // 3. Snapshot por categoría
      $categories = $this->categoryRepository->getByTenant($tenant->id());
      foreach ($categories as $category) {
        $this->createSnapshot(
          $tenant->id(),
          $yesterday,
          'category',
          $category->id(),
          $this->calculateMetricsByCategory($tenant->id(), $yesterday, $category->id())
        );
      }
    }
  }
}

 
6. APIs REST
Método	Endpoint	Descripción	Auth
GET	/api/v1/admin/dashboard	Dashboard completo con todos los widgets	Admin
GET	/api/v1/admin/dashboard/kpis	Solo KPIs principales	Admin
GET	/api/v1/admin/analytics/revenue	Evolución de ingresos	Admin
GET	/api/v1/admin/analytics/funnel	Funnel de conversión	Admin
GET	/api/v1/admin/analytics/workload	Carga de trabajo por profesional	Admin
GET	/api/v1/admin/analytics/providers	Comparativa de profesionales	Admin
GET	/api/v1/admin/analytics/categories	Métricas por categoría de servicio	Admin
GET	/api/v1/admin/analytics/clients	Top clientes y análisis	Admin
GET	/api/v1/admin/reports/export	Exportar informe en PDF/Excel	Admin
GET	/api/v1/admin/alerts	Alertas operativas activas	Admin

7. Informes Exportables
Informe	Contenido	Formatos
Resumen Mensual	KPIs, evolución, comparativa MoM, top casos	PDF, Excel
Rendimiento Equipo	Métricas por profesional, ranking, áreas mejora	PDF, Excel
Análisis de Facturación	Ingresos por categoría, cliente, período, cobros pendientes	PDF, Excel
Funnel de Ventas	Conversión por etapa, motivos de pérdida, oportunidades	PDF
Clientes VIP	Top clientes por LTV, frecuencia, satisfacción	PDF, Excel
Alertas y Riesgos	Casos estancados, cobros vencidos, plazos críticos	PDF

8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 11.1	Semana 33	Modelo datos + AnalyticsSnapshotService + cron	94_Dashboard_Profesional
Sprint 11.2	Semana 34	AdminDashboardService + APIs + cálculo de métricas	Sprint 11.1
Sprint 11.3	Semana 35	UI Dashboard + gráficos (Chart.js/Recharts) + filtros	Sprint 11.2
Sprint 11.4	Semana 36	Sistema de informes + exportación PDF/Excel + tests	Sprint 11.3

8.1 Criterios de Aceptación
•	✓ Dashboard carga en < 3 segundos con todos los widgets
•	✓ KPIs muestran comparativa con período anterior
•	✓ Gráficos interactivos con tooltips y drill-down
•	✓ Filtros por período, profesional y categoría funcionan
•	✓ Snapshots diarios se generan automáticamente a las 02:00
•	✓ Exportación PDF genera documento profesional
•	✓ Permisos RBAC: solo admin/gerente accede al dashboard

--- Fin del Documento ---
