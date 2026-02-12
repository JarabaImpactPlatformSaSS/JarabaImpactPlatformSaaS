PORTAL CLIENTE DOCUMENTAL
Carpeta Digital del Expediente Cliente-Profesional
Solicitud, Entrega y Puesta a Disposición de Documentación
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	90_ServiciosConecta_Portal_Cliente_Documental
Dependencias:	82_Services_Core, 88_Buzon_Confianza
Prioridad:	CRÍTICA - Workflow diario de profesionales
Compliance:	RGPD, LOPD-GDD, secreto profesional
 
1. Resumen Ejecutivo
El Portal Cliente Documental es la capa de workflow que transforma el Buzón de Confianza (doc 88) en una herramienta de gestión de expedientes completa. Permite a profesionales (abogados, asesores, arquitectos, médicos) solicitar documentación a sus clientes con checklists estructurados, fechas límite y recordatorios automáticos, así como poner documentos a disposición del cliente con notificaciones y confirmación de recepción.
Este componente resuelve el caos documental que sufren los profesionales liberales: emails perdidos, WhatsApps con fotos de documentos, WeTransfer sin organización, y la eterna pregunta '¿me enviaste ya las facturas?'. El portal proporciona un único punto de intercambio documental seguro, trazable y automatizado.
1.1 El Problema: Caos Documental Profesional
Canal Actual	Problemas	Consecuencias
Email	Sin cifrado, archivos dispersos, búsqueda difícil	Documentos sensibles expuestos, pérdida de tiempo
WhatsApp	Fotos de baja calidad, sin organización, sin trazabilidad	DNIs ilegibles, historial perdido al cambiar móvil
WeTransfer	Links que expiran, sin estructura, sin confirmación	'No me llegó el archivo', documentos eliminados
Dropbox/Drive	Sin workflow, permisos confusos, sin notificaciones	Cliente no sabe qué subir ni dónde, carpetas caóticas
En persona	Requiere cita, documentos físicos, sin copia digital	Ineficiente, riesgo de pérdida, sin trazabilidad

