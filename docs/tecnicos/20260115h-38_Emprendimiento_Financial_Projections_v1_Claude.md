HERRAMIENTA DE PROYECCIONES FINANCIERAS
Financial Projections Tool
Vertical de Emprendimiento Digital
JARABA IMPACT PLATFORM

Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	38_Emprendimiento_Financial_Projections
Dependencias:	36_Business_Model_Canvas, 37_MVP_Validation
 
1. Resumen Ejecutivo
La Herramienta de Proyecciones Financieras permite a emprendedores crear modelos financieros simplificados pero rigurosos para validar la viabilidad económica de sus negocios. Incluye plantillas de P&L, cash flow, punto de equilibrio y escenarios múltiples, con comparativas contra benchmarks sectoriales.
1.1 Filosofía 'Sin Humo'
•	Números reales: Basados en hipótesis validadas, no en wishful thinking
•	Escenarios honestos: Pesimista, realista, optimista - todos con fundamento
•	Benchmarks verificables: Comparativas con datos reales del sector
•	Alertas de incoherencia: Sistema detecta proyecciones irrealistas
1.2 Funcionalidades Clave
Módulo	Descripción	Output
Cuenta de Resultados	P&L mensual/anual simplificada	Ingresos, costes, beneficio
Cash Flow	Flujo de caja proyectado	Entradas, salidas, saldo
Break-Even	Punto de equilibrio	Unidades/€ necesarios para cubrir costes
Escenarios	Análisis what-if	3 escenarios con variables ajustables
Unit Economics	Métricas unitarias	CAC, LTV, margen por unidad
Benchmarks	Comparativa sectorial	Tu negocio vs sector
 
2. Arquitectura de Datos
2.1 Entidad: financial_projection
Campo	Tipo	Descripción
id	Serial	PRIMARY KEY
user_id	INT	FK users.uid
canvas_id	INT	FK business_model_canvas.id (opcional)
title	VARCHAR(255)	Nombre de la proyección
business_type	VARCHAR(24)	ENUM: product|service|saas|marketplace|hybrid
sector	VARCHAR(32)	Sector para benchmarks
projection_horizon	INT	Meses proyectados (12, 24, 36)
currency	CHAR(3)	EUR por defecto
assumptions	JSON	Supuestos base documentados
revenue_model	JSON	Estructura de ingresos
cost_structure	JSON	Estructura de costes
scenarios	JSON	3 escenarios {pessimistic, realistic, optimistic}
monthly_projections	JSON	Array de proyecciones mensuales
unit_economics	JSON	Métricas unitarias calculadas
break_even	JSON	Punto de equilibrio calculado
coherence_alerts	JSON	Alertas de incoherencia detectadas
benchmark_comparison	JSON	Comparativa con sector
version	INT	Versión de la proyección
status	VARCHAR(16)	ENUM: draft|active|archived
shared_with_mentor	BOOLEAN	Compartido con mentor
created	DATETIME	Timestamp
updated	DATETIME	Última modificación
 
3. Modelos Financieros
3.1 Estructura de Revenue Model
Tipo de Negocio	Drivers de Ingreso	Métricas Clave
Producto físico	Unidades × Precio	Unidades/mes, precio medio, devoluciones %
Servicio	Horas × Tarifa	Horas facturables, tarifa/hora, ocupación %
SaaS	Usuarios × MRR	Usuarios, ARPU, churn mensual
Marketplace	GMV × Comisión	Transacciones, valor medio, take rate %
Suscripción	Suscriptores × Precio	Suscriptores, precio, retención
3.2 Estructura de Costes
Categoría	Ejemplos	Tipo
COGS	Materiales, producción, envío	Variable
Personal	Salarios, SS, freelancers	Semifijo
Marketing	Ads, contenido, eventos	Variable
Tecnología	Hosting, software, herramientas	Fijo
Espacio	Alquiler, suministros, seguros	Fijo
Otros	Asesoría, formación, viajes	Variable
3.3 Cálculo de Break-Even
Break-Even (unidades) = Costes Fijos / (Precio - Coste Variable Unitario)
Break-Even (€) = Costes Fijos / (1 - (Costes Variables / Ingresos))
 
