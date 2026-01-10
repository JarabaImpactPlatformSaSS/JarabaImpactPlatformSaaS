# ============================================================================
# GUÍA DE INSTALACIÓN: FIRMA AUTOMÁTICA DE CERTIFICADOS DE TRAZABILIDAD
# ============================================================================
# Plataforma AgroConecta
# ============================================================================

## 1. REQUISITOS PREVIOS

### 1.1. Dependencias PHP
```bash
# Instalar TCPDF via Composer
cd /ruta/a/drupal
composer require tecnickcom/tcpdf

# Verificar que OpenSSL está disponible
php -m | grep openssl
```

### 1.2. Certificado Digital
Necesitas obtener un certificado de **Sello de Empresa** o **Representante de Persona Jurídica** de la FNMT:

1. Acceder a: https://sede.fnmt.gob.es/certificados
2. Solicitar "Certificado de Representante de Persona Jurídica" o "Sello de Empresa"
3. Acreditación presencial en oficina de registro
4. Descargar e instalar certificado en formato .p12/.pfx


## 2. INSTALACIÓN DEL CERTIFICADO EN EL SERVIDOR

### 2.1. Copiar certificado al servidor
```bash
# Crear directorio seguro (FUERA del webroot)
sudo mkdir -p /etc/ssl/private/agroconecta

# Copiar el certificado
sudo cp mi-certificado.p12 /etc/ssl/private/agroconecta/sello.p12

# Establecer permisos restrictivos
sudo chown www-data:www-data /etc/ssl/private/agroconecta/sello.p12
sudo chmod 600 /etc/ssl/private/agroconecta/sello.p12
```

### 2.2. Configurar variables de entorno
Añadir al archivo de configuración del servidor web:

**Apache (/etc/apache2/envvars):**
```bash
export AGROCONECTA_CERT_PATH="/etc/ssl/private/agroconecta/sello.p12"
export AGROCONECTA_CERT_PASSWORD="tu_contraseña_segura"
```

**Nginx + PHP-FPM (/etc/php/8.x/fpm/pool.d/www.conf):**
```ini
env[AGROCONECTA_CERT_PATH] = /etc/ssl/private/agroconecta/sello.p12
env[AGROCONECTA_CERT_PASSWORD] = tu_contraseña_segura
```

**Docker (.env o docker-compose.yml):**
```yaml
environment:
  - AGROCONECTA_CERT_PATH=/etc/ssl/private/sello.p12
  - AGROCONECTA_CERT_PASSWORD=${CERT_PASSWORD}
```

### 2.3. Reiniciar servicios
```bash
# Apache
sudo systemctl restart apache2

# Nginx + PHP-FPM
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```


## 3. INSTALACIÓN DE ARCHIVOS EN DRUPAL

### 3.1. Estructura de archivos
```
modules/custom/agroconecta_core/
├── config/
│   └── install/
│       └── agroconecta_core.firma_settings.yml  ← NUEVO
├── src/
│   └── Service/
│       ├── CertificadoPdfService.php            ← NUEVO
│       ├── FirmaDigitalService.php              ← NUEVO
│       ├── EcwidService.php                     ← EXISTENTE
│       └── TrazabilidadService.php              ← EXISTENTE
├── agroconecta_core.module                      ← MODIFICAR
└── agroconecta_core.services.yml                ← MODIFICAR
```

### 3.2. Copiar archivos
1. Copiar `CertificadoPdfService.php` a `src/Service/`
2. Copiar `FirmaDigitalService.php` a `src/Service/`
3. Copiar `agroconecta_core.firma_settings.yml` a `config/install/`
4. Añadir contenido de `services_firma_digital.yml` a `agroconecta_core.services.yml`
5. Añadir contenido de `agroconecta_core_module_additions.php` a `agroconecta_core.module`

### 3.3. Crear campo en Lote de Producción
En la interfaz de Drupal:
1. Ir a: Estructura → Tipos de contenido → Lote de Producción → Gestionar campos
2. Añadir campo nuevo:
   - **Etiqueta:** Certificado Firmado
   - **Nombre máquina:** `field_certificado_firmado`
   - **Tipo:** Archivo (File)
   - **Extensiones permitidas:** pdf
   - **Directorio:** certificados
   - **Mostrar campo:** Oculto en formulario (se rellena automáticamente)

