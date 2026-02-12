ANÁLISIS DE BRECHAS
DOCUMENTACIÓN TÉCNICA DE IMPLEMENTACIÓN
Vertical AgroConecta
Marketplace Agrario & Framework de Impacto

Versión:	1.0
Fecha:	Enero 2026
Estado:	Análisis Estratégico
Vertical:	AgroConecta (Avatar: Marta)
Clasificación:	Interno - Planificación Técnica
 
1. Resumen Ejecutivo
Este documento identifica las especificaciones técnicas de implementación que NO EXISTEN actualmente en la documentación del proyecto para la vertical AgroConecta del Ecosistema Jaraba.
1.1 Documentación Existente
La vertical de AgroConecta cuenta actualmente con:
•	Plan Maestro Consolidado - Definición como vertical estratégico modelo
•	Documento Técnico Maestro v2 - Arquitectura base Drupal Commerce + Stripe Connect
•	Trazabilidad Phy-gital - Servicio TrazabilidadService.php y QrController.php conceptuales
•	Permisos RBAC - Roles merchant, marketplace_admin, logistics_partner, consumer
•	Presupuesto modelo - Bodegas Robles como caso ejemplo (€3.188 año 1)
•	Webhooks básicos - product.created, order.completed, cart.abandoned
1.2 Gap Crítico Identificado
AgroConecta está posicionado como el "vertical estratégico modelo" que sirve de blueprint para futuras expansiones (ComercioConecta, ServiciosConecta). Sin embargo, toda la arquitectura de marketplace carece de especificaciones técnicas: gestión de catálogo, sistema de pedidos, fulfillment, integraciones con marketplaces externos, sistema de reseñas, analytics de comercio y métricas de impacto específicas.
1.3 Asimetría Documental vs. Otras Verticales
La vertical de Empleabilidad cuenta con 17 documentos técnicos de implementación que cubren todo el journey del usuario. AgroConecta, siendo el vertical "modelo", tiene 0 documentos equivalentes específicos.
Vertical	Docs Técnicos	Cobertura
Empleabilidad	17 documentos	Journey completo
Emprendimiento	21 documentos	Journey completo
AgroConecta	0 documentos	Solo menciones conceptuales
1.4 Resumen de Documentos Faltantes
Categoría	Docs Faltantes	Prioridad	Esfuerzo Est.
E-Commerce Core / Catálogo	4	CRÍTICA	Alto
Gestión de Pedidos / Fulfillment	3	CRÍTICA	Alto
Sistema Productor / Merchant	3	ALTA	Medio
Trazabilidad Phy-gital	2	ALTA	Medio
Integraciones Externas	3	ALTA	Alto
SEO/GEO & Visibilidad IA	2	ALTA	Medio
Reviews & Social Proof	2	MEDIA	Medio
Dashboards & Analytics	3	MEDIA	Medio
AI Copilots Commerce	2	MEDIA	Alto
TOTAL	24	-	-
 
2. E-Commerce Core / Sistema de Catálogo
El Plan Maestro y Documento Técnico Maestro mencionan Drupal Commerce 3.x como base, pero NO EXISTE documentación específica de implementación para el catálogo de productos agrarios.
Documento	Descripción / Contenido Esperado	Prioridad
45_AgroConecta_Commerce_Core_v1.docx	Arquitectura Commerce para marketplace agrario: tipos de producto (físico, perecedero, a granel, por lote), variaciones (peso, formato, añada), gestión de precios por volumen, impuestos agrarios específicos	CRÍTICA
46_AgroConecta_Product_Catalog_v1.docx	Sistema de catálogo: entidad product con campos específicos (origen, certificaciones, temporada, alérgenos), taxonomías (categorías, DO, ecológico), búsqueda facetada con Search API, URLs limpias SEO	CRÍTICA
47_AgroConecta_Inventory_Management_v1.docx	Gestión de inventario agrario: stock por lote/cosecha, alertas de caducidad, reservas temporales, sincronización multi-canal, gestión de mermas y productos fuera de temporada	ALTA
48_AgroConecta_Pricing_Promotions_v1.docx	Sistema de precios y promociones: precios dinámicos por temporada, descuentos por volumen, cupones, ofertas flash para producto próximo a caducar, bundles de productos	ALTA
Contenido Técnico Esperado
•	Entidades Drupal: product_agro, product_variation_agro, product_attribute (origen, certificacion, temporada)
•	Campos específicos: fecha_cosecha, fecha_caducidad, lote_produccion, certificaciones[], denominacion_origen
•	Flujos ECA: notificación stock bajo, alerta producto próximo a caducar, actualización precios estacionales
•	APIs REST: /api/v1/products, /api/v1/inventory, /api/v1/prices con filtros por productor/temporada
 
