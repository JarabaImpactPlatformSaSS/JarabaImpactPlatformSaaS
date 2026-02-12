SISTEMA DE RESEÑAS Y VALORACIONES
Reviews, Ratings, Moderación y Social Proof
Vertical AgroConecta
JARABA IMPACT PLATFORM
Documento Técnico de Implementación

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	54_AgroConecta_Reviews_System
Dependencias:	48_Product_Catalog, 49_Order_System, User System
 
1. Resumen Ejecutivo
Este documento especifica el Sistema de Reseñas y Valoraciones para AgroConecta, que permite a los clientes evaluar productos y productores, generando confianza y social proof que impulsa las conversiones. Incluye verificación de compra, moderación y respuestas de productores.
1.1 Objetivos del Sistema
•	Confianza: Reseñas verificadas de compradores reales
•	Conversión: Social proof que aumenta las ventas (+15-20%)
•	Feedback: Canal de retroalimentación para productores
•	SEO: Contenido generado por usuarios que mejora posicionamiento
•	Calidad: Identificar productores excelentes y áreas de mejora
•	Engagement: Fomentar participación activa de la comunidad
1.2 Stack Tecnológico
Componente	Tecnología
Entidad Review	Custom Entity con campos rating, texto, imágenes, verificación
Valoración	Sistema de 1-5 estrellas con medias ponderadas
Moderación	Cola de moderación + filtros automáticos de spam/toxicidad
Imágenes	Media module con límite de 5 fotos por reseña
Notificaciones	ECA triggers para solicitud y respuesta de reseñas
Filtro Contenido	Perspective API (Google) para detección de toxicidad
Rich Snippets	Schema.org AggregateRating y Review para SEO
Widgets	Componentes reutilizables: estrellas, resumen, lista
1.3 Tipos de Reseñas
Tipo	Descripción	Verificación
Producto	Valoración de un producto específico comprado	Compra verificada
Productor	Valoración general de un productor/tienda	Al menos 1 compra
Pedido	Valoración de la experiencia de compra/envío	Pedido completado
 
2. Arquitectura de Entidades
2.1 Entidad: review
Entidad principal que almacena las reseñas de productos, productores y pedidos.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador único	UNIQUE, NOT NULL
type	VARCHAR(32)	Tipo de reseña	ENUM: product|producer|order
user_id	INT	Autor de la reseña	FK user.id, NOT NULL, INDEX
target_id	INT	ID del objeto reseñado	NOT NULL, INDEX
order_id	INT	Pedido de la compra verificada	FK order.id, NULLABLE
rating	TINYINT	Puntuación 1-5 estrellas	NOT NULL, CHECK 1-5
title	VARCHAR(150)	Título de la reseña	NULLABLE
body	TEXT	Texto de la reseña	NOT NULL, MIN 20 chars
pros	TEXT	Puntos positivos (opcional)	NULLABLE
cons	TEXT	Puntos negativos (opcional)	NULLABLE
is_verified_purchase	BOOLEAN	Compra verificada	DEFAULT FALSE
status	VARCHAR(32)	Estado de moderación	ENUM, DEFAULT 'pending'
moderation_notes	TEXT	Notas del moderador	NULLABLE
helpful_count	INT	Votos de utilidad	DEFAULT 0
report_count	INT	Número de reportes	DEFAULT 0
producer_response	TEXT	Respuesta del productor	NULLABLE
producer_responded_at	DATETIME	Fecha de respuesta	NULLABLE
created	DATETIME	Fecha de creación	NOT NULL, UTC
changed	DATETIME	Última modificación	NOT NULL, UTC
2.2 Estados de Moderación
Estado	Descripción	Visible
pending	Pendiente de revisión (reseñas con flags automáticos)	No
approved	Aprobada manualmente o auto-aprobada	Sí
rejected	Rechazada por incumplir normas	No
flagged	Marcada por usuarios, pendiente de revisión	Sí (temporal)
hidden	Ocultada tras revisión de reportes	No
2.3 Entidad: review_image
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
review_id	INT	Reseña asociada	FK review.id, NOT NULL, INDEX
file_id	INT	Archivo de imagen	FK file.id, NOT NULL
alt_text	VARCHAR(255)	Texto alternativo	NULLABLE
sort_order	INT	Orden de visualización	DEFAULT 0
2.4 Entidad: review_vote
Votos de utilidad ('¿Te resultó útil esta reseña?'):
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
review_id	INT	Reseña votada	FK review.id, NOT NULL, INDEX
user_id	INT	Usuario que vota	FK user.id, NOT NULL
vote	TINYINT	Voto: 1=útil, -1=no útil	NOT NULL, CHECK -1 or 1
created	DATETIME	Fecha del voto	NOT NULL, UTC
 
