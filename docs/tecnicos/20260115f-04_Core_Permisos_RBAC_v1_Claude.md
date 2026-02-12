JARABA IMPACT PLATFORM
Sistema de Permisos y Control de Acceso
Especificación RBAC v1.0
Documento Técnico de Implementación
Enero 2026
 
1. Filosofía de Control de Acceso
El ecosistema Jaraba Impact Platform implementa un modelo de Role-Based Access Control (RBAC) combinado con Attribute-Based Access Control (ABAC) para gestionar el acceso granular a recursos en un entorno multi-tenant complejo.
1.1 Principios Fundamentales
•	Principio de Mínimo Privilegio: Los usuarios reciben únicamente los permisos necesarios para realizar sus tareas.
•	Separación de Responsabilidades: Las funciones críticas requieren múltiples roles cooperando.
•	Aislamiento Multi-Tenant: Cada tenant opera en un contexto aislado con sus propios roles y permisos.
•	Herencia Jerárquica: Los permisos se heredan en cascada: Plataforma → Vertical → Tenant → Usuario.
•	Zero Trust: Validación en cada capa, nunca confiar en inputs del usuario.
1.2 Capas de Control de Acceso
Capa	Ámbito	Mecanismo Drupal	Ejemplo
Plataforma	Global (todos los tenants)	Drupal Core Permissions	Acceso a administración global
Vertical	Por línea de negocio	Taxonomy-based filtering	AgroConecta vs EmpleoConecta
Tenant	Por organización/franquicia	Group Module Permissions	Cooperativa Olivar Sur
Plan	Por nivel de suscripción	Custom field + conditions	Starter vs Enterprise
Contenido	Por nodo/recurso específico	Content Access Module	Documento confidencial
 
2. Jerarquía de Roles del Sistema
2.1 Roles de Plataforma (Globales)
Roles que operan a nivel de toda la plataforma, sin restricción de tenant.
Rol	Machine Name	Descripción	Hereda de
Super Administrador	super_admin	Control total del sistema. Único rol que puede modificar configuración crítica.	—
Administrador Plataforma	platform_admin	Gestión operativa diaria: usuarios, tenants, métricas globales.	authenticated
Soporte Técnico	platform_support	Acceso lectura a logs, tickets, y datos de diagnóstico.	authenticated
Auditor	platform_auditor	Solo lectura de todos los datos para cumplimiento y reporting.	authenticated
API Service	api_service	Cuenta de servicio para integraciones M2M (Machine-to-Machine).	authenticated
2.2 Roles de Tenant (Group Roles)
Roles asignados dentro del contexto de un Group (tenant). Implementados via Group Module.
Rol Tenant	Machine Name	Descripción	Permisos Clave
Propietario Tenant	tenant_owner	Dueño del negocio/franquicia. Control total dentro del tenant.	Gestión usuarios, facturación, configuración
Administrador Tenant	tenant_admin	Gestión operativa del tenant delegada por el propietario.	Usuarios, contenido, productos, pedidos
Editor Contenido	tenant_editor	Creación y edición de contenido propio del tenant.	CRUD contenido, media, productos
Operador Ventas	tenant_sales	Gestión de pedidos, clientes y operaciones comerciales.	Pedidos, clientes, reportes ventas
Consultor Externo	tenant_consultant	Acceso temporal para consultores/mentores.	Lectura datos, creación informes
Miembro Básico	tenant_member	Usuario estándar con acceso a recursos del tenant.	Lectura contenido, compras, perfil
2.3 Roles por Avatar/Vertical
Roles especializados según el tipo de usuario (avatar) y la vertical donde opera.
Avatar	Vertical	Rol Específico	Funcionalidades Exclusivas
Lucía (+45 años)	Empleabilidad	job_seeker	Diagnóstico Express, Rutas formativas, CV Builder, Bolsa empleo
Javier (emprendedor)	Emprendimiento	entrepreneur	Diagnósticos negocio, Itinerarios, Mentorías, Groups colaboración
Marta (comercio)	PYMEs / Comercio	merchant	Tienda Commerce, Catálogo productos, Gestión pedidos, Stripe Connect
David (experto)	Consultores	consultant	Panel mentorías, Gestión clientes, Cobro servicios, Reportes impacto
Elena (entidad)	Institucional	entity_admin	Marca blanca, Cohortes usuarios, Reportes justificación, Grant tracking
 
