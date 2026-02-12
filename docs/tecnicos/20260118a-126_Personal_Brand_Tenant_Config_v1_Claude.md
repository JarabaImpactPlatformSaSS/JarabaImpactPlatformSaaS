
JARABA IMPACT PLATFORM

Configuración Técnica
Tenant de Marca Personal

pepejaraba.com

Group Type, Domain Access, DNS & SSL

Código	126_Personal_Brand_Tenant_Config_v1
Versión	1.0
Fecha	Enero 2026
Estado	Especificación Técnica de Implementación
Dependencias	07_Core_Configuracion_MultiTenant_v1, 123_PepeJaraba_Personal_Brand_v1
 
1. Resumen Ejecutivo
Este documento define la configuración técnica completa para implementar pepejaraba.com como un tenant especializado dentro de la arquitectura multi-tenant del Ecosistema Jaraba. Se establece un nuevo Group Type tenant_personal_brand diseñado específicamente para sitios de marca personal y corporativa que actúan como puntos de entrada humanos al ecosistema.
2. Posición Arquitectónica en el Ecosistema
2.1 Taxonomía de Dominios
El Ecosistema Jaraba opera con una arquitectura de dominios distribuida donde cada dominio cumple una función específica:
Dominio	Función	Descripción
plataformadeecosistemas.com	Operativo SaaS	Núcleo Drupal 11 multi-tenant. Todos los tenants operan aquí.
plataformadeecosistemas.es	Corporativo Legal	Sitio de la entidad Plataforma de Ecosistemas Digitales S.L.
jarabaimpact.com	Institucional B2B	Orientado a compradores institucionales (Junta, SEPE, Ayuntamientos).
pepejaraba.com	Marca Personal	Punto de entrada humano. E-E-A-T, thought leadership, lead generation.
2.2 Diagrama de Arquitectura
┌─────────────────────────────────────────────────────────────────────────┐
│                    CAPA DE PRESENTACIÓN (DNS)                           │
├─────────────────────────────────────────────────────────────────────────┤
│  pepejaraba.com    jarabaimpact.com    *.plataformadeecosistemas.com   │
│        │                  │                        │                    │
│        └──────────────────┼────────────────────────┘                    │
│                           ▼                                             │
│                    ┌─────────────┐                                      │
│                    │   NGINX     │  ← SSL Termination                   │
│                    │   Proxy     │  ← Domain Routing                    │
│                    └──────┬──────┘                                      │
│                           ▼                                             │
├─────────────────────────────────────────────────────────────────────────┤
│                    DRUPAL 11 + DOMAIN ACCESS                            │
├─────────────────────────────────────────────────────────────────────────┤
│                           │                                             │
│              ┌────────────┼────────────┐                                │
│              ▼            ▼            ▼                                │
│  ┌─────────────────┐ ┌─────────────┐ ┌─────────────────────────────┐   │
│  │ GROUP:          │ │ GROUP:      │ │ GROUP: tenant_commercial    │   │
│  │ personal_brand  │ │ institution │ │ (AgroConecta, Comercio...)  │   │
│  │ ─────────────── │ │ ─────────── │ │ ─────────────────────────   │   │
│  │ pepejaraba.com  │ │ jaraba      │ │ cooperativa-olivar.com      │   │
│  │                 │ │ impact.com  │ │ tienda-local.com            │   │
│  └─────────────────┘ └─────────────┘ └─────────────────────────────┘   │
│                                                                         │
│              TenantContextService resuelve por hostname                 │
└─────────────────────────────────────────────────────────────────────────┘
2.3 Relación con las Verticales
pepejaraba.com NO pertenece a ninguna vertical operativa (Empleabilidad, Emprendimiento, AgroConecta, ComercioConecta, ServiciosConecta). Es un meta-sitio que actúa como puerta de entrada al ecosistema completo, capturando leads que luego se distribuyen a las verticales apropiadas según su perfil.
 
