HUB DOCUMENTAL B2B
Biblioteca Documental para Partners de la Cadena de Valor
Fichas Técnicas, Certificaciones, Analíticas y Material de Marketing
Vertical AgroConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	82_AgroConecta_Partner_Document_Hub
Dependencias:	47_Commerce_Core, 52_Producer_Portal, 80_Traceability
Prioridad:	P1 - ALTA (Diferenciador B2B)
Compliance:	RGPD, LOPD-GDD, Normativa alimentaria UE
 
1. Resumen Ejecutivo
El Hub Documental B2B es el sistema centralizado que permite a los productores agrarios compartir documentación técnica, certificaciones, analíticas y material de marketing con los diferentes actores de su cadena de valor: distribuidores, exportadores, comerciales, hosteleros y compradores mayoristas.
A diferencia del Portal Cliente Documental de ServiciosConecta (diseñado para relaciones 1:1 profesional-cliente), el Hub Documental B2B implementa un modelo de biblioteca compartida 1:N donde un productor puede gestionar documentación para múltiples partners con diferentes niveles de acceso según su relación comercial.
1.1 El Problema: Fragmentación Documental B2B
Canal Actual	Problemas	Consecuencias
Email con adjuntos	PDFs desactualizados, versiones confusas, búsqueda imposible	Distribuidor vende con ficha técnica obsoleta
WhatsApp Business	Archivos que caducan, sin organización, imposible de auditar	Pérdida de certificaciones enviadas
Dropbox/Drive compartido	Permisos caóticos, sin notificaciones de actualización	Partners acceden a docs que no deberían
Web del productor	Documentación pública sin control, sin tracking	Competencia descarga fichas técnicas
Petición manual	El productor debe buscar, preparar y enviar cada vez	Horas perdidas en tareas repetitivas
1.2 La Solución: Hub Documental B2B
Biblioteca Documental Centralizada:
• Todos los documentos de producto organizados por categoría y tipo
• Versionado automático con historial de cambios
• Generación automática de fichas técnicas desde datos del producto
• Alertas de caducidad de certificaciones
Permisos por Relación Comercial:
• Niveles de acceso: Básico, Verificado, Premium
• Documentos visibles según tipo de partner y nivel
• Restricción por producto o categoría
• Revocación inmediata al terminar relación comercial
Portal Partner sin Fricción:
• Acceso con magic link (sin contraseña que recordar)
• Dashboard con productos del productor y documentación disponible
• Generación de packs documentales en ZIP
• Notificación automática cuando hay nuevos documentos
Analytics para el Productor:
• Qué documentos se descargan más
• Qué partners son más activos
• Alertas de certificaciones próximas a caducar
• Audit log completo para trazabilidad
1.3 Casos de Uso por Tipo de Partner
Partner	Documentos que Necesita	Nivel Típico
Distribuidor	Fichas técnicas, argumentarios de venta, PVP recomendado, certificaciones, imágenes HD	Verificado
Exportador	Certificados fitosanitarios, especificaciones aduanas, traducciones EN/FR/DE, analíticas laboratorio	Premium
Comercial	Material de marketing, catálogo digital, precios, promociones vigentes	Básico
HORECA	Fichas alérgenos, maridajes, origen detallado, formatos disponibles, conservación	Verificado
Mayorista	Especificaciones técnicas completas, condiciones de almacenamiento, packaging bulk	Verificado
Importador	Todo lo del exportador + documentación para su país específico	Premium
 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────────┐ │                     HUB DOCUMENTAL B2B                                  │ ├─────────────────────────────────────────────────────────────────────────┤ │                                                                         │ │  ┌──────────────────┐              ┌──────────────────┐                │ │  │      Vista       │              │      Vista       │                │ │  │    PRODUCTOR     │              │     PARTNER      │                │ │  │   (Dashboard)    │              │    (Portal)      │                │ │  └────────┬─────────┘              └────────┬─────────┘                │ │           │                                 │                          │ │           └──────────────┬─────────────────┘                          │ │                          │                                             │ │                          ▼                                             │ │             ┌───────────────────────────┐                              │ │             │  PartnerDocumentService   │                              │ │             │  (Gestión Documentos)     │                              │ │             └───────────┬───────────────┘                              │ │                         │                                              │ │     ┌──────────────────┼─────────────────────┐                        │ │     │                  │                     │                        │ │     ▼                  ▼                     ▼                        │ │ ┌──────────┐   ┌──────────────┐    ┌──────────────┐                   │ │ │ Partner  │   │  Document    │    │ TechSheet    │                   │ │ │ Access   │   │  Library     │    │ Generator    │                   │ │ │ Service  │   │  Service     │    │ Service      │                   │ │ └────┬─────┘   └──────┬───────┘    └──────┬───────┘                   │ │      │                │                   │                           │ │      └────────────────┼───────────────────┘                           │ │                       │                                                │ │                       ▼                                                │ │        ┌───────────────────────────────┐                              │ │        │    COMMERCE CORE (doc 47)     │                              │ │        │  - product_agro               │                              │ │        │  - producer_profile           │                              │ │        │  - agro_certification         │                              │ │        └───────────────────────────────┘                              │ └─────────────────────────────────────────────────────────────────────────┘
