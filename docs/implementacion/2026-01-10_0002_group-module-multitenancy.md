# Implementación de Multi-Tenancy con Group Module

**Fecha:** 2026-01-10  
**Autor:** Ecosistema Jaraba  
**Versión:** 1.0.0

---

## Tabla de Contenidos

1. [Objetivo](#1-objetivo)
2. [Módulos Instalados](#2-módulos-instalados)
3. [Configuración de Group Module](#3-configuración-de-group-module)
4. [Integración con Entidad Tenant](#4-integración-con-entidad-tenant)
5. [Automatización en Onboarding](#5-automatización-en-onboarding)
6. [Próximos Pasos](#6-próximos-pasos)
7. [Registro de Cambios](#7-registro-de-cambios)

---

## 1. Objetivo

Implementar aislamiento de contenido real por Tenant utilizando **Group Module** para gestionar membresías y permisos, y **Domain Access** para dominios personalizados.

---

## 2. Módulos Instalados

| Módulo | Versión | Propósito |
|--------|---------|-----------|
| `group` | 3.3.5 | Base para crear grupos y gestionar membresías |
| `gnode` | 3.3.5 | Permite añadir contenido (nodos) a grupos |
| `domain` | 2.0.0-rc1 | Gestión de dominios múltiples |
| `domain_access` | 2.0.0-rc1 | Asignación de contenido a dominios |

**Comando de instalación:**
```bash
lando drush en group gnode domain domain_access -y
```

---

## 3. Configuración de Group Module

### Tipo de Grupo: `tenant`

| Campo | Valor |
|-------|-------|
| Label | Tenant |
| Machine name | `tenant` |
| Descripción | Grupo que representa un inquilino/organización de la plataforma SaaS |

**URL de administración:** `/admin/group/types/manage/tenant`

### Roles Sincronizados

El módulo Group sincroniza automáticamente los roles de administrador globales con roles de grupo equivalentes.

---

## 4. Integración con Entidad Tenant

### Campo Añadido: `group_id`

```php
$fields['group_id'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Grupo de Aislamiento'))
    ->setDescription(t('Grupo asociado para aislamiento de contenido.'))
    ->setSetting('target_type', 'group')
    ->setSetting('handler_settings', [
        'target_bundles' => ['tenant' => 'tenant'],
    ]);
```

### Métodos en TenantInterface

```php
public function getGroup(): ?\Drupal\group\Entity\GroupInterface;
```

### Hook de Actualización

```php
// ecosistema_jaraba_core.install
function ecosistema_jaraba_core_update_9003() {
    // Instala campo group_id en entidad Tenant
}
```

---

## 5. Automatización en Onboarding

### TenantOnboardingService::createTenantGroup()

Al crear un nuevo Tenant mediante el flujo de onboarding, el sistema:

1. Verifica que el módulo Group está habilitado
2. Verifica que existe el tipo de grupo `tenant`
3. Crea un Group con el nombre del Tenant
4. Añade al usuario administrador como miembro
5. Vincula el Group al Tenant mediante `group_id`

```php
protected function createTenantGroup(TenantInterface $tenant, AccountInterface $admin): void
{
    $groupStorage = $this->entityTypeManager->getStorage('group');
    $group = $groupStorage->create([
        'type' => 'tenant',
        'label' => $tenant->getName(),
    ]);
    $group->save();
    $group->addMember($admin);
    $tenant->set('group_id', $group->id());
    $tenant->save();
}
```

---

## 6. Próximos Pasos

- [x] Configurar Domain Access con dominio principal
- [x] Habilitar plugins de contenido en gnode
- [ ] Crear roles específicos de grupo (tenant-admin, tenant-member)
- [ ] Implementar filtrado de contenido por grupo/tenant
- [ ] Documentar flujo de creación de usuarios dentro de un Tenant

---

## 7. Plugins gnode Configurados

| Plugin | Tipo | Estado |
|--------|------|--------|
| Group membership | Enforced | Instalado |
| Group node (Artículo) | Manual | Instalado |
| Group node (Página básica) | Manual | Instalado |

**URL de configuración:** `/admin/group/types/manage/tenant/content`

---

## 8. Subdominios Lando

El archivo `.lando.yml` fue actualizado para incluir subdominios de tenants:

```yaml
proxy:
  appserver:
    - jaraba-saas.lndo.site
    - aceitesdelsur.jaraba-saas.lndo.site
```

Tras ejecutar `lando rebuild -y`, ambos subdominios responden correctamente [200 OK].

---

## 9. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-10 | 1.0.0 | Implementación inicial de Group Module |
