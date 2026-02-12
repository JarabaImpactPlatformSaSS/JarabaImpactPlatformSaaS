SISTEMA DE RESEÑAS Y VALORACIONES
Feedback de Clientes y Reputación del Profesional
Solicitud Automática + Moderación + Publicación + Widgets
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	97_ServiciosConecta_Reviews_Ratings
Dependencias:	82_Services_Core, 96_Sistema_Facturacion
Integraciones:	Google Business Profile, Schema.org, Widgets embebibles
Prioridad:	MEDIA - Reputación y conversión
 
1. Resumen Ejecutivo
El Sistema de Reseñas y Valoraciones permite recoger feedback estructurado de los clientes al finalizar un caso o servicio. Las reseñas verificadas (solo de clientes reales que han contratado) construyen la reputación online del profesional y del despacho, mejoran el SEO local, y proporcionan social proof para la conversión de nuevos clientes.
El sistema solicita automáticamente la reseña en el momento óptimo (tras cierre satisfactorio del caso), ofrece un formulario simple y rápido, permite moderación antes de publicar, y genera widgets embebibles para mostrar las valoraciones en la web del despacho. Además, facilita la sincronización con Google Business Profile para potenciar el SEO local.
1.1 El Problema: Reseñas Desaprovechadas
Situación Actual	Problema	Consecuencia
No se piden reseñas	El profesional olvida o le da vergüenza pedir	Clientes satisfechos no dejan testimonio
Momento inadecuado	Se pide cuando el cliente está ocupado o molesto	Baja tasa de respuesta o reseñas negativas
Sin verificación	Cualquiera puede dejar reseña (incluida competencia)	Reseñas falsas, pérdida de credibilidad
Dispersión	Reseñas en Google, Facebook, sin consolidar	Difícil gestionar reputación
Sin aprovechamiento	Las buenas reseñas no se muestran en la web	Se pierde social proof para conversión

1.2 La Solución: Sistema Automatizado de Reputación
•	Solicitud automática: Email/SMS en el momento óptimo (caso cerrado + factura pagada)
•	Reseñas verificadas: Solo clientes reales con caso cerrado pueden valorar
•	Formulario optimizado: 3 clics para valorar, comentario opcional
•	Moderación: El profesional revisa antes de publicar (sin censurar negativos)
•	Respuesta a reseñas: El profesional puede responder públicamente
•	Widgets: Mostrar valoraciones en web del despacho con Schema.org
•	Sincronización Google: Invitar a dejar reseña también en Google Business
1.3 Flujo de Reseñas
┌─────────────────────────────────────────────────────────────────────────┐
│                    FLUJO DE RESEÑAS                                     │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │    Caso      │───▶│   Factura    │───▶│  Solicitud   │              │
│  │   Cerrado    │    │   Pagada     │    │  Automática  │              │
│  └──────────────┘    └──────────────┘    └───────┬──────┘              │
│                                                   │                     │
│                                                   ▼                     │
│                                        ┌──────────────────┐             │
│                                        │ Cliente recibe   │             │
│                                        │ email con link   │             │
│                                        └────────┬─────────┘             │
│                                                 │                       │
│                                                 ▼                       │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │   Cliente    │───▶│  Moderación  │───▶│  Publicada   │              │
│  │   Valora     │    │  (opcional)  │    │  + Respuesta │              │
│  └──────────────┘    └──────────────┘    └──────────────┘              │
│                                                   │                     │
│                                                   ▼                     │
│                                        ┌──────────────────┐             │
│                                        │ Widget en web +  │             │
│                                        │ Invitar Google   │             │
│                                        └──────────────────┘             │
└─────────────────────────────────────────────────────────────────────────┘
 
2. Modelo de Datos
2.1 Entidad: review (Reseña)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
provider_id	INT	Profesional valorado	FK users.uid, NOT NULL, INDEX
case_id	INT	Caso relacionado	FK client_case.id, NOT NULL
client_id	INT	Cliente que valora	FK client_profile.id, NOT NULL
client_display_name	VARCHAR(100)	Nombre a mostrar	Ej: 'María G.' o 'Anónimo'
overall_rating	TINYINT	Valoración global (1-5)	NOT NULL, CHECK 1-5
communication_rating	TINYINT	Comunicación (1-5)	NULLABLE
professionalism_rating	TINYINT	Profesionalidad (1-5)	NULLABLE
value_rating	TINYINT	Relación calidad/precio	NULLABLE
title	VARCHAR(200)	Título de la reseña	NULLABLE
comment	TEXT	Comentario del cliente	NULLABLE
service_category	VARCHAR(64)	Categoría del servicio	Ej: 'civil', 'fiscal'...
would_recommend	BOOLEAN	¿Recomendaría?	DEFAULT TRUE
status	VARCHAR(16)	Estado de la reseña	pending|published|hidden|flagged
verified	BOOLEAN	Cliente verificado	DEFAULT TRUE (solo reales)
provider_response	TEXT	Respuesta del profesional	NULLABLE
response_at	DATETIME	Fecha de respuesta	NULLABLE
published_at	DATETIME	Fecha de publicación	NULLABLE
created	DATETIME	Fecha creación	NOT NULL

