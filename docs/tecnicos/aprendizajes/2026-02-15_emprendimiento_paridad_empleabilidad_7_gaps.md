# Aprendizajes: Paridad Emprendimiento con Empleabilidad (7 Gaps)

| Campo | Valor      |
|-------|------------|
| Fecha | 2026-02-15 |

---

## Patron Principal

Replicar patterns validados de empleabilidad adaptando dimensiones, reglas y constantes al contexto emprendedor. La arquitectura de empleabilidad sirve como blueprint: la estructura del servicio, la firma de metodos y la integracion con el ecosistema se mantienen identicas; lo que cambia son las dimensiones de evaluacion, las condiciones de las reglas y el contenido especifico de cada vertical.

---

## Aprendizajes Clave

### 1. Las dimensiones del Health Score difieren por vertical

Las dimensiones que componen el Health Score no son universales. En empleabilidad se evalua `profile_completeness`; en emprendimiento el equivalente es `canvas_completeness`. El servicio sigue la misma interfaz y logica de calculo, pero las dimensiones y sus pesos deben ser definidos por cada vertical de forma independiente.

### 2. Las reglas de Journey Progression mapean 1:1, pero las condiciones son vertical-specific

Existe un mapeo directo entre las reglas de progresion de empleabilidad y las de emprendimiento. Sin embargo, las condiciones de evaluacion son completamente diferentes: donde empleabilidad evalua completitud de perfil profesional, emprendimiento evalua completitud de BMC (Business Model Canvas). La estructura de la regla es la misma; el predicado cambia.

### 3. Las secuencias de email siguen un pattern identico de servicio; solo cambian las constantes

`EmprendimientoEmailSequenceService` es estructuralmente identico a su contraparte de empleabilidad. Los nombres de secuencia, los subject lines, el contenido de las plantillas y los timings cambian, pero la logica de orquestacion, el manejo de estados y la integracion con el sistema de email son exactamente los mismos. Esto valida que el pattern de servicio de email es reutilizable entre verticales.

### 4. CopilotAgent extiende el mismo BaseAgent, pero modes/keywords/prompts son vertical-specific

El agente de copiloto para emprendimiento hereda de `BaseAgent` igual que el de empleabilidad. La diferencia esta en los 6 modos especializados, las keywords de deteccion y los prompts que alimentan cada modo. Esto confirma que `BaseAgent` esta correctamente abstraido: la logica de routing, contexto y respuesta es generica; la especializacion vive en la subclase.

### 5. Los puentes cross-vertical son salientes (outgoing), no entrantes; la direccion importa

Al implementar `EmprendimientoCrossVerticalBridgeService`, los 3 puentes definidos son salientes desde emprendimiento hacia otras verticales. La direccion del puente determina que servicio es responsable de iniciarlo y cual de recibirlo. Este detalle arquitectonico es critico: un puente mal direccionado puede crear dependencias circulares o dejar al usuario en un estado inconsistente entre verticales.

### 6. El pattern de CRM sync de jaraba_job_board se replica limpiamente a jaraba_copilot_v2

La logica de sincronizacion con CRM implementada originalmente en `jaraba_job_board` se traslado a `jaraba_copilot_v2` sin friccion significativa. El pipeline (deteccion de cambio, mapeo de campos, push al CRM, manejo de errores) es lo suficientemente generico para funcionar en cualquier modulo que necesite sync. Esto sugiere que en el futuro podria extraerse a un servicio compartido.

### 7. Los upgrade triggers necesitan tanto nuevos tipos COMO integracion fire() en FeatureGateService

No basta con definir nuevos tipos de trigger. Para que el sistema los reconozca y ejecute, es necesario integrar la llamada a `fire()` dentro de `FeatureGateService`. Este es un paso que puede olvidarse facilmente porque la definicion de tipos se hace en un lugar (configuracion/constantes) y la invocacion en otro (el servicio). Ambas partes son necesarias para que el trigger funcione end-to-end.

---

## Estadisticas

| Metrica             | Valor |
|----------------------|-------|
| Archivos nuevos      | ~10   |
| Archivos modificados | ~6    |
| Modulos tocados      | 5     |

---

## Referencia

Los archivos de pattern de la vertical de empleabilidad sirven como blueprints arquitectonicos para la implementacion de cualquier nueva vertical. Al iniciar la paridad de una vertical, el primer paso es identificar los servicios equivalentes en empleabilidad, mapear sus dimensiones/reglas al nuevo contexto, y replicar la estructura manteniendo la interfaz del ecosistema.
