# Aprendizaje #95 — Plan Elevacion ComercioConecta Clase Mundial v1

| Campo | Valor |
|-------|-------|
| **Fecha** | 2026-02-17 |
| **Modulo** | `jaraba_comercio_conecta` + `ecosistema_jaraba_core` (servicios transversales ComercioConecta) |
| **Contexto** | Implementacion completa del Plan Elevacion ComercioConecta Clase Mundial v1 — 18 fases en 3 sprints. Sprint 1: infraestructura de elevacion (F1-F5, F13-F18). Sprint 2: comercio core completo (F6-F9). Sprint 3: funcionalidades avanzadas (F10). Patron reutilizable de 14 fases aplicado por 6a vez (tras Empleabilidad, Emprendimiento, Andalucia+ei, JarabaLex, AgroConecta) combinado con 4 fases de comercio core (F6-F10). |
| **Resultado** | 42 Content Entities totales, 25 servicios registrados, 178 ficheros PHP, 9 controllers, 23 list builders, 37 forms, 42 access handlers, 5 JS files, 12 SCSS partials, 17 templates Twig, 60+ rutas, 30+ permisos, 19 admin tabs, 6 MJML email templates, 4 funnel definitions, 11 Page Builder premium templates, 9 FreemiumVerticalLimit configs, 7 UpgradeTrigger types |
| **Aprendizaje anterior** | #94 — Plan Elevacion ServiciosConecta Clase Mundial v1 |

---

## Patron Principal

**Elevacion vertical commerce con sprint de entidades masivo** — ComercioConecta es el primer vertical que combina el patron de 14 fases de elevacion (Sprint 1) con un sprint de entidades commerce masivo (Sprint 2-3: 31 nuevas entidades con access handlers, forms, list builders, services y controllers). La clave es la paralelizacion por tipo de artefacto: un agente para entidades, otro para access+forms+listbuilders, otro para services, y otro para controllers+templates. Los ficheros compartidos (services.yml, routing.yml, permissions.yml, .module, .install) se editan secuencialmente en el hilo principal tras completar todos los agentes.

---

## Aprendizajes Clave

