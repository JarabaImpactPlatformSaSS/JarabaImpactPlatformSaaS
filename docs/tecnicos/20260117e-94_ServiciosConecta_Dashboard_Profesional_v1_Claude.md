DASHBOARD PROFESIONAL
Centro de Comando para el Profesional de Servicios
Vista Unificada + Métricas de Productividad + Agenda Inteligente
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	94_ServiciosConecta_Dashboard_Profesional
Dependencias:	Todos los módulos anteriores (82-93)
Usuario:	Profesional individual (abogado, asesor, gestor...)
Prioridad:	ALTA - Punto de entrada diario del profesional
 
1. Resumen Ejecutivo
El Dashboard Profesional es la pantalla de inicio del día para cada profesional. En una sola vista muestra todo lo que necesita saber: citas de hoy, consultas urgentes pendientes, expedientes activos prioritarios, documentos que esperan su acción, métricas de productividad y alertas importantes. El objetivo es que el profesional sepa exactamente qué hacer al empezar su jornada sin tener que navegar por múltiples pantallas.
Este dashboard complementa al Dashboard Admin (doc 95), que ofrece visión agregada del despacho completo. Aquí el foco es el profesional individual: su carga de trabajo, su rendimiento, sus pendientes. La información se actualiza en tiempo real y las alertas son proactivas, no reactivas.
1.1 El Problema: Información Fragmentada
Situación Actual	Problema	Consecuencia
Múltiples herramientas	Calendario en Google, casos en Excel, emails en Outlook	15-20 min cada mañana para saber qué hacer
Sin priorización	Todo parece igual de urgente	Se atiende lo último, no lo importante
Alertas perdidas	Deadlines enterrados en emails o notas	Multas, prescripciones, clientes insatisfechos
Sin visibilidad de carga	No se sabe cuántos casos activos hay	Sobrecarga o infrautilización
Métricas inexistentes	No se mide productividad ni resultados	Imposible mejorar lo que no se mide

1.2 La Solución: Centro de Comando Unificado
•	Vista de hoy: Citas, tareas urgentes, plazos que vencen
•	Consultas entrantes: Triaje automático con urgencia y categoría
•	Expedientes prioritarios: Los que requieren acción inmediata
•	Documentos pendientes: Solicitudes de cliente esperando, entregas por revisar
•	Alertas y deadlines: Plazos críticos, citas sin confirmar, facturas pendientes
•	Métricas personales: Casos abiertos/cerrados, tiempo de respuesta, facturación
•	Copilot integrado: Asistente AI siempre disponible en sidebar
 
2. Estructura del Dashboard
2.1 Layout General
┌─────────────────────────────────────────────────────────────────────────────────┐
│  🏠 Dashboard    📋 Expedientes    📥 Consultas    📅 Agenda    💼 Clientes    │
├─────────────────────────────────────────────────────────────────────────────────┤
│                                                                                 │
│  Buenos días, María                                         Viernes 17 Ene 2026│
│                                                                                 │
│  ┌──────────────────────────────────────────────────────────────────┐          │
│  │  ⚠️ ALERTAS                                                      │          │
│  │  🔴 Caso García: Plazo judicial vence mañana                     │          │
│  │  🟡 3 consultas urgentes sin responder                           │          │
│  └──────────────────────────────────────────────────────────────────┘          │
│                                                                                 │
│  ┌───────────────────────┐  ┌───────────────────────┐  ┌──────────────────────┐│
│  │  📊 MÉTRICAS HOY      │  │  📅 AGENDA HOY        │  │  📥 CONSULTAS        ││
│  │                       │  │                       │  │                      ││
│  │  Casos activos: 23    │  │  09:00 Reunión García │  │  🔴 IVA urgente (5)  ││
│  │  Cerrados mes: 8      │  │  11:30 Cliente nuevo  │  │  🟡 Civil (3)        ││
│  │  Facturable: 4.200€   │  │  16:00 Firma López    │  │  🟢 Laboral (2)      ││
│  │  Resp. media: 2.1h    │  │                       │  │                      ││
│  └───────────────────────┘  └───────────────────────┘  └──────────────────────┘│
│                                                                                 │
│  ┌───────────────────────────────────┐  ┌───────────────────────────────────┐  │
│  │  📋 EXPEDIENTES PRIORITARIOS      │  │  📄 DOCUMENTOS PENDIENTES         │  │
│  │                                   │  │                                   │  │
│  │  🔴 EXP-2026-0142 García (Plazo!) │  │  📤 3 documentos listos entregar  │  │
│  │  🟡 EXP-2026-0138 Martínez (Docs) │  │  📥 5 esperando subir de cliente  │  │
│  │  🟡 EXP-2026-0135 López (Firma)   │  │  ✍️ 2 pendientes de firma         │  │
│  │  [Ver todos los expedientes]      │  │  [Ver todos los documentos]       │  │
│  └───────────────────────────────────┘  └───────────────────────────────────┘  │
│                                                                                 │
└─────────────────────────────────────────────────────────────────────────────────┘
2.2 Widgets Configurables
Widget	Contenido	Actualización
Alertas Críticas	Deadlines <48h, citas sin confirmar, urgencias nivel 5	Tiempo real (WebSocket)
Agenda del Día	Citas de hoy con hora, cliente, tipo, estado	Cada 5 minutos
Consultas Entrantes	Agrupadas por urgencia con triaje automático	Tiempo real
Expedientes Prioritarios	Top 5 por urgencia + motivo de prioridad	Cada 15 minutos
Documentos Pendientes	Por entregar, por recibir, por firmar	Cada 10 minutos
Métricas del Día/Semana	Casos activos, cerrados, facturación, tiempo respuesta	Cada hora
Presupuestos Pendientes	Enviados esperando respuesta, próximos a expirar	Cada 30 minutos
Actividad Reciente	Timeline de últimas acciones del profesional	Tiempo real

 
3. Modelo de Datos
3.1 Entidad: dashboard_config (Configuración Personal)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario propietario	FK users.uid, UNIQUE, NOT NULL
widget_layout	JSON	Disposición de widgets	[{widget_id, position, size, visible}]
alert_preferences	JSON	Preferencias de alertas	{deadline_hours, urgency_threshold...}
metrics_period	VARCHAR(16)	Período de métricas	ENUM: day|week|month
default_view	VARCHAR(32)	Vista por defecto	ENUM: overview|calendar|cases
updated	DATETIME	Última actualización	NOT NULL

