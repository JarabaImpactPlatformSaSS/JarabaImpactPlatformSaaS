166
ESPECIFICACIÓN TÉCNICA
Internacionalización (i18n)
ES | CA | EU | GL | EN + Traducción IA
Ecosistema Jaraba | EDI Google Antigravity
Versión:	1.0.0
Fecha:	26 Enero 2026
Horas:	60-80h
 
1. Resumen Ejecutivo
Sistema de internacionalización para el Page Builder con soporte para español, catalán, euskera, gallego e inglés, incluyendo traducción automática con IA.
Idiomas Soportados
ES (Español) - Default | CA (Català) | EU (Euskara) | GL (Galego) | EN (English)

1.1 Objetivos
•	Contenido traducible campo por campo en Form Builder
•	UI del constructor en todos los idiomas
•	Workflow de traducción: Draft → Review → Published
•	Hreflang automático para SEO multi-idioma
•	Traducción automática con Claude API
 
2. Esquema de Base de Datos
 i18n-schema.sql
CREATE TABLE tenant_languages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tenant_id INT UNSIGNED NOT NULL,
  language_code VARCHAR(5) NOT NULL,
  is_default BOOLEAN DEFAULT FALSE,
  is_enabled BOOLEAN DEFAULT TRUE,
  translation_progress DECIMAL(5,2) DEFAULT 0.00,
  UNIQUE KEY (tenant_id, language_code),
  FOREIGN KEY (tenant_id) REFERENCES groups_field_data(id)
);
 
CREATE TABLE page_content_translations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_id INT UNSIGNED NOT NULL,
  language_code VARCHAR(5) NOT NULL,
  content_data JSON NOT NULL,
  meta_title VARCHAR(60),
  meta_description VARCHAR(160),
  path_alias VARCHAR(255) NOT NULL,
  status ENUM('draft','pending','approved','published') DEFAULT 'draft',
  UNIQUE KEY (page_id, language_code),
  FOREIGN KEY (page_id) REFERENCES page_content(id)
);

3. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/i18n/languages	Idiomas del tenant
POST	/api/v1/pages/{id}/translations/{lang}	Crear traducción
POST	/api/v1/i18n/auto-translate	Traducción IA
GET	/api/v1/i18n/progress	Progreso global
 
4. Traducción Automática con IA
 auto-translate-api.json
// POST /api/v1/i18n/auto-translate
{
  "source_language": "es",
  "target_language": "ca",
  "content": { "hero_title": "Bienvenidos", "hero_subtitle": "La mejor solución" },
  "context": "landing_page",
  "tone": "professional"
}
 
// Response
{
  "translations": { "hero_title": "Benvinguts", "hero_subtitle": "La millor solució" },
  "confidence": 0.94,
  "tokens_used": 125
}

5. Límites por Plan
Capacidad	Starter	Professional	Enterprise
Idiomas activos	1	3	Ilimitados
Traducciones IA/mes	0	100	Ilimitadas
Workflow aprobación	—	Básico	Completo
6. Roadmap
Sprint	Componente	Horas
1	Schema BD + APIs CRUD	25-30h
2	UI Form Builder multi-idioma	20-25h
3	Integración Claude API + Hreflang	15-20h
Total: 60-80 horas (€4,800-€6,400)
Fin del documento.