1.2 La Solución: Portal Cliente Documental
•	Expediente digital único: Toda la documentación de un cliente/asunto en un solo lugar cifrado
•	Checklist de documentos requeridos: El profesional define qué necesita con instrucciones claras
•	Fechas límite y recordatorios: Sistema automático que persigue al cliente hasta que entregue
•	Revisión y feedback: El profesional aprueba o rechaza con comentarios específicos
•	Puesta a disposición: Notificación multicanal cuando hay documentos listos para el cliente
•	Confirmación de recepción: Prueba legal de que el cliente recibió/descargó el documento
•	Trazabilidad completa: Audit log de todas las operaciones para auditorías y reclamaciones
1.3 Casos de Uso por Profesión
Profesión	Solicita al Cliente	Pone a Disposición
Asesoría Fiscal	Facturas trimestrales, nóminas, extractos bancarios, modelo 347, certificados retenciones	Declaraciones IVA/IRPF, impuesto sociedades, cuentas anuales, certificados AEAT
Abogado	DNI, escrituras, contratos previos, poderes, pruebas documentales, sentencias previas	Demandas, recursos, escritos, sentencias, minutas, poderes para firma
Arquitecto	Fotos del terreno, escritura de propiedad, IBI, certificado catastral, cédula urbanística	Planos, memoria técnica, presupuesto, visado colegial, certificado final obra
Gestoría	Vida laboral, contratos trabajo, DNI empleados, TC2, certificados empresa	Altas/bajas SS, contratos laborales, nóminas, finiquitos, certificados
Médico privado	Informes previos, analíticas, pruebas imagen, historial medicación	Informes diagnóstico, recetas, partes baja, certificados médicos
Consultor	Datos empresa, informes internos, acceso sistemas, credenciales	Propuestas, informes análisis, presentaciones, entregables proyecto

 
2. Arquitectura del Sistema
2.1 Diagrama de Componentes
┌─────────────────────────────────────────────────────────────────────────┐
│                 PORTAL CLIENTE DOCUMENTAL                               │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────────┐              ┌──────────────────┐                 │
│  │   Vista          │              │   Vista          │                 │
│  │   PROFESIONAL    │              │   CLIENTE        │                 │
│  │   (Dashboard)    │              │   (Portal)       │                 │
│  └────────┬─────────┘              └────────┬─────────┘                 │
│           │                                 │                           │
│           └──────────────┬──────────────────┘                           │
│                          │                                              │
│                          ▼                                              │
│              ┌───────────────────────┐                                  │
│              │   ClientCaseService   │                                  │
│              │   (Gestión Expediente)│                                  │
│              └───────────┬───────────┘                                  │
│                          │                                              │
│     ┌────────────────────┼────────────────────┐                         │
│     │                    │                    │                         │
│     ▼                    ▼                    ▼                         │
│  ┌──────────┐    ┌──────────────┐    ┌──────────────┐                   │
│  │ Document │    │  Document    │    │ Notification │                   │
│  │ Request  │    │  Delivery    │    │   Service    │                   │
│  │ Service  │    │  Service     │    │              │                   │
│  └────┬─────┘    └──────┬───────┘    └──────┬───────┘                   │
│       │                 │                   │                           │
│       └─────────────────┼───────────────────┘                           │
│                         │                                               │
│                         ▼                                               │
│              ┌───────────────────────┐                                  │
│              │  BUZÓN DE CONFIANZA   │  (doc 88)                        │
│              │  - Cifrado AES-256    │                                  │
│              │  - Audit log inmutable│                                  │
│              │  - Control de acceso  │                                  │
│              └───────────────────────┘                                  │
└─────────────────────────────────────────────────────────────────────────┘
2.2 Relación con Buzón de Confianza (doc 88)
El Portal Cliente Documental NO reemplaza al Buzón de Confianza, sino que lo extiende con workflow:
Capa	Buzón Confianza (doc 88)	Portal Cliente (doc 90)
Almacenamiento	✅ secure_document cifrado E2E	Reutiliza secure_document
Cifrado	✅ AES-256-GCM zero-knowledge	Reutiliza cifrado existente
Audit log	✅ document_audit_log inmutable	Extiende con eventos de workflow
Expedientes	❌ Sin concepto de caso/expediente	✅ client_case con agrupación
Checklist	❌ Sin solicitudes estructuradas	✅ document_request con estados
Workflow	❌ Sin estados ni transiciones	✅ Pendiente→Subido→Revisado→Aprobado
Recordatorios	❌ Sin automatización	✅ Cron jobs + notificaciones
Vista cliente	❌ Solo acceso por token	✅ Portal dedicado con UX

 
3. Modelo de Datos
3.1 Entidad: client_case (Expediente)
Agrupa toda la documentación de un asunto cliente-profesional.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
case_number	VARCHAR(32)	Número de expediente	UNIQUE per tenant, AUTO (EXP-2026-0001)
provider_id	INT	Profesional responsable	FK provider_profile.id, NOT NULL
client_id	INT	Cliente del expediente	FK users.uid, NOT NULL, INDEX
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
title	VARCHAR(255)	Título del expediente	NOT NULL (ej: 'Renta 2025')
description	TEXT	Descripción/notas internas	NULLABLE
case_type_tid	INT	Tipo de expediente	FK taxonomy_term.tid (ej: Fiscal, Laboral)
client_access_token	VARCHAR(64)	Token de acceso al portal	UNIQUE, NOT NULL
status	VARCHAR(16)	Estado del expediente	ENUM: active|on_hold|completed|archived
priority	VARCHAR(16)	Prioridad	ENUM: low|normal|high|urgent
due_date	DATE	Fecha objetivo del expediente	NULLABLE
opened_at	DATETIME	Fecha apertura	NOT NULL
closed_at	DATETIME	Fecha cierre	NULLABLE
created	DATETIME	Fecha creación registro	NOT NULL
changed	DATETIME	Última modificación	NOT NULL

