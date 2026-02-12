PROVIDER PROFILE
Perfil Profesional y Credenciales
Especialidades + Colegiación + Tarifas + Área de Cobertura
Vertical ServiciosConecta - JARABA IMPACT PLATFORM
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	83_ServiciosConecta_Provider_Profile
Dependencias:	82_Services_Core, 07_MultiTenant
Integraciones:	Colegios Profesionales, Schema.org
Prioridad:	CRÍTICA - Base de identidad profesional
 
1. Resumen Ejecutivo
El módulo Provider Profile gestiona la identidad profesional completa de cada proveedor de servicios en la plataforma. A diferencia del perfil de usuario básico (Drupal user), el provider_profile contiene información específica del ejercicio profesional: especialidades, credenciales verificables, número de colegiación, tarifas, área de cobertura geográfica, y configuración de disponibilidad.
Este perfil es la base para el SEO profesional (Schema.org ProfessionalService), la generación de confianza (credenciales verificadas), y el matching inteligente entre clientes y profesionales según especialidad, ubicación y disponibilidad.
1.1 El Problema: Perfil Genérico Insuficiente
Situación Actual	Problema	Consecuencia
Perfil básico Drupal	Solo nombre, email, foto. Sin datos profesionales	No transmite confianza ni credibilidad
Sin especialidades	Cliente no sabe si el profesional maneja su caso	Matching ineficiente, tiempo perdido
Sin colegiación	No hay verificación de ejercicio legal	Riesgo legal y de reputación
Sin tarifas públicas	Cliente no conoce el coste aproximado	Abandono por incertidumbre de precio
Sin área de servicio	No se sabe si atiende en mi zona	Contactos inútiles, frustración

1.2 La Solución: Perfil Profesional Completo
•	Especialidades categorizadas: Taxonomía de áreas de práctica con niveles de experiencia
•	Credenciales verificables: Número de colegiación, licencias, seguros de RC
•	Tarifas configurables: Por hora, por servicio, rangos orientativos
•	Área de cobertura: Radio de servicio presencial + disponibilidad online
•	Experiencia y formación: CV profesional, certificaciones, casos destacados
•	SEO profesional: Schema.org markup para rich snippets en Google
•	Página pública: Landing page profesional con toda la información
 
2. Modelo de Datos
2.1 Entidad: provider_profile (Perfil Profesional)
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
uuid	UUID	Identificador público	UNIQUE, NOT NULL
user_id	INT	Usuario Drupal vinculado	FK users.uid, UNIQUE, NOT NULL
tenant_id	INT	Tenant (despacho)	FK tenant.id, NOT NULL, INDEX
display_name	VARCHAR(150)	Nombre profesional	NOT NULL
professional_title	VARCHAR(100)	Título profesional	'Abogado', 'Arquitecto'...
headline	VARCHAR(200)	Eslogan / tagline	'Especialista en...'
bio	TEXT	Biografía profesional	Max 2000 chars
photo_url	VARCHAR(255)	Foto profesional	URL a imagen
category_id	INT	Categoría principal	FK service_category.id
specialties	JSON	Especialidades	[{id, name, level}]
credentials	JSON	Credenciales verificadas	Ver estructura abajo
hourly_rate	INT	Tarifa por hora (cents)	NULLABLE, ej: 8000 = 80€
rate_range	JSON	Rango de tarifas	{min: 50, max: 150, currency: 'EUR'}
consultation_fee	INT	Tarifa primera consulta	NULLABLE (0 = gratis)
service_modalities	JSON	Modalidades de servicio	['presencial','online','domicilio']
service_area	JSON	Área de cobertura	{lat, lng, radius_km, zones[]}
languages	JSON	Idiomas	['es','en','fr']
years_experience	INT	Años de experiencia	NULLABLE
education	JSON	Formación académica	[{degree, institution, year}]
certifications	JSON	Certificaciones	[{name, issuer, date, url}]
social_links	JSON	Redes sociales	{linkedin, twitter, website}
is_accepting_clients	BOOLEAN	¿Acepta nuevos clientes?	DEFAULT TRUE
is_verified	BOOLEAN	¿Perfil verificado?	DEFAULT FALSE
verified_at	DATETIME	Fecha verificación	NULLABLE
is_public	BOOLEAN	¿Perfil público?	DEFAULT TRUE
slug	VARCHAR(100)	URL amigable	UNIQUE, 'elena-martinez-abogada'
seo_metadata	JSON	Meta tags SEO	{title, description, keywords}
created	DATETIME	Fecha creación	NOT NULL
updated	DATETIME	Última actualización	NOT NULL

 
2.2 Estructura del Campo credentials (JSON)
{
  "college_registration": {
    "college_name": "Ilustre Colegio de Abogados de Córdoba",
    "college_code": "ICACOR",
    "registration_number": "4521",
    "registration_date": "2010-03-15",
    "status": "active",
    "verification_url": "https://icacor.es/verificar/4521",
    "verified": true,
    "verified_at": "2026-01-10T10:30:00Z"
  },
  "professional_license": {
    "license_type": "Ejercicio profesional",
    "license_number": "AB-2010-4521",
    "issuing_authority": "Ministerio de Justicia",
    "valid_until": "2027-12-31",
    "verified": true
  },
  "liability_insurance": {
    "insurer": "AXA Seguros",
    "policy_number": "RC-2024-987654",
    "coverage_amount": 300000,
    "currency": "EUR",
    "valid_from": "2024-01-01",
    "valid_until": "2026-12-31",
    "verified": true
  },
  "digital_certificate": {
    "type": "FNMT",
    "serial_number": "XXX...XXX",
    "valid_until": "2028-05-15",
    "can_sign": true
  }
}

