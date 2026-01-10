Integración con Certificados Digitales
Autenticación y Firma Electrónica con FNMT
Plataforma AgroConecta v1.0
1. Resumen Ejecutivo
Este documento detalla las opciones técnicas para integrar certificados digitales expedidos por la FNMT (Fábrica Nacional de Moneda y Timbre) en la plataforma AgroConecta. Se contemplan dos escenarios principales: autenticación de usuarios mediante certificado X.509 y firma electrónica de documentos con validez legal.
2. Casos de Uso en AgroConecta
2.1. Autenticación con Certificado Digital
•	Acceso de productores a su panel de gestión sin usuario/contraseña
•	Verificación de identidad legal del productor (NIF/CIF)
•	Cumplimiento de requisitos de identificación para subvenciones RETECH
•	Acceso seguro a documentación sensible de trazabilidad
2.2. Firma Electrónica de Documentos
•	Certificados de trazabilidad de lotes con validez legal
•	Contratos digitales entre productor y plataforma
•	Albaranes de entrega firmados electrónicamente
•	Declaraciones responsables para certificaciones de calidad
 
3. Autenticación con Certificado Digital
3.1. Arquitectura Técnica
La autenticación con certificado digital utiliza el protocolo TLS con autenticación mutua (mTLS). El servidor web valida el certificado del cliente contra la cadena de certificación de la FNMT.
Componente	Función
Apache/Nginx	Validación del certificado X.509 contra CA de FNMT. Extrae datos (CN, NIF, Serial) y los pasa como headers HTTP.
Módulo Drupal	Lee los headers del servidor, mapea el NIF/CIF a un usuario existente o crea uno nuevo, e inicia sesión automáticamente.
Base de Datos	Almacena el mapeo entre número de serie del certificado y usuario Drupal para futuras validaciones.
3.2. Configuración del Servidor Web
Apache (mod_ssl)
# /etc/apache2/sites-available/agroconecta-ssl.conf <VirtualHost *:443>     ServerName agroconecta.es          SSLEngine on     SSLCertificateFile /etc/ssl/certs/agroconecta.crt     SSLCertificateKeyFile /etc/ssl/private/agroconecta.key          # Autenticación con certificado cliente     SSLVerifyClient optional     SSLVerifyDepth 3     SSLCACertificateFile /etc/ssl/certs/fnmt-ca-chain.pem          # Pasar datos del certificado a PHP     SSLOptions +StdEnvVars +ExportCertData          <Location "/login-certificado">         SSLVerifyClient require     </Location> </VirtualHost>
Nginx
# /etc/nginx/sites-available/agroconecta server {     listen 443 ssl;     server_name agroconecta.es;          ssl_certificate /etc/ssl/certs/agroconecta.crt;     ssl_certificate_key /etc/ssl/private/agroconecta.key;          # Certificado cliente opcional globalmente     ssl_client_certificate /etc/ssl/certs/fnmt-ca-chain.pem;     ssl_verify_client optional;     ssl_verify_depth 3;          location /login-certificado {         ssl_verify_client on;                  # Headers para PHP         proxy_set_header SSL-Client-Verify $ssl_client_verify;         proxy_set_header SSL-Client-S-DN $ssl_client_s_dn;         proxy_set_header SSL-Client-Serial $ssl_client_serial;     } }
 