3. Matriz de Permisos Detallada
3.1 Permisos de Gestión de Usuarios
Permiso	super_admin	platform_admin	tenant_owner	tenant_admin	tenant_editor
Crear usuarios plataforma	✓	✓	—	—	—
Eliminar usuarios plataforma	✓	—	—	—	—
Crear usuarios en tenant	✓	✓	✓	✓	—
Asignar roles tenant	✓	✓	✓	✓	—
Ver todos los usuarios	✓	✓	Solo tenant	Solo tenant	—
Modificar perfil propio	✓	✓	✓	✓	✓
Resetear contraseñas	✓	✓	Solo tenant	Solo tenant	—
Bloquear usuarios	✓	✓	Solo tenant	Solo tenant	—
Ver actividad usuarios	✓	✓	Solo tenant	Solo tenant	—
3.2 Permisos de Contenido
Permiso	tenant_owner	tenant_admin	tenant_editor	tenant_sales	tenant_member
Crear contenido	✓	✓	✓	—	—
Editar contenido propio	✓	✓	✓	—	—
Editar cualquier contenido	✓	✓	—	—	—
Eliminar contenido	✓	✓	Solo propio	—	—
Publicar/despublicar	✓	✓	✓	—	—
Gestionar revisiones	✓	✓	—	—	—
Ver contenido no publicado	✓	✓	✓	—	—
Acceder Media Library	✓	✓	✓	—	—
Ver contenido restringido	✓	✓	✓	✓	Por plan
3.3 Permisos de Commerce
Permiso	tenant_owner	tenant_admin	merchant	tenant_sales	tenant_member
Crear productos	✓	✓	✓	—	—
Editar precios	✓	✓	✓	—	—
Gestionar inventario	✓	✓	✓	✓	—
Ver pedidos tenant	✓	✓	✓	✓	Solo propios
Procesar pedidos	✓	✓	✓	✓	—
Emitir reembolsos	✓	✓	—	—	—
Ver reportes ventas	✓	✓	✓	✓	—
Configurar Stripe	✓	—	—	—	—
Exportar datos	✓	✓	—	—	—
 
3.4 Permisos del FOC (Financial Operations Center)
Permiso	super_admin	platform_admin	tenant_owner	platform_auditor	tenant_admin
Ver métricas globales	✓	✓	—	✓	—
Ver métricas tenant	✓	✓	✓	✓	✓
Ejecutar ETL manual	✓	✓	—	—	—
Configurar alertas	✓	✓	✓	—	—
Ver transacciones	✓	✓	Solo tenant	✓	Solo tenant
Exportar financieros	✓	✓	✓	✓	—
Modificar cost allocation	✓	—	—	—	—
Ver forecasting	✓	✓	✓	✓	—
Configurar playbooks	✓	✓	—	—	—
3.5 Permisos de IA/RAG
Permiso	super_admin	platform_admin	tenant_owner	tenant_admin	tenant_member
Usar asistente IA	✓	✓	✓	✓	Por plan
Ver logs de queries	✓	✓	Solo tenant	—	—
Configurar grounding	✓	✓	—	—	—
Entrenar con contenido	✓	✓	✓	✓	—
Ver estadísticas uso	✓	✓	✓	✓	—
Ajustar parámetros RAG	✓	—	—	—	—
Acceder namespaces	Solo propio	Solo asignados	Solo tenant	Solo tenant	Solo tenant
Exportar embeddings	✓	—	—	—	—
 
4. Restricciones por Plan de Suscripción
El plan de suscripción del tenant añade una capa adicional de control que restringe funcionalidades independientemente del rol del usuario.
4.1 Matriz de Funcionalidades por Plan
Funcionalidad	Starter	Professional	Enterprise
Usuarios por tenant	5	25	Ilimitados
Productos en catálogo	50	500	Ilimitados
Almacenamiento (GB)	1	10	100
Pedidos/mes	100	1,000	Ilimitados
Consultas IA/mes	50	500	5,000
Webhooks salientes	—	✓	✓
API Access	—	✓	✓
Marca blanca	—	—	✓
Multi-tienda	—	—	✓
Soporte prioritario	—	✓	✓
Account Manager	—	—	✓
SLA garantizado	—	99.5%	99.9%
Comisión plataforma	8%	5%	3%
4.2 Implementación del Control por Plan
Servicio PlanLimitsService - Valida límites antes de cada operación restringida:
// jaraba_tenant/src/Service/PlanLimitsService.php
public function canCreateProduct(string $tenant_id): bool {
  $plan = $this->tenantContext->getPlan();
  $current = $this->countProducts($tenant_id);
  $limit = self::PLAN_LIMITS[$plan]['products'];
  return $limit === -1 || $current < $limit;
}
 