3.2 Entidad: document_request (Solicitud de Documento)
Cada documento que el profesional solicita al cliente, con instrucciones y fecha límite.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
case_id	INT	Expediente	FK client_case.id, NOT NULL, INDEX
document_type_tid	INT	Tipo de documento	FK taxonomy_term.tid (DNI, Factura...)
title	VARCHAR(255)	Nombre del documento	NOT NULL (ej: 'DNI ambas caras')
instructions	TEXT	Instrucciones para el cliente	NULLABLE (ej: 'Fotos legibles, sin flash')
is_required	BOOLEAN	Obligatorio u opcional	DEFAULT TRUE
deadline	DATE	Fecha límite de entrega	NULLABLE
status	VARCHAR(16)	Estado de la solicitud	ENUM: pending|uploaded|reviewing|approved|rejected
uploaded_document_id	INT	Documento subido por cliente	FK secure_document.id, NULLABLE
uploaded_at	DATETIME	Fecha de subida	NULLABLE
reviewed_at	DATETIME	Fecha de revisión	NULLABLE
reviewed_by	INT	Quién revisó	FK users.uid, NULLABLE
rejection_reason	TEXT	Motivo de rechazo	NULLABLE (ej: 'Imagen borrosa')
reminder_count	INT	Recordatorios enviados	DEFAULT 0
last_reminder_at	DATETIME	Último recordatorio	NULLABLE
created	DATETIME	Fecha creación	NOT NULL

 
3.3 Entidad: document_delivery (Puesta a Disposición)
Cuando el profesional pone un documento a disposición del cliente con notificación y confirmación.
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
case_id	INT	Expediente	FK client_case.id, NOT NULL, INDEX
document_id	INT	Documento entregado	FK secure_document.id, NOT NULL
delivered_by	INT	Profesional que entrega	FK users.uid, NOT NULL
recipient_id	INT	Cliente destinatario	FK users.uid, NOT NULL
message	TEXT	Mensaje para el cliente	NULLABLE (ej: 'Tu declaración está lista')
notification_channels	JSON	Canales de notificación	["email", "whatsapp", "push"]
requires_acknowledgment	BOOLEAN	Requiere confirmación	DEFAULT FALSE
requires_signature	BOOLEAN	Requiere firma digital	DEFAULT FALSE
signature_request_id	INT	Solicitud de firma	FK signature_request.id, NULLABLE
status	VARCHAR(16)	Estado de la entrega	ENUM: sent|notified|viewed|downloaded|acknowledged|signed
notified_at	DATETIME	Fecha notificación enviada	NOT NULL
viewed_at	DATETIME	Fecha primera visualización	NULLABLE
downloaded_at	DATETIME	Fecha primera descarga	NULLABLE
acknowledged_at	DATETIME	Fecha confirmación recepción	NULLABLE
download_count	INT	Número de descargas	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL

3.4 Entidad: case_activity (Historial de Actividad)
Timeline de todas las acciones del expediente, visible tanto para profesional como cliente.
Campo	Tipo	Descripción	Restricciones
id	BIGSERIAL	ID autoincremental	PRIMARY KEY
case_id	INT	Expediente	FK client_case.id, NOT NULL, INDEX
activity_type	VARCHAR(32)	Tipo de actividad	ENUM: ver tabla siguiente
actor_id	INT	Quién realizó la acción	FK users.uid, NULLABLE (sistema)
actor_role	VARCHAR(16)	Rol del actor	ENUM: provider|client|system
subject_type	VARCHAR(32)	Tipo de objeto afectado	document_request|document_delivery|case
subject_id	INT	ID del objeto afectado	NULLABLE
description	VARCHAR(255)	Descripción legible	NOT NULL (ej: 'Juan subió DNI.pdf')
details	JSON	Detalles adicionales	NULLABLE
is_visible_to_client	BOOLEAN	Visible en portal cliente	DEFAULT TRUE
created	DATETIME	Fecha de la actividad	NOT NULL, INDEX

Tipos de Actividad
activity_type	Descripción	Visible Cliente
case_opened	Expediente creado	✅
document_requested	Profesional solicitó documento	✅
document_uploaded	Cliente subió documento	✅
document_approved	Profesional aprobó documento	✅
document_rejected	Profesional rechazó documento (con motivo)	✅
document_delivered	Profesional puso documento a disposición	✅
document_viewed	Cliente visualizó documento	✅
document_downloaded	Cliente descargó documento	✅
document_acknowledged	Cliente confirmó recepción	✅
reminder_sent	Sistema envió recordatorio	❌
case_note_added	Profesional añadió nota interna	❌
case_closed	Expediente cerrado	✅

 
4. Servicios Principales
4.1 ClientCaseService
<?php namespace Drupal\jaraba_portal\Service;

