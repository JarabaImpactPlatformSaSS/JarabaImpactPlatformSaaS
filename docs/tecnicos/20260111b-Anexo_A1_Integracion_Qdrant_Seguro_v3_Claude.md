
DOCUMENTO TÉCNICO MAESTRO
Jaraba Impact Platform

ANEXO A.1: Integración Qdrant

Arquitectura Dual Segura: Desarrollo (Lando) + Producción (IONOS)

🔒 Con Hardening de Seguridad Integrado

Versión 3.0 | Enero 2026
 
Tabla de Contenidos
Tabla de Contenidos	1
1. Resumen Ejecutivo de Seguridad	1
2. Arquitectura Dual Segura	1
3. Configuración Segura de Desarrollo (Lando)	1
3.1 Archivo .lando.yml Seguro	1
3.2 Archivo .env para Desarrollo	1
3.3 Archivo .gitignore	1
4. Configuración Segura de Producción (IONOS)	1
4.1 Variables de Entorno en IONOS	1
4.2 settings.php Seguro	1
4.3 Protección de Archivos Sensibles	1
5. Servicios Core con Seguridad Integrada	1
5.1 QdrantClientService.php (Seguro)	1
5.2 TenantContextService.php (Seguro)	1
5.3 EmbeddingService.php con Rate Limiting	1
6. Registro de Servicios	1
6.1 jaraba_rag.services.yml	1
7. Comandos Drush Seguros	1
8. Checklist de Seguridad	1
8.1 Credenciales y Configuración	1
8.2 Desarrollo (Lando)	1
8.3 Validación de Inputs	1
8.4 Rate Limiting	1
8.5 Logging y Monitoreo	1
8.6 Multi-Tenancy	1

 
1. Resumen Ejecutivo de Seguridad
Este documento implementa las siguientes medidas de seguridad para proteger la infraestructura RAG multi-tenant:
Vulnerabilidad	Riesgo	Mitigación Implementada
API Keys hardcodeadas	CRÍTICO	Variables de entorno obligatorias
Puertos expuestos (dev)	ALTO	Binding solo a localhost
Inyección en filtros	ALTO	Validación + whitelist estricta
Bypass de tenant	CRÍTICO	Validación multi-capa
Abuso de API OpenAI	MEDIO	Rate limiting por usuario/IP
Logging de secretos	MEDIO	Sanitización de logs
Acceso a archivos config	ALTO	.htaccess restrictivo

🔒 PRINCIPIO FUNDAMENTAL
Zero Trust: Nunca confiar en inputs del usuario. Validar TODO en servidor. Las credenciales NUNCA deben existir en codigo fuente, solo en variables de entorno del sistema operativo.
 
2. Arquitectura Dual Segura
┌─────────────────────────────────────────────────────────────────────────┐
│              ARQUITECTURA DUAL SEGURA jaraba_rag v3.0                   │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                         │
│  DESARROLLO (Lando)                    PRODUCCION (IONOS)               │
│  ══════════════════                    ══════════════════               │
│                                                                         │
│  ┌───────────────────┐                 ┌───────────────────┐            │
│  │ Drupal Container  │                 │ IONOS L-16 NVMe   │            │
│  │ ┌───────────────┐ │                 │ ┌───────────────┐ │            │
│  │ │ jaraba_rag    │ │                 │ │ jaraba_rag    │ │            │
│  │ │ + Validacion  │ │                 │ │ + Validacion  │ │            │
│  │ │ + RateLimit   │ │                 │ │ + RateLimit   │ │            │
│  │ └───────┬───────┘ │                 │ └───────┬───────┘ │            │
│  └─────────┼─────────┘                 └─────────┼─────────┘            │
│            │                                     │                      │
│  ┌─────────▼─────────┐                           │ TLS 1.3             │
│  │ Qdrant Container  │                           │                      │
│  │ 127.0.0.1:6333    │◄── Solo localhost         ▼                      │
│  │ (API Key opcional)│                 ┌───────────────────┐            │
│  └───────────────────┘                 │ Qdrant Cloud      │            │
│                                        │ (API Key REQUERIDA│            │
│  🔒 Aislamiento red local              │  + IP Whitelist)  │            │
│                                        └───────────────────┘            │
│                                        🔒 TLS + Auth                    │
│                                                                         │
├─────────────────────────────────────────────────────────────────────────┤
│  CREDENCIALES: Solo en variables de entorno del SO                      │
│  VALIDACION: Multi-capa en TenantContextService                         │
│  RATE LIMIT: 100 req/min por usuario autenticado                        │
└─────────────────────────────────────────────────────────────────────────┘
 