5. Integración con Group Module
El Group Module es el pilar del sistema multi-tenant. Cada tenant es un Group que encapsula usuarios, contenido y configuración.
5.1 Tipos de Grupo (Group Types)
Group Type	Machine Name	Uso	Plugins Habilitados
Tenant Comercial	tenant_commercial	Tiendas y comercios que venden productos	group_membership, group_node:product, group_commerce_store
Tenant Formativo	tenant_training	Entidades que ofrecen formación	group_membership, group_node:course, group_node:certification
Tenant Institucional	tenant_institutional	Administraciones y entidades públicas	group_membership, group_node:*, brand_white_label
Cohorte	cohort	Agrupación temporal de usuarios (programa)	group_membership, progress_tracking
Comunidad	community	Espacios de colaboración entre usuarios	group_membership, group_node:discussion, group_media
5.2 Roles de Grupo (Group Roles)
Cada Group Type define sus propios roles con permisos específicos:
Group Role	Permisos sobre Contenido	Permisos sobre Miembros	Permisos Administrativos
group_owner	CRUD completo todo contenido	Invitar, aprobar, expulsar, asignar roles	Configurar grupo, eliminar grupo
group_admin	CRUD completo todo contenido	Invitar, aprobar, asignar roles (excepto owner)	Configurar grupo
group_editor	Crear, editar propio y asignado	Ver miembros	—
group_member	Ver contenido publicado	Ver miembros públicos	—
group_outsider	Ver contenido público	—	Solicitar membresía
5.3 Configuración de Permisos de Grupo
# Exportación de permisos de grupo (config/sync/group.role.tenant_commercial-admin.yml)
id: tenant_commercial-admin
label: 'Administrador de Tienda'
group_type: tenant_commercial
permissions:
  - 'view group'
  - 'edit group'
  - 'administer members'
  - 'create group_node:commerce_product entity'
  - 'update any group_node:commerce_product entity'
  - 'delete any group_node:commerce_product entity'
 
6. Control de Acceso a Contenido
6.1 Niveles de Visibilidad
Nivel	Código	Descripción	Ejemplo de Uso
Público	public	Visible para todos, incluyendo anónimos	Página de inicio, catálogo público
Autenticado	authenticated	Requiere login, cualquier usuario	Dashboard general, perfil
Miembro Tenant	tenant_member	Solo miembros del tenant actual	Contenido exclusivo tienda
Plan Específico	plan:professional	Requiere plan mínimo	Funcionalidades premium
Rol Específico	role:merchant	Requiere rol concreto	Panel de gestión de productos
Privado	private	Solo el autor y administradores	Borradores, datos sensibles
6.2 Implementación con Content Access
El módulo Content Access permite configurar permisos a nivel de tipo de contenido y nodo individual.
// Hook para verificar acceso personalizado
function jaraba_access_node_access(NodeInterface $node, $op, AccountInterface $account) {
  // Verificar contexto de tenant
  $tenant_context = \Drupal::service('jaraba_tenant.context');
  $node_tenant = $node->get('field_tenant')->target_id;
  $user_tenant = $tenant_context->getCurrentTenantId();
  
  // Denegar si el contenido pertenece a otro tenant
  if ($node_tenant && $node_tenant !== $user_tenant) {
    return AccessResult::forbidden('Cross-tenant access denied');
  }
  
  // Verificar nivel de plan si aplica
  $required_plan = $node->get('field_required_plan')->value;
  if ($required_plan && !$tenant_context->hasPlanLevel($required_plan)) {
    return AccessResult::forbidden('Plan upgrade required');
  }
  
  return AccessResult::neutral();
}
 
7. Permisos Específicos por Vertical
7.1 Vertical Empleabilidad
Permiso	job_seeker	employer	career_advisor	entity_admin
Completar Diagnóstico Express	✓	—	✓	✓
Ver rutas formativas	✓	—	✓	✓
Inscribirse en cursos	✓	—	—	—
Crear CV digital	✓	—	—	—
Publicar ofertas empleo	—	✓	—	✓
Ver candidatos	—	✓	✓	✓
Contactar candidatos	—	✓	✓	✓
Gestionar cohortes	—	—	✓	✓
Ver reportes impacto	—	—	✓	✓
Emitir certificaciones	—	—	✓	✓
7.2 Vertical Emprendimiento
Permiso	entrepreneur	mentor	consultant	entity_admin
Completar diagnóstico negocio	✓	—	✓	✓
Acceder itinerario personalizado	✓	—	—	—
Solicitar mentoría	✓	—	—	—
Crear plan de negocio	✓	—	—	—
Ofrecer mentorías	—	✓	✓	—
Cobrar por servicios	—	✓	✓	—
Ver dashboard emprendedores	—	✓	✓	✓
Validar hitos	—	✓	✓	✓
Gestionar programas	—	—	—	✓
Generar informes justificación	—	—	✓	✓
7.3 Vertical Comercio (AgroConecta)
Permiso	merchant	marketplace_admin	logistics_partner	consumer
Crear tienda	✓	—	—	—
Gestionar catálogo	✓	✓	—	—
Procesar pedidos	✓	✓	Ver asignados	—
Configurar envíos	✓	✓	✓	—
Ver analíticas tienda	✓	✓	—	—
Realizar compras	✓	✓	—	✓
Dejar reseñas	—	—	—	✓
Gestionar devoluciones	✓	✓	—	Solicitar
Acceder API	Por plan	✓	✓	—
Sync marketplaces externos	Por plan	✓	—	—
 
