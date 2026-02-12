
MATCHING ENGINE
Motor de Matching Candidato-Oferta
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM
Documento Técnico de Implementación
Versión:	1.0
Fecha:	Enero 2026
Estado:	Especificación Técnica
Código:	19_Empleabilidad_Matching_Engine
Dependencias:	11_Job_Board, 15_Candidate_Profile, Qdrant
 
1. Resumen Ejecutivo
El Matching Engine es el sistema de inteligencia artificial que conecta candidatos con ofertas de empleo de forma bidireccional y semántica. Combina matching tradicional basado en reglas con búsqueda vectorial en Qdrant para encontrar coincidencias más allá de keywords exactas.
1.1 Tipos de Matching
Tipo	Descripción	Caso de Uso
Job → Candidates	Dado una oferta, encontrar candidatos compatibles	Employer busca talento proactivamente
Candidate → Jobs	Dado un perfil, recomendar ofertas relevantes	Feed personalizado para candidato
Application Score	Calcular compatibilidad de una aplicación específica	Ranking de candidatos en ATS
Similar Jobs	Encontrar ofertas similares a una dada	'También te puede interesar'
Similar Candidates	Encontrar perfiles similares a uno dado	Recomendaciones de sourcing
1.2 Arquitectura Híbrida
El engine combina dos enfoques complementarios:
•	Rule-Based Matching: Filtros duros (ubicación, salario, tipo contrato) y scoring de atributos estructurados
•	Semantic Matching: Embeddings vectoriales en Qdrant para similitud de texto libre (descripciones, resúmenes)
 
2. Arquitectura Técnica
2.1 Stack Tecnológico
Componente	Tecnología
Vector Database	Qdrant Cloud con namespaces por tenant
Embedding Model	text-embedding-3-small (OpenAI) o multilingual-e5-large (open source)
Scoring Engine	PHP custom con pesos configurables
Cache Layer	Redis para resultados frecuentes y embeddings recientes
Queue System	Drupal Queue + Redis para indexación async
API Layer	REST endpoints en módulo jaraba_matching
2.2 Esquema de Vectores en Qdrant
Se mantienen dos colecciones principales con namespace por tenant:
Colección: job_vectors
Campo	Tipo	Descripción	Restricciones
id	UUID	job_posting.uuid	PRIMARY KEY
vector	FLOAT[1536]	Embedding del job	Concatenación de campos
tenant_id	INT	Para filtering	Payload field
vertical_id	INT	Vertical de negocio	Payload field
status	STRING	Estado de la oferta	Payload: published|closed
location_city	STRING	Ciudad	Payload field
job_type	STRING	Tipo de contrato	Payload field
experience_level	STRING	Nivel experiencia	Payload field
salary_min	FLOAT	Salario mínimo	Payload field
salary_max	FLOAT	Salario máximo	Payload field
skills	INT[]	IDs de skills	Payload array
published_at	DATETIME	Fecha publicación	Payload field
Colección: candidate_vectors
Campo	Tipo	Descripción	Restricciones
id	UUID	candidate_profile.uuid	PRIMARY KEY
vector	FLOAT[1536]	Embedding del perfil	Concatenación de campos
tenant_id	INT	Tenant principal	Payload field
job_search_status	STRING	Estado de búsqueda	Payload: active|passive|not_looking
location_city	STRING	Ciudad	Payload field
willing_to_relocate	BOOL	Dispuesto mudarse	Payload field
remote_preference	STRING	Preferencia remoto	Payload field
experience_years	FLOAT	Años experiencia	Payload field
desired_salary_min	FLOAT	Salario mínimo deseado	Payload field
skills	INT[]	IDs de skills	Payload array
completeness_score	INT	Score de perfil	Payload field
last_updated	DATETIME	Última actualización	Payload field
 
3. Algoritmo de Matching
3.1 Pipeline de Cálculo
1.	Pre-filtering: Aplicar filtros duros (ubicación, salario, tipo) para reducir candidatos
2.	Vector Search: Buscar top-K candidatos por similitud semántica en Qdrant
3.	Rule Scoring: Calcular score estructurado para cada candidato filtrado
4.	Score Fusion: Combinar scores semántico y estructurado con pesos configurables
5.	Ranking: Ordenar por score final, aplicar diversidad si configurado
6.	Caching: Almacenar resultado en Redis con TTL configurable
3.2 Fórmula de Score Final
final_score = (semantic_score × α) + (rule_score × β) + (boost_score × γ)
Donde α + β + γ = 1.0 (configurable por vertical/tenant)
3.3 Componentes del Rule Score
Factor	Peso	Cálculo
Skills Match	35%	|skills_candidato ∩ skills_required| / |skills_required| × 100
Skills Preferred	10%	|skills_candidato ∩ skills_preferred| / |skills_preferred| × 100
Experience Fit	20%	Gaussian decay desde experience_level óptimo (σ = 2 años)
Location Match	15%	100 si match exacto, decay por distancia, bonus si remote_ok
Salary Alignment	15%	Overlap entre rangos: max(0, min(max_job, max_cand) - max(min_job, min_cand))
Availability	5%	100 si immediate, decay por semanas de espera
 
4. Factores de Boost
Los boost factors ajustan el score final basándose en señales adicionales del ecosistema.
Boost Factor	Valor	Condición
Ecosystem Graduate	+15%	Candidato completó learning path en LMS
Verified Credentials	+10%	Skills verificados por certificaciones del ecosistema
Profile Completeness	+5%	completeness_score >= 80
Active Job Seeker	+5%	job_search_status = 'active'
Recent Activity	+3%	last_activity < 7 días
Ecosystem Employer	+10%	Empleador es PYME del ecosistema (employer.is_ecosystem_member)
Featured Job	+20%	job_posting.is_featured = TRUE
Referral Bonus	+8%	Candidato referido por empleado de la empresa
 
