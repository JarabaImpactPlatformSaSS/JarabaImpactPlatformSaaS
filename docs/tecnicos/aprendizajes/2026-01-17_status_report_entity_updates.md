# Aprendizajes: Status Report y Entity Updates

**Fecha:** 2026-01-17  
**Contexto:** Corrección de errores en `/admin/reports/status`

---

## 1. Trusted Host Patterns para Lando

### Problema
Drupal mostraba error "Trusted Host Settings: No activado".

### Solución
Añadir al final de `settings.php`:

```php
$settings['trusted_host_patterns'] = [
  '^jaraba-saas\.lndo\.site$',
  '^.+\.jaraba-saas\.lndo\.site$',  // Subdominios
  '^localhost$',
  '^plataformadeecosistemas\.es$',
  '^.+\.plataformadeecosistemas\.es$',
];
```

### Documentación Oficial
https://www.drupal.org/docs/installing-drupal/trusted-host-settings#s-trusted-host-settings-for-lando

---

## 2. Sincronización Docker ↔ Windows en Lando

### Problema
Los cambios en `settings.php` en Windows no se reflejaban en el contenedor Docker.

### Síntomas
- `php -l` mostraba errores de sintaxis no presentes en archivo local
- Contenido del archivo aparecía "corrupto" o mezclado

### Soluciones
1. **Preferida:** Editar archivo local y verificar con `lando rebuild -y`
2. **Alternativa:** Copiar manualmente:
   ```bash
   docker cp web/sites/default/settings.php container:/app/web/sites/default/
   ```
3. **Verificar sintaxis:**
   ```bash
   docker exec container php -l /app/web/sites/default/settings.php
   ```

### Lección
En Windows con Docker, editar archivos directamente desde host y forzar sincronización.

---

## 3. Entity Definition Updates con Datos Existentes

### Problema
Drupal reportaba "Entity/Field Definitions: Mismatches" pero `drush updb` no los resolvía.

### Diagnóstico
```bash
drush ev "print_r(Drupal::entityDefinitionUpdateManager()->getChangeList());"
```

Códigos de cambio:
- `1` = Install (nuevo)
- `2` = Update (modificar)
- `3` = Uninstall (eliminar)

### Instalación de Entidades Nuevas
```bash
drush php-eval '$um = \Drupal::entityDefinitionUpdateManager(); 
$et = \Drupal::entityTypeManager()->getDefinition("entity_id"); 
$um->installEntityType($et);'
```

### Instalación de Campos Nuevos
```bash
drush php-eval '$um = \Drupal::entityDefinitionUpdateManager(); 
$defs = \Drupal::service("entity_field.manager")->getFieldStorageDefinitions("entity_id"); 
$um->installFieldStorageDefinition("field_name", "entity_id", "module", $defs["field_name"]);'
```

### Cambio de Tipo de Campo con Datos
**Drupal NO permite cambiar tipo de campo si hay datos.** Opciones:

1. **Ajustar código al tipo existente** (rápido):
   - Cambiar definición en `baseFieldDefinitions()` para usar tipo en DB
   - Ejemplo: `list_string` → `string`, `commerce_price` → `decimal`

2. **Migrar datos** (complejo):
   - Crear campo nuevo con tipo correcto
   - Copiar datos del campo viejo
   - Eliminar campo viejo
   - Renombrar campo nuevo

### Script de Diagnóstico
```php
$um = \Drupal::entityDefinitionUpdateManager();
$stored = $um->getFieldStorageDefinition('field_name', 'entity_type');
echo "Tipo en DB: " . $stored->getType();

$defs = \Drupal::service("entity_field.manager")->getFieldStorageDefinitions("entity_type");
echo "Tipo en código: " . $defs['field_name']->getType();
```

---

## 4. Módulos Redundantes

### Detectados
- `search` → Usar Search API
- `toolbar` → Usar Navigation module

### Desinstalar
```bash
drush pm:uninstall search toolbar -y
```

**Cuidado:** Puede desinstalar dependencias. Reinstalar si es necesario:
```bash
drush pm:enable modulo_dependiente -y
```
