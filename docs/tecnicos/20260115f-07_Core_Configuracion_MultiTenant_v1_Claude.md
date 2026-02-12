JARABA IMPACT PLATFORM
Configuración Multi-Tenant
Group Module + Domain Access
Guía de Implementación y Configuración
Enero 2026
 
1. Arquitectura Soft Multi-Tenancy
La Jaraba Impact Platform utiliza Soft Multi-Tenancy mediante el módulo Group de Drupal. Todos los tenants comparten una única instalación y base de datos, con aislamiento lógico estricto.
1.1 Comparativa de Modelos Multi-Tenant
Modelo	Descripción	Ventajas	Desventajas	Uso
Multisite (Hard)	Una instalación Drupal por tenant	Aislamiento total, personalización ilimitada	Alto coste, mantenimiento N×	Clientes enterprise/gobierno
Soft (Group)	Una instalación, aislamiento lógico	Coste marginal ≈0, actualizaciones centralizadas	Complejidad de permisos	RECOMENDADO para SaaS
Híbrido	Soft + Multisite para grandes	Flexibilidad máxima	Complejidad operativa	Escala masiva
1.2 Ventajas del Modelo Soft Multi-Tenant
•	Eficiencia Operativa: Una actualización de seguridad se aplica instantáneamente a todos los tenants
•	Economía de Escala: Cientos de tenants coexisten con coste marginal cercano a cero
•	Visión Global: Analytics e IA pueden acceder a datos agregados (con permisos)
•	Jerarquía Flexible: Soporta estructuras Franquicia → Sub-franquicias → Tiendas
•	Noisy Neighbor Detection: Monitoreo de recursos por Group ID para ajuste de pricing
1.3 Diagrama de Arquitectura
┌─────────────────────────────────────────────────────────────────┐
│                    DRUPAL 11 + COMMERCE 3.x                     │
├─────────────────────────────────────────────────────────────────┤
│                      BASE DE DATOS ÚNICA                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │   GROUP:    │  │   GROUP:    │  │   GROUP:    │   ...       │
│  │  Tenant A   │  │  Tenant B   │  │  Tenant C   │             │
│  │  ─────────  │  │  ─────────  │  │  ─────────  │             │
│  │  • Store    │  │  • Store    │  │  • Store    │             │
│  │  • Products │  │  • Products │  │  • Products │             │
│  │  • Orders   │  │  • Orders   │  │  • Orders   │             │
│  │  • Users    │  │  • Users    │  │  • Users    │             │
│  │  • Config   │  │  • Config   │  │  • Config   │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
 
2. Instalación y Configuración Base
2.1 Módulos Requeridos
Módulo	Versión	Comando de Instalación	Función
drupal/group	^3.0	composer require drupal/group	Core de multi-tenancy
drupal/gnode	^3.0	composer require drupal/gnode	Relación Group-Node
drupal/grequest	^3.0	composer require drupal/grequest	Solicitudes de membresía
drupal/ginvite	^3.0	composer require drupal/ginvite	Invitaciones a grupos
drupal/domain	^2.0	composer require drupal/domain	Gestión de dominios
drupal/domain_access	^2.0	composer require drupal/domain_access	Acceso por dominio
drupal/domain_config	^2.0	composer require drupal/domain_config	Config por dominio
2.2 Secuencia de Instalación
# 1. Instalar módulos via Composer
composer require drupal/group:^3.0 drupal/gnode:^3.0 \
  drupal/grequest:^3.0 drupal/ginvite:^3.0

# 2. Habilitar módulos
drush en group gnode grequest ginvite -y

# 3. (Opcional) Instalar Domain Access para subdominios
composer require drupal/domain:^2.0
drush en domain domain_access domain_config -y

# 4. Limpiar caché
drush cr

# 5. Verificar instalación
drush pml | grep -E '(group|domain)'
2.3 Configuración Inicial
Acceder a /admin/group para gestionar la configuración de grupos.
 
3. Configuración de Group Types
Los Group Types definen las plantillas de configuración para diferentes tipos de tenants. Cada type tiene sus propios campos, roles y plugins.
3.1 Group Types del Ecosistema Jaraba
Group Type	Machine Name	Descripción	Vertical
Tenant Comercial	tenant_commercial	Tiendas y comercios que venden productos físicos/digitales	AgroConecta, ComercioConecta
Tenant Formativo	tenant_training	Entidades que ofrecen cursos y formación	Empleabilidad, Emprendimiento
Tenant Institucional	tenant_institutional	Administraciones públicas y entidades (marca blanca)	Todos
Cohorte	cohort	Agrupación temporal de usuarios en un programa	Empleabilidad
Comunidad	community	Espacios de colaboración y networking	Emprendimiento
3.2 Creación de Group Type: tenant_commercial
Ruta: /admin/group/types/add
# config/sync/group.type.tenant_commercial.yml
langcode: en
status: true
dependencies:
  enforced:
    module:
      - jaraba_tenant
