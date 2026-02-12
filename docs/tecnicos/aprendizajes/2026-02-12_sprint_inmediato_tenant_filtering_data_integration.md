# Aprendizajes: Sprint Inmediato — Tenant Filtering + Data Integration

**Fecha:** 2026-02-12
**Contexto:** Resolucion de 48 TODOs del catalogo v1.2.0 en 8 fases, cubriendo aislamiento multi-tenant y reemplazo de datos mock/stub por consultas a entidades reales en 27 modulos.
**Alcance:** 49 ficheros (44 modificados + 4 nuevos + 1 doc), +3337/-183 lineas, 27 modulos

---

## 1. Patron Tenant Filtering via Group Membership (group_relationship)

**Situacion:** Multiples servicios y controladores necesitaban filtrar datos por tenant, pero no existia un patron consistente. Algunos usaban tenant_id hardcoded, otros no filtraban en absoluto.

**Aprendizaje:** El patron canonico para filtrar por tenant en queries SQL/DB es:

```php
// Para entity queries (EntityStorageInterface)
$query->condition('tenant_id', $tenantId);

// Para database queries directas (joins con Group)
$query->join('group_relationship_field_data', 'gr', 'b.user_id = gr.entity_id');
$query->condition('gr.gid', $group->id());
$query->condition('gr.plugin_id', 'group_membership');
```

La clave es que `group_relationship_field_data` es la tabla canonica para membership, y el `plugin_id = 'group_membership'` filtra solo relaciones de membresia (no contenido del grupo).

**Regla TENANT-001:** Todo entity query o database query que devuelva datos de usuario/contenido DEBE incluir filtro por tenant. Para entidades con campo `tenant_id`, usar `->condition('tenant_id', $tenantId)`. Para queries DB directas, hacer JOIN a `group_relationship_field_data` filtrando por `gid` y `plugin_id = 'group_membership'`.

---

## 2. TenantContextService como Punto de Inyeccion Unico

**Situacion:** Los controladores necesitaban acceso al tenant actual, pero cada uno resolvia el tenant de forma diferente.

**Aprendizaje:** `TenantContextService` es el servicio canonico para obtener el tenant actual. Proporciona:
- `getCurrentTenant(): ?TenantInterface` — el objeto completo
- `getCurrentTenantId(): ?int` — solo el ID (conveniente para conditions)
- `hasAccessToTenant(TenantInterface $tenant): bool` — verificacion

El servicio cachea el resultado por request (`$cachedTenant`), evitando N+1 queries. La resolucion es por `admin_user_id` del tenant (futuro: Group membership).

**Regla TENANT-002:** Todo controlador o servicio que necesite contexto de tenant DEBE inyectar `ecosistema_jaraba_core.tenant_context` (TenantContextService). NUNCA resolver el tenant manualmente via queries ad-hoc. Usar `getCurrentTenantId()` para conditions simples, `getCurrentTenant()` cuando se necesite el plan u otros campos.

---

## 3. Entidades CandidateLanguage y EmployerProfile — Patron de Referencia

**Situacion:** Faltaban dos entidades clave: idiomas del candidato (para CV builder y matching) y perfil de empleador (para marketplace laboral).

**Aprendizaje:** El patron para crear Content Entities nuevas en este proyecto es:

1. **Fichero Entity + Interface** en `src/Entity/` del modulo
2. **Campos baseFieldDefinitions()** con tipos consistentes:
   - `user_id` → entity_reference a 'user' con EntityOwnerTrait
   - `tenant_id` → entity_reference a 'tenant' (cuando aplica)
   - Strings con max_length explicito
   - Booleans como `BaseFieldDefinition::create('boolean')->setDefaultValue(FALSE)`
   - Timestamps via `EntityChangedTrait` + `created` field
3. **Constantes para valores permitidos** (ej. CEFR levels, company sizes)
4. **Getters en la interfaz** para los campos principales

**CandidateLanguage** sigue CandidateSkill (mismo modulo), con campos CEFR granulares (reading, writing, speaking, listening) mas certificaciones.