2.2 Diferencias con Portal Cliente Documental (ServiciosConecta)
Aspecto	ServiciosConecta (doc 90)	AgroConecta (este doc)
Modelo	Expediente único por cliente (1:1)	Biblioteca compartida por partner (1:N)
Workflow	Solicitar → Subir → Revisar → Aprobar	Publicar → Compartir → Acceder
Acceso	Token único por expediente	Permisos por rol/relación comercial
Documentos	Únicos (DNI, escrituras, contratos)	Estandarizados (fichas, certs, catálogos)
Caducidad	Por expediente/asunto	Por producto/cosecha/certificación
Generación	Manual (profesional sube)	Automática (fichas desde entidades)
 
3. Modelo de Datos
3.1 Entidad: partner_relationship
Representa la relación comercial entre un productor y un partner externo (distribuidor, exportador, etc.).
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
producer_id	INT	Productor que comparte	FK producer_profile.id, NOT NULL
partner_email	VARCHAR(255)	Email del partner	NOT NULL, INDEX
partner_name	VARCHAR(255)	Nombre/empresa partner	NOT NULL
partner_type	VARCHAR(32)	Tipo de partner	ENUM: distribuidor|exportador|comercial|horeca|mayorista|importador
access_level	VARCHAR(16)	Nivel de acceso	ENUM: basico|verificado|premium
access_token	VARCHAR(64)	Token para magic link	UNIQUE, NOT NULL
allowed_products	JSON	IDs productos accesibles	NULLABLE (null = todos)
allowed_categories	JSON	IDs categorías accesibles	NULLABLE (null = todas)
status	VARCHAR(16)	Estado de la relación	ENUM: pending|active|suspended|revoked
notes	TEXT	Notas internas	NULLABLE
last_access_at	DATETIME	Último acceso al portal	NULLABLE
created	DATETIME	Fecha creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL
3.2 Entidad: product_document
Documento asociado a un producto o al productor en general.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
producer_id	INT	Productor propietario	FK producer_profile.id, NOT NULL
product_id	INT	Producto asociado	FK product_agro.id, NULLABLE
title	VARCHAR(255)	Título del documento	NOT NULL
document_type	VARCHAR(32)	Tipo de documento	ENUM: ficha_tecnica|analitica|certificacion|marketing|especificacion|catalogo|otro
file_id	INT	Archivo adjunto	FK file_managed.fid, NOT NULL
is_auto_generated	BOOLEAN	Generado automáticamente	DEFAULT FALSE
min_access_level	VARCHAR(16)	Nivel mínimo requerido	ENUM: basico|verificado|premium, DEFAULT basico
allowed_partner_types	JSON	Tipos de partner permitidos	NULLABLE (null = todos)
version	VARCHAR(16)	Versión del documento	DEFAULT '1.0'
valid_from	DATE	Fecha inicio validez	NULLABLE
valid_until	DATE	Fecha fin validez	NULLABLE (certificaciones)
language	VARCHAR(5)	Idioma del documento	DEFAULT 'es'
download_count	INT	Número de descargas	DEFAULT 0
is_active	BOOLEAN	Documento activo	DEFAULT TRUE
created	DATETIME	Fecha creación	NOT NULL
changed	DATETIME	Última modificación	NOT NULL
3.3 Entidad: document_download_log
Registro de cada descarga de documento para analytics y auditoría.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
document_id	INT	Documento descargado	FK product_document.id, NOT NULL
relationship_id	INT	Partner que descarga	FK partner_relationship.id, NOT NULL
downloaded_at	DATETIME	Fecha/hora descarga	NOT NULL, INDEX
ip_address	VARCHAR(45)	IP del partner	NULLABLE
user_agent	VARCHAR(255)	User agent del navegador	NULLABLE
3.4 Tipos de Documento Predefinidos
Tipo	Descripción	Auto-Gen	Nivel Mín.
ficha_tecnica	Especificaciones del producto: composición, origen, conservación, alérgenos	✅ Sí	Básico
analitica	Resultados de laboratorio: acidez, peróxidos, polifenoles, etc.	❌ No	Verificado
certificacion	Certificados oficiales: DO, ecológico, fitosanitario, etc.	❌ No	Básico
marketing	Material promocional: imágenes HD, argumentario, catálogo	❌ No	Básico
especificacion	Docs técnicos avanzados: aduanas, almacenamiento bulk, etc.	❌ No	Premium
catalogo	Catálogo completo de productos del productor	✅ Sí	Básico
 
