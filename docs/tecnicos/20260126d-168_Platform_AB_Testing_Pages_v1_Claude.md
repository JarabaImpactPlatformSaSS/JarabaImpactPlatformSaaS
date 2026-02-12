168
ESPECIFICACIÓN TÉCNICA
A/B Testing Páginas
Experimentos | Variantes | Significancia Estadística
Ecosistema Jaraba | EDI Google Antigravity
Versión:	1.0.0
Fecha:	26 Enero 2026
Horas:	50-60h
 
1. Resumen Ejecutivo
Sistema de A/B testing integrado en Page Builder para crear variantes, distribuir tráfico y medir conversiones con significancia estadística.
Capacidades
Split testing N variantes | Distribución configurable | Objetivos: click, form, scroll | Auto-promoción ganador
1.1 Tipos de Test
•	A/B simple: Control vs 1 variante
•	A/B/n: Control vs múltiples variantes
•	Multivariate: Combinar cambios de bloques
 
2. Esquema de Base de Datos
 ab-testing-schema.sql
CREATE TABLE page_experiments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  page_id INT UNSIGNED NOT NULL,
  status ENUM('draft','running','paused','completed') DEFAULT 'draft',
  goal_type ENUM('conversion','click','form_submit','scroll_depth'),
  goal_target VARCHAR(255),
  traffic_allocation DECIMAL(5,2) DEFAULT 100.00,
  confidence_threshold DECIMAL(5,2) DEFAULT 95.00,
  winner_variant_id INT UNSIGNED NULL,
  started_at TIMESTAMP NULL,
  ended_at TIMESTAMP NULL,
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id),
  FOREIGN KEY (page_id) REFERENCES page_content(id)
);
 
CREATE TABLE page_experiment_variants (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  experiment_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  is_control BOOLEAN DEFAULT FALSE,
  traffic_weight DECIMAL(5,2) DEFAULT 50.00,
  content_data JSON NOT NULL,
  visitors INT DEFAULT 0,
  conversions INT DEFAULT 0,
  FOREIGN KEY (experiment_id) REFERENCES page_experiments(id)
);

3. APIs REST
Método	Endpoint	Descripción
POST	/api/v1/experiments	Crear experimento
POST	/api/v1/experiments/{id}/start	Iniciar test
GET	/api/v1/experiments/{id}/results	Resultados + stats
POST	/api/v1/experiments/{id}/declare-winner	Declarar ganador
POST	/api/v1/experiments/events	Eventos (beacon)
 
4. Resultados API
 results-response.json
{
  "experiment_id": 456,
  "status": "running",
  "variants": [
    { "name": "Control", "visitors": 2280, "conversions": 114, "rate": 5.0, "confidence": 0 },
    { "name": "Green CTA", "visitors": 2240, "conversions": 156, "rate": 6.96, "improvement": 39.2, "confidence": 97.8, "is_winner": true }
  ],
  "recommendation": { "action": "declare_winner", "variant_id": 2 }
}

5. Cálculo Estadístico
•	Conversion Rate: CR = conversions / visitors
•	Z-Score para test de dos proporciones
•	Confidence = 1 - p-value (two-tailed)
•	Mínimo ~3,900 visitantes por variante para detectar 10% mejora
6. Límites por Plan
Capacidad	Starter	Professional	Enterprise
Experimentos activos	0	3	Ilimitados
Variantes por test	—	2	10
Auto-promoción	—	—	✓
Multivariate	—	—	✓
7. Roadmap
Sprint	Componente	Horas
1	Schema BD + APIs CRUD	20-24h
2	JS client + UI creación	20-22h
3	Dashboard resultados	12-14h
Total: 50-60 horas (€4,000-€4,800)
Fin del documento.