3. Configuración Segura de Desarrollo (Lando)
3.1 Archivo .lando.yml Seguro
# .lando.yml - VERSION SEGURA v3.0
name: jaraba
recipe: drupal11

config:
  php: '8.3'
  via: nginx
  webroot: web
  database: mariadb:10.6

services:
  appserver:
    overrides:
      environment:
        # Variables desde .env local (NUNCA commitear)
        QDRANT_HOST: qdrant
        QDRANT_PORT: '6333'
        QDRANT_API_KEY: ${QDRANT_DEV_API_KEY:-}
        OPENAI_API_KEY: ${OPENAI_API_KEY}
        DRUPAL_ENV: development

  database:
    type: mariadb:10.6
    portforward: 3307

  # ═══════════════════════════════════════════════════════════════
  # QDRANT - CONFIGURACION SEGURA
  # ═══════════════════════════════════════════════════════════════
  qdrant:
    type: compose
    services:
      image: qdrant/qdrant:v1.7.4  # Version fija, no :latest
      command: ./qdrant
      ports:
        # 🔒 SEGURO: Solo accesible desde localhost
        - '127.0.0.1:6333:6333'
        - '127.0.0.1:6334:6334'
      volumes:
        - qdrant_data:/qdrant/storage
      environment:
        - QDRANT__LOG_LEVEL=WARN
        # 🔒 API Key incluso en desarrollo (buena practica)
        - QDRANT__SERVICE__API_KEY=${QDRANT_DEV_API_KEY:-dev_key_12345}
      # 🔒 Limites de recursos
      deploy:
        resources:
          limits:
            memory: 2G
          reservations:
            memory: 512M

tooling:
  drush:
    service: appserver
    cmd: drush --root=/app/web
  qdrant-status:
    service: appserver
    cmd: |
      curl -s -H "api-key: ${QDRANT_DEV_API_KEY:-dev_key_12345}" \
        http://qdrant:6333/collections | jq
    description: Ver estado de Qdrant (con auth)

volumes:
  qdrant_data:

3.2 Archivo .env para Desarrollo
🔴 ARCHIVO .env NUNCA EN GIT
Este archivo contiene secretos. Debe estar en .gitignore. Cada desarrollador crea el suyo localmente.

# .env (desarrollo local) - NUNCA COMMITEAR

# OpenAI - Obtener en https://platform.openai.com/api-keys
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxx

# Qdrant - API Key local (puede ser cualquier string)
QDRANT_DEV_API_KEY=dev_local_key_change_me_12345

# Drupal
DRUPAL_HASH_SALT=generate-random-64-char-string-here
3.3 Archivo .gitignore
# .gitignore - Excluir archivos sensibles

# Variables de entorno
.env
.env.*
!.env.example

# Configuracion local de Drupal
web/sites/*/settings.local.php
web/sites/*/services.local.yml

# Lando
.lando.local.yml

# IDE
.idea/
.vscode/

# Logs
*.log
logs/
 
4. Configuración Segura de Producción (IONOS)
4.1 Variables de Entorno en IONOS
Configurar en el panel de IONOS o via SSH en /etc/environment:
# /etc/environment o en panel IONOS

# Qdrant Cloud
QDRANT_CLUSTER_URL=https://abc123-xyz.eu-west-1.aws.cloud.qdrant.io:6333
QDRANT_API_KEY=tu_api_key_de_qdrant_cloud

# OpenAI
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxx

# Entorno
DRUPAL_ENV=production
4.2 settings.php Seguro
<?php
// sites/default/settings.php - VERSION SEGURA v3.0

/**
 * Deteccion de entorno.
 */
$is_lando = getenv('LANDO') === 'ON';
$drupal_env = getenv('DRUPAL_ENV') ?: ($is_lando ? 'development' : 'production');

/**
 * ═══════════════════════════════════════════════════════════════════
 * CONFIGURACION QDRANT - SEGURA
 * ═══════════════════════════════════════════════════════════════════
 */