5. Entidades de Soporte
5.1 Entidad: match_result
Cache de resultados de matching para evitar recálculos frecuentes:
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
match_type	VARCHAR(32)	Tipo de match	ENUM: job_to_candidates|candidate_to_jobs|application
source_type	VARCHAR(16)	Tipo de origen	ENUM: job|candidate
source_id	INT	ID del origen	FK según source_type
target_type	VARCHAR(16)	Tipo de destino	ENUM: job|candidate
target_id	INT	ID del destino	FK según target_type
final_score	DECIMAL(5,2)	Score final combinado	RANGE 0-100
semantic_score	DECIMAL(5,2)	Score semántico	RANGE 0-100
rule_score	DECIMAL(5,2)	Score de reglas	RANGE 0-100
boost_score	DECIMAL(5,2)	Score de boost	RANGE 0-100
score_breakdown	JSON	Desglose detallado	Cada factor con su valor
rank_position	INT	Posición en ranking	>= 1
is_stale	BOOLEAN	Resultado caducado	DEFAULT FALSE
computed_at	DATETIME	Fecha de cálculo	NOT NULL, UTC
expires_at	DATETIME	Fecha de expiración	NOT NULL, UTC, INDEX
5.2 Entidad: match_feedback
Feedback de usuarios para mejorar el algoritmo (reinforcement learning):
Campo	Tipo	Descripción	Restricciones
id	Serial	ID interno	PRIMARY KEY
match_result_id	INT	Resultado evaluado	FK match_result.id
user_id	INT	Usuario que da feedback	FK users.uid
user_role	VARCHAR(16)	Rol del usuario	ENUM: candidate|employer
feedback_type	VARCHAR(16)	Tipo de feedback	ENUM: relevant|not_relevant|applied|hired|rejected
feedback_reason	VARCHAR(64)	Motivo si negativo	NULLABLE
implicit	BOOLEAN	Feedback implícito	DEFAULT FALSE (click, apply)
created_at	DATETIME	Fecha de feedback	NOT NULL, UTC
 
6. Generación de Embeddings
6.1 Texto para Embedding de Job
El embedding de una oferta se genera concatenando campos relevantes:
embedding_text = f""" Job Title: {job.title} Company: {employer.company_name} ({employer.industry}) Location: {job.location_city}, {job.remote_type} Type: {job.job_type}, {job.experience_level}  Description: {job.description}  Requirements: {job.requirements}  Skills Required: {', '.join(job.skills_required)} Skills Preferred: {', '.join(job.skills_preferred)} """
6.2 Texto para Embedding de Candidato
embedding_text = f""" Professional: {profile.headline} Location: {profile.location_city}, {profile.remote_preference} Experience: {total_years} years  Summary: {profile.summary}  Recent Experience: {format_experiences(profile.work_experiences[:3])}  Skills: {', '.join(profile.skills)} Languages: {', '.join(profile.languages)} Certifications: {', '.join(profile.certifications)} """
6.3 Trigger de Re-indexación
Los embeddings se regeneran automáticamente cuando:
•	job_posting: Cualquier cambio en title, description, requirements, skills
•	candidate_profile: Cambio en headline, summary, experiences, skills
•	Batch re-index: Cron semanal para consistency check
 
7. APIs REST
Método	Endpoint	Descripción
GET	/api/v1/match/jobs/{job_id}/candidates	Candidatos compatibles para una oferta
GET	/api/v1/match/candidates/{profile_id}/jobs	Ofertas recomendadas para un candidato
GET	/api/v1/match/score	Calcular score entre job y candidate específicos
GET	/api/v1/match/jobs/{job_id}/similar	Ofertas similares a una dada
GET	/api/v1/match/candidates/{profile_id}/similar	Candidatos similares a uno dado
POST	/api/v1/match/feedback	Enviar feedback sobre un match
POST	/api/v1/match/reindex/job/{job_id}	Forzar re-indexación de una oferta
POST	/api/v1/match/reindex/candidate/{profile_id}	Forzar re-indexación de un candidato
8. Flujos de Automatización (ECA)
8.1 ECA-MATCH-001: Indexación de Nueva Oferta
Trigger: job_posting.status = 'published'
7.	Generar embedding_text concatenando campos
8.	Llamar a embedding API (OpenAI/local)
9.	Upsert en Qdrant collection job_vectors
10.	Invalidar cache de matches relacionados
11.	Pre-calcular top-50 candidatos y cachear
8.2 ECA-MATCH-002: Feedback Loop
Trigger: job_application.status = 'hired' OR 'rejected'
12.	Crear match_feedback con tipo hired/rejected
13.	Actualizar estadísticas de accuracy del modelo
14.	Si acumulados > 100 feedbacks: trigger retraining flag
9. Roadmap de Implementación
Sprint	Timeline	Entregables	Deps
Sprint 1	Semana 1-2	Setup Qdrant. Colecciones. Entidades match_result, match_feedback.	Job Board
Sprint 2	Semana 3-4	Pipeline de embeddings. Indexación inicial de jobs y candidates.	Sprint 1
Sprint 3	Semana 5-6	Algoritmo de scoring. Rule engine. Boost factors.	Sprint 2
Sprint 4	Semana 7-8	APIs REST. Integración con Job Board y Candidate Portal.	Sprint 3
Sprint 5	Semana 9-10	Feedback loop. Flujos ECA. Caching Redis. QA. Go-live.	Sprint 4
— Fin del Documento —
19_Empleabilidad_Matching_Engine_v1.docx | Jaraba Impact Platform | Enero 2026