2.3 Taxonomía: service_category (Categorías de Servicio)
Código	Categoría	Schema.org Type
legal	Legal (Abogados, Procuradores)	Attorney, LegalService
health	Salud (Médicos, Fisios, Psicólogos)	Physician, MedicalBusiness
technical	Técnico (Arquitectos, Ingenieros, Peritos)	ProfessionalService
financial	Financiero (Asesores fiscales, Gestores)	AccountingService
consulting	Consultoría (Consultores, Coaches)	ProfessionalService
wellness	Bienestar (Nutricionistas, Terapeutas)	HealthAndBeautyBusiness

 
3. Servicios Principales
3.1 ProviderProfileService
<?php namespace Drupal\jaraba_providers\Service;

class ProviderProfileService {
  
  public function createProfile(User $user, array $data): ProviderProfile {
    // Generar slug único
    $slug = $this->generateUniqueSlug($data['display_name'], $data['professional_title']);
    
    $profile = ProviderProfile::create([
      'user_id' => $user->id(),
      'tenant_id' => $data['tenant_id'],
      'display_name' => $data['display_name'],
      'professional_title' => $data['professional_title'],
      'headline' => $data['headline'] ?? null,
      'bio' => $data['bio'] ?? null,
      'category_id' => $data['category_id'],
      'specialties' => $data['specialties'] ?? [],
      'credentials' => [],
      'service_modalities' => $data['modalities'] ?? ['presencial'],
      'is_verified' => false,
      'is_public' => false, // Hasta completar perfil mínimo
      'slug' => $slug,
    ]);
    
    $this->eventDispatcher->dispatch(new ProviderProfileCreatedEvent($profile));
    return $profile;
  }
  
  public function updateCredentials(ProviderProfile $profile, array $credentials): void {
    $current = $profile->getCredentials();
    $merged = array_merge($current, $credentials);
    $profile->setCredentials($merged);
    $profile->save();
    
    // Marcar para verificación si hay nuevas credenciales
    if ($this->hasNewCredentials($current, $merged)) {
      $this->verificationQueue->add($profile);
    }
  }
  
  public function verifyCollegeRegistration(ProviderProfile $profile): bool {
    $credentials = $profile->getCredentials();
    $college = $credentials['college_registration'] ?? null;
    
    if (!$college) {
      return false;
    }
    
    // Verificar contra API del colegio (si existe)
    // O marcar para verificación manual
    $isValid = $this->collegeVerifier->verify(
      $college['college_code'],
      $college['registration_number']
    );
    
    if ($isValid) {
      $credentials['college_registration']['verified'] = true;
      $credentials['college_registration']['verified_at'] = date('c');
      $profile->setCredentials($credentials);
      $profile->setIsVerified(true);
      $profile->setVerifiedAt(new \DateTime());
      $profile->save();
    }
    
    return $isValid;
  }
  