3.3. Módulo Drupal para Autenticación
Se propone extender el módulo agroconecta_core con un servicio de autenticación por certificado. A continuación se muestra la estructura básica:
Servicio: CertificateAuthService.php
<?php  namespace Drupal\agroconecta_core\Service;  use Drupal\Core\Session\AccountProxyInterface; use Drupal\user\Entity\User; use Symfony\Component\HttpFoundation\RequestStack;  /**  * Servicio de autenticación mediante certificado digital X.509.  */ class CertificateAuthService {    protected $requestStack;   protected $currentUser;    public function __construct(RequestStack $request_stack, AccountProxyInterface $current_user) {     $this->requestStack = $request_stack;     $this->currentUser = $current_user;   }    /**    * Extrae los datos del certificado de los headers HTTP.    */   public function getCertificateData(): ?array {     $request = $this->requestStack->getCurrentRequest();          // Headers configurados en Apache/Nginx     $verify = $request->headers->get('SSL-Client-Verify');     $dn = $request->headers->get('SSL-Client-S-DN');     $serial = $request->headers->get('SSL-Client-Serial');          if ($verify !== 'SUCCESS' || empty($dn)) {       return NULL;     }          // Parsear el Distinguished Name (DN)     // Formato típico FNMT: /CN=APELLIDO1 APELLIDO2, NOMBRE - NIF 12345678X/...     $data = [];     if (preg_match('/CN=([^,\/]+)/', $dn, $matches)) {       $data['cn'] = $matches[1];     }     if (preg_match('/NIF\s*([0-9]{8}[A-Z])/', $dn, $matches)) {       $data['nif'] = $matches[1];     }     if (preg_match('/CIF\s*([A-Z][0-9]{8})/', $dn, $matches)) {       $data['cif'] = $matches[1];     }     $data['serial'] = $serial;     $data['dn'] = $dn;          return $data;   }    /**    * Busca o crea un usuario basado en el NIF/CIF del certificado.    */   public function findOrCreateUser(array $cert_data): ?User {     $identifier = $cert_data['nif'] ?? $cert_data['cif'] ?? NULL;          if (!$identifier) {       return NULL;     }          // Buscar usuario por campo field_nif_cif     $users = \Drupal::entityTypeManager()       ->getStorage('user')       ->loadByProperties(['field_nif_cif' => $identifier]);          if (!empty($users)) {       return reset($users);     }          // Opcionalmente: crear usuario automáticamente     // (Dependiendo de la política de registro)     return NULL;   }    /**    * Inicia sesión con el usuario del certificado.    */   public function loginWithCertificate(): bool {     $cert_data = $this->getCertificateData();          if (!$cert_data) {       return FALSE;     }          $user = $this->findOrCreateUser($cert_data);          if ($user && $user->isActive()) {       user_login_finalize($user);       return TRUE;     }          return FALSE;   } }
 
4. Firma Electrónica de Documentos
4.1. Tipos de Firma y Validez Legal
Tipo	Descripción	Validez Legal
Simple	Firma básica (ej: checkbox "Acepto", imagen de firma)	Mínima. Útil para aceptación de términos internos.
Avanzada	Vinculada al firmante, detecta cambios posteriores, bajo control exclusivo.	Admisible como prueba en juicio. Equivalente a firma manuscrita.
Cualificada	Firma avanzada + certificado cualificado + dispositivo seguro (QSCD).	Máxima. Presunción de autenticidad. Requerida para trámites con AAPP.
4.2. Opciones de Implementación
Opción A: AutoFirma (Cliente de Firma del Gobierno)
AutoFirma es una aplicación de escritorio desarrollada por el Gobierno de España que permite firmar documentos con el certificado instalado en el navegador o en tarjeta criptográfica.
Ventajas	Gratuito, soporta DNIe y FNMT, firma cualificada, bien documentado, ampliamente aceptado.
Desventajas	Requiere instalación local del usuario, experiencia de usuario compleja, dependencia de Java.
Integración	Protocolo afirma:// + JavaScript Bridge. Drupal genera el documento, AutoFirma lo firma.
Complejidad	Media. Requiere gestión del flujo asíncrono firma-subida.
Opción B: Servicios de Firma Cloud (SaaS)
Plataformas como Viafirma, Signaturit, DocuSign o Lleida.net ofrecen APIs para integrar firma electrónica sin que el usuario necesite instalar software.
Ventajas	Sin instalación, UX excelente, APIs REST sencillas, custodia de documentos, sellado de tiempo.
Desventajas	Coste por firma (0.50€-3€/firma), dependencia de tercero, datos sensibles en cloud externo.
Proveedores	Viafirma (español, integra FNMT), Signaturit, Lleida.net, DocuSign.
Complejidad	Baja. Integración via API REST + Webhooks.
Opción C: Firma en Servidor con Certificado Centralizado
El servidor posee un certificado de persona jurídica (de la PYME o cooperativa) y firma automáticamente los documentos generados por la plataforma.
Ventajas	Automatizable, sin intervención del usuario, ideal para certificados de trazabilidad masivos.
Desventajas	No firma el productor individualmente (firma la plataforma), requiere HSM para máxima seguridad.
Herramientas	OpenSSL, PortableSigner, DSS (Digital Signature Services de la UE), iText.
Complejidad	Alta. Requiere custodia segura del certificado y cumplimiento normativo.
 
5. Recomendación para AgroConecta
5.1. Enfoque Pragmático por Fases
Fase 1 (Corto plazo): Implementar autenticación opcional con certificado digital para productores que lo deseen. Esto añade valor diferencial sin obligar a todos los usuarios.
Fase 2 (Medio plazo): Integrar firma en servidor para certificados de trazabilidad de lotes. La plataforma (persona jurídica) firma automáticamente los documentos generados.
Fase 3 (Largo plazo): Evaluar integración con AutoFirma o servicio cloud para firma de contratos individuales entre productor y cliente.
5.2. Arquitectura Propuesta
┌─────────────────────────────────────────────────────────────────┐ │                        AGROCONECTA                               │ ├──────────────────────────┬──────────────────────────────────────┤ │   AUTENTICACIÓN          │   FIRMA ELECTRÓNICA                   │ ├──────────────────────────┼──────────────────────────────────────┤ │                          │                                       │ │  ┌──────────────────┐    │   ┌──────────────────────────────┐   │ │  │ Login Tradicional│    │   │ Firma Servidor (Trazabilidad)│   │ │  │ (usuario/pass)   │    │   │ Certificado PJ AgroConecta   │   │ │  └──────────────────┘    │   └──────────────────────────────┘   │ │          │               │              │                        │ │          ▼               │              ▼                        │ │  ┌──────────────────┐    │   ┌──────────────────────────────┐   │ │  │ Login Certificado│    │   │ Firma Usuario (Contratos)    │   │ │  │ (X.509 FNMT)     │    │   │ AutoFirma / Viafirma         │   │ │  └──────────────────┘    │   └──────────────────────────────┘   │ │                          │                                       │ └──────────────────────────┴──────────────────────────────────────┘
6. Requisitos Técnicos
6.1. Para Autenticación con Certificado
•	Servidor web con soporte SSL/TLS y autenticación cliente (Apache mod_ssl o Nginx)
•	Cadena de certificados de la FNMT (AC Raíz, AC Subordinadas)
•	Campo en perfil de usuario para almacenar NIF/CIF
•	Módulo Drupal para gestionar el flujo de autenticación
6.2. Para Firma en Servidor
•	Certificado de persona jurídica (representante o sello de empresa)
•	Librería de firma PDF (iText, Apache PDFBox + BouncyCastle)
•	Servicio de sellado de tiempo (TSA) para LTV (Long Term Validation)
•	Almacenamiento seguro del certificado (idealmente HSM o vault)
7. Recursos y Enlaces
•	FNMT - Certificados: https://www.sede.fnmt.gob.es/certificados
•	AutoFirma: https://firmaelectronica.gob.es/Home/Descargas.html
•	Viafirma: https://www.viafirma.com/
•	DSS (EU Digital Signature Services): https://ec.europa.eu/digital-building-blocks/wikis/display/DIGITAL/Digital+Signature+Service+-++DSS
•	Módulo Drupal x509: https://www.drupal.org/project/x509