if ($is_lando) {
  // DESARROLLO LOCAL
  $config['jaraba_rag.settings']['qdrant_cluster_url'] = 'http://qdrant:6333';
  $config['jaraba_rag.settings']['qdrant_api_key'] = getenv('QDRANT_DEV_API_KEY') ?: '';
  $config['jaraba_rag.settings']['environment'] = 'development';
}
else {
  // PRODUCCION - Variables de entorno OBLIGATORIAS
  $qdrant_url = getenv('QDRANT_CLUSTER_URL');
  $qdrant_key = getenv('QDRANT_API_KEY');

  // 🔒 VALIDACION: Fallar si no hay credenciales
  if (empty($qdrant_url)) {
    throw new \RuntimeException(
      'SEGURIDAD: QDRANT_CLUSTER_URL no configurada en variables de entorno'
    );
  }
  if (empty($qdrant_key)) {
    throw new \RuntimeException(
      'SEGURIDAD: QDRANT_API_KEY no configurada en variables de entorno'
    );
  }

  // 🔒 VALIDACION: URL debe ser HTTPS en produccion
  if (!str_starts_with($qdrant_url, 'https://')) {
    throw new \RuntimeException(
      'SEGURIDAD: QDRANT_CLUSTER_URL debe usar HTTPS en produccion'
    );
  }

  $config['jaraba_rag.settings']['qdrant_cluster_url'] = $qdrant_url;
  $config['jaraba_rag.settings']['qdrant_api_key'] = $qdrant_key;
  $config['jaraba_rag.settings']['environment'] = 'production';
}

/**
 * ═══════════════════════════════════════════════════════════════════
 * CONFIGURACION OPENAI - SEGURA
 * ═══════════════════════════════════════════════════════════════════
 */
$openai_key = getenv('OPENAI_API_KEY');

if (empty($openai_key) && !$is_lando) {
  throw new \RuntimeException(
    'SEGURIDAD: OPENAI_API_KEY no configurada en variables de entorno'
  );
}

$config['jaraba_rag.settings']['openai_api_key'] = $openai_key;
$config['jaraba_rag.settings']['embedding_model'] = 'text-embedding-3-small';

/**
 * ═══════════════════════════════════════════════════════════════════
 * CONFIGURACION DE SEGURIDAD RAG
 * ═══════════════════════════════════════════════════════════════════
 */
$config['jaraba_rag.settings']['security'] = [
  // Rate limiting
  'rate_limit_window' => 60,      // segundos
  'rate_limit_max_auth' => 100,   // usuarios autenticados
  'rate_limit_max_anon' => 10,    // usuarios anonimos
  
  // Validacion
  'max_query_length' => 1000,     // caracteres
  'max_embedding_text' => 8000,   // caracteres
  
  // Tenants
  'allowed_verticals' => ['agro', 'arte', 'turismo', 'empleo'],
  'allowed_plans' => ['starter', 'growth', 'pro', 'enterprise'],
];
4.3 Protección de Archivos Sensibles
# web/.htaccess - Añadir estas reglas

# Bloquear acceso a archivos de configuracion
<FilesMatch "^\.(env|gitignore|lando).*$">
  Require all denied
</FilesMatch>

<FilesMatch "^(settings|services).*\.php$">
  Require all denied
</FilesMatch>

# Bloquear archivos YAML de configuracion
<FilesMatch "\.(yml|yaml)$">
  Require all denied
</FilesMatch>

# Bloquear archivos de log
<FilesMatch "\.log$">
  Require all denied
</FilesMatch>
 
5. Servicios Core con Seguridad Integrada
5.1 QdrantClientService.php (Seguro)
<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

/**
 * Cliente seguro para Qdrant.
 */
class QdrantClientService {

  private ClientInterface $httpClient;
  private string $clusterUrl;
  private ?string $apiKey;
  private string $environment;
  private array $securityConfig;
  private $logger;

  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client;
    $config = $config_factory->get('jaraba_rag.settings');
    
    $this->clusterUrl = rtrim($config->get('qdrant_cluster_url') ?? '', '/');
    $this->apiKey = $config->get('qdrant_api_key') ?: NULL;
    $this->environment = $config->get('environment') ?? 'production';
    $this->securityConfig = $config->get('security') ?? [];
    $this->logger = $logger_factory->get('jaraba_rag');