2.2 Entidad: review_request (Solicitud de Reseña)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Token único para el link	UNIQUE, NOT NULL
case_id	INT	Caso relacionado	FK client_case.id, NOT NULL
client_id	INT	Cliente a solicitar	FK client_profile.id, NOT NULL
provider_id	INT	Profesional a valorar	FK users.uid, NOT NULL
channel	VARCHAR(16)	Canal de envío	email|sms|whatsapp
sent_at	DATETIME	Cuándo se envió	NULLABLE
opened_at	DATETIME	Cuándo se abrió el link	NULLABLE
completed_at	DATETIME	Cuándo se completó	NULLABLE
review_id	INT	Reseña creada	FK review.id, NULLABLE
reminder_count	INT	Recordatorios enviados	DEFAULT 0, MAX 2
expires_at	DATETIME	Expiración del link	NOT NULL (30 días)
status	VARCHAR(16)	Estado	pending|sent|opened|completed|expired
created	DATETIME	Fecha creación	NOT NULL

 
2.3 Entidad: provider_rating_summary (Resumen de Valoraciones)
Tabla desnormalizada para consultas rápidas de rating agregado:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
provider_id	INT	Profesional	FK users.uid, UNIQUE
tenant_id	INT	Tenant	FK tenant.id, NOT NULL
total_reviews	INT	Total reseñas publicadas	DEFAULT 0
average_rating	DECIMAL(3,2)	Media global	1.00 - 5.00
avg_communication	DECIMAL(3,2)	Media comunicación	NULLABLE
avg_professionalism	DECIMAL(3,2)	Media profesionalidad	NULLABLE
avg_value	DECIMAL(3,2)	Media relación calidad/precio	NULLABLE
recommend_percent	DECIMAL(5,2)	% que recomendaría	0-100
rating_distribution	JSON	Distribución por estrellas	{1: 2, 2: 1, 3: 5, 4: 20, 5: 45}
last_review_at	DATETIME	Última reseña	NULLABLE
updated	DATETIME	Última actualización	NOT NULL

3. Servicios Principales
3.1 ReviewService
<?php namespace Drupal\jaraba_reviews\Service;

class ReviewService {
  
  public function createReview(ReviewRequest $request, array $data): Review {
    // Validar que el request no ha expirado ni completado
    if ($request->getStatus() !== 'opened') {
      throw new InvalidRequestException('Solicitud no válida');
    }
    
    $review = Review::create([
      'tenant_id' => $request->getCase()->getTenantId(),
      'provider_id' => $request->getProviderId(),
      'case_id' => $request->getCaseId(),
      'client_id' => $request->getClientId(),
      'client_display_name' => $this->formatDisplayName($request->getClient()),
      'overall_rating' => $data['overall_rating'],
      'communication_rating' => $data['communication_rating'] ?? null,
      'professionalism_rating' => $data['professionalism_rating'] ?? null,
      'value_rating' => $data['value_rating'] ?? null,
      'title' => $data['title'] ?? null,
      'comment' => $data['comment'] ?? null,
      'service_category' => $request->getCase()->getCategory()->getMachineName(),
      'would_recommend' => $data['would_recommend'] ?? true,
      'status' => $this->determineInitialStatus($data),
      'verified' => true, // Siempre verificado (cliente real)
    ]);
    
    // Marcar request como completado
    $request->setStatus('completed');
    $request->setCompletedAt(new \DateTime());
    $request->setReviewId($review->id());
    $request->save();
    
    // Actualizar summary del profesional
    $this->ratingSummaryService->recalculate($request->getProviderId());
    
    $this->eventDispatcher->dispatch(new ReviewCreatedEvent($review));
    return $review;
  }
  
  public function publish(Review $review): Review {
    $review->setStatus('published');
    $review->setPublishedAt(new \DateTime());
    $review->save();
    
    $this->ratingSummaryService->recalculate($review->getProviderId());
    $this->eventDispatcher->dispatch(new ReviewPublishedEvent($review));
    
    return $review;
  }
  
  public function addResponse(Review $review, string $response): Review {
    $review->setProviderResponse($response);
    $review->setResponseAt(new \DateTime());
    $review->save();
    
    // Notificar al cliente que el profesional respondió
    $this->notificationService->notifyReviewResponse($review);
    
    return $review;
  }
  
  private function determineInitialStatus(array $data): string {
    // Auto-publicar si >=4 estrellas, moderar si <4
    if ($data['overall_rating'] >= 4) {
      return 'published';
    }
    return 'pending'; // Requiere revisión
  }
}

 
3.2 ReviewRequestService
<?php namespace Drupal\jaraba_reviews\Service;

class ReviewRequestService {
  
