# Aprendizajes: Elevacion Andalucia +ei a Clase Mundial (12 Fases, 18 Gaps)

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-15 |

---

## Patron Principal

La estructura de 12 fases para elevar un vertical a clase mundial esta validada como blueprint replicable. Andalucia +ei es el tercer vertical elevado con la misma metodologia (empleabilidad 10 fases, emprendimiento 6 fases + 7 gaps). Las 12 fases son: Page Template+FAB, SCSS compliance, Design Tokens, Feature Gating, Email Lifecycle, Cross-Vertical Bridges, Journey Progression, Health Scores, i18n, Upgrade Triggers+CRM, A/B Testing, Embudo Ventas. El resultado son 18 gaps cerrados y paridad de clase mundial con los otros dos verticales principales.

---

## Aprendizajes Clave

### 1. La estructura de 12 fases para verticales esta validada como blueprint replicable

Andalucia +ei es el tercer vertical elevado con la misma estructura (empleabilidad 10 fases, emprendimiento 6+7 gaps). Las fases son: Page Template+FAB, SCSS compliance, Design Tokens, Feature Gating, Email Lifecycle, Cross-Vertical Bridges, Journey Progression, Health Scores, i18n, Upgrade Triggers+CRM, A/B Testing, Embudo Ventas. La repeticion del pattern en tres verticales confirma que la secuencia es estable y que cada fase tiene dependencias claras con las anteriores.

### 2. La compilacion SCSS dentro de Docker requiere NVM path explicito

`export PATH=/user/.nvm/versions/node/v20.20.0/bin:$PATH` antes de `npx sass`. Este patron ya esta documentado pero sigue siendo un paso critico que no debe olvidarse. Sin el path explicito, el contenedor Docker no encuentra el binario de sass y la compilacion falla silenciosamente o con errores de comando no encontrado. Este paso debe estar en todo checklist de fase SCSS.

### 3. TranslatableMarkup en JourneyDefinition requiere migrar de const arrays a static methods

PHP no permite `new TranslatableMarkup()` en constantes de clase. La solucion es migrar de `const` a `static methods`, manteniendo backward compatibility via `getJourneyDefinition()` que delega a metodos estaticos. Este pattern aplica a cualquier lugar donde se necesiten objetos instanciados en definiciones que originalmente eran constantes. La migracion es segura porque el contrato publico del metodo no cambia.

### 4. Las FreemiumVerticalLimit configs siguen nomenclatura predecible: vertical_plan_feature

18 configs YAML para 6 features x 3 planes. El patron `andalucia_ei_{plan}_{feature}.yml` es consistente con empleabilidad y emprendimiento. Esta nomenclatura predecible permite generar las configs programaticamente y facilita la auditoria: basta con listar los archivos YAML del directorio de config para verificar que todas las combinaciones plan-feature estan cubiertas.

### 5. Los servicios de cross-vertical bridges deben ser outgoing y context-aware

Los 4 bridges desde andalucia_ei evaluan condiciones del participante (fase, horas, skills, insercion) para recomendar otros verticales. Maximo 2 bridges activos simultaneamente mas dismiss tracking via State API. La direccion outgoing significa que el vertical origen es responsable de evaluar cuando activar el bridge; el vertical destino solo necesita existir y tener su landing preparada.

### 6. El hook_ENTITY_TYPE_insert() es el punto de anclaje correcto para welcome email + CRM lead

Al crear un participante se necesita enrollment en SEQ_AEI_001, tracking de conversion `participant_enrolled` y sync CRM inicial. Esto unifica el punto de entrada del embudo en un solo hook. Dispersar estas acciones en multiples hooks o eventos introduce riesgo de ejecucion parcial; centralizar en `hook_ENTITY_TYPE_insert()` garantiza que el participante siempre entra al embudo completo de forma atomica.

### 7. El cron proactive evaluation necesita su propio timer independiente dentro del cron hook existente

La funcion `_jaraba_andalucia_ei_cron_proactive_evaluation()` tiene su propio state key y timer de 24h, invocada dentro del `jaraba_andalucia_ei_cron()` que ya corre cada 6h para STO sync. Este patron de timers anidados permite que diferentes tareas dentro del mismo modulo tengan cadencias independientes sin necesidad de registrar cron jobs adicionales en el sistema.

### 8. El dashboard controller debe enriquecerse con health score, bridges y proactive actions en un solo punto

En vez de multiples API calls desde el frontend, el controller inyecta todo en el render array. La proactive action tambien se pasa via drupalSettings para el JS del FAB. Este enfoque de single-pass rendering reduce la latencia percibida por el usuario y simplifica la logica del frontend: todo lo que necesita el dashboard llega en una sola carga del controller.

### 9. Los 8 eventos de conversion A/B deben mapear a hitos reales del journey del participante

Los eventos son: participant_enrolled, first_ia_session, diagnostic_completed, training_10h, training_50h, orientation_10h, phase_insertion, plan_upgraded. Cada evento se trackea en el punto exacto del codigo donde ocurre el hito. No se usan eventos sinteticos ni proxys; el tracking vive junto al codigo de negocio que produce el hito, garantizando que la medicion refleja la realidad del journey.

---

## Estadisticas

| Metrica                    | Valor |
|----------------------------|-------|
| Archivos nuevos            | ~30   |
| Archivos modificados       | ~13   |
| Total archivos             | ~43   |
| Servicios nuevos           | 7     |
| FreemiumVerticalLimit configs | 18 |
| MJML templates             | 6     |
| Fases completadas          | 12    |
| Gaps cerrados              | 18    |
| Modulos tocados            | 5     |

---

## Referencia

Los archivos de empleabilidad y emprendimiento siguen siendo blueprints validos para la elevacion de cualquier vertical. Con Andalucia +ei elevado, los 3 verticales principales (empleabilidad, emprendimiento, Andalucia +ei) tienen paridad de clase mundial. Al iniciar la elevacion de un nuevo vertical, se recomienda seguir la secuencia de 12 fases validada, adaptando dimensiones, reglas y constantes al contexto especifico del vertical.