3.2 Entidad: provider_alert (Alertas del Profesional)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Profesional destino	FK users.uid, NOT NULL, INDEX
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
alert_type	VARCHAR(32)	Tipo de alerta	ENUM: deadline|inquiry|document|booking|quote|system
severity	VARCHAR(16)	Severidad	ENUM: critical|warning|info
title	VARCHAR(255)	Título de la alerta	NOT NULL
message	TEXT	Mensaje detallado	NOT NULL
related_type	VARCHAR(32)	Tipo de entidad relacionada	case|inquiry|booking|quote|document
related_id	INT	ID de entidad relacionada	NOT NULL
action_url	VARCHAR(255)	URL de acción	NOT NULL
status	VARCHAR(16)	Estado de la alerta	ENUM: active|dismissed|resolved
expires_at	DATETIME	Cuándo expira	NULLABLE
created	DATETIME	Fecha creación	NOT NULL

 
4. Servicios Principales
4.1 DashboardService
<?php namespace Drupal\jaraba_dashboard\Service;

class DashboardService {
  
  public function getDashboardData(int $userId): DashboardData {
    return new DashboardData([
      'alerts' => $this->alertService->getActiveAlerts($userId),
      'today_bookings' => $this->bookingService->getTodayBookings($userId),
      'pending_inquiries' => $this->inquiryService->getPendingByUrgency($userId),
      'priority_cases' => $this->caseService->getPriorityCases($userId, 5),
      'pending_documents' => $this->documentService->getPendingActions($userId),
      'metrics' => $this->metricsService->getProviderMetrics($userId),
      'pending_quotes' => $this->quoteService->getPendingQuotes($userId),
      'recent_activity' => $this->activityService->getRecent($userId, 10),
    ]);
  }
  
  public function getWidgetData(int $userId, string $widgetId): array {
    return match($widgetId) {
      'alerts' => $this->alertService->getActiveAlerts($userId),
      'today_bookings' => $this->bookingService->getTodayBookings($userId),
      'inquiries' => $this->inquiryService->getPendingByUrgency($userId),
      'priority_cases' => $this->caseService->getPriorityCases($userId, 5),
      'documents' => $this->documentService->getPendingActions($userId),
      'metrics' => $this->metricsService->getProviderMetrics($userId),
      'quotes' => $this->quoteService->getPendingQuotes($userId),
      'activity' => $this->activityService->getRecent($userId, 10),
      default => throw new InvalidWidgetException($widgetId),
    };
  }
}

4.2 ProviderMetricsService
<?php namespace Drupal\jaraba_dashboard\Service;

class ProviderMetricsService {
  
  public function getProviderMetrics(int $userId, string $period = 'week'): ProviderMetrics {
    $dateRange = $this->getDateRange($period);
    
    return new ProviderMetrics([
      // Expedientes
      'active_cases' => $this->countActiveCases($userId),
      'cases_opened' => $this->countCasesOpened($userId, $dateRange),
      'cases_closed' => $this->countCasesClosed($userId, $dateRange),
      
      // Consultas
      'inquiries_received' => $this->countInquiriesReceived($userId, $dateRange),
      'inquiries_converted' => $this->countInquiriesConverted($userId, $dateRange),
      'conversion_rate' => $this->calculateConversionRate($userId, $dateRange),
      
      // Tiempos
      'avg_response_time_hours' => $this->avgResponseTime($userId, $dateRange),
      'avg_case_duration_days' => $this->avgCaseDuration($userId, $dateRange),
      
      // Facturación
      'revenue_period' => $this->calculateRevenue($userId, $dateRange),
      'pending_invoices' => $this->sumPendingInvoices($userId),
      'quotes_sent' => $this->countQuotesSent($userId, $dateRange),
      'quotes_accepted' => $this->countQuotesAccepted($userId, $dateRange),
      
      // Citas
      'bookings_completed' => $this->countBookingsCompleted($userId, $dateRange),
      'no_shows' => $this->countNoShows($userId, $dateRange),
    ]);
  }
}

 
4.3 AlertGenerator
<?php namespace Drupal\jaraba_dashboard\Service;