3. Definición del Group Type: tenant_personal_brand
3.1 Justificación del Nuevo Group Type
Los Group Types existentes (tenant_commercial, tenant_training, tenant_institutional) están diseñados para verticales operativas con funcionalidades específicas (e-commerce, LMS, marca blanca institucional). Un sitio de marca personal requiere:
•	Blog y sistema de artículos optimizado para SEO/GEO
•	Lead magnets y formularios de captura
•	Integración con newsletter (Mailchimp/Brevo)
•	Calendario de citas (Calendly)
•	Showcase de servicios (sin carrito de compra)
•	Social proof (testimonios, logos de clientes)
3.2 Configuración YAML del Group Type
Archivo: config/sync/group.type.tenant_personal_brand.yml
langcode: en
status: true
dependencies:
  enforced:
    module:
      - jaraba_tenant
id: tenant_personal_brand
label: 'Tenant Marca Personal'
description: 'Sitios de marca personal y corporativa. Blogs, lead generation, thought leadership.'
new_revision: true
creator_membership: true
creator_wizard: true
creator_roles:
  - tenant_personal_brand-owner
3.3 Campos del Group Type
Campo	Machine Name	Tipo	Descripción
Nombre público	field_public_name	Text	Nombre visible del sitio
Dominio	field_custom_domain	Text	Dominio personalizado
Theme Preset	field_theme_preset	List (text)	Preset visual a aplicar
Logo	field_logo	Image	Logo del sitio
Foto perfil	field_profile_photo	Image	Foto principal de la persona
Bio corta	field_short_bio	Text (formatted)	Biografía de 2-3 frases
Calendly URL	field_calendly_url	Link	URL de Calendly para citas
Newsletter ID	field_newsletter_list_id	Text	ID de lista Mailchimp/Brevo
Redes sociales	field_social_links	Link (multiple)	LinkedIn, Twitter, YouTube, etc.
GA4 Measurement ID	field_ga4_id	Text	Google Analytics 4 ID
3.4 Group Content Plugins Habilitados
Plugin	Entidad	Propósito
group_membership	user	Gestión de usuarios del tenant
group_node:article	node:article	Blog posts
group_node:service	node:service	Servicios ofrecidos
group_node:testimonial	node:testimonial	Testimonios de clientes
group_node:resource	node:resource	Lead magnets (PDFs, guías)
group_node:case_study	node:case_study	Casos de éxito
group_media	media	Imágenes y vídeos
3.5 Roles de Grupo
Archivo: config/sync/group.role.tenant_personal_brand-owner.yml
langcode: en
status: true
dependencies:
  config:
    - group.type.tenant_personal_brand
id: tenant_personal_brand-owner
label: 'Propietario'
weight: 0
internal: false
audience: member
scope: individual
group_type: tenant_personal_brand
admin: true
permissions:
  - 'administer group'
  - 'administer members'
  - 'delete group'
  - 'edit group'
  - 'view group'
  - 'create group_node:article entity'
  - 'delete any group_node:article entity'
  - 'update any group_node:article entity'
  - 'view group_node:article entity'
  - 'create group_node:service entity'
  - 'delete any group_node:service entity'
  - 'update any group_node:service entity'
  - 'view group_node:service entity'
  - 'create group_node:testimonial entity'
  - 'create group_node:resource entity'
  - 'create group_node:case_study entity'
  - 'create group_media entity'
  - 'delete any group_media entity'
 
4. Configuración de Domain Access
4.1 Crear el Domain Record
Ruta administrativa: /admin/config/domain → Add domain
Archivo: config/sync/domain.record.pepejaraba_com.yml
langcode: en
status: true
dependencies: {  }
id: pepejaraba_com
hostname: pepejaraba.com
name: 'Pepe Jaraba - Marca Personal'
scheme: https
weight: 10
is_default: false
third_party_settings:
  jaraba_tenant:
    group_id: 1  # ID del Group tenant_personal_brand creado
    theme_preset: 'personal-brand-premium'
    enable_analytics: true
4.2 Alias para www
Archivo: config/sync/domain.record.www_pepejaraba_com.yml
langcode: en
status: true
dependencies:
  config:
    - domain.record.pepejaraba_com