id: tenant_commercial
label: 'Tenant Comercial'
description: 'Tiendas y comercios con capacidad de venta'
new_revision: true
creator_membership: true
creator_wizard: true
creator_roles:
  - tenant_commercial-owner
3.3 Campos del Group Type
Campo	Machine Name	Tipo	Descripción
Vertical	field_vertical	Entity Reference (Taxonomy)	Vertical de negocio (Agro, Comercio, etc.)
Plan	field_plan	List (text)	starter | professional | enterprise
Logo	field_logo	Image	Logo del tenant para branding
Color Primario	field_color_primary	Color	Color principal de la marca
Stripe Account ID	field_stripe_account_id	String	ID de cuenta Stripe Connect
Stripe Onboarding	field_stripe_onboarding_complete	Boolean	Estado de onboarding Stripe
Platform Fee	field_platform_fee_percent	Decimal	Porcentaje de comisión (5.00)
Dominio Custom	field_custom_domain	String	Dominio personalizado (opcional)
 
4. Configuración de Group Roles
Cada Group Type define sus propios Group Roles con permisos específicos dentro del contexto del grupo.
4.1 Roles para tenant_commercial
Rol	Machine Name	Descripción	Asignación
Propietario	tenant_commercial-owner	Control total del tenant, facturación, configuración	Automática al crear
Administrador	tenant_commercial-admin	Gestión operativa delegada por el propietario	Manual por owner
Editor	tenant_commercial-editor	Creación y edición de productos y contenido	Manual
Vendedor	tenant_commercial-sales	Gestión de pedidos y clientes	Manual
Miembro	tenant_commercial-member	Acceso básico a recursos del tenant	Al unirse
4.2 Configuración YAML de Roles
# config/sync/group.role.tenant_commercial-owner.yml
langcode: en
status: true
dependencies:
  config:
    - group.type.tenant_commercial
id: tenant_commercial-owner
label: 'Propietario'
weight: 0
internal: false
audience: member
scope: individual
group_type: tenant_commercial
admin: true  # Tiene todos los permisos
permissions:
  - 'administer group'
  - 'administer members'
  - 'delete group'
  - 'edit group'
  - 'view group'
  - 'create group_node:commerce_product entity'
  - 'delete any group_node:commerce_product entity'
  - 'delete own group_node:commerce_product entity'
  - 'update any group_node:commerce_product entity'
  - 'update own group_node:commerce_product entity'
  - 'view group_node:commerce_product entity'
  - 'view unpublished group_node:commerce_product entity'
4.3 Matriz de Permisos por Rol
Permiso	owner	admin	editor	sales	member
Administrar grupo	✓	—	—	—	—
Eliminar grupo	✓	—	—	—	—
Editar configuración grupo	✓	✓	—	—	—
Administrar miembros	✓	✓	—	—	—
Crear productos	✓	✓	✓	—	—
Editar cualquier producto	✓	✓	—	—	—
Editar productos propios	✓	✓	✓	—	—
Ver productos no publicados	✓	✓	✓	—	—
Gestionar pedidos	✓	✓	—	✓	—
Ver contenido del grupo	✓	✓	✓	✓	✓
 
5. Group Content Plugins
Los Group Content Plugins definen qué tipos de entidades pueden asociarse a un grupo y cómo se gestionan los permisos sobre ellas.
5.1 Plugins Habilitados por Group Type
Plugin	tenant_commercial	tenant_training	tenant_institutional	cohort
group_membership	✓	✓	✓	✓
group_node:article	✓	✓	✓	—
group_node:page	✓	✓	✓	—
group_node:product	✓	—	✓	—
group_node:course	—	✓	✓	✓
group_commerce_store	✓	—	✓	—
group_media	✓	✓	✓	—
subgroup:tenant_*	—	—	✓	—
5.2 Configuración de Plugin group_node
Ruta: /admin/group/types/manage/tenant_commercial/content
# config/sync/group.content_type.tenant_commercial-group_node-commerce_product.yml
langcode: en
status: true
dependencies:
  config:
    - group.type.tenant_commercial
    - node.type.commerce_product
  module:
    - gnode
