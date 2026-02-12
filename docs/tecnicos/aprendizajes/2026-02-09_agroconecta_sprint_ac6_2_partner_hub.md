# Aprendizajes: Sprint AC6-2 Partner Document Hub B2B

**Fecha:** 2026-02-09  
**Sprint:** AC6-2  
**Documento técnico:** Doc 82

## Contexto

Implementación full-stack del Hub Documental B2B para compartir documentación comercial entre productores agrícolas y sus socios (distribuidores, exportadores, certificadores).

## Decisiones de Arquitectura

### 1. Magic Link Authentication
- Se implementó autenticación sin contraseña mediante tokens de 64 caracteres (`bin2hex(random_bytes(32))`)
- El token se genera automáticamente en `PartnerRelationshipForm::save()` al crear una relación
- Permite acceso público al portal sin requerir cuenta Drupal para partners
- **Cuidado**: siempre responder `200` en `/request-link` para evitar enumeración de emails

### 2. Control de Acceso Granular por Niveles
- 3 niveles jerárquicos: `basico` < `verificado` < `premium`
- Cada documento tiene un `min_access_level` que filtra automáticamente
- Los partners también se restringen por `partner_type` y productos/categorías permitidos
- La lógica centralizada en `PartnerDocumentService::getAccessibleDocuments()`

### 3. Audit Log Inmutable
- `DocumentDownloadLog` es una entidad sin formularios de edición
- Solo se crea programáticamente desde `logDownload()` con IP + user agent
- `AccessControlHandler` devuelve `AccessResult::forbidden()` para create/update/delete directo

### 4. Dos Controllers Separados
- `PartnerHubApiController`: 9 endpoints autenticados (productor gestiona sus datos)
- `PartnerPortalApiController`: 8 endpoints públicos (partner accede vía token)
- Separación clara de responsabilidades y flujos de seguridad distintos

## Patrones Aplicados

| Patrón | Dónde | Detalle |
|--------|-------|---------|
| Token-based auth | Portal endpoints | Token en URL, validación contra BD |
| Soft delete | `ProductDocument` | `is_active = false` en vez de eliminar |
| Counter increment | `logDownload()` | `$doc->set('download_count', $count + 1)` atómico |
| ZIP streaming | `downloadPack/downloadAll` | `ZipArchive` con temp file + `BinaryFileResponse` |
| CSV export | `exportDownloadsCsv()` | `php://temp` + `fputcsv()` + `StreamedResponse` |
| Stagger animations | JS IntersectionObserver | `--stagger-index` CSS var + `transition-delay` |

## Lecciones Aprendidas

### ✅ `devel-entity-updates` para Drupal 10+
- `drush entity:updates` NO existe en Drush 12+
- Usar `drush devel-entity-updates -y` (requiere módulo Devel)
- Instala automáticamente las tablas de entidades nuevas

### ✅ URL del entorno local
- La URL correcta del proyecto es `https://jaraba-saas.lndo.site/`
- NO es `https://jaraba-impact-platform.lndo.site/`
- Verificar siempre `.lando.yml` para confirmar el nombre del proxy

### ✅ SQL via Drush con SSL
- Error `ERROR 2026 (HY000): TLS/SSL error` al usar `drush sqlq`
- Workaround: verificar tablas con `drush devel-entity-updates` (lista pending updates)
- La compilación SCSS y las operaciones de entidad funcionan sin SSL

### ✅ Estructura de Sprint Consolidada
- Patrón de 4 fases probado y funcional:
  1. Entities + Handlers
  2. Service (lógica de negocio)
  3. Controllers + Routes (API)
  4. Frontend (SCSS + JS + Twig + Library)
- Este patrón se aplicó en AC6-1 (QR) y AC6-2 (Partner Hub) con éxito

## Estadísticas del Sprint

| Métrica | Valor |
|---------|-------|
| Archivos nuevos | 18 |
| Archivos modificados | 4 |
| Content Entities | 3 |
| Handlers (ListBuilder + ACH + Forms) | 10 |
| Métodos de servicio | 12+ |
| API endpoints | 17 |
| Rutas en routing.yml | 19 |
| Líneas SCSS | 530+ |
| Líneas JS | 268 |
| Líneas PHP (entities+service+controllers) | ~1,500 |
