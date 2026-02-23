# Implementacion Vertical Retention Playbooks (Doc 179)

**Fecha:** 2026-02-20
**Sesion:** Implementacion completa del motor de retencion verticalizado en jaraba_customer_success
**Reglas nuevas:** ENTITY-APPEND-001, CONFIG-SEED-001
**Aprendizaje #104**

---

## Contexto

El modulo `jaraba_customer_success` (Doc 113) ya implementaba health scores genericos, churn predictions y playbooks, pero trataba a todos los tenants igual independientemente de su vertical. Un agricultor de AgroConecta tiene ciclos estacionales de cosecha; un comerciante de ComercioConecta tiene picos en rebajas/Navidad; un emprendedor en fase de ideacion tiene 40% de churn natural. Se necesitaba verticalizar el motor de retencion con calendarios estacionales, senales de churn por vertical y predicciones ajustadas estacionalmente.

---

## Lecciones Aprendidas

### 1. Las entidades append-only NO deben tener form handlers de edicion

**Situacion:** `SeasonalChurnPrediction` es un registro inmutable — una vez creada una prediccion, no debe editarse ni eliminarse.

**Patron correcto:**
- En la anotacion `@ContentEntityType`, solo definir `form = {"default" = ...}` (para admin UI de visualizacion), nunca `edit` ni `delete`.
- El `AccessControlHandler` DEBE denegar `update` y `delete` para todos los usuarios.
- No incluir `EntityChangedTrait` — no hay campo `changed` porque la entidad nunca cambia.
- La creacion se hace programaticamente desde el servicio (`SeasonalChurnService`), no desde formularios de usuario.

**Regla: ENTITY-APPEND-001** — Las entidades de registro inmutable NO DEBEN tener form handlers de edicion/eliminacion.

### 2. El config seeding via update hooks requiere codificacion JSON explicita

**Situacion:** Los 5 perfiles verticales se definen como YAMLs en `config/install/` con arrays PHP nativos (seasonality_calendar, churn_risk_signals, etc.). Pero la entidad almacena estos campos como `string_long` con JSON serializado.

**Patron correcto:**
- Los YAMLs almacenan arrays PHP nativos (legibles, editables manualmente):
  ```yaml
  seasonality_calendar:
    - month: 1
      risk_level: high
      label: Post-cosecha
      adjustment: 0.15
  ```
- El `update_hook` lee el YAML con `Yaml::decode(file_get_contents($path))`, codifica cada campo complejo con `json_encode($data[$field])`, y crea la entidad via `Entity::create($values)->save()`.
- Los getters de la entidad retornan `json_decode($this->get('field')->value, TRUE) ?? []`.
- Siempre verificar existencia previa con `$storage->loadByProperties()` para idempotencia.

**Regla: CONFIG-SEED-001** — Los YAMLs de config/install almacenan arrays nativos; el update_hook codifica a JSON antes de crear la entidad.

### 3. Los campos JSON en string_long necesitan getters con decode/fallback

**Situacion:** `VerticalRetentionProfile` tiene 10 campos JSON (seasonality_calendar, churn_risk_signals, health_score_weights, critical_features, reengagement_triggers, upsell_signals, seasonal_offers, expected_usage_pattern, playbook_overrides). Todos son `string_long` en la BD.

**Patron correcto:**
```php
public function getSeasonalityCalendar(): array {
  $value = $this->get('seasonality_calendar')->value;
  return $value ? json_decode($value, TRUE) : [];
}
```
- Siempre `json_decode($value, TRUE)` para obtener arrays asociativos.
- Fallback a `[]` si el campo esta vacio o nulo.
- El formulario de edicion valida que el JSON sea valido y cumple las reglas de negocio (ej. seasonality_calendar debe tener exactamente 12 entradas, health_score_weights deben sumar 100).

### 4. El VerticalRetentionService usa State API con TTL para cache de evaluaciones

**Situacion:** Las evaluaciones de retencion por tenant son costosas (consultan health score, senales, perfil vertical, estacionalidad). No se deben recalcular en cada request.

