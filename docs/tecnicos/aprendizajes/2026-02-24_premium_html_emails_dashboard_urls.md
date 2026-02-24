# Aprendizaje #115 — Emails Premium HTML + URLs Dashboard Corregidas

**Fecha:** 2026-02-24
**Commit:** `592a353f`
**Modulo:** `jaraba_andalucia_ei`
**Ficheros modificados:** 3

---

## Contexto

Los 3 correos del modulo Andalucia +ei (`confirmacion_solicitud`, `nueva_solicitud`, `eligibility_notification`) usaban cuerpo en texto plano via `$message['body'][] = t("texto\n...")`. Aunque el theme `ecosistema_jaraba_theme` proporciona un wrapper branded (`email-wrap.html.twig`) con header gradiente, logo y footer, el contenido interior se renderizaba como texto sin formato, sin jerarquia visual ni CTAs.

Ademas, las tarjetas de acceso rapido en el estado "solicitud en revision" del dashboard enlazaban a landing pages publicas (`/empleo`, `/talento`, `/emprender`) en lugar de a las paginas funcionales de la app para usuarios autenticados.

---

## Problema 1: Emails en texto plano dentro de wrapper premium (P1-EMAIL)

### Antes

```php
// hook_mail() — texto plano, sin HTML.
$message['body'][] = t(
    "Hola @nombre,\n\n" .
    "Hemos recibido correctamente tu solicitud...\n" .
    "RESUMEN DE TU SOLICITUD:\n" .
    "- Programa: Andalucía +ei...",
    ['@nombre' => $params['nombre']]
);
```

El resultado: el branded wrapper pone header/footer premium, pero el body es un bloque monotonico de texto sin enfasis, sin tarjetas, sin botones.

### Despues

```php
// hook_mail() — HTML premium con inline CSS + table layout.
$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$message['body'][] = \Drupal\Core\Render\Markup::create(
    '<p style="margin:0 0 16px;font-size:16px;...">' .
        t('Hola <strong>@nombre</strong>,', ['@nombre' => $esc($nombre)]) .
    '</p>' .
    // Summary card con borde verde...
    '<table ...><tr><td style="background-color:#F0FDF4;border-left:4px solid #10B981;...">' .
        // ...
    '</td></tr></table>' .
    // CTA button...
    '<table ...><tr><td style="border-radius:6px;background-color:#FF8C42;">' .
        '<a href="..." style="...color:#ffffff;...">' . t('Explorar la plataforma') . '</a>' .
    '</td></tr></table>'
);
```

### Patron clave: `Markup::create()` para HTML en emails

Drupal escapa automaticamente los strings en `$message['body'][]`. Para inyectar HTML que se renderice dentro de `email-wrap.html.twig`, **se debe usar `\Drupal\Core\Render\Markup::create()`** que marca el contenido como safe HTML.

### Diseno de los 3 emails

| Email | Destinatario | Elementos HTML |
|-------|-------------|----------------|
| `confirmacion_solicitud` | Solicitante | Saludo + tarjeta resumen verde + 3 pasos numerados (circulos naranjas) + CTA "Explorar la plataforma" + nota WhatsApp |
| `nueva_solicitud` | Admin | Tabla datos solicitante (zebra striping) + tarjeta triaje IA con color segun recomendacion (verde/amarillo/rojo) + badge + CTA "Revisar solicitud" |
| `eligibility_notification` | Admin | Tarjeta progreso verde con horas (orientacion + formacion + checkmarks) + CTA "Ver ficha del participante" |

### Compatibilidad email clients

- **Inline CSS** en cada elemento (email clients eliminan `<style>` blocks)
- **Table-based layout** para Outlook (no soporta flexbox/grid)
- **`role="presentation"`** en tables decorativas (accesibilidad)
- **`cellpadding="0" cellspacing="0" border="0"`** reset para cada table
- **Colores de la paleta Jaraba**: corporativo #233D63, primario #FF8C42, exito #10B981

### Escapado seguro

```php
// Helper para valores en atributos HTML.
$esc = fn($v) => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

// Texto traducible — t() auto-escapa placeholders @.
t('Hola <strong>@nombre</strong>,', ['@nombre' => $esc($nombre)])

// URLs en atributos href — siempre escapar.
'<a href="' . $esc($url) . '">'
```

