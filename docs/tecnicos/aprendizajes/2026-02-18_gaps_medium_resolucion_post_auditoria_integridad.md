# Gaps Medium Resolucion Post-Auditoria Integridad

| Clave | Valor |
|-------|-------|
| Fecha | 2026-02-18 |
| Aprendizaje # | 97 |
| Contexto | Resolucion de gaps medium identificados en la auditoria de integridad de los 4 planes del 20260217 |
| Gaps resueltos | 10 test files N2 Growth Ready, 31 strict_types ServiciosConecta, CopilotApiController proactive ComercioConecta |
| Resultado | Integridad elevada: ComercioConecta 95%→100%, N2 Growth Ready 95%→100% |

---

## Patron Principal

Los gaps medium de una auditoria de integridad (tests ausentes, strict_types incompletos, rutas API faltantes) se resuelven mejor en paralelo con agentes especializados por modulo, verificando PHP lint en cada artefacto.

---

## Aprendizajes Clave

### 1. Tests N2 con Agentes Paralelos

| Campo | Detalle |
|-------|---------|
| Situacion | 5 modulos N2 Growth Ready (jaraba_funding, jaraba_multiregion, jaraba_institutional, jaraba_agents, jaraba_predictive) tenian 0 tests. Crear 10 test files secuencialmente habria sido lento |
| Aprendizaje | Lanzar 1 agente por modulo en paralelo (5 agentes simultaneos) permite crear todos los tests en ~2 minutos en lugar de ~10. Cada agente lee los servicios del modulo, identifica metodos publicos, y genera tests con el patron mock canonico |
| Regla | **TEST-N2-001**: Cada modulo N2 requiere minimo 2 test files de servicio. Usar agentes paralelos (1 por modulo) para generar tests en batch. Patron mock: EntityTypeManagerInterface + LoggerInterface + domain-specific mocks |

### 2. Patron Mock Canonico PHPUnit Drupal

| Campo | Detalle |
|-------|---------|
| Situacion | Los 10 test files necesitaban un patron consistente de mocking para servicios Drupal |
| Aprendizaje | El patron canonico para servicios Drupal 11 es: extends UnitTestCase, setUp() con parent::setUp(), createMock() para EntityTypeManagerInterface + EntityStorageInterface + QueryInterface. Para campos de entidad, usar objetos stdClass con propiedad `value` o FieldItemListInterface mocks |
| Regla | **TEST-MOCK-001**: Usar siempre el patron canonico de mock: (1) createMock(EntityTypeManagerInterface), (2) createMock(EntityStorageInterface) configurado en getStorage(), (3) createMock(QueryInterface) con willReturnSelf() para accessCheck/condition/sort/range, (4) campos como (object)['value'=>$val] o FieldItemListInterface mock |

### 3. strict_types Batch Multi-Vertical

| Campo | Detalle |
|-------|---------|
| Situacion | ServiciosConecta tenia 31/37 PHP files sin strict_types, similar al gap de ComercioConecta (177/178) resuelto anteriormente |
| Aprendizaje | El mismo script de batch fix funciona para cualquier vertical. La verificacion post-fix con `grep -rL "declare(strict_types=1)" src/ --include="*.php"` debe dar 0 resultados |
| Regla | **STRICT-BATCH-001**: Tras cada elevacion vertical, ejecutar scan strict_types en `src/` del modulo. Si hay ficheros sin strict_types, aplicar batch fix con `sed -i "1 a\\\\ndeclare(strict_types=1);"`. Verificar con grep -L que no quede ninguno |

### 4. Proactive API Pattern Replicable

| Campo | Detalle |
|-------|---------|
| Situacion | ComercioConecta tenia todas las reglas de journey progression pero no exponia el endpoint proactive API para que el frontend Copilot FAB lo consultase |
| Aprendizaje | El patron proactive API es replicable entre verticales: mismo controller structure (GET getPendingAction + POST dismissAction), misma ruta pattern (/api/v1/copilot/{vertical}/proactive), mismo servicio backend (JourneyProgressionService) |
| Regla | **COPILOT-PROACTIVE-001**: Al elevar un vertical, verificar que existe: (1) CopilotApiController con metodo proactive(), (2) ruta en routing.yml con methods [GET, POST], (3) servicio JourneyProgressionService registrado en services.yml. Si falta alguno, crearlo siguiendo el patron del vertical candidato (jaraba_candidate) |

### 5. Falsos Positivos DI en Auditorias

| Campo | Detalle |
|-------|---------|
| Situacion | La auditoria reporto "DI incompleta" en jaraba_funding pero la verificacion manual revelo que los 5 servicios tenian constructores correctos con 3 argumentos matcheando services.yml |
| Aprendizaje | Los reportes de DI incompleta de agentes de auditoria pueden ser falsos positivos si el agente no verifica el services.yml contra los constructores reales. Siempre verificar manualmente antes de "arreglar" |
| Regla | **AUDIT-DI-001**: Antes de corregir un reporte de DI incompleta, verificar manualmente: (1) grep del constructor en el servicio PHP, (2) grep del servicio en services.yml, (3) contar argumentos en ambos. Solo proceder si hay discrepancia real |

---

## Resumen de Tests Creados

| Modulo | Test File 1 | Tests | Test File 2 | Tests |
|--------|-------------|-------|-------------|-------|
| jaraba_funding | OpportunityTrackerServiceTest | 11 | BudgetAnalyzerServiceTest | 9 |
| jaraba_multiregion | TaxCalculatorServiceTest | 17 | ViesValidatorServiceTest | 12 |
| jaraba_institutional | ProgramManagerServiceTest | ~12 | StoFichaGeneratorServiceTest | ~20 |
| jaraba_agents | AgentOrchestratorServiceTest | 22 | GuardrailsEnforcerServiceTest | 24 |
| jaraba_predictive | ChurnPredictorServiceTest | 10 | LeadScorerServiceTest | 11 |
| **Total** | | | | **~148** |
