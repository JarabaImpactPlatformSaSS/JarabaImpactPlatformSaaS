SERVICE OFFERINGS
Catálogo de Servicios Profesionales
Duración + Precio + Modalidad + Reserva Online
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	84_ServiciosConecta_Service_Offerings
Dependencias:	82_Services_Core, 83_Provider_Profile
Relacionado:	85_Booking_Engine, 92_Presupuestador_Auto
Prioridad:	CRÍTICA - Define qué se puede reservar
 
1. Resumen Ejecutivo
El módulo Service Offerings gestiona el catálogo de servicios que cada profesional ofrece a través de la plataforma. A diferencia de un e-commerce de productos físicos, los servicios profesionales tienen características únicas: duración variable, múltiples modalidades (presencial/online/domicilio), precios que pueden ser fijos, por hora, o bajo presupuesto, y requisitos específicos de preparación.
Este módulo conecta el perfil del profesional (83_Provider_Profile) con el motor de reservas (85_Booking_Engine), permitiendo que los clientes descubran, comparen y reserven servicios específicos con información completa sobre qué incluye, cuánto dura, cómo se presta y cuánto cuesta.
1.1 Tipos de Servicios Profesionales
Tipo	Ejemplo	Características
Consulta puntual	Primera consulta legal, revisión médica	Duración fija, precio fijo, reservable online
Servicio completo	Divorcio de mutuo acuerdo, proyecto básico	Duración variable, precio cerrado, hitos
Por horas	Asesoría fiscal, coaching empresarial	Tarifa/hora, duración estimada
Paquete/Bono	Bono 5 sesiones fisioterapia	Precio paquete, múltiples citas
Bajo presupuesto	Reforma integral, pleito complejo	Sin precio público, requiere valoración
Suscripción	Asesoría fiscal mensual, mantenimiento	Cuota recurrente, servicios incluidos

1.2 Flujo del Cliente
┌─────────────────────────────────────────────────────────────────────────┐
│              FLUJO DE DESCUBRIMIENTO Y RESERVA                          │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │   Buscar     │───▶│    Ver       │───▶│   Comparar   │              │
│  │  Categoría   │    │  Servicios   │    │   Opciones   │              │
│  └──────────────┘    └──────────────┘    └───────┬──────┘              │
│                                                   │                     │
│         ┌────────────────────────────────────────┘                     │
│         │                                                              │
│         ▼                                                              │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐              │
│  │  Seleccionar │───▶│    Elegir    │───▶│   Reservar   │              │
│  │   Servicio   │    │  Fecha/Hora  │    │  + Pagar     │              │
│  └──────────────┘    └──────────────┘    └──────────────┘              │
└─────────────────────────────────────────────────────────────────────────┘
 