**Patron correcto:**
- Almacenar el resultado en State API con clave namespaced: `jaraba_cs.retention.{tenant_id}`.
- Incluir `_timestamp` en el valor almacenado.
- Al leer, verificar TTL de 24 horas: si `REQUEST_TIME - timestamp > 86400`, recalcular.
- El QueueWorker cron refresca todas las evaluaciones diariamente.

### 5. La orquestacion paralela con agentes maximiza el throughput

**Situacion:** La implementacion requeria 25 archivos nuevos y 11 modificaciones. Hacerlo secuencialmente seria muy lento.

**Patron correcto:**
- Dividir el trabajo en bloques independientes (entidades, servicios, controladores, configs, templates, YAML modifications).
- Lanzar 4-5 agentes en paralelo, cada uno con un bloque completo de archivos.
- Verificar al final con glob + line counts + grep de elementos clave.
- Los agentes NO deben escribir en el mismo archivo — cada archivo tiene un unico propietario.

---

## Archivos Creados (25)

| Archivo | Lineas | Descripcion |
|---------|--------|-------------|
| `Entity/VerticalRetentionProfile.php` | 376 | Entidad con 16 campos, EntityChangedTrait, JSON getters |
| `Entity/VerticalRetentionProfileInterface.php` | 111 | Interface con 15 metodos, constantes verticales |
| `Entity/SeasonalChurnPrediction.php` | 222 | Entidad append-only, 11 campos |
| `Entity/SeasonalChurnPredictionInterface.php` | 62 | Interface con urgencia NONE/LOW/MEDIUM/HIGH/CRITICAL |
| `Access/VerticalRetentionProfileAccessControlHandler.php` | 35 | view/update/delete por permisos |
| `Access/SeasonalChurnPredictionAccessControlHandler.php` | 37 | Append-only: deniega update/delete |
| `VerticalRetentionProfileListBuilder.php` | 56 | Tabla admin con vertical, label, inactividad, estado |
| `SeasonalChurnPredictionListBuilder.php` | 73 | Tabla con tenant, mes, probabilidades, urgencia |
| `Form/VerticalRetentionProfileForm.php` | 137 | Formulario con validacion JSON |
| `Service/VerticalRetentionService.php` | 458 | Motor: health score, senales, estacionalidad, riesgo |
| `Service/SeasonalChurnService.php` | 277 | Predicciones mensuales ajustadas, batch runner |
| `Controller/RetentionApiController.php` | 397 | 7 endpoints API REST con CSRF |
| `Controller/RetentionDashboardController.php` | 173 | Dashboard FOC con heatmap |
| `Plugin/QueueWorker/VerticalRetentionCronWorker.php` | 63 | Cron worker 120s |
| 5 x `config/install/retention_profile.*.yml` | 60 c/u | Perfiles AgroConecta, ComercioConecta, ServiciosConecta, Empleabilidad, Emprendimiento |
| `templates/jaraba-cs-retention-dashboard.html.twig` | 76 | Dashboard principal |
| `templates/partials/_retention-calendar-heatmap.html.twig` | 62 | Heatmap 12x5 con ARIA |
| `templates/partials/_retention-risk-card.html.twig` | 53 | Card de riesgo por vertical |
| `templates/partials/_playbook-timeline.html.twig` | 48 | Timeline de ejecuciones |
| `js/retention-dashboard.js` | 88 | Behaviors: counters, highlight, tooltips |
| `scss/_retention-playbooks.scss` | 515 | BEM completo: dashboard, heatmap, risk-card, timeline |

## Archivos Modificados (11)

| Archivo | Cambio |
|---------|--------|
| `routing.yml` | +8 rutas (1 FOC + 7 API) |
| `services.yml` | +2 servicios con DI |
| `permissions.yml` | +2 permisos |
| `links.menu.yml` | +1 enlace admin |
| `links.task.yml` | +2 tabs |
| `links.action.yml` | +1 boton accion |
| `libraries.yml` | +1 library retention-dashboard |
| `.module` | +theme entry, cron, 2 hooks |
| `.install` | +update_10001 (instala entidades + seed 5 perfiles) |
| `schema.yml` | +137 lineas schema retention_profile |
| `main.scss` | +@use retention-playbooks |