class ClientCaseService {
  
  public function createCase(
    int $providerId,
    int $clientId,
    string $title,
    array $options = []
  ): ClientCase {
    // Generar número de expediente único
    $caseNumber = $this->generateCaseNumber($providerId);
    
    // Generar token de acceso al portal
    $accessToken = bin2hex(random_bytes(32));
    
    $case = $this->repository->create([
      'case_number' => $caseNumber,
      'provider_id' => $providerId,
      'client_id' => $clientId,
      'tenant_id' => $this->getTenantId($providerId),
      'title' => $title,
      'client_access_token' => $accessToken,
      'status' => 'active',
      'priority' => $options['priority'] ?? 'normal',
      'due_date' => $options['due_date'] ?? null,
      'opened_at' => new DateTime(),
    ]);
    
    // Registrar actividad
    $this->activityService->log($case, 'case_opened');
    
    // Notificar al cliente
    $this->notificationService->sendCaseOpened($case);
    
    return $case;
  }
  
  public function addDocumentRequests(
    ClientCase $case,
    array $requests
  ): array {
    $created = [];
    
    foreach ($requests as $req) {
      $docRequest = $this->requestRepository->create([
        'case_id' => $case->id(),
        'title' => $req['title'],
        'document_type_tid' => $req['type_tid'] ?? null,
        'instructions' => $req['instructions'] ?? null,
        'is_required' => $req['is_required'] ?? true,
        'deadline' => $req['deadline'] ?? null,
        'status' => 'pending',
      ]);
      
      $this->activityService->log($case, 'document_requested', [
        'subject_type' => 'document_request',
        'subject_id' => $docRequest->id(),
        'description' => "Se solicitó: {$req['title']}",
      ]);
      
      $created[] = $docRequest;
    }
    
    // Notificar al cliente de los documentos pendientes
    $this->notificationService->sendDocumentsRequested($case, $created);
    
    return $created;
  }
  
  public function getCaseProgress(ClientCase $case): CaseProgress {
    $requests = $this->requestRepository->findByCase($case->id());
    
    $total = count($requests);
    $pending = 0;
    $uploaded = 0;
    $approved = 0;
    $rejected = 0;
    
    foreach ($requests as $req) {
      match ($req->getStatus()) {
        'pending' => $pending++,
        'uploaded', 'reviewing' => $uploaded++,
        'approved' => $approved++,
        'rejected' => $rejected++,
      };
    }
    
    return new CaseProgress(
      total: $total,
      pending: $pending,
      uploaded: $uploaded,
      approved: $approved,
      rejected: $rejected,
      percentComplete: $total > 0 ? round(($approved / $total) * 100) : 0
    );
  }
}

 
4.2 DocumentRequestService
<?php namespace Drupal\jaraba_portal\Service;

class DocumentRequestService {
  
  public function uploadDocument(
    DocumentRequest $request,
    int $clientId,
    UploadedFile $file,
    string $encryptedDek,
    string $iv,
    string $authTag
  ): DocumentRequest {
    // Verificar que el cliente tiene acceso a este expediente
    $case = $request->getCase();
    if ($case->getClientId() !== $clientId) {
      throw new AccessDeniedException('No tienes acceso a este expediente');
    }
    
    // Guardar documento en Buzón de Confianza
    $document = $this->vaultService->store(
      ownerId: $case->getProviderId(), // El profesional es propietario
      file: $file,
      encryptedDek: $encryptedDek,
      iv: $iv,
      authTag: $authTag,
      metadata: [
        'title' => $request->getTitle(),
        'original_filename' => $file->getClientOriginalName(),
        'mime_type' => $file->getMimeType(),
        'case_id' => $case->id(),
      ]
    );
    
    // Dar acceso al cliente a su propio documento subido
    $this->accessService->grantAccess($document, $clientId, ['view', 'download']);
    
    // Actualizar solicitud
    $request->setUploadedDocumentId($document->id());
    $request->setUploadedAt(new DateTime());
    $request->setStatus('uploaded');
    $this->requestRepository->save($request);
    
    // Registrar actividad
    $this->activityService->log($case, 'document_uploaded', [
      'subject_type' => 'document_request',
      'subject_id' => $request->id(),
      'description' => "Cliente subió: {$request->getTitle()}",
      'actor_role' => 'client',
    ]);
    
    // Notificar al profesional
    $this->notificationService->sendDocumentUploaded($request);
    
    return $request;
  }
  