4. Servicios PHP
4.1 PartnerRelationshipService
<?php  namespace Drupal\agroconecta_hub\Service;  class PartnerRelationshipService {    public function createRelationship(     int $producerId,     string $partnerEmail,     string $partnerName,     string $partnerType,     string $accessLevel = 'basico'   ): PartnerRelationship {     // Verificar que no existe relación activa     $existing = $this->findByProducerAndEmail($producerId, $partnerEmail);     if ($existing && $existing->getStatus() === 'active') {       throw new RelationshipExistsException('Ya existe relación activa');     }      $relationship = PartnerRelationship::create([       'producer_id' => $producerId,       'partner_email' => $partnerEmail,       'partner_name' => $partnerName,       'partner_type' => $partnerType,       'access_level' => $accessLevel,       'access_token' => $this->generateSecureToken(),       'status' => 'pending',     ]);     $relationship->save();      // Enviar invitación por email     $this->notificationService->sendPartnerInvitation($relationship);      return $relationship;   }    public function activateByToken(string $token): PartnerRelationship {     $relationship = $this->findByToken($token);     if (!$relationship) {       throw new InvalidTokenException('Token inválido o expirado');     }     $relationship->setStatus('active');     $relationship->setLastAccessAt(new DateTime());     $relationship->save();     return $relationship;   }    public function getAccessibleDocuments(     PartnerRelationship $relationship   ): array {     return $this->documentService->getForPartner(       $relationship->getProducerId(),       $relationship->getAccessLevel(),       $relationship->getPartnerType(),       $relationship->getAllowedProducts(),       $relationship->getAllowedCategories()     );   } } 
4.2 TechSheetGeneratorService
Genera fichas técnicas PDF automáticamente desde los datos del producto.
<?php  namespace Drupal\agroconecta_hub\Service;  class TechSheetGeneratorService {    public function generateForProduct(     ProductAgro $product,     string $language = 'es'   ): ProductDocument {     // Recopilar datos del producto     $data = [       'product_name' => $product->getTitle(),       'producer_name' => $product->getProducer()->getName(),       'origin' => $product->getOriginRegion(),       'certifications' => $this->getCertificationNames($product),       'allergens' => $product->getAllergens(),       'storage_conditions' => $product->getStorageConditions(),       'best_before' => $product->getShelfLife(),       'nutritional_info' => $product->getNutritionalInfo(),       'ingredients' => $product->getIngredients(),       'formats' => $this->getAvailableFormats($product),     ];      // Renderizar plantilla Twig     $html = $this->twig->render(       '@agroconecta_hub/tech-sheet.html.twig',       ['data' => $data, 'language' => $language]     );      // Convertir a PDF con Gotenberg/wkhtmltopdf     $pdfContent = $this->pdfGenerator->fromHtml($html);      // Guardar como file_managed     $filename = sprintf(       'ficha-tecnica-%s-%s.pdf',       $product->getSku(),       date('Ymd')     );     $file = $this->fileRepository->writeData(       $pdfContent,       "private://hub-documents/$filename"     );      // Crear o actualizar product_document     return $this->documentService->createOrUpdate([       'producer_id' => $product->getProducerId(),       'product_id' => $product->id(),       'title' => "Ficha Técnica - {$product->getTitle()}",       'document_type' => 'ficha_tecnica',       'file_id' => $file->id(),       'is_auto_generated' => TRUE,       'language' => $language,     ]);   } } 
 
5. Portal Partner (Interfaz)
El partner accede a su portal mediante un magic link enviado por email. No necesita crear cuenta ni recordar contraseña.
5.1 Flujo de Acceso
1. Productor invita a partner desde su dashboard
2. Partner recibe email con magic link único
3. Al hacer clic, se activa la relación y accede al portal
4. Token se regenera periódicamente por seguridad
5. Partner puede solicitar nuevo magic link en cualquier momento
5.2 Wireframe del Portal Partner
┌─────────────────────────────────────────────────────────────────────────┐ │  🌿 AgroConecta                        Partner: Distribuidora Sur S.L. │ │  ═══════════════════════════════════════════════════════════════════   │ │                                                                         │ │  Productor: FINCA LOS OLIVOS                    Nivel: Verificado ✓    │ │  Último acceso: 17/01/2026 10:30                                       │ │                                                                         │ │  ┌────────────────────────────────────────────────────────────────┐    │ │  │  🔔 DOCUMENTOS ACTUALIZADOS RECIENTEMENTE                      │    │ │  ├────────────────────────────────────────────────────────────────┤    │ │  │  📄 Ficha Técnica - AOVE Picual 750ml  (Actualizado 15/01)     │    │ │  │  📄 Certificado Ecológico 2026         (Nuevo 10/01)           │    │ │  └────────────────────────────────────────────────────────────────┘    │ │                                                                         │ │  ┌────────────────────────────────────────────────────────────────┐    │ │  │  📦 PRODUCTOS DISPONIBLES                     [Buscar...]      │    │ │  ├────────────────────────────────────────────────────────────────┤    │ │  │                                                                │    │ │  │  🫒 AOVE Picual 750ml              🫒 AOVE Hojiblanca 500ml    │    │ │  │  ├─ 📄 Ficha Técnica              ├─ 📄 Ficha Técnica         │    │ │  │  ├─ 📄 Analítica 2026             ├─ 📄 Analítica 2026        │    │ │  │  ├─ 📄 Cert. Ecológico            ├─ 📄 Cert. Ecológico       │    │ │  │  └─ 📸 Imágenes HD                └─ 📸 Imágenes HD           │    │ │  │  [⬇️ Descargar Pack]               [⬇️ Descargar Pack]         │    │ │  │                                                                │    │ │  └────────────────────────────────────────────────────────────────┘    │ │                                                                         │ │  ┌────────────────────────────────────────────────────────────────┐    │ │  │  📂 DOCUMENTACIÓN GENERAL DEL PRODUCTOR                        │    │ │  ├────────────────────────────────────────────────────────────────┤    │ │  │  📄 Catálogo 2026 Completo                     [⬇️ Descargar]  │    │ │  │  📄 Certificado DOP Priego de Córdoba          [⬇️ Descargar]  │    │ │  │  📄 Argumentario de Ventas                     [⬇️ Descargar]  │    │ │  │  📄 Historia de la Finca                       [⬇️ Descargar]  │    │ │  └────────────────────────────────────────────────────────────────┘    │ │                                                                         │ │  [📦 Descargar Todo en ZIP]    [📧 Contactar al Productor]            │ └─────────────────────────────────────────────────────────────────────────┘
 
6. APIs REST
6.1 APIs para Productor
Método	Endpoint	Descripción
POST	/api/v1/hub/partners	Crear nueva relación con partner
GET	/api/v1/hub/partners	Listar partners del productor
PATCH	/api/v1/hub/partners/{uuid}	Actualizar relación (nivel, permisos)
DELETE	/api/v1/hub/partners/{uuid}	Revocar acceso a partner
POST	/api/v1/hub/documents	Subir nuevo documento
GET	/api/v1/hub/documents	Listar documentos del productor
PATCH	/api/v1/hub/documents/{uuid}	Actualizar documento (permisos, validez)
DELETE	/api/v1/hub/documents/{uuid}	Desactivar documento
POST	/api/v1/hub/documents/generate/{product_id}	Generar ficha técnica automática
GET	/api/v1/hub/analytics	Estadísticas de descargas
6.2 APIs para Partner (Portal)
Método	Endpoint	Descripción
GET	/api/v1/portal/{token}	Obtener datos del portal (productor, productos)
GET	/api/v1/portal/{token}/products	Listar productos accesibles
GET	/api/v1/portal/{token}/products/{id}/documents	Documentos de un producto
GET	/api/v1/portal/{token}/documents	Todos los documentos accesibles
GET	/api/v1/portal/{token}/documents/{uuid}/download	Descargar documento individual
POST	/api/v1/portal/{token}/products/{id}/download-pack	Descargar pack ZIP del producto
POST	/api/v1/portal/{token}/download-all	Descargar todo en ZIP
POST	/api/v1/portal/request-link	Solicitar nuevo magic link por email
 
7. Flujos de Automatización (ECA)
Código	Evento	Acciones
HUB-001	partner.created	Email de invitación con magic link al partner + notificación al productor
HUB-002	partner.activated	Notificar al productor que el partner aceptó + enviar welcome kit
HUB-003	document.created	Notificar a partners activos con acceso a ese documento/producto
HUB-004	document.updated	Email a partners que descargaron versión anterior: 'Nueva versión disponible'
HUB-005	product.updated	Regenerar ficha técnica automática si is_auto_generated = TRUE
HUB-006	certification.expiring_soon	Alerta al productor 30/15/7 días antes de caducidad de certificación
HUB-007	cron.weekly	Resumen semanal al productor: descargas, partners activos, docs populares
HUB-008	partner.inactive_30d	Notificar al productor de partner inactivo + sugerir re-engagement
HUB-009	document.downloaded	Incrementar contador + registrar en audit log
HUB-010	partner.revoked	Notificar al partner que su acceso ha sido revocado
 
8. Analytics para el Productor
8.1 Métricas del Dashboard
Métrica	Descripción	Actualización
Partners Activos	Número de partners con acceso activo	Tiempo real
Descargas Totales	Total de descargas de todos los documentos	Tiempo real
Descargas Última Semana	Descargas en los últimos 7 días	Diaria
Documento Más Popular	Documento con más descargas en el mes	Diaria
Partner Más Activo	Partner con más descargas en el mes	Diaria
Certificaciones por Caducar	Certificaciones que caducan en 30 días	Diaria
Docs Sin Descargar	Documentos subidos sin ninguna descarga	Semanal
8.2 Reportes Disponibles
• Descargas por documento (período seleccionable)
• Descargas por partner (quién descarga más)
• Actividad de partners (últimos accesos)
• Documentos por producto (completitud)
• Export CSV de todas las descargas para auditoría
 
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 4.1	Semana 7	Entidades partner_relationship + product_document + download_log	47_Commerce_Core
Sprint 4.2	Semana 8	PartnerRelationshipService + PartnerDocumentService + APIs productor	Sprint 4.1
Sprint 4.3	Semana 9	TechSheetGeneratorService + plantillas Twig + generación PDF	Sprint 4.2
Sprint 4.4	Semana 10	Portal Partner (UI) + APIs partner + magic link + ZIP generation	Sprint 4.3
Sprint 4.5	Semana 11	Dashboard productor + analytics + flujos ECA + notificaciones	Sprint 4.4
Sprint 4.6	Semana 12	Testing E2E + optimización + documentación + deploy	Sprint 4.5
9.1 Criterios de Aceptación
☐ Productor puede invitar partners con diferentes niveles de acceso
☐ Partner recibe magic link y puede acceder sin crear cuenta
☐ Partner solo ve documentos según su nivel y tipo
☐ Fichas técnicas se generan automáticamente desde datos del producto
☐ Partner puede descargar documentos individuales o packs ZIP
☐ Productor ve estadísticas de descargas y partners
☐ Alertas de certificaciones próximas a caducar funcionando
☐ Notificaciones automáticas cuando hay documentos nuevos/actualizados
☐ Audit log completo de todas las operaciones
9.2 Estimación de Esfuerzo
Componente	Horas Est.	Complejidad
Modelo de datos y migraciones	8-12h	Media
Servicios PHP (3 servicios principales)	24-32h	Alta
APIs REST (productor + partner)	16-24h	Media
Generador de fichas técnicas PDF	12-16h	Alta
Portal Partner (UI)	16-24h	Media
Dashboard productor + analytics	12-16h	Media
Flujos ECA + notificaciones	8-12h	Baja
Testing + documentación	8-12h	Baja
TOTAL ESTIMADO	104-148h	6 sprints
--- Fin del Documento ---