id: www_pepejaraba_com
hostname: www.pepejaraba.com
name: 'Pepe Jaraba (www)'
scheme: https
weight: 11
is_default: false
redirect: 301  # Redirige a pepejaraba.com (sin www)
canonical: pepejaraba_com
4.3 Verificación del TenantContextService
El servicio TenantContextService (documentado en 07_Core_Configuracion_MultiTenant_v1) resolverá automáticamente el tenant cuando detecte el hostname 
jaraba_tenant/src/Service/TenantContextService.php (extracto relevante):
private function findTenantByDomain(string $host): ?TenantInterface {
    // 1. Buscar por hostname exacto en Domain records
    $domain_storage = $this->entityTypeManager->getStorage('domain');
    $domains = $domain_storage->loadByProperties(['hostname' => $host]);
    
    if ($domain = reset($domains)) {
        // Obtener group_id vinculado al dominio
        $group_id = $domain->getThirdPartySetting('jaraba_tenant', 'group_id');
        if ($group_id) {
            return $this->loadTenantByGroupId($group_id);
        }
    }
    
    // 2. Fallback: extraer subdominio si aplica
    // pepejaraba.com NO usa subdominio, se resuelve en paso 1
    
    return NULL;
}
 
5. Configuración DNS (IONOS)
5.1 Acceso al Panel de Control
1.	Acceder a https://my.ionos.es
2.	Navegar a Dominios y SSL → pepejaraba.com
3.	Seleccionar DNS → Gestionar registros DNS
5.2 Registros DNS Requeridos
Nombre	Tipo	Valor	TTL
@	A	[IP_SERVIDOR_IONOS]	3600 (1 hora)
www	CNAME	pepejaraba.com	3600
@	AAAA	[IPv6_SERVIDOR] (opcional)	3600
5.3 Verificación de Propagación DNS
Comandos para verificar desde terminal:
# Verificar registro A
dig pepejaraba.com A +short
# Debe devolver: [IP_SERVIDOR]
 
# Verificar registro CNAME para www
dig www.pepejaraba.com CNAME +short
# Debe devolver: pepejaraba.com.
 
# Verificar propagación global (usar herramienta online)
# https://www.whatsmydns.net/#A/pepejaraba.com
5.4 Alternativa: Proxy Cloudflare (Opcional)
Si se desea usar Cloudflare como CDN/WAF, los registros serían:
Nombre	Tipo	Valor	Proxy
@	A	[IP_SERVIDOR]	Proxied (naranja)
www	CNAME	pepejaraba.com	Proxied (naranja)
 
6. Configuración SSL (Let's Encrypt)
6.1 Obtener Certificado
Ejecutar en el servidor como root:
# Opción A: Certbot con Nginx (recomendado)
sudo certbot --nginx -d pepejaraba.com -d www.pepejaraba.com
 
# Opción B: Certbot standalone (si Nginx no está configurado aún)
sudo certbot certonly --standalone -d pepejaraba.com -d www.pepejaraba.com
 
# Opción C: Añadir al certificado wildcard existente
sudo certbot certonly --dns-cloudflare \
  -d '*.plataformadeecosistemas.com' \
  -d 'plataformadeecosistemas.com' \
  -d 'pepejaraba.com' \
  -d 'www.pepejaraba.com'
6.2 Ubicación de Certificados
Archivo	Ruta
Certificado	/etc/letsencrypt/live/pepejaraba.com/fullchain.pem
Clave privada	/etc/letsencrypt/live/pepejaraba.com/privkey.pem
6.3 Renovación Automática
Verificar cron de renovación:
# Verificar timer de systemd
sudo systemctl status certbot.timer
 
# O verificar crontab
sudo crontab -l | grep certbot
 
# Test de renovación (dry-run)
sudo certbot renew --dry-run
 
7. Configuración Nginx
7.1 Virtual Host Completo
Archivo: /etc/nginx/sites-available/pepejaraba.com
# Redirección HTTP → HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name pepejaraba.com www.pepejaraba.com;
    return 301 https://pepejaraba.com$request_uri;
}
 
# Redirección www → sin www
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name www.pepejaraba.com;
    
    ssl_certificate /etc/letsencrypt/live/pepejaraba.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pepejaraba.com/privkey.pem;
    
    return 301 https://pepejaraba.com$request_uri;
}
 