2. Modelo de Datos
2.1 Entidad: service_offering (Servicio Ofertado)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
tenant_id	INT	Tenant	FK tenant.id, NOT NULL, INDEX
provider_id	INT	Profesional que lo ofrece	FK provider_profile.id, NOT NULL
name	VARCHAR(150)	Nombre del servicio	NOT NULL
slug	VARCHAR(100)	URL amigable	UNIQUE per provider
short_description	VARCHAR(300)	Descripción corta	Para listados
full_description	TEXT	Descripción completa	Markdown permitido
category_id	INT	Categoría de servicio	FK service_category.id
service_type	VARCHAR(32)	Tipo de servicio	consultation|full|hourly|package|quote|subscription
duration_minutes	INT	Duración en minutos	NULLABLE (si variable)
duration_range	JSON	Rango de duración	{min: 30, max: 60}
price_type	VARCHAR(16)	Tipo de precio	fixed|hourly|range|quote|free
price	INT	Precio en céntimos	NULLABLE, ej: 8000 = 80€
price_range	JSON	Rango de precio	{min: 50, max: 150}
currency	VARCHAR(3)	Moneda	DEFAULT 'EUR'
tax_rate	DECIMAL(5,2)	% IVA aplicable	DEFAULT 21.00
modalities	JSON	Modalidades disponibles	['presencial','online','domicilio']
requires_prepayment	BOOLEAN	¿Requiere pago previo?	DEFAULT FALSE
prepayment_amount	INT	Cantidad a pagar por adelantado	NULLABLE (% o fijo)
cancellation_policy	VARCHAR(32)	Política de cancelación	flexible|moderate|strict
cancellation_hours	INT	Horas mínimas para cancelar	DEFAULT 24
what_includes	JSON	Qué incluye el servicio	['Análisis inicial', 'Informe']
what_to_bring	JSON	Qué debe traer el cliente	['DNI', 'Documentación previa']
faqs	JSON	Preguntas frecuentes	[{q, a}]
is_featured	BOOLEAN	¿Destacado?	DEFAULT FALSE
is_bookable_online	BOOLEAN	¿Reservable online?	DEFAULT TRUE
is_active	BOOLEAN	¿Activo?	DEFAULT TRUE
display_order	INT	Orden de visualización	DEFAULT 0
created	DATETIME	Fecha creación	NOT NULL
updated	DATETIME	Última actualización	NOT NULL

 
2.2 Entidad: service_package (Paquetes/Bonos)
Para servicios que se venden como paquetes de múltiples sesiones:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
service_id	INT	Servicio base	FK service_offering.id
name	VARCHAR(100)	Nombre del paquete	'Bono 5 sesiones'
sessions_included	INT	Número de sesiones	NOT NULL
price	INT	Precio total paquete	cents
savings_percent	DECIMAL(5,2)	% ahorro vs individual	Calculado
validity_days	INT	Días de validez	DEFAULT 90
is_active	BOOLEAN	¿Activo?	DEFAULT TRUE

2.3 Entidad: client_package (Paquete Comprado)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
package_id	INT	Paquete comprado	FK service_package.id
client_id	INT	Cliente	FK client.id
sessions_total	INT	Sesiones totales	Copiado de package
sessions_used	INT	Sesiones usadas	DEFAULT 0
sessions_remaining	INT	Sesiones restantes	Calculado
purchased_at	DATETIME	Fecha de compra	NOT NULL
expires_at	DATETIME	Fecha de expiración	purchased_at + validity_days
status	VARCHAR(16)	Estado	active|exhausted|expired

 
3. Servicios Principales
3.1 ServiceOfferingService
<?php namespace Drupal\jaraba_services\Service;

class ServiceOfferingService {
  
  public function createService(ProviderProfile $provider, array $data): ServiceOffering {
    $slug = $this->generateUniqueSlug($data['name'], $provider->id());
    
    $service = ServiceOffering::create([
      'tenant_id' => $provider->getTenantId(),
      'provider_id' => $provider->id(),
      'name' => $data['name'],
      'slug' => $slug,
      'short_description' => $data['short_description'],
      'full_description' => $data['full_description'] ?? null,
      'category_id' => $data['category_id'],
      'service_type' => $data['service_type'],
      'duration_minutes' => $data['duration_minutes'] ?? null,
      'price_type' => $data['price_type'],
      'price' => $data['price'] ?? null,
      'modalities' => $data['modalities'] ?? ['presencial'],
      'requires_prepayment' => $data['requires_prepayment'] ?? false,
      'cancellation_policy' => $data['cancellation_policy'] ?? 'moderate',
      'what_includes' => $data['what_includes'] ?? [],
      'what_to_bring' => $data['what_to_bring'] ?? [],
      'is_bookable_online' => $data['is_bookable_online'] ?? true,
    ]);
    
    $this->eventDispatcher->dispatch(new ServiceCreatedEvent($service));
    return $service;
  }
  
  public function getBookableServices(ProviderProfile $provider): array {
    return $this->repository->findBy([
      'provider_id' => $provider->id(),
      'is_active' => true,
      'is_bookable_online' => true,
    ], ['display_order' => 'ASC']);
  }
  