  public function createAndSend(ClientCase $case): ?ReviewRequest {
    // Verificar que no existe ya una solicitud para este caso
    if ($this->repository->existsForCase($case->id())) {
      return null;
    }
    
    $request = ReviewRequest::create([
      'case_id' => $case->id(),
      'client_id' => $case->getClientId(),
      'provider_id' => $case->getProviderId(),
      'channel' => $this->determineChannel($case->getClient()),
      'expires_at' => (new \DateTime())->modify('+30 days'),
      'status' => 'pending',
    ]);
    
    // Enviar inmediatamente
    $this->send($request);
    
    return $request;
  }
  
  public function send(ReviewRequest $request): void {
    $reviewUrl = $this->generateReviewUrl($request);
    
    switch ($request->getChannel()) {
      case 'email':
        $this->mailer->sendReviewRequest($request, $reviewUrl);
        break;
      case 'sms':
        $this->smsService->sendReviewRequest($request, $reviewUrl);
        break;
      case 'whatsapp':
        $this->whatsappService->sendReviewRequest($request, $reviewUrl);
        break;
    }
    
    $request->setStatus('sent');
    $request->setSentAt(new \DateTime());
    $request->save();
  }
  
  public function sendReminder(ReviewRequest $request): void {
    if ($request->getReminderCount() >= 2) {
      return; // Máximo 2 recordatorios
    }
    
    $this->send($request);
    $request->setReminderCount($request->getReminderCount() + 1);
    $request->save();
  }
  
  private function generateReviewUrl(ReviewRequest $request): string {
    return $this->urlGenerator->generate(
      'jaraba_reviews.submit',
      ['token' => $request->uuid()],
      UrlGeneratorInterface::ABSOLUTE_URL
    );
  }
}

 
4. APIs REST
Método	Endpoint	Descripción	Auth
GET	/api/v1/reviews/submit/{token}	Obtener formulario de reseña (público)	Token
POST	/api/v1/reviews/submit/{token}	Enviar reseña	Token
GET	/api/v1/reviews	Listar reseñas del tenant	Provider
GET	/api/v1/reviews/{uuid}	Detalle de reseña	Provider
POST	/api/v1/reviews/{uuid}/publish	Publicar reseña pendiente	Provider
POST	/api/v1/reviews/{uuid}/hide	Ocultar reseña (con motivo)	Admin
POST	/api/v1/reviews/{uuid}/respond	Añadir respuesta del profesional	Provider
GET	/api/v1/providers/{id}/reviews	Reseñas públicas de un profesional	Public
GET	/api/v1/providers/{id}/rating-summary	Resumen de valoraciones	Public
GET	/api/v1/reviews/widget/{provider_id}	Datos para widget embebible	Public

5. Widgets y SEO
5.1 Widget Embebible
Código que el despacho puede insertar en su web:
<!-- Widget de Valoraciones Jaraba -->
<div id="jaraba-reviews-widget"
     data-provider-id="abc123"
     data-theme="light"
     data-max-reviews="5">
</div>
<script src="https://platform.jaraba.es/widgets/reviews.js"></script>

5.2 Schema.org para Rich Snippets
{
  "@context": "https://schema.org",
  "@type": "LegalService",
  "name": "Despacho García & Asociados",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "reviewCount": "73",
    "bestRating": "5"
  },
  "review": [
    {
      "@type": "Review",
      "author": { "@type": "Person", "name": "María G." },
      "datePublished": "2026-01-15",
      "reviewRating": {
        "@type": "Rating",
        "ratingValue": "5"
      },
      "reviewBody": "Excelente atención y resultados..."
    }
  ]
}

 
6. Flujos de Automatización (ECA)
Código	Evento	Acciones
REV-001	case.closed + invoice.paid	Esperar 24h → Crear review_request → Enviar solicitud
REV-002	review_request.sent + 7 días	Si no completada → Enviar primer recordatorio
REV-003	review_request.reminder_1 + 7 días	Si no completada → Enviar segundo y último recordatorio
REV-004	review.created (rating >= 4)	Auto-publicar → Notificar profesional → Sugerir Google review
REV-005	review.created (rating < 4)	Marcar pending → Notificar profesional para moderación
REV-006	review.published	Recalcular provider_rating_summary → Invalidar caché widget
REV-007	review.response_added	Notificar cliente por email que el profesional respondió

7. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 13.1	Semana 41	Modelo datos + ReviewService + ReviewRequestService	96_Facturacion
Sprint 13.2	Semana 42	Formulario público + APIs + moderación	Sprint 13.1
Sprint 13.3	Semana 43	Widget embebible + Schema.org + ECA automations	Sprint 13.2
Sprint 13.4	Semana 44	Integración Google Business + recordatorios + tests	Sprint 13.3

7.1 Criterios de Aceptación
•	✓ Solicitud automática 24h después de caso cerrado + factura pagada
•	✓ Formulario de valoración completable en < 60 segundos
•	✓ Auto-publicación de reseñas >= 4 estrellas
•	✓ Moderación disponible para reseñas < 4 estrellas
•	✓ Widget embebible funciona en cualquier web
•	✓ Schema.org genera rich snippets en Google
•	✓ Máximo 2 recordatorios automáticos por solicitud

--- Fin del Documento ---