### Parametro `dashboard_url` anadido

`notifyApplicant()` en `SolicitudEiPublicForm.php` ahora pasa `dashboard_url` como URL absoluta generada via `Url::fromRoute('jaraba_andalucia_ei.dashboard', [], ['absolute' => TRUE])`. El CTA del email de confirmacion enlaza directamente al dashboard.

---

## Problema 2: URLs de tarjetas apuntan a landing pages publicas (P2-URLS)

### Antes

```twig
{# Hardcoded paths a landing pages de marketing (pre-login) #}
<a href="/empleo" class="aei-solicitud-state__feature">
<a href="/talento" class="aei-solicitud-state__feature">
<a href="/emprender" class="aei-solicitud-state__feature">
```

Estas rutas (`ecosistema_jaraba_core.vertical.empleo/talento/emprender`) son landing pages publicas con CTA "Crear cuenta" / "Ya tengo cuenta" — inutiles para un usuario ya autenticado.

### Despues

```twig
{# Rutas funcionales via path() de Drupal #}
<a href="{{ path('jaraba_job_board.search') }}" ...>       {# → /es/jobs #}
<a href="{{ path('jaraba_candidate.my_profile') }}" ...>   {# → /es/my-profile #}
<a href="{{ path('ecosistema_jaraba_core.landing.emprendimiento') }}" ...> {# → /es/emprendimiento #}
```

### Mapeo de rutas corregido

| Tarjeta | Ruta anterior (landing) | Ruta corregida (funcional) | Modulo |
|---------|------------------------|---------------------------|--------|
| Ofertas de empleo | `/empleo` | `/jobs` | `jaraba_job_board` |
| Tu perfil profesional | `/talento` | `/my-profile` | `jaraba_candidate` |
| Herramientas emprendimiento | `/emprender` | `/emprendimiento` | `ecosistema_jaraba_core` |

---

## Reglas nuevas

### EMAIL-HTML-PREMIUM-001

> **Los emails del SaaS DEBEN usar cuerpo HTML con `Markup::create()`, inline CSS y table layout.**
> El wrapper `email-wrap.html.twig` proporciona shell branded (header + footer). El body debe ser HTML estructurado (tablas, tarjetas, CTAs) — NO texto plano. Usar `Markup::create()` para marcar como safe HTML. Escapar valores con `htmlspecialchars()` y `t()` con placeholders `@`.

### TWIG-ROUTE-PATH-001

> **En templates Twig, usar `{{ path('route.name') }}` para enlaces internos, NUNCA hardcodear paths.**
> Los paths hardcodeados (`/empleo`, `/talento`) no respetan prefijos de idioma (`/es/`, `/en/`) ni cambios de routing. Usar `path()` garantiza URLs correctas con prefijo de idioma y compatibilidad con cambios de rutas.

---

## Verificacion

```bash
# Test renderizado de los 3 emails (sin enviar).
lando drush ev "
\$msg = ['body' => [], 'subject' => '', 'headers' => []];
\$params = ['nombre' => 'Test', 'colectivo' => 'Test', 'dashboard_url' => 'https://example.com'];
jaraba_andalucia_ei_mail('confirmacion_solicitud', \$msg, \$params);
echo str_contains((string)\$msg['body'][0], '<table') ? 'HTML OK' : 'FAIL';
"

# Verificar URLs en dashboard renderizado.
lando ssh -c "curl -s -b /tmp/cookies.txt https://jaraba-saas.lndo.site/es/andalucia-ei" \
  | grep -oP 'href="/es/[^"]*"' | grep -E '(jobs|my-profile|emprendimiento)'
# Esperado: /es/jobs, /es/my-profile, /es/emprendimiento

# Ver emails en Mailhog.
# http://mail.jaraba-saas.lndo.site/
```

---

## Ficheros modificados

| Fichero | Cambio |
|---------|--------|
| `jaraba_andalucia_ei.module` | `hook_mail()`: 3 emails reescritos de texto plano a HTML premium con `Markup::create()` |
| `src/Form/SolicitudEiPublicForm.php` | `notifyApplicant()`: anadido param `dashboard_url` (URL absoluta) |
| `templates/andalucia-ei-dashboard.html.twig` | 3 enlaces de tarjetas: hardcoded paths → `{{ path('route.name') }}` |