### 1. Paralelizacion por tipo de artefacto en sprints de entidades masivos

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | Sprint 2 requeria crear 28 entidades nuevas (F6: 9, F7: 3, F8: 5, F9: 11) con sus access handlers, forms, list builders, services y controllers. Ejecutar secuencialmente seria extremadamente lento. |
| **Aprendizaje** | Agrupar por tipo de artefacto y lanzar 4 agentes paralelos: (1) Agente entidades — crea los ficheros Entity/*.php, (2) Agente access+forms+listbuilders — crea Access/*Handler.php + Form/*Form.php + ListBuilder/*ListBuilder.php, (3) Agente services — crea Service/*Service.php, (4) Agente controllers+templates — crea Controller/*Controller.php + templates/*.html.twig. Tras completar los 4 agentes, actualizar ficheros compartidos secuencialmente: services.yml, routing.yml, permissions.yml, links.task.yml, links.menu.yml, .module (hook_theme + hook_preprocess_html), .install (update hook). |
| **Regla** | **ENTITY-BATCH-001**: Al crear 5+ entidades en un sprint, agrupar por tipo de artefacto (entities, access+forms, services, controllers) y lanzar agentes paralelos. Ficheros compartidos (services.yml, routing.yml, .module, .install) siempre en hilo principal post-agentes. |

### 2. Patron update hook para instalacion masiva de entidades

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | ComercioConecta ya estaba instalado con 11 entidades de Sprint 1 (productos, variaciones, stock, merchant). Sprint 2-3 anaden 31 entidades nuevas que necesitan schemas en la base de datos. |
| **Aprendizaje** | Crear un `hook_update_NNNNN()` que recorre una lista de entity_type_ids y para cada uno verifica si ya existe en `entityDefinitionUpdateManager()`. Si no existe, obtiene la definicion con `getDefinition()` e instala con `installEntityType()`. Patron: verificar antes de instalar para idempotencia. Agrupar todas las entidades de un sprint en un solo update hook para minimizar el numero de updates. |
| **Regla** | **ENTITY-BATCH-INSTALL-001**: Al anadir multiples entidades a un modulo existente, crear un unico `hook_update_NNNNN()` que itere sobre la lista de entity_type_ids, verifique existencia con `getEntityType()`, y solo instale si no existe. Nunca crear un update hook por entidad individual. |

### 3. Integracion de cron con servicios especializados

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | `hook_cron()` contenia logica inline para deteccion de carritos abandonados y expiracion de flash offers. Esto dificultaba testing y violaba SRP. |
| **Aprendizaje** | Delegar toda la logica de cron a metodos de servicios dedicados: `CartRecoveryService::detectAbandonedCarts()` para carritos, `FlashOfferService::activateScheduledOffers()` y `expireEndedOffers()` para ofertas. El hook_cron solo instancia servicios via `\Drupal::service()` e invoca metodos. Esto permite testear la logica de negocio independientemente del cron. |
| **Regla** | **CRON-SERVICE-001**: En hook_cron(), nunca escribir logica de negocio inline. Delegar siempre a metodos de servicios dedicados. El hook solo debe instanciar servicios e invocar metodos. Esto facilita testing unitario y respeta SRP. |

### 4. Stripe Connect con split de pagos multi-vendor

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | ComercioConecta es un marketplace multi-vendor donde cada pedido puede contener items de multiples comerciantes. Los pagos deben dividirse automaticamente. |
| **Aprendizaje** | Implementar el patron de sub-pedidos (SuborderRetail) donde el pedido principal se divide en sub-pedidos por comerciante. CheckoutService crea el pedido principal + N sub-pedidos. StripePaymentRetailService genera un PaymentIntent por el total y luego transfiere a cada comerciante (menos comision del 10%) via Transfer API. Si Stripe no esta configurado, usar modo simulado con estado 'simulated'. IVA fijo del 21% aplicado en checkout. |
| **Regla** | **COMMERCE-SPLIT-001**: En marketplaces multi-vendor, siempre crear sub-pedidos por comerciante. Pagos via Stripe Transfer API (no Destination Charges) para mayor flexibilidad. Modo simulado obligatorio como fallback cuando Stripe no esta configurado. Comision configurable por plan via FeatureGateService::getCommissionRate(). |

### 5. Patron de busqueda con Haversine y sinonimos

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | ComercioSearchService necesita buscar productos y comerciantes por proximidad geografica ("cerca de mi") y expandir busquedas con sinonimos. |
| **Aprendizaje** | Implementar formula Haversine directamente en PHP para calcular distancias en km entre coordenadas GPS. Para sinonimos, mantener una entidad SearchSynonym con pares (term, synonym) y expandir la query original antes de buscar. El autocompletado usa una query DB con LIKE para rapidez. Los resultados se cachean por hash de parametros de busqueda. |
| **Regla** | **SEARCH-GEO-001**: Para busquedas geolocalizadas, usar formula Haversine en PHP (no en SQL para compatibilidad). Sinonimos como Content Entity (no config) para permitir gestion via admin UI. Autocompletado via LIKE para rendimiento, busqueda completa via facetas para precision. |

### 6. Metricas del sprint de comercio

| Aspecto | Detalle |
|---------|---------|
| **Situacion** | ComercioConecta es el vertical mas grande del ecosistema en numero de entidades (42) y servicios (25), superando a AgroConecta (20 entidades) y JarabaLex (~15 entidades entre todos los modulos). |
| **Aprendizaje** | La ejecucion paralela con 4 agentes por fase permite implementar sprints de entidades masivos en tiempo razonable. El patron de agrupacion por tipo de artefacto escala bien hasta 30+ entidades. Los ficheros compartidos (services.yml crece a 270 lineas, routing.yml a 60+ rutas, permissions.yml a 285 lineas) siguen siendo manejables con ediciones secuenciales cuidadosas post-agente. |
| **Regla** | **VERTICAL-SCALE-001**: Verticales con 40+ entidades son viables con el patron de agentes paralelos. Cuando services.yml supera 200 lineas, considerar agrupar services en secciones con comentarios prominentes por fase (F6, F7, F8, etc.). Cuando routing.yml supera 50 rutas, agrupar por prefijo de ruta con comentarios de seccion. |
