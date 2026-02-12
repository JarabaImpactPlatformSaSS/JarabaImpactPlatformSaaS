
RECOMMENDATION SYSTEM
Motor de Recomendaciones
Cursos, Jobs, Contenido Personalizado
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	21_Empleabilidad_Recommendation_System
Dependencias:	19_Matching_Engine, 08_LMS, 11_Job_Board
 
1. Resumen Ejecutivo
El Recommendation System es un motor unificado que genera recomendaciones personalizadas de múltiples tipos de contenido: cursos, ofertas de empleo, recursos, y acciones. A diferencia del Matching Engine (job-candidate específico), este sistema abarca todo el ecosistema con enfoque en engagement y progresión del usuario.
1.1 Tipos de Recomendaciones
Tipo	Descripción	Ubicación
Next Course	Siguiente curso recomendado en la ruta de aprendizaje	Dashboard, LMS
Skill Gap Courses	Cursos para cerrar gaps identificados en diagnóstico	Dashboard, Perfil
Jobs For You	Ofertas compatibles con perfil actual	Dashboard, Job Board
Similar Jobs	Ofertas similares a una vista/guardada	Job detail page
Profile Actions	Acciones para mejorar perfil	Dashboard, Perfil
Content Resources	Artículos, guías, templates relevantes	Contextual
Companies to Follow	Empresas afines a intereses del usuario	Dashboard, Job Board
1.2 Enfoques de Recomendación
•	Content-Based: Basado en atributos del contenido y perfil del usuario
•	Collaborative Filtering: Basado en comportamiento de usuarios similares
•	Knowledge-Based: Reglas de negocio del programa Impulso Empleo
•	Hybrid: Combinación ponderada de los anteriores
 
2. Arquitectura del Sistema
2.1 Componentes
Componente	Función
User Profile Analyzer	Analiza perfil, gaps, historial, comportamiento del usuario
Content Indexer	Mantiene índice de cursos, jobs, recursos con embeddings
Similarity Engine	Calcula similitud user-content y content-content
Collaborative Engine	Analiza patrones de usuarios similares
Rule Engine	Aplica reglas de negocio del programa
Ranking Service	Combina scores y ordena resultados finales
Cache Layer	Redis para recomendaciones pre-computadas
Feedback Collector	Recoge feedback explícito e implícito
2.2 Pipeline de Recomendación
1.	Context Loading: Cargar perfil completo del usuario, historial, estado actual
2.	Candidate Generation: Generar pool de candidatos (broad retrieval)
3.	Content-Based Scoring: Score basado en atributos y embeddings
4.	Collaborative Scoring: Score basado en usuarios similares
5.	Rule Application: Aplicar reglas de negocio (boosts, filters)
6.	Score Fusion: Combinar scores con pesos configurables
7.	Diversity Injection: Asegurar variedad en resultados
8.	Ranking & Truncation: Ordenar y limitar a top-N
9.	Explanation Generation: Generar explicación para el usuario
 
3. Algoritmos de Recomendación
3.1 Next Course Algorithm
def recommend_next_course(user):     # 1. Determinar estado actual     current_path = user.active_learning_path     completed_courses = user.completed_courses          # 2. Si está en una path, seguir secuencia     if current_path:         next_in_path = current_path.get_next_uncompleted_course()         if next_in_path:             return next_in_path          # 3. Basado en gaps del diagnóstico     gaps = user.diagnostic.primary_gap     gap_courses = Course.filter(addresses_gap=gaps, not_in=completed_courses)          # 4. Ordenar por:     #    - Relevancia al gap (40%)     #    - Popularidad entre usuarios similares (30%)     #    - Nivel apropiado al usuario (20%)     #    - Duración (preferir cortos) (10%)          scores = []     for course in gap_courses:         score = (             gap_relevance(course, gaps) * 0.4 +             collaborative_score(course, user) * 0.3 +             level_fit(course, user) * 0.2 +             duration_preference(course) * 0.1         )         scores.append((course, score))          return sorted(scores, key=lambda x: x[1], reverse=True)[:5]
3.2 Jobs For You Algorithm
Utiliza el Matching Engine (19) con filtros adicionales:
•	Excluir jobs ya aplicados o descartados
•	Boost a jobs de empresas seguidas
•	Boost a jobs similares a guardados
•	Penalizar jobs con muchas aplicaciones (saturación)
•	Diversity: máximo 2 jobs de la misma empresa
3.3 Profile Actions Algorithm
Reglas de negocio para acciones de mejora de perfil:
Condición	Acción Recomendada	Prioridad
photo_url IS NULL	Añadir foto de perfil	Alta
summary.length < 100	Completar resumen profesional	Alta
work_experiences.count = 0	Añadir experiencia laboral	Alta
skills.count < 5	Añadir más competencias	Media
cv_documents.count = 0	Generar CV	Media
linkedin_url IS NULL	Conectar LinkedIn	Baja
 