# Servidor principal
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name pepejaraba.com;
    
    # SSL
    ssl_certificate /etc/letsencrypt/live/pepejaraba.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pepejaraba.com/privkey.pem;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_session_tickets off;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;
    
    # Drupal root (MISMO que el SaaS principal)
    root /var/www/plataformadeecosistemas/web;
    index index.php;
    
    # Logs específicos
    access_log /var/log/nginx/pepejaraba.com.access.log;
    error_log /var/log/nginx/pepejaraba.com.error.log;
    
    # Drupal clean URLs
    location / {
        try_files $uri /index.php?$query_string;
    }
    
    # PHP-FPM
    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
    }
    
    # Bloquear archivos sensibles
    location ~ /\. {
        deny all;
    }
    
    location = /favicon.ico {
        log_not_found off;
        access_log off;
    }
    
    location = /robots.txt {
        allow all;
        log_not_found off;
        access_log off;
    }
    
    # Cache de assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
7.2 Activar el Sitio
# Crear enlace simbólico
sudo ln -s /etc/nginx/sites-available/pepejaraba.com /etc/nginx/sites-enabled/
 
# Verificar configuración
sudo nginx -t
 
# Recargar Nginx
sudo systemctl reload nginx
 
8. Relación con el Triple Motor Económico
pepejaraba.com no genera ingresos directamente, pero es un multiplicador de conversión que alimenta los tres motores económicos del ecosistema:
Motor	Contribución de pepejaraba.com	Mecanismo
Institucional (30%)	E-E-A-T y autoridad de Pepe Jaraba como experto	Credibilidad en propuestas a Junta, SEPE, Ayuntamientos
Mercado Privado (40%)	Lead generation hacia productos del ecosistema	Formularios → CRM → Verticales (Kits, cursos, SaaS)
Licencias (30%)	Demostración viva del sistema de marca personal	Caso de uso para vender franquicias de tenant_personal_brand
8.1 Flujo de Leads
┌────────────────────┐
│  pepejaraba.com    │
│  (Marca Personal)  │
└─────────┬──────────┘
          │
          ▼ Lead capturado (formulario, newsletter, Calendly)
          │
    ┌─────┴─────┐
    │   CRM     │  ← Scoring y clasificación
    │  Central  │
    └─────┬─────┘
          │
    ┌─────┼─────┬─────────────┬─────────────┐
    ▼     ▼     ▼             ▼             ▼
┌──────┐ ┌──────┐ ┌──────────┐ ┌───────────┐ ┌───────────┐
│Emplea│ │Empren│ │ Agro     │ │ Comercio  │ │ Servicios │
│bilid │ │dimien│ │ Conecta  │ │ Conecta   │ │ Conecta   │
│ ad   │ │ to   │ │          │ │           │ │           │
└──────┘ └──────┘ └──────────┘ └───────────┘ └───────────┘
 
9. Checklist de Implementación
9.1 Pre-requisitos
☐	Dominio pepejaraba.com registrado y accesible
☐	Acceso a panel DNS de IONOS
☐	Acceso SSH al servidor con permisos sudo
☐	Certbot instalado en el servidor
9.2 Drupal (SaaS)
☐	Group Type tenant_personal_brand creado
☐	Campos del Group Type configurados
☐	Group Content Plugins habilitados
☐	Roles de grupo configurados (owner)
☐	Domain record pepejaraba_com creado
☐	Domain record www_pepejaraba_com creado (redirect)
☐	Group pepe_jaraba_brand creado y vinculado al domain
☐	Theme preset personal-brand-premium aplicado
9.3 DNS (IONOS)
☐	Registro A para @ configurado
☐	Registro CNAME para www configurado
☐	Propagación DNS verificada (dig / whatsmydns.net)
9.4 Servidor
☐	Certificado SSL obtenido (certbot)
☐	Renovación automática configurada
☐	Virtual host Nginx creado
☐	Enlace simbólico en sites-enabled
☐	Nginx recargado sin errores
9.5 Verificación Final
☐	https://pepejaraba.com carga correctamente
☐	http://pepejaraba.com redirige a https
☐	https://www.pepejaraba.com redirige a sin www
☐	TenantContextService detecta el tenant correcto
☐	Theme preset se aplica correctamente
☐	Contenido del tenant se muestra aislado

--- Fin del Documento ---

Jaraba Impact Platform © 2026