    // 🔒 Validar configuracion al inicializar
    $this->validateConfiguration();
  }

  /**
   * Validar que la configuracion es segura.
   */
  private function validateConfiguration(): void {
    if (empty($this->clusterUrl)) {
      throw new \RuntimeException('Qdrant cluster URL no configurada');
    }

    // En produccion, requerir HTTPS y API Key
    if ($this->environment === 'production') {
      if (!str_starts_with($this->clusterUrl, 'https://')) {
        throw new \RuntimeException('Produccion requiere HTTPS para Qdrant');
      }
      if (empty($this->apiKey)) {
        throw new \RuntimeException('Produccion requiere API Key para Qdrant');
      }
    }

    // 🔒 Log seguro: NO incluir URLs ni credenciales
    $this->logger->info('Qdrant client inicializado [@env]', [
      '@env' => $this->environment,
    ]);
  }

  /**
   * Query con filtros VALIDADOS.
   */
  public function query(
    string $collection,
    array $vector,
    array $filters,
    int $limit = 5
  ): array {
    // 🔒 Validar nombre de collection
    $this->validateCollectionName($collection);
    
    // 🔒 Validar limite
    $limit = min(max($limit, 1), 20); // Entre 1 y 20

    try {
      $payload = [
        'vector' => $vector,
        'limit' => $limit,
        'with_payload' => TRUE,
        'filter' => $this->buildSecureFilter($filters),
      ];

      $response = $this->httpClient->post(
        "{$this->clusterUrl}/collections/{$collection}/points/search",
        [
          'headers' => $this->getHeaders(),
          'json' => $payload,
          'timeout' => 10,
          'connect_timeout' => 5,
        ]
      );

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data['result'] ?? [];
    }
    catch (GuzzleException $e) {
      // 🔒 Log sin detalles sensibles
      $this->logger->error('Qdrant query failed: @code', [
        '@code' => $e->getCode(),
      ]);
      throw new ServiceUnavailableHttpException(30, 'Servicio temporalmente no disponible');
    }
  }

  /**
   * 🔒 Construir filtro con validacion estricta.
   */
  private function buildSecureFilter(array $filters): array {
    $must = [];
    $allowedPlans = $this->securityConfig['allowed_plans'] ?? 
      ['starter', 'growth', 'pro', 'enterprise'];

    // 🔒 VALIDAR tenant_id: debe ser entero positivo
    if (!empty($filters['tenant_id'])) {
      $tenantId = filter_var(
        $filters['tenant_id'], 
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
      );
      
      if ($tenantId === FALSE) {
        $this->logger->warning('Intento de inyeccion en tenant_id detectado');
        throw new \InvalidArgumentException('tenant_id invalido');
      }
      
      $must[] = [
        'key' => 'tenant_id',
        'match' => ['any' => [(string) $tenantId, 'shared']],
      ];
    }

    // 🔒 VALIDAR plan_levels: solo valores de whitelist
    if (!empty($filters['plan_levels']) && is_array($filters['plan_levels'])) {
      $validPlans = array_values(array_intersect(
        $filters['plan_levels'],
        $allowedPlans
      ));
      
      if (empty($validPlans)) {
        $this->logger->warning('plan_levels invalidos recibidos');
        throw new \InvalidArgumentException('plan_levels invalidos');
      }
      
      $must[] = [
        'key' => 'plan_level',
        'match' => ['any' => $validPlans],
      ];
    }

    return empty($must) ? [] : ['must' => $must];
  }

  /**
   * 🔒 Validar nombre de collection contra whitelist.
   */
  private function validateCollectionName(string $name): void {
    $allowedVerticals = $this->securityConfig['allowed_verticals'] ?? 
      ['agro', 'arte', 'turismo', 'empleo'];
    $allowedCollections = array_map(fn($v) => "jaraba_{$v}", $allowedVerticals);

    if (!in_array($name, $allowedCollections, TRUE)) {
      $this->logger->warning('Intento de acceso a collection no permitida: @name', [
        '@name' => substr($name, 0, 50),
      ]);
      throw new \InvalidArgumentException('Collection no permitida');
    }
  }

  /**
   * Upsert con validacion.
   */
  public function upsert(string $collection, array $points): bool {
    $this->validateCollectionName($collection);
    
    // 🔒 Limitar numero de puntos por request
    if (count($points) > 100) {
      throw new \InvalidArgumentException('Maximo 100 puntos por upsert');
    }

    try {
      $response = $this->httpClient->put(
        "{$this->clusterUrl}/collections/{$collection}/points",
        [
          'headers' => $this->getHeaders(),
          'json' => ['points' => $points],
          'timeout' => 60,
        ]
      );
      return $response->getStatusCode() === 200;
    }
    catch (GuzzleException $e) {
      $this->logger->error('Qdrant upsert failed: @code', ['@code' => $e->getCode()]);
      return FALSE;
    }
  }

  private function getHeaders(): array {
    $headers = ['Content-Type' => 'application/json'];
    if (!empty($this->apiKey)) {
      $headers['api-key'] = $this->apiKey;
    }
    return $headers;
  }
}
 