  public function reviewDocument(
    DocumentRequest $request,
    int $reviewerId,
    string $decision, // 'approve' | 'reject'
    ?string $rejectionReason = null
  ): DocumentRequest {
    $case = $request->getCase();
    
    // Verificar que es el profesional del expediente
    if ($case->getProviderId() !== $this->getProviderIdForUser($reviewerId)) {
      throw new AccessDeniedException('No puedes revisar este documento');
    }
    
    $request->setReviewedAt(new DateTime());
    $request->setReviewedBy($reviewerId);
    
    if ($decision === 'approve') {
      $request->setStatus('approved');
      $activityType = 'document_approved';
      $description = "Documento aprobado: {$request->getTitle()}";
    } else {
      $request->setStatus('rejected');
      $request->setRejectionReason($rejectionReason);
      $activityType = 'document_rejected';
      $description = "Documento rechazado: {$request->getTitle()}. Motivo: {$rejectionReason}";
    }
    
    $this->requestRepository->save($request);
    
    // Registrar actividad
    $this->activityService->log($case, $activityType, [
      'subject_type' => 'document_request',
      'subject_id' => $request->id(),
      'description' => $description,
      'actor_role' => 'provider',
    ]);
    
    // Notificar al cliente
    if ($decision === 'reject') {
      $this->notificationService->sendDocumentRejected($request);
    }
    
    return $request;
  }
}

 
4.3 DocumentDeliveryService
<?php namespace Drupal\jaraba_portal\Service;

class DocumentDeliveryService {
  
  public function deliverDocument(
    ClientCase $case,
    SecureDocument $document,
    int $deliveredBy,
    array $options = []
  ): DocumentDelivery {
    // Crear registro de entrega
    $delivery = $this->deliveryRepository->create([
      'case_id' => $case->id(),
      'document_id' => $document->id(),
      'delivered_by' => $deliveredBy,
      'recipient_id' => $case->getClientId(),
      'message' => $options['message'] ?? null,
      'notification_channels' => $options['channels'] ?? ['email', 'whatsapp'],
      'requires_acknowledgment' => $options['requires_acknowledgment'] ?? false,
      'requires_signature' => $options['requires_signature'] ?? false,
      'status' => 'sent',
      'notified_at' => new DateTime(),
    ]);
    
    // Dar acceso al cliente al documento
    $permissions = ['view', 'download'];
    if ($options['requires_signature'] ?? false) {
      $permissions[] = 'sign';
    }
    $this->accessService->grantAccess($document, $case->getClientId(), $permissions);
    
    // Registrar actividad
    $this->activityService->log($case, 'document_delivered', [
      'subject_type' => 'document_delivery',
      'subject_id' => $delivery->id(),
      'description' => "Documento disponible: {$document->getTitle()}",
      'actor_role' => 'provider',
    ]);
    
    // Enviar notificaciones multicanal
    $this->notificationService->sendDocumentDelivered($delivery);
    
    // Si requiere firma, crear solicitud
    if ($options['requires_signature'] ?? false) {
      $signatureRequest = $this->signatureService->createRequest(
        $document,
        [['email' => $case->getClient()->getEmail(), 'name' => $case->getClient()->getName()]],
        $options['signature_options'] ?? []
      );
      $delivery->setSignatureRequestId($signatureRequest->id());
      $this->deliveryRepository->save($delivery);
    }
    
    return $delivery;
  }
  
  public function recordView(DocumentDelivery $delivery): void {
    if ($delivery->getViewedAt() === null) {
      $delivery->setViewedAt(new DateTime());
      $delivery->setStatus('viewed');
      $this->deliveryRepository->save($delivery);
      
      $this->activityService->log($delivery->getCase(), 'document_viewed', [
        'subject_type' => 'document_delivery',
        'subject_id' => $delivery->id(),
        'description' => "Cliente visualizó: {$delivery->getDocument()->getTitle()}",
        'actor_role' => 'client',
      ]);
    }
  }
  
