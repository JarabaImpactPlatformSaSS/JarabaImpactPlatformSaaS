---
description: Automatizaciones ECA - Usar hooks de Drupal en lugar de ECA UI/BPMN
---

# Directrices para Automatizaciones en Módulos Custom

## Decisión de Arquitectura

**Las automatizaciones tipo ECA (Event-Condition-Action) en módulos custom de Jaraba se implementan mediante hooks de Drupal, NO mediante la UI BPMN de ECA.**

## Justificación

1. **Consistencia**: El vertical Empleabilidad (jaraba_job_board, jaraba_lms, jaraba_candidate) ya utiliza este patrón
2. **Versionado**: Los hooks en código se versionan con Git, mientras que la config ECA BPMN requiere exportación/importación
3. **Testabilidad**: El código en .module se puede testear unitariamente
4. **Rendimiento**: Evita la capa de abstracción de ECA

## Patrón de Implementación

Ubicar las automatizaciones en el archivo `.module` del módulo:

### 1. Hook `hook_entity_insert()` - Creación de entidades
```php
function MODULO_entity_insert(EntityInterface $entity): void {
  $entityType = $entity->getEntityTypeId();
  
  if ($entityType === 'mi_entidad') {
    _MODULO_handle_new_entity($entity);
  }
}
```

### 2. Hook `hook_entity_update()` - Cambios de estado
```php
function MODULO_entity_update(EntityInterface $entity): void {
  if ($entity->getEntityTypeId() === 'mi_entidad') {
    $newStatus = $entity->get('status')->value ?? '';
    $oldStatus = $entity->original->get('status')->value ?? '';
    
    if ($newStatus !== $oldStatus) {
      _MODULO_handle_status_change($entity, $oldStatus, $newStatus);
    }
  }
}
```

### 3. Hook `hook_cron()` - Tareas programadas
```php
function MODULO_cron(): void {
  // Recordatorios, detección de no-shows, notificaciones periódicas
  _MODULO_process_periodic_tasks();
}
```

### 4. Hook `hook_mail()` - Templates de email
```php
function MODULO_mail(string $key, array &$message, array $params): void {
  switch ($key) {
    case 'notification_type':
      $message['subject'] = t('Asunto');
      $message['body'][] = t('Cuerpo del mensaje');
      break;
  }
}
```

### 5. Queues para envío diferido
```php
function _MODULO_queue_email(string $key, string $to, array $context): void {
  $queue = \Drupal::queue('modulo_notification_mail');
  $queue->createItem([
    'key' => $key,
    'to' => $to,
    'langcode' => 'es',
    'context' => $context,
  ]);
}
```

## Ejemplos Implementados

| Módulo | Archivo | Hooks Implementados |
|--------|---------|---------------------|
| jaraba_job_board | jaraba_job_board.module | entity_insert, entity_update, cron, mail |
| jaraba_mentoring | jaraba_mentoring.module | entity_insert, entity_update, cron, mail |
| jaraba_lms | jaraba_lms.module | entity_insert |
| jaraba_diagnostic | jaraba_diagnostic.module | entity_presave, entity_insert |

## Cuándo SÍ usar ECA UI/BPMN

- Automatizaciones configurables por el usuario final
- Flujos que necesiten modificarse sin deployment
- Integraciones no-críticas con terceros

## Referencias

- jaraba_job_board.module líneas 280-792
- jaraba_mentoring.module (implementación completa)