id: tenant_commercial-group_node-commerce_product
label: 'Producto del Tenant'
description: 'Productos que pertenecen a este tenant comercial'
group_type: tenant_commercial
content_plugin: group_node:commerce_product
plugin_config:
  group_cardinality: 1        # Un producto pertenece a UN solo grupo
  entity_cardinality: 0       # Sin límite de productos por grupo
  use_creation_wizard: true   # Wizard para crear productos
  auto_assign_creator: true   # El creador se asigna automáticamente
5.3 Plugin group_commerce_store
Permite asociar tiendas de Commerce a grupos para aislar catálogos y pedidos:
# Plugin personalizado: jaraba_group/src/Plugin/Group/RelationPlugin/GroupCommerceStore.php
<?php

namespace Drupal\jaraba_group\Plugin\Group\RelationPlugin;

use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a group relation type for Commerce stores.
 *
 * @GroupRelationType(
 *   id = "group_commerce_store",
 *   label = @Translation("Commerce Store"),
 *   description = @Translation("Adds commerce stores to groups."),
 *   entity_type_id = "commerce_store",
 *   entity_access = TRUE,
 *   reference_label = @Translation("Store"),
 *   reference_description = @Translation("The store to add."),
 *   handlers = {
 *     "access" = "Drupal\group\Plugin\Group\RelationHandler\EmptyAccessControl",
 *   }
 * )
 */
class GroupCommerceStore extends GroupRelationBase {}
 
6. Configuración de Domain Access
El módulo Domain Access permite asignar subdominios o dominios personalizados a cada tenant, mejorando la experiencia de marca blanca.
6.1 Estrategia de Dominios
Tipo	Patrón	Ejemplo	Uso
Subdominio automático	{tenant}.jarabaimpact.com	olivarsur.jarabaimpact.com	Plan Starter/Professional
Dominio personalizado	www.{domain}.com	www.olivarsur.es	Plan Enterprise
Path-based	jarabaimpact.com/{tenant}	jarabaimpact.com/olivarsur	Fallback sin DNS
6.2 Configuración de Dominios
Ruta: /admin/config/domain
# config/sync/domain.record.olivarsur_jarabaimpact_com.yml
langcode: en
status: true
dependencies: {  }
id: olivarsur_jarabaimpact_com
hostname: olivarsur.jarabaimpact.com
name: 'Cooperativa Olivar Sur'
scheme: https
weight: 0
is_default: false
third_party_settings:
  jaraba_tenant:
    group_id: 42  # Referencia al Group/Tenant
6.3 Resolución de Tenant por Dominio
// jaraba_tenant/src/Service/TenantContextService.php

/**
 * Determina el tenant actual por dominio.
 */
private function findTenantByDomain(string $host): ?TenantInterface {
  // 1. Buscar en Domain records
  $domain_storage = $this->entityTypeManager->getStorage('domain');
  $domains = $domain_storage->loadByProperties(['hostname' => $host]);
  
  if ($domain = reset($domains)) {
    $group_id = $domain->getThirdPartySetting('jaraba_tenant', 'group_id');
    if ($group_id) {
      return $this->loadTenantByGroupId($group_id);
    }
  }
  
  // 2. Extraer subdominio (tenant.jarabaimpact.com)
  if (preg_match('/^([a-z0-9-]+)\.jarabaimpact\.com$/', $host, $matches)) {
    $subdomain = $matches[1];
    return $this->loadTenantByMachineName($subdomain);
  }
  
  return NULL;
}
6.4 Configuración DNS (Wildcard)
# Configuración DNS en el proveedor (Cloudflare, Route53, etc.)

# Wildcard para subdominios automáticos
*.jarabaimpact.com    A       203.0.113.50
*.jarabaimpact.com    AAAA    2001:db8::50

# Dominio personalizado (el cliente configura su DNS)
www.olivarsur.es      CNAME   custom.jarabaimpact.com

# SSL Wildcard con Let's Encrypt
# certbot certonly --dns-cloudflare -d '*.jarabaimpact.com'
 
7. TenantContextService
El TenantContextService es el servicio central que determina el tenant actual y proporciona métodos de acceso utilizados por todos los módulos del ecosistema.
7.1 Prioridad de Resolución
Prioridad	Método	Descripción	Ejemplo
1	Route Parameter	Parámetro explícito en la URL	/tenant/{tenant_id}/products
2	Domain/Subdomain	Host de la petición HTTP	olivarsur.jarabaimpact.com
3	Group Membership	Grupo principal del usuario	Usuario pertenece a 1 tenant
4	Session Storage	Tenant seleccionado en sesión	Switcher de tenants (admins)
7.2 Implementación Completa
// jaraba_tenant/src/Service/TenantContextService.php
<?php

