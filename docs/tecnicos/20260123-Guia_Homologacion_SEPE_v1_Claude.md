# Guía de Homologación SEPE - Jaraba Impact Platform

## Proceso de Validación y Declaración Responsable

**Fecha:** 2026-01-23  
**Estado:** Pendiente de trámite  
**Normativa:** Orden TMS/369/2019

---

## 1. Requisitos Previos ✅

| Requisito | Estado | Notas |
|-----------|--------|-------|
| Módulo SEPE instalado | ✅ | `jaraba_sepe_teleformacion` |
| 6 operaciones SOAP | ✅ | Implementadas |
| WSDL conforme | ✅ | `seguimiento-teleformacion.wsdl` |
| Web Service accesible | ✅ | `/sepe/ws/seguimiento` |
| Centro de formación creado | ⚠️ | Crear al menos 1 centro |
| Acción formativa de prueba | ⚠️ | Crear con datos reales |

---

## 2. Kit de Autoevaluación SEPE

### 2.1 Descarga

El kit de autoevaluación se descarga desde la sede electrónica del SEPE:

1. **URL:** https://sede.sepe.gob.es/contenidosSede/generico.do?pagina=formacion/teleformacion
   **URL actual:** https://sede.sepe.gob.es/portalSede/es/procedimientos-y-servicios/empresas/formacion-para-el-empleo/acreditacion-e-inscripcion-en-teleformacion
2. **Sección:** "Documentación técnica para entidades"
3. **Archivo:** `kit_autoevaluacion_teleformacion_vX.X.zip`

> ⚠️ **Nota:** Si la URL no funciona, buscar en la sección de Formación Profesional para el Empleo del SEPE.

### 2.2 Requisitos del Kit

- **Java JRE 8+** instalado
- **Conectividad HTTPS** al Web Service
- **Certificado SSL válido** en el servidor

### 2.3 Configuración

Editar el archivo `config.properties` dentro del kit:

```properties
# URL del Web Service SOAP
ws.endpoint.url=https://plataformadeecosistemas.es/sepe/ws/seguimiento

# URL del WSDL
ws.wsdl.url=https://plataformadeecosistemas.es/sepe/ws/seguimiento/wsdl

# Timeout en segundos
ws.timeout=30
```

### 2.4 Ejecución

```bash
cd kit_autoevaluacion
java -jar sepe-validador.jar

# O si hay script batch/shell:
./validar.sh
```

### 2.5 Operaciones Validadas

El kit verifica las 6 operaciones:

| Operación | Endpoint | Parámetros |
|-----------|----------|------------|
| ObtenerDatosCentro | Automático | - |
| CrearAccion | Manual | idAccion |
| ObtenerListaAcciones | Automático | - |
| ObtenerDatosAccion | Manual | idAccion |
| ObtenerParticipantes | Manual | idAccion |
| ObtenerSeguimiento | Manual | idAccion, dni |

### 2.6 Informe de Validación

El kit genera un archivo `informe_validacion.pdf` que debe adjuntarse a la Declaración Responsable.

**Resultado esperado:** 6/6 operaciones OK ✅

---

## 3. Declaración Responsable

### 3.1 Qué es

Documento oficial que presenta la entidad ante el SEPE declarando que:
- Dispone de plataforma de teleformación homologable
- El Web Service cumple con los requisitos técnicos
- Se compromete a mantener el seguimiento de participantes

### 3.2 Documentación Requerida

| Documento | Descripción |
|-----------|-------------|
| Modelo oficial DR | Formulario Declaración Responsable |
| Informe kit validación | Generado por el kit SEPE |
| Manual de usuario | Guía de uso de la plataforma |
| Especificaciones técnicas | Arquitectura del Web Service |
| Certificado SSL | Validez del certificado HTTPS |

### 3.3 Presentación

**Vía telemática (recomendada):**
1. Acceder a https://sede.sepe.gob.es
2. Identificarse con certificado digital o Cl@ve
3. Buscar: "Declaración Responsable Teleformación"
4. Adjuntar documentación
5. Firmar y presentar

**Plazo:** Antes de iniciar cualquier acción formativa subvencionada

### 3.4 Modelo de Declaración Responsable

```
DECLARACIÓN RESPONSABLE

D./Dña. [NOMBRE REPRESENTANTE LEGAL]
con NIF [NIF]
en calidad de [CARGO] de la entidad [RAZÓN SOCIAL]
con CIF [CIF]

DECLARA BAJO SU RESPONSABILIDAD:

1. Que la plataforma de teleformación ubicada en la URL:
   https://plataformadeecosistemas.es
   
   cumple con los requisitos técnicos establecidos en la 
   Orden TMS/369/2019, de 28 de marzo.

2. Que el Web Service de seguimiento está disponible en:
   https://plataformadeecosistemas.es/sepe/ws/seguimiento
   
   y responde correctamente a las 6 operaciones requeridas.

3. Que se compromete a mantener actualizada la información
   de seguimiento de los participantes según los plazos
   establecidos en la normativa vigente.

4. Que adjunta a esta declaración:
   - Informe de validación del kit de autoevaluación SEPE
   - Manual de usuario de la plataforma
   - Especificaciones técnicas del Web Service

En [CIUDAD], a [FECHA]

Firma: ____________________
```

---

## 4. Checklist Final

### Antes de la Validación

- [ ] Centro SEPE creado con datos reales
- [ ] Al menos 1 acción formativa de prueba
- [ ] Al menos 1 participante con DNI válido
- [ ] Certificado SSL válido y activo
- [ ] Web Service accesible desde Internet

### Después de la Validación

- [ ] Informe del kit: 6/6 OK
- [ ] Documentación preparada
- [ ] Representante legal identificado
- [ ] Certificado digital disponible

### Después de la Presentación

- [ ] Número de registro obtenido
- [ ] Copia de la declaración archivada
- [ ] Configurar centro como "homologado" en la plataforma

---

## 5. URLs de Referencia

| Recurso | URL |
|---------|-----|
| Sede SEPE | https://sede.sepe.gob.es |
| Normativa | https://www.boe.es/eli/es/o/2019/03/28/tms369 |
| Contacto SEPE | formacion@sepe.es |

---

## 6. Comandos de Verificación

```bash
# Verificar WSDL accesible
curl -I https://plataformadeecosistemas.es/sepe/ws/seguimiento/wsdl

# Probar operación ObtenerDatosCentro
curl -X POST \
  -H "Content-Type: text/xml" \
  -d '<?xml version="1.0"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Body>
    <ObtenerDatosCentro xmlns="http://sepe.es/ws/seguimiento"/>
  </soap:Body>
</soap:Envelope>' \
  https://plataformadeecosistemas.es/sepe/ws/seguimiento
```

---

**Estado del módulo:** 88% completado  
**Pendiente:** Ejecución del kit + Presentación DR  
**Responsable:** Equipo administrativo + Representante legal
