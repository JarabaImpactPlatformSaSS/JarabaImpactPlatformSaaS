# Implementación: Automatización de Domain en Multi-Tenancy

**Fecha de creación:** 2026-01-10 12:30  
**Última actualización:** 2026-01-10 12:30  
**Autor:** IA Asistente  
**Versión:** 1.0.0

---

## 📑 Tabla de Contenidos (TOC)

1. [Objetivo](#1-objetivo)
2. [Contexto](#2-contexto)
3. [Implementación](#3-implementación)
4. [Verificación](#4-verificación)
5. [Migración de Datos Existentes](#5-migración-de-datos-existentes)
6. [Consideraciones para Desarrollo Local](#6-consideraciones-para-desarrollo-local)
7. [Registro de Cambios](#7-registro-de-cambios)

---

## 1. Objetivo

Automatizar la creación de dominios personalizados (Domain Access) durante el flujo de onboarding de nuevos Tenants, completando la integración multi-tenant del módulo `ecosistema_jaraba_core`.

---

## 2. Contexto

### Estado Previo

| Componente | Estado Pre-Implementación |
|------------|---------------------------|
| Group Module | ✅ Integrado automáticamente |
| Domain Access | ❌ Configuración manual requerida |
| Tenant.domain (string) | ✅ Campo legacy para hostname |
| Tenant.domain_id | ❌ No existía |

### Problema

Los nuevos tenants requerían intervención manual del administrador para:
1. Crear un nuevo Domain en `/admin/config/domain`
2. Vincular manualmente el tenant con el domain
3. Configurar el proxy de Lando (desarrollo local)

### Solución

Automatizar el paso 1 y 2 en `TenantOnboardingService.createTenantDomain()`.

---

## 3. Implementación

### 3.1 Modificación de Entidad Tenant

#### Archivo: `src/Entity/TenantInterface.php`

Añadidos dos métodos para acceso tipado al Domain:

```php
/**
 * Obtiene la entidad Domain asociada a este Tenant.
 */
public function getDomainEntity(): ?\Drupal\domain\Entity\Domain;

/**
 * Establece la entidad Domain asociada a este Tenant.
 */
public function setDomainEntity(\Drupal\domain\Entity\Domain $domain): self;
```

#### Archivo: `src/Entity/Tenant.php`

```php
// Nuevo import
use Drupal\domain\Entity\Domain;

// Implementación de métodos
public function getDomainEntity(): ?Domain
{
    $domain = $this->get('domain_id')->entity;
    return $domain instanceof Domain ? $domain : NULL;
}

public function setDomainEntity(Domain $domain): TenantInterface
{
    $this->set('domain_id', $domain->id());
    return $this;
}

// Nuevo campo en baseFieldDefinitions()
$fields['domain_id'] = BaseFieldDefinition::create('entity_reference')
    ->setLabel(t('Dominio Asignado'))
    ->setDescription(t('Dominio de Domain Access asociado a este tenant.'))
    ->setSetting('target_type', 'domain')
    ->setSetting('handler', 'default')
    ->setDisplayConfigurable('form', TRUE)
    ->setDisplayConfigurable('view', TRUE);
```

### 3.2 Schema Update

#### Archivo: `ecosistema_jaraba_core.install`

```php
/**
 * Añade el campo domain_id a la entidad Tenant para integración con Domain Access.
 */
function ecosistema_jaraba_core_update_9004() {
  $entity_definition_manager = \Drupal::entityDefinitionUpdateManager();
  
  $field_storage_definition = $entity_definition_manager
    ->getFieldStorageDefinition('domain_id', 'tenant');

  if (!$field_storage_definition) {
    $field_storage_definition = \Drupal\Core\Field\BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Dominio Asignado'))
      ->setDescription(t('Dominio de Domain Access asociado.'))
      ->setSetting('target_type', 'domain')
      ->setSetting('handler', 'default');

    $entity_definition_manager->installFieldStorageDefinition(
      'domain_id',
      'tenant',
      'ecosistema_jaraba_core',
      $field_storage_definition
    );

    return t('Campo domain_id añadido a Tenant.');
  }
  return t('Campo domain_id ya existe.');
}
```

### 3.3 Automatización en TenantOnboardingService

#### Archivo: `src/Service/TenantOnboardingService.php`

##### Método: `createTenantDomain()`

```php
protected function createTenantDomain(TenantInterface $tenant): void
{
    // 1. Verificar módulo Domain disponible
    if (!$this->entityTypeManager->hasDefinition('domain')) {
        return;
    }

    // 2. Generar slug DNS-compatible
    $slug = $this->generateDomainSlug($tenant->getName());
    $baseDomain = 'jaraba-saas.lndo.site'; // Configurable por entorno
    $hostname = $slug . '.' . $baseDomain;
    $machineName = str_replace(['.', '-'], '_', $hostname);

    // 3. Verificar/reutilizar existente
    $domainStorage = $this->entityTypeManager->getStorage('domain');
    $existing = $domainStorage->load($machineName);
    if ($existing) {
        $tenant->set('domain_id', $existing->id());
        $tenant->save();
        return;
    }

    // 4. Crear nuevo Domain
    $domain = $domainStorage->create([
        'id' => $machineName,
        'name' => $tenant->getName(),
        'hostname' => $hostname,
        'scheme' => 'https',
        'status' => 1,
        'is_default' => FALSE,
    ]);
    $domain->save();

    // 5. Vincular al Tenant
    $tenant->set('domain_id', $domain->id());
    $tenant->save();
}
```

##### Método: `generateDomainSlug()`

```php
protected function generateDomainSlug(string $name): string
{
    // Transliteración de caracteres especiales (á → a, ñ → n)
    if (class_exists('\Transliterator')) {
        $transliterator = \Transliterator::create('Any-Latin; Latin-ASCII; Lower()');
        $slug = $transliterator->transliterate($name);
    } else {
        $slug = strtolower($name);
        // Fallback manual para español
    }

    // Limpiar caracteres no DNS-válidos
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');

    // Limitar a 60 caracteres
    if (strlen($slug) > 60) {
        $slug = substr($slug, 0, 60);
    }

    return $slug ?: 'tenant-' . time();
}
```

##### Actualización del flujo `processRegistration()`

```php
// 5. Crear grupo asociado
$this->createTenantGroup($tenant, $user);

// 6. Crear dominio en Domain Access ← NUEVO
$this->createTenantDomain($tenant);

// 7. Iniciar periodo de prueba
$this->tenantManager->startTrial($tenant);
```

---

## 4. Verificación

### Ejecución de Updates

```bash
lando drush updb -y
# [notice] Campo domain_id añadido a Tenant para integración con Domain Access.
# [success] Finished performing updates.
```

### Verificación de Campo

```
[✓] domain_id field exists on Tenant entity
- Type: entity_reference
- Target: domain
```

### Test de Métodos

```
[✓] getDomainEntity() method exists
[✓] setDomainEntity() method exists
```

### Test de Generación de Slugs

| Nombre Original | Slug Generado |
|-----------------|---------------|
| Cooperativa Aceites del Sur | cooperativa-aceites-del-sur |
| Viñas La Mancha S.L. | vinas-la-mancha-s-l |
| Área 51 - Almería | area-51-almeria |

---

## 5. Migración de Datos Existentes

Los tenants creados antes de esta implementación no tienen `domain_id` asignado.

### Opción A: Migración Manual (UI)

1. Ir a `/admin/structure/tenant/{id}/edit`
2. Seleccionar el dominio en el campo "Dominio Asignado"
3. Guardar

### Opción B: Script de Migración

```php
// Para cada tenant sin domain_id:
$tenant = $storage->load($id);
$domain = $domainStorage->load('machine_name_del_domain');
$tenant->set('domain_id', $domain->id());
$tenant->save();
```

### Resultado de Migración

El tenant "Cooperativa Aceites del Sur" fue vinculado exitosamente:

```
[✓] Tenant now has domain: aceitesdelsur.jaraba-saas.lndo.site
```

---

## 6. Consideraciones para Desarrollo Local

### Problema

Lando no puede resolver subdominios dinámicamente sin configuración previa.

### Soluciones

#### Opción 1: Configuración explícita en `.lando.yml`

```yaml
proxy:
  appserver:
    - jaraba-saas.lndo.site
    - aceitesdelsur.jaraba-saas.lndo.site
    - nuevo-tenant.jaraba-saas.lndo.site
```

Después: `lando rebuild`

#### Opción 2: Wildcard DNS + Lando (Avanzado)

Configurar `*.jaraba-saas.lndo.site` en `/etc/hosts` o usar dnsmasq.

### Producción

En producción, usar un wildcard certificate SSL y DNS con `*.jaraba.io`.

---

## 7. Registro de Cambios

| Fecha | Versión | Descripción |
|-------|---------|-------------|
| 2026-01-10 | 1.0.0 | Implementación inicial de automatización de Domain |