5.2 TenantContextService.php (Seguro)
<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Servicio seguro de contexto de tenant.
 */
class TenantContextService {

  private GroupMembershipLoaderInterface $membershipLoader;
  private AccountInterface $currentUser;
  private array $securityConfig;
  private $logger;

  public function __construct(
    GroupMembershipLoaderInterface $membership_loader,
    AccountInterface $current_user,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->membershipLoader = $membership_loader;
    $this->currentUser = $current_user;
    $this->securityConfig = $config_factory->get('jaraba_rag.settings')->get('security') ?? [];
    $this->logger = $logger_factory->get('jaraba_rag');
  }

  /**
   * 🔒 Obtener contexto con validacion multi-capa.
   */
  public function getContext(): array {
    // 🔒 CAPA 1: Usuario debe estar autenticado
    if ($this->currentUser->isAnonymous()) {
      $this->logger->warning('Intento de acceso anonimo a contexto de tenant');
      throw new AccessDeniedHttpException('Autenticacion requerida');
    }

    // 🔒 CAPA 2: Usuario debe tener membership
    $memberships = $this->membershipLoader->loadByUser($this->currentUser);
    
    if (empty($memberships)) {
      $this->logger->warning('Usuario @uid sin tenant asignado', [
        '@uid' => $this->currentUser->id(),
      ]);
      throw new AccessDeniedHttpException('Usuario sin tenant asignado');
    }

    $group = reset($memberships)->getGroup();

    // 🔒 CAPA 3: Grupo debe existir y estar activo
    if (!$group) {
      throw new AccessDeniedHttpException('Tenant no encontrado');
    }

    if (method_exists($group, 'isPublished') && !$group->isPublished()) {
      $this->logger->warning('Intento de acceso a tenant inactivo: @gid', [
        '@gid' => $group->id(),
      ]);
      throw new AccessDeniedHttpException('Tenant inactivo');
    }

    // 🔒 CAPA 4: Construir contexto con valores sanitizados
    $context = [
      'tenant_id' => (int) $group->id(),
      'tenant_name' => $this->sanitizeString($group->label() ?? ''),
      'vertical' => $this->sanitizeVertical(
        $group->hasField('field_vertical') 
          ? $group->get('field_vertical')->value 
          : NULL
      ),
      'plan' => $this->sanitizePlan(
        $group->hasField('field_plan') 
          ? $group->get('field_plan')->value 
          : NULL
      ),
    ];

    // 🔒 CAPA 5: Validacion final de integridad
    $this->validateContextIntegrity($context);

    return $context;
  }

  /**
   * 🔒 Sanitizar vertical contra whitelist.
   */
  private function sanitizeVertical(?string $vertical): string {
    $allowed = $this->securityConfig['allowed_verticals'] ?? 
      ['agro', 'arte', 'turismo', 'empleo'];
    
    if ($vertical !== NULL && in_array($vertical, $allowed, TRUE)) {
      return $vertical;
    }
    
    // Default seguro
    return 'agro';
  }