4. Entidades de Soporte
4.1 Entidad: recommendation_log
Registro de recomendaciones servidas para análisis y feedback:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario	FK users.uid, NOT NULL, INDEX
recommendation_type	VARCHAR(32)	Tipo de recomendación	ENUM: course|job|action|resource|company
context	VARCHAR(64)	Dónde se mostró	dashboard|lms|job_board|profile|email
items_shown	JSON	IDs de items mostrados	Array con scores
algorithm_version	VARCHAR(16)	Versión del algoritmo	NOT NULL
computation_time_ms	INT	Tiempo de cálculo	Milliseconds
served_at	DATETIME	Timestamp	NOT NULL, UTC, INDEX
4.2 Entidad: recommendation_feedback
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
log_id	INT	Recomendación	FK recommendation_log.id
item_id	INT	Item específico	NOT NULL
item_type	VARCHAR(32)	Tipo de item	course|job|etc
feedback_type	VARCHAR(16)	Tipo de feedback	ENUM: click|enroll|apply|dismiss|save
is_positive	BOOLEAN	Feedback positivo	click, enroll, apply = TRUE
created_at	DATETIME	Timestamp	NOT NULL, UTC
4.3 Entidad: user_similarity
Cache de usuarios similares (pre-computado diariamente):
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
user_id	INT	Usuario base	FK users.uid, NOT NULL
similar_user_id	INT	Usuario similar	FK users.uid, NOT NULL
similarity_score	DECIMAL(4,3)	Score 0-1	NOT NULL
similarity_factors	JSON	Factores de similitud	skills, behavior, profile_type
computed_at	DATETIME	Fecha de cálculo	NOT NULL
 
5. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/recommendations/courses	Cursos recomendados
GET	/api/v1/recommendations/courses/next	Siguiente curso recomendado
GET	/api/v1/recommendations/jobs	Jobs recomendados
GET	/api/v1/recommendations/jobs/{id}/similar	Jobs similares a uno dado
GET	/api/v1/recommendations/actions	Acciones de perfil recomendadas
GET	/api/v1/recommendations/companies	Empresas para seguir
GET	/api/v1/recommendations/feed	Feed combinado para dashboard
POST	/api/v1/recommendations/feedback	Enviar feedback sobre recomendación
POST	/api/v1/recommendations/{id}/dismiss	Descartar recomendación
6. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Entidades log, feedback. Pipeline base. API estructura.	Matching
Sprint 2	Semana 3-4	Next Course algorithm. Profile Actions rules. Content-based.	Sprint 1
Sprint 3	Semana 5-6	Jobs For You integration. Similar items. Collaborative filtering.	Sprint 2
Sprint 4	Semana 7-8	Feed unificado. Explanation generation. Caching.	Sprint 3
Sprint 5	Semana 9-10	Feedback loop. A/B testing. Analytics. QA. Go-live.	Sprint 4
— Fin del Documento —
21_Empleabilidad_Recommendation_System_v1.docx | Jaraba Impact Platform | Enero 2026