class AlertGenerator {
  
  public function generateAlertsForProvider(int $userId): void {
    // 1. Alertas de deadlines
    $upcomingDeadlines = $this->caseRepository->getUpcomingDeadlines(
      $userId,
      hours: 48 // Próximas 48 horas
    );
    
    foreach ($upcomingDeadlines as $case) {
      $this->createAlert([
        'user_id' => $userId,
        'alert_type' => 'deadline',
        'severity' => $this->calculateDeadlineSeverity($case->getDueDate()),
        'title' => "Plazo próximo: {$case->getTitle()}",
        'message' => "Vence: {$case->getDueDate()->format('d/m H:i')}",
        'related_type' => 'case',
        'related_id' => $case->id(),
        'action_url' => "/cases/{$case->uuid()}",
        'expires_at' => $case->getDueDate(),
      ]);
    }
    
    // 2. Alertas de consultas urgentes
    $urgentInquiries = $this->inquiryRepository->getUrgentUnassigned(
      $userId,
      minUrgency: 4
    );
    
    if (count($urgentInquiries) > 0) {
      $this->createAlert([
        'alert_type' => 'inquiry',
        'severity' => 'warning',
        'title' => count($urgentInquiries) . ' consultas urgentes sin responder',
        'action_url' => '/inquiries?filter=urgent',
      ]);
    }
    
    // 3. Alertas de citas sin confirmar
    $unconfirmedBookings = $this->bookingRepository->getUnconfirmed(
      $userId,
      withinHours: 24
    );
    
    foreach ($unconfirmedBookings as $booking) {
      $this->createAlert([
        'alert_type' => 'booking',
        'severity' => 'warning',
        'title' => "Cita sin confirmar: {$booking->getClientName()}",
        'related_type' => 'booking',
        'related_id' => $booking->id(),
      ]);
    }
    
    // 4. Alertas de documentos pendientes de revisión
    $pendingReview = $this->documentRequestRepository->getPendingReview($userId);
    // ... similar pattern
  }
}

 
5. APIs REST
Método	Endpoint	Descripción	Auth
GET	/api/v1/dashboard	Obtener todos los datos del dashboard	Provider
GET	/api/v1/dashboard/widgets/{id}	Obtener datos de widget específico	Provider
GET	/api/v1/dashboard/alerts	Listar alertas activas	Provider
POST	/api/v1/dashboard/alerts/{id}/dismiss	Descartar alerta	Provider
GET	/api/v1/dashboard/metrics	Métricas del profesional	Provider
GET	/api/v1/dashboard/config	Configuración del dashboard	Provider
PUT	/api/v1/dashboard/config	Actualizar configuración	Provider
GET	/api/v1/dashboard/activity	Timeline de actividad reciente	Provider

6. Actualizaciones en Tiempo Real
El dashboard utiliza WebSockets para actualizaciones en tiempo real de elementos críticos:
Evento	Trigger	Acción en Dashboard
inquiry.created	Nueva consulta entrante asignada al profesional	Actualiza widget consultas + notificación
alert.created	Nueva alerta crítica generada	Añade a widget alertas + sonido si crítica
booking.confirmed	Cliente confirma cita	Actualiza estado en agenda del día
document.uploaded	Cliente sube documento al portal	Actualiza widget documentos pendientes
quote.accepted	Cliente acepta presupuesto	Notificación + actualiza métricas

7. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 10.1	Semana 29	Modelo datos + DashboardService + APIs base	Módulos 82-93
Sprint 10.2	Semana 30	AlertGenerator + ProviderMetricsService + WebSocket	Sprint 10.1
Sprint 10.3	Semana 31	UI Dashboard + widgets configurables + drag-drop	Sprint 10.2
Sprint 10.4	Semana 32	Tiempo real + notificaciones push + tests E2E	Sprint 10.3

7.1 Criterios de Aceptación
•	✓ Dashboard carga en < 2 segundos con todos los widgets
•	✓ Alertas críticas visibles inmediatamente al entrar
•	✓ Agenda del día muestra citas con estado actualizado
•	✓ Métricas calculadas correctamente por período
•	✓ Widgets configurables por el usuario (posición, visibilidad)
•	✓ Actualizaciones en tiempo real funcionando via WebSocket
•	✓ Responsive: funciona en desktop, tablet y móvil

--- Fin del Documento ---