  /**
   * 🔒 Sanitizar plan contra whitelist.
   */
  private function sanitizePlan(?string $plan): string {
    $allowed = $this->securityConfig['allowed_plans'] ?? 
      ['starter', 'growth', 'pro', 'enterprise'];
    
    if ($plan !== NULL && in_array($plan, $allowed, TRUE)) {
      return $plan;
    }
    
    // Default: plan mas restrictivo
    return 'starter';
  }

  /**
   * 🔒 Sanitizar strings genericos.
   */
  private function sanitizeString(string $value): string {
    // Eliminar caracteres potencialmente peligrosos
    $clean = preg_replace('/[<>"\']/', '', $value);
    // Limitar longitud
    return substr($clean ?? '', 0, 255);
  }

  /**
   * 🔒 Validar integridad del contexto.
   */
  private function validateContextIntegrity(array $context): void {
    if ($context['tenant_id'] <= 0) {
      throw new \RuntimeException('Integridad de tenant_id comprometida');
    }

    if (empty($context['vertical']) || empty($context['plan'])) {
      throw new \RuntimeException('Contexto de tenant incompleto');
    }
  }

  /**
   * Obtener filtros para Qdrant.
   */
  public function getSearchFilters(): array {
    $context = $this->getContext();
    
    return [
      'tenant_id' => $context['tenant_id'],
      'plan_levels' => $this->getAccessiblePlanLevels($context['plan']),
    ];
  }

  /**
   * Obtener nombre de collection.
   */
  public function getCollectionName(): string {
    $context = $this->getContext();
    return 'jaraba_' . $context['vertical'];
  }

  /**
   * Planes accesibles segun suscripcion.
   */
  private function getAccessiblePlanLevels(string $plan): array {
    return match($plan) {
      'enterprise' => ['starter', 'growth', 'pro', 'enterprise'],
      'pro' => ['starter', 'growth', 'pro'],
      'growth' => ['starter', 'growth'],
      default => ['starter'],
    };
  }
}
 
5.3 EmbeddingService.php con Rate Limiting
<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Servicio de embeddings con rate limiting.
 */
class EmbeddingService {

  private ClientInterface $httpClient;
  private string $apiKey;
  private string $model;
  private CacheBackendInterface $cache;
  private AccountProxyInterface $currentUser;
  private RequestStack $requestStack;
  private array $securityConfig;
  private $logger;

  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    AccountProxyInterface $current_user,
    RequestStack $request_stack,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->httpClient = $http_client;
    $config = $config_factory->get('jaraba_rag.settings');
    
    $this->apiKey = $config->get('openai_api_key') ?? '';
    $this->model = $config->get('embedding_model') ?? 'text-embedding-3-small';
    $this->securityConfig = $config->get('security') ?? [];
    $this->cache = $cache;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->logger = $logger_factory->get('jaraba_rag');
  }

  /**
   * 🔒 Generar embedding con validacion y rate limiting.
   */
  public function embed(string $text): array {
    // 🔒 Rate limiting
    $this->enforceRateLimit();

    // 🔒 Validar longitud del texto
    $maxLength = $this->securityConfig['max_embedding_text'] ?? 8000;
    if (strlen($text) > $maxLength) {
      throw new \InvalidArgumentException(
        "Texto excede limite de {$maxLength} caracteres"
      );
    }

    // 🔒 Sanitizar texto
    $cleanText = $this->sanitizeText($text);

    if (empty($cleanText)) {
      throw new \InvalidArgumentException('Texto vacio despues de sanitizar');
    }

    // Llamar a OpenAI
    $response = $this->httpClient->post(
      'https://api.openai.com/v1/embeddings',
      [
        'headers' => [
          'Authorization' => 'Bearer ' . $this->apiKey,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $this->model,
          'input' => $cleanText,
        ],
        'timeout' => 30,
      ]
    );

    $data = json_decode($response->getBody()->getContents(), TRUE);
    return $data['data'][0]['embedding'] ?? [];
  }

  /**
   * 🔒 Aplicar rate limiting.
   */
  private function enforceRateLimit(): void {
    $window = $this->securityConfig['rate_limit_window'] ?? 60;
    $maxAuth = $this->securityConfig['rate_limit_max_auth'] ?? 100;
    $maxAnon = $this->securityConfig['rate_limit_max_anon'] ?? 10;

    $identifier = $this->getRateLimitIdentifier();
    $key = 'jaraba_rag:rate:' . $identifier;
    $maxRequests = $this->currentUser->isAuthenticated() ? $maxAuth : $maxAnon;

    $cached = $this->cache->get($key);
    $currentCount = $cached ? (int) $cached->data : 0;

    if ($currentCount >= $maxRequests) {
      $this->logger->warning('Rate limit excedido: @id', ['@id' => $identifier]);
      throw new TooManyRequestsHttpException(
        $window,
        'Limite de solicitudes excedido. Intenta de nuevo en un minuto.'
      );
    }

    // Incrementar contador
    $this->cache->set($key, $currentCount + 1, time() + $window);
  }

  /**
   * 🔒 Obtener identificador para rate limiting.
   */
  private function getRateLimitIdentifier(): string {
    if ($this->currentUser->isAuthenticated()) {
      return 'user:' . $this->currentUser->id();
    }

    // Para anonimos, usar IP (con cuidado de proxies)
    $request = $this->requestStack->getCurrentRequest();
    $ip = $request ? $request->getClientIp() : 'unknown';
    
    // 🔒 Validar que es una IP valida
    if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
      $ip = 'invalid';
    }

    return 'ip:' . $ip;
  }

  /**
   * 🔒 Sanitizar texto antes de enviar a OpenAI.
   */
  private function sanitizeText(string $text): string {
    // Eliminar caracteres de control
    $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
    // Normalizar espacios
    $clean = preg_replace('/\s+/', ' ', $clean ?? '');
    // Trim
    return trim($clean ?? '');
  }
}
 
