# ============================================================================
# GUÍA DE INSTALACIÓN: INTEGRACIÓN CON AUTOFIRMA
# ============================================================================
# Plataforma AgroConecta - Firma Electrónica del Usuario
# ============================================================================


## 1. RESUMEN

Esta guía explica cómo integrar AutoFirma en AgroConecta para que los 
productores puedan firmar documentos (contratos, declaraciones) con su
propio certificado digital (FNMT, DNIe).


## 2. REQUISITOS PREVIOS

### 2.1. En el servidor (Drupal)
- Drupal 10/11 con agroconecta_core instalado
- HTTPS obligatorio (AutoFirma solo funciona con SSL)
- PHP con extensión openssl habilitada

### 2.2. En el cliente (usuario)
- AutoFirma instalado (https://firmaelectronica.gob.es/Home/Descargas.html)
- Certificado digital instalado (FNMT o DNIe)
- Navegador moderno (Chrome, Firefox, Edge)


## 3. INSTALACIÓN EN DRUPAL

### 3.1. Estructura de archivos

```
modules/custom/agroconecta_core/
├── js/
│   ├── autofirma.js              ← Descargar (ver paso 3.2)
│   └── agroconecta-firma.js      ← Copiar archivo entregado
├── src/
│   └── Controller/
│       └── AutoFirmaController.php  ← Copiar archivo entregado
├── templates/
│   └── autofirma/
│       └── documentos-pendientes.html.twig  ← Copiar archivo entregado
├── agroconecta_core.routing.yml  ← Añadir rutas
└── agroconecta_core.libraries.yml  ← Añadir librería

themes/custom/agroconecta_theme/
└── scss/
    └── components/
        └── _autofirma.scss       ← Copiar archivo entregado
```

### 3.2. Descargar librería oficial de AutoFirma

```bash
# Opción 1: Desde GitHub del MinDigital
wget https://github.com/ctt-gob-es/clienteafirma/raw/master/afirma-simple/src/main/webapp/js/autofirma.js \
     -O modules/custom/agroconecta_core/js/autofirma.js

# Opción 2: Desde el portal de firma (versión más reciente)
# Ir a: https://administracionelectronica.gob.es/ctt/clienteafirma
# Descargar el SDK JavaScript
```

### 3.3. Copiar archivos

1. Copiar `AutoFirmaController.php` a `src/Controller/`
2. Copiar `agroconecta-firma.js` a `js/`
3. Copiar `documentos-pendientes.html.twig` a `templates/autofirma/`
4. Copiar `_autofirma.scss` al tema

### 3.4. Añadir rutas

Añadir el contenido de `routing_autofirma.yml` al archivo 
`agroconecta_core.routing.yml` existente.

### 3.5. Añadir librería

Añadir el contenido de `libraries_autofirma.yml` al archivo
`agroconecta_core.libraries.yml` (crear si no existe).

### 3.6. Compilar SCSS

```bash
# Si usas npm/gulp
npm run build

# O manualmente
sass scss/main.scss css/main.css
```

### 3.7. Limpiar caché

```bash
drush cr
```


## 4. CREAR TIPO DE CONTENIDO "DOCUMENTO FIRMA"

### 4.1. Crear el Content Type

Ir a: Estructura → Tipos de contenido → Añadir tipo de contenido

- **Nombre:** Documento para Firma
- **Nombre máquina:** `documento_firma`
- **Descripción:** Documentos que requieren firma electrónica del usuario

### 4.2. Añadir campos

| Campo | Nombre máquina | Tipo | Descripción |
|-------|----------------|------|-------------|
| Documento PDF | `field_documento_pdf` | Archivo | PDF original a firmar |
| Estado Firma | `field_estado_firma` | Lista (texto) | borrador, pendiente, firmado, rechazado, expirado |
| Fecha Límite | `field_fecha_limite` | Fecha | Fecha límite para firmar |
| Firmante Destinatario | `field_firmante_destinatario` | Referencia (Usuario) | Quién debe firmar |
| Fecha Firma | `field_fecha_firma` | Fecha y hora | Cuándo se firmó |
| Firmante UID | `field_firmante_uid` | Referencia (Usuario) | Quién firmó realmente |
| Documento Firmado | `field_documento_firmado` | Archivo | PDF firmado |
| Info Certificado | `field_info_certificado` | Texto largo | JSON con datos del certificado |
| Contenido Relacionado | `field_contenido_relacionado` | Referencia (Contenido) | Lote, producto, etc. |
| Tipo Documento | `field_tipo_documento` | Lista (texto) | contrato, declaracion, certificado |

### 4.3. Configurar valores de Estado Firma

```
borrador|Borrador
pendiente|Pendiente de firma
firmado|Firmado
rechazado|Rechazado
expirado|Expirado
```


## 5. INTEGRAR EN EL DASHBOARD DEL PRODUCTOR

### 5.1. Modificar user.html.twig

Añadir en la sección de gestión documental:

```twig
{# Documentos pendientes de firma #}
{% set documentos_firma = drupal_view('documentos_firma_usuario', 'block_pendientes') %}
{% if documentos_firma %}
  <div class="dashboard-section documentos-firma mb-4">
    <h4 class="section-title">
      <i class="bi bi-pen me-2"></i>Documentos para Firma
    </h4>
    {{ documentos_firma }}
  </div>
{% endif %}
```

### 5.2. Crear Vista "documentos_firma_usuario"

- **Mostrar:** Contenido tipo "Documento para Firma"
- **Filtro contextual:** Firmante Destinatario = Usuario actual
- **Filtro:** Estado = pendiente
- **Ordenar por:** Fecha límite (ascendente)


## 6. USO

### 6.1. Crear documento para firma (Admin/Sistema)

```php
// Ejemplo: Crear contrato de alta para nuevo productor
$node = Node::create([
  'type' => 'documento_firma',
  'title' => 'Contrato de Alta - ' . $user->getDisplayName(),
  'field_tipo_documento' => 'contrato',
  'field_estado_firma' => 'pendiente',
  'field_firmante_destinatario' => $user->id(),
  'field_fecha_limite' => date('Y-m-d', strtotime('+30 days')),
  'field_documento_pdf' => ['target_id' => $pdf_file->id()],
]);
$node->save();
```

### 6.2. Flujo del productor

1. El productor ve el documento en su dashboard
2. Pulsa "Firmar con Certificado"
3. Se abre AutoFirma
4. Selecciona su certificado (FNMT/DNIe)
5. El documento firmado se guarda automáticamente


## 7. SOLUCIÓN DE PROBLEMAS

### AutoFirma no se abre

1. Verificar que AutoFirma está instalado
2. Verificar que el sitio usa HTTPS
3. Reiniciar el navegador después de instalar AutoFirma
4. En Windows: verificar que no está bloqueado por antivirus

### "No se encontraron certificados"

1. Verificar que el certificado está instalado en el navegador
2. Para DNIe: verificar que el lector de tarjetas funciona
3. Para FNMT: renovar si está caducado

### Error de conexión WebSocket

1. Verificar que AutoFirma está ejecutándose
2. Verificar que el firewall permite conexiones locales (puerto 63117)
3. Probar con protocolo afirma:// como fallback


## 8. CONSIDERACIONES MÓVIL

AutoFirma NO funciona en dispositivos móviles. Para usuarios móviles:

### Alternativa 1: Firma diferida
- El usuario inicia el proceso en móvil
- Recibe email con enlace
- Completa la firma en un ordenador de escritorio

### Alternativa 2: Cl@ve Firma
- Integrar con el servicio Cl@ve Firma del Gobierno
- Permite firma en la nube sin instalación local
- Requiere que el usuario tenga cuenta Cl@ve


## 9. SEGURIDAD

- ✅ HTTPS obligatorio
- ✅ Token CSRF en todas las peticiones POST
- ✅ Validación de firma en servidor
- ✅ La clave privada nunca sale del dispositivo del usuario
- ✅ Registro de auditoría de todas las firmas
- ❌ NUNCA almacenar certificados o claves privadas


## 10. RECURSOS

- AutoFirma: https://firmaelectronica.gob.es/Home/Descargas.html
- Documentación AutoFirma: https://administracionelectronica.gob.es/ctt/clienteafirma
- VALIDe (verificar firmas): https://valide.redsara.es
- Certificados FNMT: https://www.sede.fnmt.gob.es/certificados
- Cl@ve: https://clave.gob.es