3. Gestión de Pedidos / Fulfillment
El ecosistema requiere un sistema completo de gestión de pedidos adaptado a las particularidades del comercio agrario: productos perecederos, envíos refrigerados, recogida en finca, grupos de compra. NO EXISTE documentación técnica.
Documento	Descripción / Contenido Esperado	Prioridad
49_AgroConecta_Order_System_v1.docx	Sistema de pedidos: entidad order con estados específicos (pending/processing/ready/shipped/delivered), workflows multi-productor, notificaciones bidireccionales, historial de compras	CRÍTICA
50_AgroConecta_Checkout_Flow_v1.docx	Proceso de checkout: carrito multi-vendor, cálculo de envío por origen, selección de fecha entrega, opciones de recogida en origen, pago con split automático Stripe Connect	CRÍTICA
51_AgroConecta_Shipping_Logistics_v1.docx	Sistema de envíos: zonas de envío, tarifas por peso/volumen, opciones refrigeradas, integración con transportistas (MRW, SEUR), tracking, puntos de recogida, grupos de compra locales	ALTA
Contenido Técnico Esperado
•	Entidades Drupal: commerce_order_agro, shipment_agro, pickup_point
•	Estados de pedido: draft → pending → paid → processing → ready_for_pickup/shipped → delivered → completed
•	Flujos ECA: notificación nuevo pedido a productor, alerta envío pendiente, confirmación entrega, solicitud de reseña post-compra
•	Webhooks Make.com: order.created, order.shipped, order.delivered para integraciones CRM y transportistas
4. Sistema Productor / Merchant Portal
Los productores agrarios (avatar Marta) necesitan un portal completo para gestionar su tienda, productos, pedidos y analíticas. El RBAC define permisos básicos pero NO EXISTE especificación del portal.
Documento	Descripción / Contenido Esperado	Prioridad
52_AgroConecta_Producer_Portal_v1.docx	Portal del productor: dashboard con métricas de ventas, gestión de catálogo, procesamiento de pedidos, configuración de tienda, perfil público, gestión de certificaciones	ALTA
53_AgroConecta_Store_Setup_v1.docx	Onboarding de tienda: wizard de configuración, branding personalizado, configuración Stripe Connect Express, verificación de certificaciones, setup de envíos	ALTA
54_AgroConecta_Producer_Profile_v1.docx	Perfil público del productor: storytelling (historia, valores, proceso), galería de imágenes, certificaciones verificadas, ubicación/mapa, productos destacados, reseñas	MEDIA
Contenido Técnico Esperado
•	Entidades Drupal: producer_profile, store_settings, certification_record
•	Dashboard widgets: ventas_hoy, pedidos_pendientes, productos_bajo_stock, valoracion_media, visitas_tienda
•	Flujos ECA: recordatorio certificación próxima a caducar, alerta nueva reseña, notificación hito de ventas
 