### 3.4. Limpiar caché e importar configuración
```bash
# Limpiar caché
drush cr

# Si el módulo ya está instalado, importar nueva configuración
drush cim --partial --source=modules/custom/agroconecta_core/config/install

# O reinstalar el módulo (cuidado: puede perder datos)
# drush pm:uninstall agroconecta_core
# drush pm:enable agroconecta_core
```


## 4. VERIFICACIÓN DE LA INSTALACIÓN

### 4.1. Verificar certificado
Crear un archivo PHP temporal para verificar:

```php
<?php
// test_certificado.php (eliminar después de probar)

$cert_path = getenv('AGROCONECTA_CERT_PATH');
$cert_pass = getenv('AGROCONECTA_CERT_PASSWORD');

echo "Ruta certificado: " . ($cert_path ?: "NO DEFINIDA") . "\n";
echo "Archivo existe: " . (file_exists($cert_path) ? "SÍ" : "NO") . "\n";

if (file_exists($cert_path)) {
    $cert_content = file_get_contents($cert_path);
    $certs = [];
    
    if (openssl_pkcs12_read($cert_content, $certs, $cert_pass)) {
        $info = openssl_x509_parse($certs['cert']);
        echo "Certificado válido: SÍ\n";
        echo "Titular: " . $info['subject']['CN'] . "\n";
        echo "Emisor: " . $info['issuer']['CN'] . "\n";
        echo "Válido hasta: " . date('d/m/Y', $info['validTo_time_t']) . "\n";
    } else {
        echo "ERROR: No se pudo leer el certificado. Verificar contraseña.\n";
    }
}
```

Ejecutar:
```bash
drush php:script test_certificado.php
```

### 4.2. Crear lote de prueba
1. Ir a: Contenido → Añadir contenido → Lote de Producción
2. Rellenar datos mínimos (producto, fechas, etc.)
3. Guardar
4. Verificar:
   - Mensaje de éxito "Certificado generado y firmado"
   - Campo "Certificado Firmado" tiene archivo adjunto
   - El PDF se puede descargar y abrir

### 4.3. Verificar firma del PDF
1. Abrir el PDF en Adobe Acrobat Reader
2. Debe mostrar "Firmado digitalmente por..."
3. O usar VALIDe: https://valide.redsara.es


## 5. SOLUCIÓN DE PROBLEMAS

### Error: "Certificado no encontrado"
- Verificar ruta en variable de entorno
- Verificar permisos del archivo (debe ser legible por www-data)
- Reiniciar servidor web después de cambiar envvars

### Error: "No se pudo leer certificado PKCS#12"
- Contraseña incorrecta
- Archivo corrupto (volver a exportar desde navegador)

### PDF se genera pero no se firma
- Verificar que TCPDF está instalado: `composer show tecnickcom/tcpdf`
- Revisar logs: `drush watchdog:show --type=agroconecta_core`

### Error de permisos en directorio certificados
```bash
# Crear directorio y establecer permisos
mkdir -p sites/default/files/private/certificados
chown -R www-data:www-data sites/default/files/private/certificados
chmod -R 775 sites/default/files/private/certificados
```


## 6. MANTENIMIENTO

### 6.1. Renovación del certificado
El certificado FNMT caduca cada 2 años. Antes de la fecha:
1. Solicitar renovación en sede.fnmt.gob.es
2. Descargar nuevo certificado
3. Reemplazar archivo en servidor
4. Reiniciar servicios

### 6.2. Monitorización
Configurar alerta para:
- Fecha de caducidad del certificado (30 días antes)
- Errores en logs de firma
- Espacio en disco para almacenar PDFs

### 6.3. Copias de seguridad
- Incluir `/etc/ssl/private/agroconecta/` en backups
- Guardar copia segura de la contraseña del certificado
- Backup regular de `sites/default/files/private/certificados/`


## 7. CONSIDERACIONES DE SEGURIDAD

⚠️ **CRÍTICO:**
- NUNCA versionar el certificado .p12 en Git
- NUNCA poner la contraseña en código fuente
- Usar SIEMPRE variables de entorno para credenciales
- Permisos del certificado: 600 (solo propietario)
- Directorio private:// para PDFs (no accesible por URL directa)

✅ **RECOMENDADO:**
- Auditar accesos al certificado
- Rotar contraseña si hay cambios de personal
- Usar HSM en producción de alto riesgo
- Implementar alertas de uso anómalo
