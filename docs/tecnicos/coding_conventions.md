# 📐 Convenciones de Código — Jaraba Impact Platform

> **Versión:** 1.0.0
> **Fecha:** 2026-02-11
> **Origen:** [Auditoría Coherencia #3](../implementacion/20260211-Auditoria_Coherencia_9_Roles_v1.md)

---

## PHP: Constructores y Dependency Injection

### Convención

| Contexto | Patrón | Ejemplo |
|----------|--------|---------|
| **Servicios nuevos** | PHP 8.2+ Constructor Promotion | `protected Type $prop` en constructor |
| **Servicios existentes** | Legacy aceptado | Refactorizar solo al modificar el servicio |

### ✅ Constructor Promotion (nuevos servicios)

```php
class MiServicio {
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}
}
```

**Usado en:** `jaraba_foc`, `TenantSubscriptionService`, `TenantMeteringService`, `PricingRuleEngine`

### ✅ Legacy (aceptado en servicios existentes)

```php
class MiServicio {
  protected $entityTypeManager;
  protected $logger;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }
}
```

**Usado en:** `TenantManager`, `ReverseTrialService`, `ExpansionRevenueService`

### Regla

> **No refactorizar servicios existentes solo por estilo.** Aplicar promoted properties al crear servicios nuevos o al modificar sustancialmente un servicio existente. El objetivo es consistencia **interna** dentro de cada módulo, no uniformidad forzada entre módulos.

---

## PHP: Strict Types

Todos los archivos PHP nuevos DEBEN incluir:

```php
<?php

declare(strict_types=1);
```

---

## PHP: Naming

| Elemento | Convención | Ejemplo |
|----------|-----------|---------|
| Servicios | `PascalCase` + suffix `Service` | `TenantSubscriptionService` |
| Entidades | `PascalCase` | `Tenant`, `PricingRule` |
| Controladores | `PascalCase` + suffix `Controller` | `UsageDashboardController` |
| Tests | Clase + suffix `Test` | `TenantSubscriptionServiceTest` |
| Service IDs | `snake_case` con prefijo módulo | `ecosistema_jaraba_core.plan_validator` |

---

## Twig / Frontend

- Comentarios en **español**
- Variables Twig: `snake_case`
- CSS classes: BEM (`block__element--modifier`)
- Design tokens: `var(--ej-*)` (Federated Design Tokens)

---

## Documentación

- Docblocks en **español** para comentarios de negocio
- PHPDoc tags en **inglés** (`@param`, `@return`, `@throws`)
- Archivos Markdown en español