3. Cálculo de Valoraciones
El sistema calcula valoraciones agregadas usando un algoritmo ponderado que favorece reseñas verificadas y recientes.
3.1 Valoración de Producto
function calculateProductRating(productId) {
  const reviews = getApprovedReviews(productId, 'product');
  
  let weightedSum = 0;
  let totalWeight = 0;
  
  for (const review of reviews) {
    let weight = 1.0;
    
    // Compra verificada: +50% peso
    if (review.is_verified_purchase) weight *= 1.5;
    
    // Reseña con texto extenso: +20% peso
    if (review.body.length > 200) weight *= 1.2;
    
    // Reseña con fotos: +10% peso
    if (review.images.length > 0) weight *= 1.1;
    
    // Decaimiento temporal: -5% por cada 6 meses de antigüedad
    const monthsOld = getMonthsOld(review.created);
    weight *= Math.max(0.5, 1 - (monthsOld / 6) * 0.05);
    
    weightedSum += review.rating * weight;
    totalWeight += weight;
  }
  
  // Mínimo 3 reseñas para mostrar valoración
  if (reviews.length < 3) return null;
  
  return (weightedSum / totalWeight).toFixed(1);
}
3.2 Valoración de Productor
La valoración del productor combina sus reseñas directas con las valoraciones de sus productos:
•	Reseñas de productor: 40% del peso total
•	Media de productos: 50% del peso total (media ponderada de sus productos)
•	Tasa de respuesta: 10% (penalización si < 50% de respuestas)
3.3 Distribución de Ratings
Visualización del desglose de valoraciones:
⭐⭐⭐⭐⭐  ████████████████████  65%  (47 reseñas)
⭐⭐⭐⭐     ██████████            22%  (16 reseñas)
⭐⭐⭐       ████                   8%  (6 reseñas)
⭐⭐         █                      3%  (2 reseñas)
⭐           █                      2%  (1 reseña)
 
4. Flujo de Creación de Reseñas
4.1 Solicitud de Reseña
Proceso automatizado para solicitar reseñas tras la entrega:
1.	Pedido entregado (confirmado por tracking o cliente)
2.	Esperar 3 días (tiempo para probar el producto)
3.	Enviar email de solicitud con link directo a formulario de reseña
4.	Si no hay respuesta en 7 días: enviar recordatorio
5.	Máximo 2 recordatorios por pedido
6.	Incluir incentivo: +10 puntos de fidelización por reseña
4.2 Formulario de Reseña
┌─────────────────────────────────────────────────────────────────┐
│  📝 ESCRIBE TU RESEÑA                                           │
│                                                                 │
│  [Img] AOVE Picual Premium 500ml                                │
│        Finca Los Olivos                                         │
│                                                                 │
│  Tu valoración: *                                               │
│  [☆] [☆] [☆] [☆] [☆]  (Selecciona de 1 a 5 estrellas)          │
│                                                                 │
│  Título de tu reseña:                                           │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Excelente aceite, muy aromático                         │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                 │
│  Cuéntanos tu experiencia: *                                    │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ Me encanta este aceite. El sabor es intenso y          │    │
│  │ afrutado, perfecto para ensaladas y tostadas...        │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                              Min. 20 caracteres │
│                                                                 │
│  📷 Añade fotos (opcional):                                     │
│  [+] [img1] [img2]                   Máximo 5 fotos             │
│                                                                 │
│  [  ] Recomiendo este producto                                  │
│                                                                 │
│              [Publicar Reseña]    [Cancelar]                    │
└─────────────────────────────────────────────────────────────────┘
4.3 Validaciones del Formulario
Campo	Validación	Error
Rating	Obligatorio, valor entre 1 y 5	Selecciona una valoración
Título	Opcional, máx 150 caracteres	Título demasiado largo
Texto	Obligatorio, mín 20, máx 2000 caracteres	Min 20 caracteres
Imágenes	Máx 5, formatos JPG/PNG/WebP, máx 5MB cada una	Formato no válido
Duplicado	Solo 1 reseña por usuario/producto/pedido	Ya has reseñado este producto
Spam	Filtro de enlaces, emails, teléfonos	Texto contiene contenido no permitido
 