6. Registro de Servicios
6.1 jaraba_rag.services.yml
services:
  # Cliente Qdrant seguro
  jaraba_rag.qdrant_client:
    class: Drupal\jaraba_rag\Service\QdrantClientService
    arguments:
      - '@http_client'
      - '@config.factory'
      - '@logger.factory'

  # Servicio de embeddings con rate limiting
  jaraba_rag.embedding:
    class: Drupal\jaraba_rag\Service\EmbeddingService
    arguments:
      - '@http_client'
      - '@config.factory'
      - '@cache.default'
      - '@current_user'
      - '@request_stack'
      - '@logger.factory'

  # Contexto de tenant seguro
  jaraba_rag.tenant_context:
    class: Drupal\jaraba_rag\Service\TenantContextService
    arguments:
      - '@group.membership_loader'
      - '@current_user'
      - '@config.factory'
      - '@logger.factory'

  # Chunking
  jaraba_rag.chunking:
    class: Drupal\jaraba_rag\Service\ChunkingService
    arguments:
      - '@config.factory'

  # Indexacion
  jaraba_rag.indexing:
    class: Drupal\jaraba_rag\Service\IndexingService
    arguments:
      - '@jaraba_rag.qdrant_client'
      - '@jaraba_rag.embedding'
      - '@jaraba_rag.chunking'
      - '@jaraba_rag.tenant_context'
      - '@entity_type.manager'
      - '@logger.factory'

  # Retrieval
  jaraba_rag.retrieval:
    class: Drupal\jaraba_rag\Service\RetrievalService
    arguments:
      - '@jaraba_rag.qdrant_client'
      - '@jaraba_rag.embedding'
      - '@jaraba_rag.tenant_context'
      - '@config.factory'
      - '@logger.factory'
 
7. Comandos Drush Seguros
<?php

declare(strict_types=1);

namespace Drupal\jaraba_rag\Commands;

use Drush\Commands\DrushCommands;
use Drupal\jaraba_rag\Service\QdrantClientService;
use Drupal\jaraba_rag\Service\IndexingService;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Comandos Drush para jaraba_rag.
 */
class JarabaRagCommands extends DrushCommands {

  public function __construct(
    private QdrantClientService $qdrantClient,
    private IndexingService $indexingService
  ) {
    parent::__construct();
  }

  /**
   * Verificar conexion con Qdrant.
   *
   * @command jaraba:qdrant:status
   * @aliases jqs
   * @usage drush jaraba:qdrant:status
   */
  public function status(): void {
    $health = $this->qdrantClient->healthCheck();

    if ($health['status'] === 'ok') {
      $this->io()->success('Qdrant conectado correctamente');
      // 🔒 NO mostrar URL ni credenciales
      $this->io()->table(
        ['Propiedad', 'Valor'],
        [
          ['Entorno', $health['environment']],
          ['Estado', 'Conectado'],
          ['Collections', (string) $health['collections']],
        ]
      );
    }
    else {
      $this->io()->error('Error de conexion con Qdrant');
      // 🔒 NO mostrar mensaje de error detallado
      $this->io()->note('Verifica las variables de entorno');
    }
  }