5. Trazabilidad Phy-gital
El Documento Técnico Maestro menciona TrazabilidadService.php y QrController.php conceptualmente, pero NO EXISTE especificación detallada del sistema completo de trazabilidad que conecta producto físico con digital.
Documento	Descripción / Contenido Esperado	Prioridad
55_AgroConecta_Traceability_System_v1.docx	Sistema de trazabilidad completo: generación de IDs únicos (LOTE-2025-XXXX), registro de eventos de cadena de suministro, página pública de trazabilidad, integración con certificaciones	ALTA
56_AgroConecta_QR_Dynamic_v1.docx	Sistema de QR dinámicos: generación con endroid/qr-code, landing de producto escaneado, captura de leads, solicitud de reseña in-situ, analytics de escaneos, integración con packaging físico	ALTA
Contenido Técnico Esperado
•	Entidades Drupal: traceability_record, qr_code, scan_event
•	Datos de trazabilidad: origen, productor, fecha_cosecha, proceso_produccion, certificaciones, lote, fecha_envasado
•	Analytics QR: total_scans, unique_users, geographic_distribution, conversion_to_purchase, review_requests
6. Integraciones Externas
El Plan Maestro menciona Make.com como hub de integración y la capacidad de sincronizar con marketplaces externos. NO EXISTE documentación de las integraciones específicas necesarias para AgroConecta.
Documento	Descripción / Contenido Esperado	Prioridad
57_AgroConecta_Marketplace_Sync_v1.docx	Sincronización con marketplaces: Meta Catalog API (Facebook/Instagram Shop), Google Merchant Center, Amazon Seller, escenarios Make.com, mapeo de atributos, gestión de stock multicanal	ALTA
58_AgroConecta_POS_Integration_v1.docx	Integración con TPV: conectores para Square, SumUp, Shopify POS, sincronización de ventas físicas, unificación de inventario online/offline, reportes consolidados	ALTA
59_AgroConecta_External_APIs_v1.docx	APIs de terceros: transportistas (MRW, SEUR API), verificación de certificaciones (ecológico, DO), servicios meteorológicos para alertas de cosecha, pasarelas de pago alternativas	MEDIA
Escenarios Make.com Esperados
•	Product Sync: Drupal → Make.com → Facebook/Instagram/Google Shopping
•	Order Import: Marketplace externo → Make.com → Drupal Commerce
•	Stock Unification: POS venta → Make.com → Actualización stock Drupal → Propagación a marketplaces
•	Shipping Labels: Pedido confirmado → Make.com → API transportista → Etiqueta generada
 
7. SEO/GEO & Visibilidad IA
El Documento Técnico Maestro define la visión GEO (Generative Engine Optimization) con Answer Capsules y Schema.org, pero NO EXISTE especificación de implementación para productos y productores agrarios.
Documento	Descripción / Contenido Esperado	Prioridad
60_AgroConecta_SEO_Products_v1.docx	SEO para productos: Schema.org Product con campos agrarios, Answer Capsules en descripciones, URLs semánticas (/aceite-oliva-virgen-extra-cordoba), meta tags dinámicos, sitemap de productos	ALTA
61_AgroConecta_Local_SEO_v1.docx	SEO local para productores: Schema.org LocalBusiness/FoodEstablishment, integración Google Business Profile, optimización "cerca de mí", rich snippets de ubicación y reseñas	ALTA
Schema.org Específico para Agro
•	Product: name, description, image, offers, aggregateRating, brand, countryOfOrigin, productionDate
•	FoodEstablishment: para productores con venta directa
•	Review: reviewRating, author, datePublished, reviewBody
•	Offer: price, priceCurrency, availability, priceValidUntil, shippingDetails
8. Reviews & Social Proof
Las reseñas son críticas para la confianza en productos alimentarios. El sistema de QR menciona "captación de reseñas in-situ" pero NO EXISTE especificación del sistema completo de reviews.
Documento	Descripción / Contenido Esperado	Prioridad
62_AgroConecta_Review_System_v1.docx	Sistema de reseñas: entidad review con rating, texto, fotos, verificación de compra, moderación, respuesta del productor, agregación de puntuaciones, widgets de display	MEDIA
63_AgroConecta_Social_Proof_v1.docx	Social proof adicional: badges de confianza, contador de ventas, testimonios destacados, certificaciones verificadas visualmente, integración con redes sociales, UGC (fotos de clientes)	MEDIA
Contenido Técnico Esperado
•	Entidades Drupal: review, review_response, review_photo, trust_badge
•	Métricas: rating_average, review_count, response_rate, photo_count
•	Flujos ECA: solicitud de review post-entrega (7 días), alerta nueva review al productor, escalado de reviews negativas
 