**EmployerProfile** sigue CandidateProfile como espejo para empleadores, con `tenant_id` adicional para vinculacion multi-tenant.

---

## 4. Entity References: target_type Correcto desde el Inicio

**Situacion:** TrainingProduct y CertificationProgram referenciaban `'node'` en lugar de `'lms_course'` para sus campos de cursos.

**Aprendizaje:** Cuando existe una Content Entity dedicada (como `lms_course`), los entity_reference fields DEBEN apuntar al tipo correcto, no a `'node'` como fallback generico. El target_type incorrecto:
- Rompe la integridad referencial
- Impide que los form widgets muestren el selector correcto
- Genera errores en Views cuando se usan relaciones

**Regla ENTITY-REF-001:** Los campos entity_reference DEBEN usar el target_type mas especifico disponible. Si la entidad custom existe e esta instalada, NUNCA usar 'node' como fallback.

---

## 5. Data Integration: De Mock a Entity Queries

**Situacion:** ~35 metodos en servicios y controladores devolvian datos mock (arrays hardcoded, valores por defecto) en lugar de consultar entidades reales.

**Aprendizaje:** El patron consistente para reemplazar datos mock es:

```php
// 1. Intentar cargar entidad(es)
try {
    $entities = $this->entityTypeManager
        ->getStorage('entity_type')
        ->loadByProperties(['user_id' => $userId]);

    if (!empty($entities)) {
        $entity = reset($entities);
        return $entity->get('field_name')->value;
    }
} catch (\Exception $e) {
    // 2. Fallback al valor por defecto anterior (nunca crash)
}
return $defaultValue;
```

Principios clave:
- **Try/catch obligatorio**: las entidades pueden no estar instaladas
- **Fallback graceful**: mantener el valor por defecto anterior como safety net
- **loadByProperties** para busquedas por campo unico, **getQuery** para busquedas complejas
- **accessCheck(FALSE)** en queries de servicio (el acceso se verifica en la capa de controlador)

---

## 6. Metricas de Almacenamiento via file_managed + Group Membership

**Situacion:** El calculo de almacenamiento usado por tenant estaba en `$storageUsed = 0` (stub).

**Aprendizaje:** Para calcular almacenamiento real:
1. Obtener UIDs de miembros del grupo via `group_relationship`
2. Cargar las relaciones para extraer `entity_id` (el UID del miembro)
3. Usar `getAggregateQuery()` con `aggregate('filesize', 'SUM')` sobre `file` storage filtrado por UIDs

El `getAggregateQuery()` de Drupal es la forma correcta de hacer SUM/COUNT/AVG sobre entity storage sin cargar todas las entidades.

---

## 7. Duplicacion jaraba_billing / ecosistema_jaraba_core

**Situacion:** ExpansionRevenueService e ImpactCreditService existen en ambos modulos. El de ecosistema_jaraba_core estaba actualizado; el de jaraba_billing tenia un TODO pendiente.

**Aprendizaje:** Cuando hay servicios duplicados entre modulos (por la extraccion TD-003 God Object), los cambios deben aplicarse a AMBAS copias. El ServiceProvider de ecosistema_jaraba_core registra aliases condicionalmente, pero el codigo fuente de ambos debe mantenerse sincronizado.

**Regla BILLING-001:** Cambios en servicios que existen tanto en `jaraba_billing` como en `ecosistema_jaraba_core` (ImpactCreditService, ExpansionRevenueService) DEBEN aplicarse en ambas copias simultaneamente. En el futuro, consolidar a una unica fuente de verdad.

---

## 8. Resumen de Reglas Nuevas

| Regla | Descripcion |
|-------|-------------|
| TENANT-001 | Filtro tenant obligatorio en queries de datos |
| TENANT-002 | TenantContextService como punto unico de inyeccion |
| ENTITY-REF-001 | target_type mas especifico disponible |
| BILLING-001 | Cambios sincronizados jaraba_billing / ecosistema_jaraba_core |