5. Sistema de Moderación
La moderación combina filtros automáticos con revisión manual para garantizar calidad y cumplimiento de normas.
5.1 Auto-Moderación
Filtro	Descripción	Acción
Toxicidad (Perspective API)	Score > 0.7 en insultos, amenazas, discurso de odio	→ pending
Spam	URLs, emails, teléfonos, texto repetitivo	→ pending
Palabras prohibidas	Lista negra de palabras/frases inapropiadas	→ pending
Velocidad sospechosa	Reseña escrita en < 30 segundos	→ pending
Usuario nuevo	Primera reseña de un usuario registrado < 7 días	→ pending
Compra verificada + texto ok	Sin flags automáticos y compra confirmada	→ approved
5.2 Cola de Moderación (Admin)
Interfaz para moderadores con las siguientes funciones:
•	Lista de pendientes: Reseñas en estado 'pending' ordenadas por antigüedad
•	Detalle expandible: Ver reseña completa, imágenes, historial del usuario
•	Flags mostrados: Indicadores de qué filtro activó la revisión
•	Acciones: Aprobar, Rechazar (con motivo), Editar, Banear usuario
•	Bulk actions: Aprobar/rechazar múltiples reseñas
•	Métricas: Tiempo medio de moderación, % aprobadas/rechazadas
5.3 Reportes de Usuarios
Cualquier usuario puede reportar una reseña:
Motivo de Reporte	Descripción
Spam o publicidad	Contenido promocional no relacionado con el producto
Contenido ofensivo	Insultos, discriminación, lenguaje inapropiado
Información falsa	Datos incorrectos o engañosos sobre el producto
No es sobre el producto	Reseña sobre envío, atención al cliente, etc.
Conflicto de intereses	Sospecha de reseña del propio productor o competencia
Otro	Campo libre para explicar el motivo
Regla: Si una reseña recibe 3+ reportes únicos → cambia automáticamente a 'flagged' para revisión.
 
6. Respuestas de Productores
Los productores pueden responder a las reseñas de sus productos para agradecer, aclarar o resolver problemas.
6.1 Flujo de Respuesta
7.	Productor recibe notificación de nueva reseña
8.	Accede a la reseña desde su portal o desde el email
9.	Escribe respuesta (máx 1000 caracteres)
10.	Respuesta pasa por filtro de toxicidad
11.	Si aprobada: se publica y notifica al cliente
12.	Solo 1 respuesta por reseña (no permite hilos)
6.2 Visualización
┌─────────────────────────────────────────────────────────────────┐
│  ⭐⭐⭐⭐⭐  Excelente aceite, muy aromático                      │
│                                                                 │
│  Me encanta este aceite. El sabor es intenso y afrutado,        │
│  perfecto para ensaladas y tostadas. La botella es muy          │
│  elegante también. Repetiré seguro.                             │
│                                                                 │
│  👤 María G.  •  ✓ Compra verificada  •  14 enero 2026          │
│  ¿Te resultó útil? [👍 12] [👎 1]  •  [Reportar]                 │
│                                                                 │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │ 💬 Respuesta de Finca Los Olivos:                         │  │
│  │                                                           │  │
│  │ ¡Muchas gracias por tu reseña, María! Nos alegra mucho    │  │
│  │ que hayas disfrutado de nuestro AOVE Picual. Es nuestra   │  │
│  │ variedad estrella esta temporada. ¡Te esperamos pronto!   │  │
│  │                                            15 enero 2026  │  │
│  └───────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
6.3 Métricas de Respuesta
•	Tasa de respuesta: % de reseñas respondidas (meta: > 80%)
•	Tiempo medio de respuesta: Horas/días hasta responder (meta: < 48h)
•	Alertas: Notificar si hay reseñas negativas (1-2 estrellas) sin responder > 24h
•	Badge: 'Responde habitualmente' si tasa > 90%
 
