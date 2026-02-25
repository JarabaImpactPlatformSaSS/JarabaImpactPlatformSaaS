# Aprendizaje #125: Migracion Global a PremiumEntityFormBase — 237 Formularios, 50 Modulos

**Fecha:** 2026-02-25
**Categoria:** Arquitectura de Formularios / UX
**Impacto:** Alto — Estandariza la experiencia de administracion de todas las entidades del SaaS

---

## 1. Contexto

Antes de esta migracion, los formularios de entidad ContentEntity usaban `ContentEntityForm` directamente, con fieldsets, details groups y layouts inconsistentes. Tras crear `PremiumEntityFormBase` para el vertical CRM (5 forms), se decidio estandarizar TODOS los formularios de la plataforma.

**Problema raiz:** Cada modulo tenia su propio estilo de formulario. Algunos usaban fieldsets, otros details groups, otros campos planos. No habia navegacion dentro de formularios largos. La experiencia de administracion era fragmentada.

## 2. Alcance

- **237 formularios** migrados
- **50 modulos** afectados
- **8 fases** de migracion (agrupadas por complejidad y vertical)
- **0 `ContentEntityForm`** restantes en modulos custom (excepto `SettingsForm` que no aplica)

## 3. Patrones de Migracion

### Patron A: Simple (Mayoria)
Formularios sin inyeccion de dependencias ni campos computados.

```php
class MiEntityForm extends PremiumEntityFormBase {
  protected function getSectionDefinitions(): array {
    return [
      'general' => [
        'label' => 'General',
        'icon' => 'ui/settings',
        'fields' => ['name', 'description', 'status'],
      ],
    ];
  }

  protected function getFormIcon(): string {
    return 'ui/file-text';
  }
}
```

### Patron B: Computed Fields
Campos auto-calculados que el admin puede ver pero no editar.

```php
// En buildForm() o form_alter:
$form['engagement_score']['#disabled'] = TRUE;
$form['bant_score']['#disabled'] = TRUE;
```

**Pitfall:** Usar `#access = FALSE` oculta completamente el campo. El admin pierde visibilidad. Siempre usar `#disabled = TRUE`.

### Patron C: DI (Dependency Injection)
Formularios que inyectan servicios adicionales.

```php
public static function create(ContainerInterface $container): static {
  $instance = parent::create($container);
  $instance->miServicio = $container->get('mi_modulo.mi_servicio');
  return $instance;
}
```

**Pitfall:** Llamar a `new static()` en lugar de `parent::create()` pierde las dependencias de PremiumEntityFormBase (entity_type.manager, etc.).

### Patron D: Custom Logic
Formularios con logica especial en `buildForm()` o `save()`.

```php
public function buildForm(array $form, FormStateInterface $form_state): array {
  $form = parent::buildForm($form, $form_state);
  // Logica custom DESPUES de parent para mantener secciones
  $form['mi_campo_custom'] = [...];
  return $form;
}
```

## 4. Decisiones Clave

### Iconos de Seccion
Cada seccion usa un icono del sistema `jaraba_icon()` con categorias semanticas:
- `ui/settings` → Configuracion general
- `ui/globe` → Ubicacion, internacionalizacion
- `users/user` → Datos personales
- `actions/sparkles` → IA, scoring
- `fiscal/invoice` → Facturacion, financiero
- `ui/lock` → Seguridad, privacidad

### Fieldsets PROHIBIDOS
Los `#type => 'fieldset'` y `#type => 'details'` rompen la navegacion por pills de PremiumEntityFormBase. Toda agrupacion visual se hace via secciones.

### Campos Ocultos (HIDDEN_FIELDS)
Los campos internos (`uid`, `created`, `changed`, `status`, `user_id`, `tenant_id`) se definen en la constante `HIDDEN_FIELDS` de cada form. Se renderizan como hidden inputs pero no aparecen en ninguna seccion.

### Redirect Post-Save
`parent::save()` redirige automaticamente a la ruta `collection` de la entidad. Formularios con redirect custom (como user edit → canonical) deben implementarlo en el submit handler, no en el form class.

## 5. Enfoque de Verificacion

### Despues de cada fase:
1. `grep -rl "extends ContentEntityForm" web/modules/custom/*/src/Form/ | grep -v PremiumEntityFormBase | grep -v SettingsForm` → debe disminuir progresivamente
2. `php vendor/bin/phpunit --testsuite Unit` → 0 failures
3. Verificacion visual selectiva de formularios migrados

### Verificacion final:
- El grep anterior retorna 0 resultados
- PHPUnit: 2899 tests passing, 0 failures
- Todos los formularios usan glass-card UI con navigation pills

## 6. Metricas

| Metrica | Valor |
|---------|-------|
| Formularios migrados | 237 |
| Modulos afectados | 50 |
| Fases de migracion | 8 |
| ContentEntityForm restantes | 0 |
| Tests passing | 2899 |
| Patrones identificados | 4 (A, B, C, D) |

## 7. Regla Establecida

**PREMIUM-FORMS-PATTERN-001 (P1):** Todo formulario de entidad ContentEntity DEBE extender `PremiumEntityFormBase`. Implementar `getSectionDefinitions()` y `getFormIcon()`. Fieldsets/details PROHIBIDOS. Computed fields con `#disabled = TRUE`. DI via `parent::create()`.

## 8. Fix Asociado: USR-004

Durante la migracion se detecto que `/user/{id}/edit` recargaba la misma pagina tras save. Causa: Drupal core `ProfileForm::save()` no establece redirect. Fix: nuevo submit handler `_ecosistema_jaraba_core_user_profile_redirect` que redirige a `entity.user.canonical`. El handler de password reset se ejecuta despues y tiene prioridad, redirigiendo a `/empleabilidad`.

---

**Cross-refs:** Directrices v73.0.0 (PREMIUM-FORMS-PATTERN-001), Arquitectura v74.0.0 (ASCII box Premium Entity Forms), Flujo v28.0.0 (Regla #42), Indice v97.0.0 (HAL-05).
