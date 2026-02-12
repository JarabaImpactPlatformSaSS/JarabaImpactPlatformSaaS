169
ESPECIFICACIÓN TÉCNICA
Versionado de Páginas
Historial | Rollback | Scheduled | Workflow
Ecosistema Jaraba | 26 Enero 2026 | 30-40h
 
1. Resumen
Control de versiones para páginas con historial automático, diff, rollback y publicación programada.
Capacidades
Historial auto | Diff visual | Rollback | Scheduled publish | Workflow aprobación
1.1 Objetivos
•	Historial automático de content_data
•	Diff visual entre versiones
•	Rollback instantáneo
•	Publicación programada
•	Workflow: Draft → Review → Published
 
2. Esquema BD
 versioning-schema.sql
CREATE TABLE page_content_revisions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page_id INT NOT NULL,
  revision_number INT NOT NULL,
  content_data JSON NOT NULL,
  change_summary VARCHAR(255),
  created_by INT NOT NULL,
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY (page_id, revision_number)
);
CREATE TABLE page_scheduled_publish (
  id INT AUTO_INCREMENT PRIMARY KEY,
  page_id INT NOT NULL,
  revision_id INT NOT NULL,
  scheduled_at TIMESTAMP NOT NULL,
  status ENUM('pending','published','cancelled') DEFAULT 'pending'
);

3. APIs
Método	Endpoint	Descripción
GET	/api/v1/pages/{id}/revisions	Lista revisiones
GET	/api/v1/pages/{id}/diff/{r1}/{r2}	Comparar
POST	/api/v1/pages/{id}/rollback/{rev}	Rollback
POST	/api/v1/pages/{id}/schedule	Programar
4. Límites Plan
Capacidad	Starter	Professional	Enterprise
Revisiones	5	20	Ilimitadas
Scheduled	—	✓	✓
Workflow	—	Básico	Completo
5. Roadmap
Sprint	Componente	Horas
1	Schema + Auto-save	15-18h
2	UI + Diff + Workflow	15-20h
Total: 30-40h (€2,400-€3,200)
Fin documento.