namespace Drupal\jaraba_tenant\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class TenantContextService implements TenantContextServiceInterface {

  protected ?TenantInterface $currentTenant = NULL;
  protected bool $resolved = FALSE;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RequestStack $requestStack,
    protected AccountInterface $currentUser,
    protected GroupMembershipLoaderInterface $membershipLoader
  ) {}

  public function getCurrentTenant(): ?TenantInterface {
    if ($this->resolved) {
      return $this->currentTenant;
    }
    
    $this->resolved = TRUE;
    $request = $this->requestStack->getCurrentRequest();
    
    // 1. Route parameter
    if ($tenantId = $request->attributes->get('tenant')) {
      $this->currentTenant = $this->loadTenant($tenantId);
      return $this->currentTenant;
    }
    
    // 2. Domain matching
    if ($tenant = $this->findTenantByDomain($request->getHost())) {
      $this->currentTenant = $tenant;
      return $this->currentTenant;
    }
    
    // 3. User's primary group
    if ($this->currentUser->isAuthenticated()) {
      $this->currentTenant = $this->getUserPrimaryTenant();
      return $this->currentTenant;
    }
    
    // 4. Session (para admin switcher)
    if ($sessionTenant = $request->getSession()->get('active_tenant')) {
      $this->currentTenant = $this->loadTenant($sessionTenant);
    }
    
    return $this->currentTenant;
  }

  public function getCurrentTenantId(): ?int {
    return $this->getCurrentTenant()?->id();
  }

  public function getCurrentVertical(): ?string {
    return $this->getCurrentTenant()?->getVertical();
  }

  public function getPlan(): string {
    return $this->getCurrentTenant()?->getPlanType() ?? 'starter';
  }
}
 
8. Aislamiento de Datos (Query Alter)
El aislamiento de datos se garantiza mediante hook_query_alter() que intercepta todas las queries a entidades tenant-aware y añade filtros automáticos.
8.1 Entidades Tenant-Aware
Entidad	Tabla Base	Campo Tenant	Filtrado Automático
commerce_product	commerce_product	field_tenant_id	✓
commerce_order	commerce_order	field_tenant_id	✓
commerce_store	commerce_store	field_tenant_id	✓
node (productos)	node_field_data	field_tenant_id	✓
financial_transaction	financial_transaction	tenant_id	✓
cost_allocation	cost_allocation	tenant_id	✓
diagnostic_express_result	diagnostic_express_result	tenant_id	✓
ai_query_log	ai_query_log	tenant_id	✓
8.2 Hook de Query Alter
// jaraba_tenant.module

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Implements hook_query_TAG_alter() para aislamiento multi-tenant.
 */
function jaraba_tenant_query_tenant_aware_alter(AlterableInterface $query) {
  $tenantContext = \Drupal::service('jaraba_tenant.context');
  $tenant = $tenantContext->getCurrentTenant();
  
  if (!$tenant) {
    return;
  }
  
  // Bypass para super_admin
  if (\Drupal::currentUser()->hasPermission('bypass tenant isolation')) {
    return;
  }
  
  $tables = $query->getTables();
  foreach ($tables as $alias => $table) {
    if ($tenantContext->isTenantAwareTable($table['table'])) {
      $query->condition($alias . '.tenant_id', $tenant->id());
    }
  }
}

/**
 * Implements hook_entity_query_alter().
 */
function jaraba_tenant_entity_query_alter(QueryInterface $query) {
  $entityType = $query->getEntityTypeId();
  
  $tenantAwareTypes = [
    'financial_transaction',
    'cost_allocation',
    'commerce_product',
    'commerce_order',
    'diagnostic_express_result',
    'ai_query_log',
  ];
  
  if (in_array($entityType, $tenantAwareTypes)) {
    $query->addTag('tenant_aware');
  }
}
 
9. Flujo de Creación de Tenants
9.1 Proceso de Onboarding
1.	Registro de Usuario: El propietario se registra en la plataforma
2.	Selección de Plan: Elige Starter, Professional o Enterprise
3.	Creación de Group: Se crea el Group del tipo correspondiente
4.	Entidad Tenant: Se crea la entidad tenant vinculada al Group
5.	Commerce Store: (Si comercial) Se crea la tienda en Commerce
6.	Theme Config: Se inicializa la configuración visual
7.	Stripe Connect: Se redirige a onboarding de Stripe
8.	Activación: Tenant activo y listo para operar
9.2 Servicio de Creación de Tenants
// jaraba_tenant/src/Service/TenantCreationService.php