  public function recordDownload(DocumentDelivery $delivery): void {
    $delivery->incrementDownloadCount();
    
    if ($delivery->getDownloadedAt() === null) {
      $delivery->setDownloadedAt(new DateTime());
      $delivery->setStatus('downloaded');
    }
    
    $this->deliveryRepository->save($delivery);
    
    $this->activityService->log($delivery->getCase(), 'document_downloaded', [
      'subject_type' => 'document_delivery',
      'subject_id' => $delivery->id(),
      'description' => "Cliente descargó: {$delivery->getDocument()->getTitle()}",
      'actor_role' => 'client',
    ]);
    
    // Notificar al profesional (configurable)
    $this->notificationService->sendDocumentDownloaded($delivery);
  }
  
  public function acknowledgeReceipt(DocumentDelivery $delivery, int $clientId): void {
    if ($delivery->getRecipientId() !== $clientId) {
      throw new AccessDeniedException('No puedes confirmar este documento');
    }
    
    $delivery->setAcknowledgedAt(new DateTime());
    $delivery->setStatus('acknowledged');
    $this->deliveryRepository->save($delivery);
    
    $this->activityService->log($delivery->getCase(), 'document_acknowledged', [
      'subject_type' => 'document_delivery',
      'subject_id' => $delivery->id(),
      'description' => "Cliente confirmó recepción: {$delivery->getDocument()->getTitle()}",
      'actor_role' => 'client',
    ]);
    
    // Notificar al profesional
    $this->notificationService->sendReceiptAcknowledged($delivery);
  }
}

 
5. Portal del Cliente (Interfaz)
El cliente accede a su portal mediante un link con token único enviado por email/WhatsApp. No necesita recordar contraseña si tiene sesión iniciada, o puede acceder con el token directamente.
5.1 Wireframe del Portal Cliente
┌─────────────────────────────────────────────────────────────────────────┐
│  🏢 García & Asociados Asesores                              [Salir]  │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  📁 Expediente: Declaración Renta 2025          Ref: EXP-2026-0042     │
│  ═══════════════════════════════════════════════════════════════════   │
│                                                                         │
│  Progreso: ████████████░░░░░░░  60%  (3 de 5 documentos aprobados)     │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  ⚠️  DOCUMENTOS PENDIENTES DE SUBIR (2)                         │   │
│  ├─────────────────────────────────────────────────────────────────┤   │
│  │  📄 Certificado de retenciones 2025                             │   │
│  │     ℹ️ Solicitar a su empresa el modelo 190                      │   │
│  │     📅 Fecha límite: 15/02/2026                                  │   │
│  │     [📤 Subir documento]                                         │   │
│  ├─────────────────────────────────────────────────────────────────┤   │
│  │  🔴 Extracto bancario diciembre (RECHAZADO)                     │   │
│  │     ❌ Motivo: Solo aparece la primera página, necesito completo │   │
│  │     [📤 Subir documento corregido]                               │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  📥  DOCUMENTOS DISPONIBLES PARA DESCARGAR (2)                  │   │
│  ├─────────────────────────────────────────────────────────────────┤   │
│  │  📄 Borrador_IRPF_2025.pdf                          🆕 NUEVO    │   │
│  │     💬 "Revisa el borrador y confirma si los datos son correctos" │   │
│  │     📅 Disponible desde: 17/01/2026                              │   │
│  │     [📥 Descargar]  [✅ Confirmar recepción]                     │   │
│  ├─────────────────────────────────────────────────────────────────┤   │
│  │  📄 Contrato_Servicios.pdf                       ✍️ PARA FIRMAR │   │
│  │     💬 "Firma el contrato para iniciar el servicio"              │   │
│  │     [📥 Descargar]  [✍️ Firmar con AutoFirma]  [✍️ Firmar con Cl@ve] │   │
│  └─────────────────────────────────────────────────────────────────┘   │
│                                                                         │
│  ┌─────────────────────────────────────────────────────────────────┐   │
│  │  📜 HISTORIAL DE ACTIVIDAD                                      │   │
│  ├─────────────────────────────────────────────────────────────────┤   │
│  │  17/01 10:30  📄 Nuevo documento disponible: Borrador_IRPF.pdf  │   │
│  │  16/01 14:15  🔴 Extracto bancario rechazado (incompleto)       │   │
│  │  15/01 09:00  📤 Subiste: Extracto_Bancario_Dic.pdf             │   │
│  │  14/01 11:20  ✅ Nómina diciembre aprobada                      │   │
│  │  [Ver historial completo...]                                    │   │
│  └─────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────┘
 