  public function calculatePrice(ServiceOffering $service, array $options = []): PriceCalculation {
    $basePrice = match($service->getPriceType()) {
      'fixed' => $service->getPrice(),
      'hourly' => $service->getPrice() * ($options['hours'] ?? 1),
      'range' => $options['selected_price'] ?? $service->getPriceRange()['min'] * 100,
      'quote' => null, // Requiere presupuesto
      'free' => 0,
    };
    
    $taxAmount = $basePrice ? round($basePrice * ($service->getTaxRate() / 100)) : null;
    
    return new PriceCalculation(
      subtotal: $basePrice,
      tax_rate: $service->getTaxRate(),
      tax_amount: $taxAmount,
      total: $basePrice ? $basePrice + $taxAmount : null,
      requires_quote: $service->getPriceType() === 'quote'
    );
  }
  
  public function searchServices(array $filters): array {
    $query = $this->repository->createQueryBuilder();
    
    if (!empty($filters['category'])) {
      $query->condition('category_id', $filters['category']);
    }
    if (!empty($filters['modality'])) {
      $query->condition('modalities', $filters['modality'], 'CONTAINS');
    }
    if (!empty($filters['max_price'])) {
      $query->condition('price', $filters['max_price'] * 100, '<=');
    }
    if (!empty($filters['location'])) {
      // Join con provider_profile para filtrar por área de servicio
      $query->join('provider_profile', 'p', 'service.provider_id = p.id');
      $query->condition('p.service_area', $filters['location'], 'GEO_WITHIN');
    }
    
    $query->condition('is_active', true);
    $query->condition('is_bookable_online', true);
    
    return $query->execute();
  }
}

 
4. APIs REST
Método	Endpoint	Descripción	Auth
POST	/api/v1/services	Crear servicio	Provider
GET	/api/v1/services	Buscar servicios (público)	Public
GET	/api/v1/services/{uuid}	Detalle de servicio	Public
PUT	/api/v1/services/{uuid}	Actualizar servicio	Provider
DELETE	/api/v1/services/{uuid}	Desactivar servicio	Provider
GET	/api/v1/providers/{id}/services	Servicios de un profesional	Public
POST	/api/v1/services/{uuid}/packages	Crear paquete/bono	Provider
GET	/api/v1/services/{uuid}/packages	Listar paquetes de un servicio	Public
POST	/api/v1/services/{uuid}/calculate-price	Calcular precio con opciones	Public
GET	/api/v1/clients/me/packages	Mis paquetes comprados	Client

5. Ejemplos por Categoría Profesional
Profesión	Servicio	Duración	Precio	Tipo
Abogado	Primera consulta legal	30 min	50€	consultation
Abogado	Divorcio mutuo acuerdo	Variable	800-1.200€	full
Fisioterapeuta	Sesión de fisioterapia	45 min	40€	consultation
Fisioterapeuta	Bono 10 sesiones	10x45 min	350€	package
Arquitecto	Consulta técnica	1 hora	75€	hourly
Arquitecto	Proyecto básico vivienda	Variable	Presupuesto	quote
Asesor fiscal	Declaración IRPF	Variable	60-120€	fixed
Asesor fiscal	Asesoría mensual autónomo	Mensual	50€/mes	subscription
Psicólogo	Sesión de terapia	50 min	60€	consultation

6. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 2.1	Semana 4	Entidad service_offering + APIs CRUD + búsqueda básica	83_Provider_Profile
Sprint 2.2	Semana 5	Paquetes/bonos + client_package + cálculo de precios	Sprint 2.1
Sprint 2.3	Semana 6	Búsqueda avanzada + filtros + integración con Booking	Sprint 2.2, 85_Booking

6.1 Criterios de Aceptación
•	✓ Profesional puede crear servicio con todos los tipos de precio
•	✓ Cliente puede buscar servicios por categoría, modalidad, precio
•	✓ Cálculo de precio funciona correctamente con IVA
•	✓ Paquetes muestran ahorro vs precio individual
•	✓ Servicios se integran con motor de reservas
•	✓ Página pública de servicio con toda la información

--- Fin del Documento ---