public function createTenant(array $data): TenantInterface {
  // 1. Crear Group
  $group = $this->groupStorage->create([
    'type' => $data['group_type'] ?? 'tenant_commercial',
    'label' => $data['name'],
    'field_vertical' => $data['vertical_id'],
    'field_plan' => $data['plan_type'] ?? 'starter',
    'uid' => $data['owner_uid'],
  ]);
  $group->save();
  
  // 2. Añadir owner como miembro con rol owner
  $group->addMember($this->userStorage->load($data['owner_uid']), [
    'group_roles' => [$data['group_type'] . '-owner'],
  ]);
  
  // 3. Crear entidad Tenant
  $tenant = $this->tenantStorage->create([
    'group_id' => $group->id(),
    'name' => $data['name'],
    'machine_name' => $this->generateMachineName($data['name']),
    'vertical_id' => $data['vertical_id'],
    'plan_type' => $data['plan_type'] ?? 'starter',
    'platform_fee_percent' => $this->getFeeByPlan($data['plan_type']),
    'status' => 'active',
  ]);
  $tenant->save();
  
  // 4. Crear Store (si comercial)
  if (in_array($data['group_type'], ['tenant_commercial'])) {
    $this->createCommerceStore($tenant, $data);
  }
  
  // 5. Inicializar theme config
  $this->initializeThemeConfig($tenant);
  
  // 6. Disparar evento para ECA
  $this->eventDispatcher->dispatch(
    new TenantCreatedEvent($tenant),
    TenantEvents::TENANT_CREATED
  );
  
  return $tenant;
}
 
10. Panel de Administración de Tenants
10.1 Rutas de Administración
Ruta	Descripción	Permiso Requerido
/admin/group	Listado de todos los grupos	administer groups
/admin/group/types	Gestión de Group Types	administer group types
/group/{group}/members	Gestión de miembros del grupo	administer members
/group/{group}/edit	Editar configuración del grupo	edit group
/group/{group}/content	Contenido asociado al grupo	view group
/admin/jaraba/tenants	Panel centralizado de tenants	administer tenants
10.2 Switcher de Tenants (Para Admins)
Los administradores de plataforma pueden cambiar de contexto entre tenants para soporte:
// jaraba_tenant/src/Form/TenantSwitcherForm.php

public function buildForm(array $form, FormStateInterface $form_state) {
  // Solo para usuarios con permiso
  if (!$this->currentUser->hasPermission('switch tenant context')) {
    throw new AccessDeniedHttpException();
  }
  
  $form['tenant'] = [
    '#type' => 'entity_autocomplete',
    '#title' => $this->t('Seleccionar Tenant'),
    '#target_type' => 'tenant',
    '#required' => TRUE,
  ];
  
  $form['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Cambiar Contexto'),
  ];
  
  return $form;
}

public function submitForm(array &$form, FormStateInterface $form_state) {
  $tenant_id = $form_state->getValue('tenant');
  $this->requestStack->getSession()->set('active_tenant', $tenant_id);
  
  $this->messenger()->addStatus(
    $this->t('Contexto cambiado a tenant @id', ['@id' => $tenant_id])
  );
}
 
Apéndice: Checklist de Implementación
Módulos:
•	[ ] Group Module (^3.0) instalado y habilitado
•	[ ] GNode, GRequest, GInvite instalados
•	[ ] Domain Access instalado (si se usan subdominios)
Group Types:
•	[ ] tenant_commercial creado con campos
•	[ ] tenant_training creado con campos
•	[ ] tenant_institutional creado con campos
•	[ ] cohort y community creados
Group Roles:
•	[ ] Roles owner/admin/editor/sales/member definidos
•	[ ] Permisos configurados para cada rol
Content Plugins:
•	[ ] group_node plugins habilitados por tipo
•	[ ] group_commerce_store plugin implementado
•	[ ] group_media plugin habilitado
Domain Access:
•	[ ] Wildcard DNS configurado
•	[ ] SSL wildcard activo
•	[ ] Resolución de tenant por dominio funcionando
TenantContextService:
•	[ ] Servicio implementado y registrado
•	[ ] Prioridad de resolución funcionando
•	[ ] Query alter aplicando filtros
Testing:
•	[ ] Test de aislamiento entre tenants
•	[ ] Test de resolución por dominio
•	[ ] Test de permisos por rol de grupo
— Fin del Documento —
Jaraba Impact Platform © 2026