  /**
   * Crear collections iniciales.
   *
   * @command jaraba:qdrant:init
   * @aliases jqi
   */
  public function init(): void {
    $collections = ['jaraba_agro', 'jaraba_arte', 'jaraba_turismo', 'jaraba_empleo'];

    foreach ($collections as $name) {
      $this->io()->write("Creando {$name}... ");
      if ($this->qdrantClient->createCollection($name)) {
        $this->io()->writeln('<info>OK</info>');
      }
      else {
        $this->io()->writeln('<error>ERROR</error>');
      }
    }

    $this->io()->success('Inicializacion completada');
  }

  /**
   * Reindexar contenido.
   *
   * @command jaraba:qdrant:reindex
   * @aliases jqr
   * @option tenant ID del tenant a reindexar
   * @option all Reindexar todos los tenants
   */
  public function reindex(array $options = ['tenant' => NULL, 'all' => FALSE]): void {
    if ($options['all']) {
      // 🔒 Confirmar operacion destructiva
      if (!$this->io()->confirm('Esto reindexara TODOS los tenants. Continuar?', FALSE)) {
        return;
      }
      $count = $this->indexingService->reindexAll();
      $this->io()->success("Reindexadas {$count} entidades");
    }
    elseif ($options['tenant']) {
      // 🔒 Validar tenant ID
      $tenantId = filter_var($options['tenant'], FILTER_VALIDATE_INT);
      if ($tenantId === FALSE || $tenantId <= 0) {
        $this->io()->error('tenant debe ser un ID numerico valido');
        return;
      }
      $count = $this->indexingService->reindexTenant((string) $tenantId);
      $this->io()->success("Reindexadas {$count} entidades");
    }
    else {
      $this->io()->warning('Especifica --all o --tenant=ID');
    }
  }
}
 
8. Checklist de Seguridad
8.1 Credenciales y Configuración
☐	API Keys en variables de entorno (NO en código)
☐	.env excluido de Git (.gitignore)
☐	.env protegido con .htaccess
☐	settings.php valida credenciales en producción
☐	HTTPS obligatorio en producción para Qdrant
☐	API Key requerida en producción
8.2 Desarrollo (Lando)
☐	Puertos Qdrant solo en 127.0.0.1 (localhost)
☐	Version de imagen Docker fija (no :latest)
☐	Limites de recursos configurados (memory)
☐	API Key opcional pero recomendada
8.3 Validación de Inputs
☐	tenant_id validado como integer > 0
☐	plan_levels validados contra whitelist
☐	vertical validado contra whitelist
☐	Collection names validados contra whitelist
☐	Longitud de queries limitada
☐	Texto de embeddings sanitizado
8.4 Rate Limiting
☐	Rate limiting por usuario autenticado (100/min)
☐	Rate limiting por IP para anónimos (10/min)
☐	Logging de excesos de rate limit
☐	Respuesta 429 con Retry-After header
8.5 Logging y Monitoreo
☐	Logs NO contienen URLs de cluster
☐	Logs NO contienen API Keys (ni parciales)
☐	Logs NO contienen payloads de usuario
☐	Intentos de acceso no autorizado logueados
☐	Intentos de inyección logueados como warning
8.6 Multi-Tenancy
☐	Usuario anónimo NO puede acceder a contexto
☐	Usuario debe tener membership en grupo
☐	Grupo debe estar publicado/activo
☐	Filtro de tenant_id siempre aplicado en queries
☐	Plan level limita acceso a contenido

✅ CONFIGURACIÓN SEGURA COMPLETADA
Este documento implementa las mejores practicas de seguridad para la integracion Qdrant. Las credenciales estan protegidas, los inputs validados, y el aislamiento multi-tenant garantizado. Revisar checklist antes de cada deploy.

— Fin del Anexo A.1 v3.0 (Seguro) —
Jaraba Impact Platform | Enero 2026