6. APIs REST
6.1 APIs para Profesional
Método	Endpoint	Descripción
POST	/api/v1/cases	Crear nuevo expediente
GET	/api/v1/cases	Listar expedientes del profesional
GET	/api/v1/cases/{uuid}	Detalle de expediente con progreso
PATCH	/api/v1/cases/{uuid}	Actualizar expediente (estado, prioridad)
POST	/api/v1/cases/{uuid}/requests	Añadir solicitudes de documentos
GET	/api/v1/cases/{uuid}/requests	Listar solicitudes del expediente
POST	/api/v1/requests/{uuid}/review	Aprobar o rechazar documento subido
POST	/api/v1/cases/{uuid}/deliver	Poner documento a disposición del cliente
GET	/api/v1/cases/{uuid}/deliveries	Listar entregas del expediente
GET	/api/v1/cases/{uuid}/activity	Historial de actividad completo
POST	/api/v1/cases/{uuid}/remind	Enviar recordatorio manual al cliente

6.2 APIs para Cliente (Portal)
Método	Endpoint	Descripción
GET	/api/v1/portal/{token}	Obtener datos del expediente para portal cliente
GET	/api/v1/portal/{token}/requests	Listar documentos pendientes de subir
POST	/api/v1/portal/{token}/requests/{id}/upload	Subir documento solicitado
GET	/api/v1/portal/{token}/deliveries	Listar documentos disponibles para descargar
GET	/api/v1/portal/{token}/deliveries/{id}/download	Descargar documento puesto a disposición
POST	/api/v1/portal/{token}/deliveries/{id}/acknowledge	Confirmar recepción de documento
GET	/api/v1/portal/{token}/activity	Historial de actividad (visible para cliente)

7. Flujos de Automatización (ECA)
Código	Evento	Acciones
PCD-001	case.created	Email + WhatsApp al cliente con link al portal + instrucciones
PCD-002	request.created	Notificar al cliente que hay nuevos documentos pendientes
PCD-003	request.uploaded	Notificar al profesional que el cliente subió documento
PCD-004	request.rejected	Notificar al cliente con motivo de rechazo + link para resubir
PCD-005	delivery.created	Notificación multicanal al cliente de documento disponible
PCD-006	delivery.downloaded	Notificar al profesional (configurable) + registrar en audit
PCD-007	delivery.acknowledged	Notificar al profesional de la confirmación de recepción
PCD-008	cron.daily	Recordatorios automáticos: docs pendientes > 3 días sin subir
PCD-009	request.deadline_approaching	Recordatorio urgente 48h antes de fecha límite
PCD-010	request.deadline_passed	Alerta al profesional de documento vencido sin entregar

 
8. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 6.1	Semana 13	Entidades client_case + document_request + case_activity	88_Buzon_Confianza
Sprint 6.2	Semana 14	ClientCaseService + DocumentRequestService + APIs profesional	Sprint 6.1
Sprint 6.3	Semana 15	DocumentDeliveryService + notificaciones multicanal + recordatorios	Sprint 6.2
Sprint 6.4	Semana 16	Portal cliente (UI) + APIs cliente + integración firma + tests E2E	Sprint 6.3 + 89_Firma_Digital

8.1 Criterios de Aceptación
•	✓ Profesional puede crear expediente y añadir checklist de documentos requeridos
•	✓ Cliente recibe notificación multicanal con link a su portal
•	✓ Cliente puede subir documentos cifrados desde el portal
•	✓ Profesional puede aprobar/rechazar con feedback específico
•	✓ Profesional puede poner documentos a disposición con notificación
•	✓ Cliente puede descargar y confirmar recepción
•	✓ Recordatorios automáticos funcionando (3 días, 48h antes deadline)
•	✓ Historial de actividad visible para ambas partes
•	✓ Integración con firma digital para documentos que requieren firma

--- Fin del Documento ---