  public function checkProfileCompleteness(ProviderProfile $profile): array {
    $missing = [];
    
    if (empty($profile->getDisplayName())) $missing[] = 'display_name';
    if (empty($profile->getProfessionalTitle())) $missing[] = 'professional_title';
    if (empty($profile->getBio())) $missing[] = 'bio';
    if (empty($profile->getPhotoUrl())) $missing[] = 'photo';
    if (empty($profile->getSpecialties())) $missing[] = 'specialties';
    if (empty($profile->getCredentials())) $missing[] = 'credentials';
    
    $completeness = 100 - (count($missing) * 15);
    
    return [
      'completeness' => max(0, $completeness),
      'missing_fields' => $missing,
      'can_publish' => $completeness >= 70,
    ];
  }
}

 
4. SEO y Schema.org
4.1 Structured Data para Profesionales
{
  "@context": "https://schema.org",
  "@type": "Attorney",
  "name": "Elena Martínez García",
  "jobTitle": "Abogada especialista en Derecho Civil y Familia",
  "description": "Más de 15 años de experiencia en divorcios...",
  "image": "https://ejemplo.jaraba.es/providers/elena-martinez/photo.jpg",
  "url": "https://ejemplo.jaraba.es/profesionales/elena-martinez-abogada",
  "telephone": "+34 957 123 456",
  "email": "elena@ejemplo.es",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "C/ Mayor, 15",
    "addressLocality": "Cabra",
    "addressRegion": "Córdoba",
    "postalCode": "14940",
    "addressCountry": "ES"
  },
  "areaServed": {
    "@type": "GeoCircle",
    "geoMidpoint": { "@type": "GeoCoordinates", "latitude": 37.47, "longitude": -4.44 },
    "geoRadius": "50000"
  },
  "knowsAbout": ["Derecho Civil", "Derecho de Familia", "Divorcios"],
  "hasCredential": {
    "@type": "EducationalOccupationalCredential",
    "credentialCategory": "Colegiación",
    "recognizedBy": {
      "@type": "Organization",
      "name": "Ilustre Colegio de Abogados de Córdoba"
    }
  },
  "priceRange": "€€",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "reviewCount": "47"
  }
}

5. APIs REST
Método	Endpoint	Descripción	Auth
POST	/api/v1/providers	Crear perfil profesional	User
GET	/api/v1/providers/me	Obtener mi perfil	Provider
PUT	/api/v1/providers/me	Actualizar mi perfil	Provider
PUT	/api/v1/providers/me/credentials	Actualizar credenciales	Provider
POST	/api/v1/providers/me/verify	Solicitar verificación	Provider
GET	/api/v1/providers	Buscar profesionales (público)	Public
GET	/api/v1/providers/{slug}	Perfil público de profesional	Public
GET	/api/v1/providers/nearby	Profesionales cercanos (geoloc)	Public
GET	/api/v1/categories	Listar categorías de servicio	Public

6. Roadmap de Implementación
Sprint	Timeline	Entregables	Dependencias
Sprint 1.1	Semana 1	Entidad provider_profile + taxonomía categories + APIs CRUD	82_Services_Core
Sprint 1.2	Semana 2	ProviderProfileService + verificación + completeness check	Sprint 1.1
Sprint 1.3	Semana 3	Página pública + Schema.org + SEO + búsqueda geolocalizada	Sprint 1.2

6.1 Criterios de Aceptación
•	✓ Profesional puede crear y completar su perfil en < 10 minutos
•	✓ Credenciales de colegiación verificables (manual o API)
•	✓ Página pública con URL amigable (slug)
•	✓ Schema.org genera rich snippets en Google
•	✓ Búsqueda por categoría, especialidad y ubicación funciona
•	✓ Indicador de completitud del perfil visible

--- Fin del Documento ---