4. Sistema de Escenarios
4.1 Definición de Escenarios
Escenario	Modificador	Supuestos
Pesimista	70% del realista	Crecimiento lento, más churn, costes +20%
Realista	Base	Hipótesis validadas, datos de mercado
Optimista	130% del realista	Crecimiento acelerado, menor CAC, viral
4.2 Variables Ajustables
•	Tasa de crecimiento mensual: % de incremento de clientes/ventas mes a mes
•	Precio medio: Variación del ticket medio
•	Tasa de conversión: % de leads que se convierten en clientes
•	Churn/Retención: % de clientes que se pierden mensualmente
•	CAC: Coste de adquisición de cliente
 
5. Unit Economics
5.1 Métricas Calculadas
Métrica	Fórmula	Target Saludable
CAC	Gasto Marketing / Clientes Nuevos	< 1/3 del LTV
LTV	ARPU × Vida Media Cliente	> 3× CAC
LTV:CAC Ratio	LTV / CAC	> 3:1
Payback Period	CAC / (ARPU × Margen Bruto)	< 12 meses
Margen Bruto	(Ingresos - COGS) / Ingresos	> 50% servicios, > 30% producto
Margen Contribución	(Precio - Coste Variable) / Precio	> 40%
5.2 Alertas de Coherencia
El sistema detecta automáticamente proyecciones poco realistas:
Alerta	Condición	Recomendación
🔴 LTV:CAC < 1	Pierdes dinero por cliente	Revisar pricing o reducir CAC
🟠 Margen < 20%	Margen muy ajustado	Revisar estructura de costes
🟠 Crecimiento > 30%/mes	Crecimiento poco realista	Documentar cómo lo lograrás
🟡 Break-even > 24m	Tardas mucho en ser rentable	Buscar financiación o pivotar
🟡 Cash negativo	Te quedas sin dinero	Planificar ronda o préstamo
 
6. Benchmarks Sectoriales
Comparativa automática con datos del sector para contextualizar las proyecciones:
Sector	Margen Bruto Típico	LTV:CAC Típico	Payback Típico
Comercio físico	30-50%	2:1 - 4:1	6-12 meses
E-commerce	25-45%	2:1 - 3:1	3-9 meses
Servicios profesionales	50-70%	3:1 - 5:1	3-6 meses
SaaS B2B	70-85%	3:1 - 5:1	12-18 meses
SaaS B2C	60-75%	2:1 - 3:1	6-12 meses
Hostelería	60-70%	1.5:1 - 3:1	12-24 meses
Marketplace	15-25% (comisión)	4:1 - 8:1	18-36 meses
 
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/projections	Lista de proyecciones del usuario
GET	/api/v1/projections/{id}	Detalle de proyección
POST	/api/v1/projections	Crear nueva proyección
PUT	/api/v1/projections/{id}	Actualizar proyección
POST	/api/v1/projections/{id}/calculate	Recalcular métricas
GET	/api/v1/projections/{id}/scenarios	Obtener escenarios
POST	/api/v1/projections/{id}/scenarios	Guardar escenario personalizado
GET	/api/v1/projections/{id}/export	Exportar a Excel/PDF
GET	/api/v1/benchmarks/{sector}	Obtener benchmarks del sector
 
8. Roadmap de Implementación
Sprint	Timeline	Entregables
Sprint 1	Semana 1-2	Entidad financial_projection. Templates por tipo negocio.
Sprint 2	Semana 3-4	P&L y Cash Flow builders. Cálculos automáticos.
Sprint 3	Semana 5-6	Sistema de escenarios. Variables ajustables.
Sprint 4	Semana 7-8	Unit economics. Alertas de coherencia.
Sprint 5	Semana 9-10	Benchmarks. Exportación Excel/PDF. QA.
8.1 KPIs de Éxito
KPI	Target	Medición
Adopción	> 40%	% emprendedores que crean proyección
Completitud	> 60%	% proyecciones con todos los campos
Iteraciones	> 3	Versiones promedio por proyección
Uso con mentor	> 50%	% compartidas con mentor
--- Fin del Documento ---