8. Seguridad y Auditoría
8.1 Logging de Accesos
Todas las operaciones sensibles se registran en la tabla access_audit_log:
Campo	Tipo	Descripción
id	SERIAL	ID autoincremental
timestamp	DATETIME	Momento exacto UTC
user_id	INT	Usuario que realizó la acción
tenant_id	INT	Tenant donde ocurrió
action	VARCHAR(64)	Tipo de acción: login, access_denied, permission_change, etc.
resource_type	VARCHAR(64)	Tipo de recurso: node, user, order, etc.
resource_id	INT	ID del recurso afectado
ip_address	VARCHAR(45)	IP del cliente (IPv4/IPv6)
user_agent	VARCHAR(255)	Navegador/cliente
details	JSON	Información adicional contextual
8.2 Alertas de Seguridad
El sistema genera alertas automáticas ante comportamientos sospechosos:
•	Múltiples intentos de acceso fallidos (>5 en 15 minutos desde misma IP)
•	Acceso desde ubicación inusual (país diferente al habitual)
•	Escalación de privilegios (cambio de rol a nivel superior)
•	Acceso masivo a datos (exportación de >1000 registros)
•	Intento de acceso cross-tenant (violación de aislamiento)
8.3 Política de Contraseñas
Requisito	Valor	Configurable
Longitud mínima	12 caracteres	Sí
Complejidad	Mayúscula + minúscula + número + símbolo	Sí
Historial	No reutilizar últimas 5	Sí
Expiración	90 días (configurable por tenant)	Sí
Bloqueo temporal	5 intentos fallidos = 30 min bloqueo	Sí
MFA	Obligatorio para roles admin	Sí (por rol)
 
9. Guía de Implementación
9.1 Módulos Drupal Requeridos
Módulo	Versión	Función
drupal/group	^3.0	Multi-tenancy basada en grupos
drupal/group_permissions	^2.0	Permisos granulares por grupo
drupal/content_access	^2.0	Control de acceso a nivel de nodo
drupal/role_delegation	^1.2	Delegación de asignación de roles
drupal/permissions_by_term	^3.0	Permisos basados en taxonomía
drupal/tfa	^2.0	Two-Factor Authentication
drupal/password_policy	^4.0	Políticas de contraseñas
drupal/flood_control	^2.0	Protección contra ataques de fuerza bruta
9.2 Orden de Instalación y Configuración
1.	Instalar módulos base: group, content_access, tfa
2.	Crear Group Types: tenant_commercial, tenant_training, tenant_institutional
3.	Definir Group Roles: owner, admin, editor, member para cada tipo
4.	Configurar Content Access: Permisos por defecto para cada tipo de contenido
5.	Implementar hooks: hook_node_access, hook_entity_access, hook_query_alter
6.	Activar TFA: Obligatorio para roles administrativos
7.	Configurar Password Policy: Según tabla 8.3
8.	Habilitar logging: Tabla access_audit_log + triggers
9.3 Testing de Permisos
Implementar tests automatizados para validar la matriz de permisos:
// tests/src/Functional/RbacMatrixTest.php
public function testTenantIsolation() {
  // Crear usuario en Tenant A
  $userA = $this->createUserInTenant('tenant_a', 'tenant_member');
  // Crear contenido en Tenant B
  $nodeB = $this->createNodeInTenant('tenant_b', 'article');
  // Verificar que userA NO puede acceder a nodeB
  $this->drupalLogin($userA);
  $this->drupalGet('node/' . $nodeB->id());
  $this->assertSession()->statusCodeEquals(403);
}
 
Apéndice A: Checklist de Configuración
•	[ ] Group Module instalado y configurado
•	[ ] Group Types creados (commercial, training, institutional)
•	[ ] Group Roles definidos para cada tipo
•	[ ] Content Access configurado por tipo de contenido
•	[ ] Hooks de acceso implementados en jaraba_access
•	[ ] TFA habilitado para roles admin
•	[ ] Password Policy configurada
•	[ ] Tabla access_audit_log creada
•	[ ] Alertas de seguridad configuradas
•	[ ] Tests de RBAC ejecutados y pasando
•	[ ] Documentación de roles entregada a stakeholders
•	[ ] Training completado para administradores
— Fin del Documento —
Jaraba Impact Platform © 2026