9. Dashboards & Analytics
El FOC documenta métricas SaaS genéricas, pero NO EXISTE documentación de dashboards específicos para productores, marketplace admin, y métricas de impacto de comercio agrario.
Documento	Descripción / Contenido Esperado	Prioridad
64_AgroConecta_Dashboard_Producer_v1.docx	Dashboard del productor: ventas (hoy/semana/mes), pedidos por estado, productos top, valoración media, visitas, conversión, comparativa con periodo anterior, alertas	MEDIA
65_AgroConecta_Dashboard_Marketplace_v1.docx	Dashboard del marketplace admin: GMV total, comisiones, productores activos, productos listados, pedidos diarios, categorías top, salud del marketplace	MEDIA
66_AgroConecta_Impact_Metrics_v1.docx	Métricas de impacto específicas: ingresos generados a productores locales, km evitados (producto local vs importado), empleos indirectos, productores digitalizados, certificaciones conseguidas	ALTA
KPIs Específicos de AgroConecta
•	Para productores: GMV, AOV (Average Order Value), Conversion Rate, Review Score, Repeat Customer Rate
•	Para marketplace: Total GMV, Platform Take Rate, Active Producers, Products Listed, Orders/Day
•	Para impacto: Local Economic Impact (€), Food Miles Saved, Producer Income Growth, Digital Adoption Rate
10. AI Copilots Commerce
El ecosistema tiene documentado el AI Business Copilot para Emprendimiento. NO EXISTE especificación del Producer Copilot mencionado en el Plan Maestro ni del Agente Vendedor Local.
Documento	Descripción / Contenido Esperado	Prioridad
67_AgroConecta_Producer_Copilot_v1.docx	Producer Copilot: generación de descripciones de producto, optimización SEO, sugerencias de precios, análisis de competencia, asistente de respuesta a reseñas, planificación de inventario	MEDIA
68_AgroConecta_Sales_Agent_v1.docx	Agente Vendedor IA: chatbot de atención al cliente, recomendaciones de producto, venta cruzada, recuperación de carritos abandonados, soporte post-venta, integración WhatsApp Business	MEDIA
Acciones del Producer Copilot
•	generate_description: Foto de producto → Descripción optimizada SEO con Answer Capsule
•	suggest_price: Análisis de competencia y costes → Precio recomendado con margen
•	respond_review: Reseña recibida → Borrador de respuesta profesional
•	forecast_demand: Histórico + temporada → Predicción de demanda para planificar producción
 
11. Roadmap de Documentación Propuesto
Plan de desarrollo de las 24 especificaciones técnicas identificadas, organizado por fases de implementación:
Fase	Documentos	Dependencias	Timeline Est.
Fase 1	Commerce Core, Product Catalog, Order System, Checkout Flow	Core entities, Stripe Connect	Semanas 1-4
Fase 2	Producer Portal, Store Setup, Inventory, Shipping	Fase 1 completada	Semanas 5-8
Fase 3	Traceability, QR Dynamic, SEO Products, Local SEO	Fase 2 + QR library	Semanas 9-12
Fase 4	Marketplace Sync, POS Integration, External APIs	Fase 3 + Make.com	Semanas 13-16
Fase 5	Reviews, Social Proof, Dashboards, Impact Metrics	Fase 4 + FOC	Semanas 17-20
Fase 6	Producer Copilot, Sales Agent, Pricing/Promotions, Profile	Todas + Qdrant RAG	Semanas 21-24
 
12. Conclusión y Recomendaciones
La vertical de AgroConecta tiene documentación estratégica sólida (posicionamiento como vertical modelo, presupuesto tipo, arquitectura base), pero carece de las 24 especificaciones técnicas de implementación necesarias para construir el sistema completo.
12.1 Acciones Inmediatas
1.	Priorizar documentación de Commerce Core: Sin catálogo de productos, no hay marketplace
2.	Especificar sistema de pedidos: Es el flujo crítico que genera ingresos
3.	Definir Producer Portal: Sin experiencia de productor sólida, no hay oferta
12.2 Riesgos de No Documentar
•	Implementación inconsistente sin especificaciones claras
•	Imposibilidad de replicar el "blueprint" a ComercioConecta y ServiciosConecta
•	Pérdida de coherencia con la filosofía "Sin Humo" (desarrollo sin documentación = humo)
•	Deuda técnica acumulada por desarrollar sin blueprint
•	Dificultad para justificar inversiones ante financiadores (Kit Digital, fondos LEADER)
12.3 Sinergia con Otras Verticales
Muchos componentes de AgroConecta pueden diseñarse con abstracción para reutilización:
•	Commerce Core: Base para ComercioConecta (retail) y ServiciosConecta (servicios)
•	Producer Portal: Adaptable a Merchant Portal (comercio) y Provider Portal (servicios)
•	Reviews System: Reutilizable en todas las verticales de marketplace
•	SEO/GEO Components: Schema.org parametrizable por tipo de negocio
•	AI Copilots: Arquitectura común con prompts especializados por vertical
12.4 Inversión Estimada
Concepto	Documentos	Horas Est.
Especificaciones técnicas completas	24 documentos	120-160 horas
Diagramas y flujos	48 diagramas aprox.	24-32 horas
Revisión y validación	-	16-24 horas
TOTAL ESTIMADO	24 docs + anexos	160-216 horas
--- Fin del Documento ---