7. SEO y Rich Snippets
Las reseñas generan datos estructurados que mejoran la visibilidad en buscadores y aumentan el CTR.
7.1 Schema.org AggregateRating
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "AOVE Picual Premium 500ml",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "reviewCount": "72",
    "bestRating": "5",
    "worstRating": "1"
  },
  "review": [
    {
      "@type": "Review",
      "author": {"@type": "Person", "name": "María G."},
      "datePublished": "2026-01-14",
      "reviewRating": {"@type": "Rating", "ratingValue": "5"},
      "reviewBody": "Excelente aceite, muy aromático..."
    }
  ]
}
7.2 Resultado en Google
AOVE Picual Premium 500ml - Finca Los Olivos
www.agroconecta.es › producto › aove-picual-premium
⭐⭐⭐⭐⭐ 4.8  (72 reseñas)  •  €12.50
Aceite de oliva virgen extra de variedad Picual, cosecha temprana. Sabor intenso y afrutado. Producción ecológica certificada.
 
8. APIs del Sistema de Reseñas
8.1 Endpoints Públicos
Método	Endpoint	Descripción
GET	/api/v1/products/{id}/reviews	Listar reseñas de un producto
GET	/api/v1/products/{id}/rating	Obtener valoración agregada
GET	/api/v1/producers/{id}/reviews	Listar reseñas de un productor
GET	/api/v1/reviews/{id}	Detalle de una reseña
8.2 Endpoints de Cliente
Método	Endpoint	Descripción
POST	/api/v1/reviews	Crear nueva reseña
PATCH	/api/v1/reviews/{id}	Editar reseña propia
DELETE	/api/v1/reviews/{id}	Eliminar reseña propia
POST	/api/v1/reviews/{id}/vote	Votar reseña (útil/no útil)
POST	/api/v1/reviews/{id}/report	Reportar reseña
GET	/api/v1/me/reviews	Mis reseñas escritas
GET	/api/v1/me/reviews/pending	Productos pendientes de reseñar
8.3 Endpoints de Productor
Método	Endpoint	Descripción
GET	/api/v1/producer/reviews	Reseñas de mis productos
POST	/api/v1/producer/reviews/{id}/respond	Responder a una reseña
PATCH	/api/v1/producer/reviews/{id}/response	Editar respuesta
GET	/api/v1/producer/reviews/stats	Estadísticas de reseñas
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semana 1-2	Entidad review con campos core. Formulario de creación. Verificación de compra.	49_Order_System
Sprint 2	Semana 3-4	Cálculo de ratings ponderados. Visualización en ficha de producto. Votos de utilidad.	48_Product_Catalog
Sprint 3	Semana 5-6	Sistema de moderación: auto-filtros, cola de moderación, reportes.	Perspective API
Sprint 4	Semana 7-8	Respuestas de productores. Notificaciones. Solicitud automática de reseñas.	52_Producer_Portal
Sprint 5	Semana 9-10	Imágenes en reseñas. SEO: Schema.org, rich snippets.	Sprint 4
Sprint 6	Semana 11-12	Reseñas de productor. Integración puntos fidelización. QA. Go-live.	53_Customer_Portal
--- Fin del Documento ---
54_AgroConecta_Reviews_System_v1.docx | Jaraba Impact Platform | Enero 2026
