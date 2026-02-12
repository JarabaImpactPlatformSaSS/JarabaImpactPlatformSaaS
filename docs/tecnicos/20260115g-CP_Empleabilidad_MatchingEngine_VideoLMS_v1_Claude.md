CONTRAPROPUESTA TÉCNICA
Componentes Descartados: Viabilidad sobre Infraestructura Existente
Vertical de Empleabilidad Digital
JARABA IMPACT PLATFORM

Campo	Valor
Versión:	1.0
Fecha:	Enero 2026
Estado:	Contrapropuesta Técnica
Código:	CP_Empleabilidad_MatchingEngine_VideoLMS
Contexto:	Respuesta a descarte EDI Google Antigravity
 
1. Resumen Ejecutivo
Este documento presenta una contrapropuesta técnica fundamentada ante la decisión del EDI Google Antigravity de descartar tres componentes del vertical de Empleabilidad. Tras análisis de la documentación del proyecto, se concluye que dos de los tres componentes descartados son viables para implementación inmediata.

CONCLUSIÓN PRINCIPAL
Qdrant ya es infraestructura base del SaaS. El argumento 'requiere Qdrant' es inválido para el Matching Engine. El argumento 'requiere hosting video' ignora alternativas de coste cero.
1.1 Matriz de Evaluación
Componente	Argumento Descarte	Evaluación	Recomendación
Matching Engine	Requiere Qdrant	INVÁLIDO - Qdrant existe	INCLUIR en Fase 1
Video Player LMS	Requiere hosting video	PARCIAL - Hay alternativas €0	INCLUIR con YouTube
Agente RPA Postulación	Requiere análisis RPA	VÁLIDO - Alta complejidad	POSPONER a Fase 3+
1.2 Evidencia Documental
La infraestructura Qdrant está documentada y especificada en múltiples documentos del proyecto:
•	20260110h-KB_AI_Nativa_MultiTenant_v2: Qdrant como opción Vector DB (~$25/mes)
•	20260111b-Anexo_A1_Integración_Qdrant_Seguro_v3: Checklist completo de seguridad y multi-tenancy
•	20_Empleabilidad_AI_Copilot_v1: Qdrant como Vector Store del pipeline RAG
•	44_Emprendimiento_AI_Business_Copilot_v1: Mismo stack con Qdrant
 
2. Matching Engine: Especificación de Implementación
2.1 Análisis de Dependencias Reales
El Matching Engine documentado en 19_Empleabilidad_Matching_Engine_v1 requiere los siguientes componentes. Análisis de disponibilidad:
Componente	Estado Actual	Esfuerzo Adicional	Bloqueante
Qdrant Cloud	✅ Ya especificado en KB AI	0 - Configuración	NO
Embedding Model (OpenAI)	✅ Ya usado en AI Copilot	0 - Reutilizar	NO
Redis Cache	✅ Arquitectura base SaaS	0 - Disponible	NO
Colección job_vectors	❌ Por crear	Bajo - Schema YAML	NO
Colección candidate_vectors	❌ Por crear	Bajo - Schema YAML	NO
Pipeline embeddings	⚠️ Parcial (KB existe)	Medio - Adaptar	NO
Scoring Engine PHP	❌ Por desarrollar	Medio - 2 sprints	NO
APIs REST matching	❌ Por desarrollar	Medio - 2 sprints	NO

ANÁLISIS DE VIABILIDAD
0 componentes bloqueantes. La infraestructura vectorial ya existe. El Matching Engine es funcionalidad sobre infraestructura existente, NO infraestructura nueva.
2.2 Arquitectura de Colecciones Qdrant
Las colecciones se crean con el patrón de namespacing multi-tenant ya establecido:
2.2.1 Colección: job_vectors
namespace_pattern: empleabilidad_tenant_{tenant_id}_jobs
Campo	Tipo	Descripción	Uso en Matching
id	UUID	job_posting.uuid	Identificador único
vector	FLOAT[1536]	Embedding del job	Similitud semántica
tenant_id	INT	Filtro multi-tenant	Aislamiento obligatorio
status	STRING	published|closed	Pre-filtro activos
location_city	STRING	Ciudad oferta	Filtro geográfico
job_type	STRING	full_time|part_time|...	Filtro tipo contrato
experience_level	STRING	junior|mid|senior	Filtro experiencia
salary_min	FLOAT	Salario mínimo	Filtro rango salarial
salary_max	FLOAT	Salario máximo	Filtro rango salarial
skills	INT[]	IDs de skills requeridas	Match por competencias
remote_type	STRING	onsite|hybrid|remote	Filtro modalidad
2.2.2 Colección: candidate_vectors
namespace_pattern: empleabilidad_tenant_{tenant_id}_candidates
Campo	Tipo	Descripción	Uso en Matching
id	UUID	candidate_profile.uuid	Identificador único
vector	FLOAT[1536]	Embedding del perfil	Similitud semántica
tenant_id	INT	Filtro multi-tenant	Aislamiento obligatorio
job_search_status	STRING	active|passive|not_looking	Pre-filtro búsqueda
location_city	STRING	Ciudad candidato	Filtro geográfico
willing_to_relocate	BOOL	Dispuesto mudarse	Ampliar búsqueda
remote_preference	STRING	onsite|hybrid|remote	Filtro modalidad
experience_years	FLOAT	Años experiencia total	Filtro senioridad
desired_salary_min	FLOAT	Expectativa salarial	Match salarial
skills	INT[]	IDs de skills	Match por competencias
completeness_score	INT	Score perfil 0-100	Boost perfiles completos
 
2.3 Implementación del Rule-Based Matching (Fase 1)
Para MVP, se implementa primero el matching basado en reglas que no requiere búsqueda vectorial. Esto permite valor inmediato mientras se afina el matching semántico.
2.3.1 Fórmula de Score Estructurado
rule_score = (skills_match × 0.35) + (experience_fit × 0.20) + (location_match × 0.15) + (salary_alignment × 0.15) + (skills_preferred × 0.10) + (availability × 0.05)
Factor	Peso	Cálculo	Rango
Skills Match	35%	|skills_candidato ∩ skills_required| / |skills_required| × 100	0-100
Experience Fit	20%	Gaussian decay desde experience_level óptimo (σ = 2 años)	0-100
Location Match	15%	100 si match exacto, decay por distancia, bonus si remote_ok	0-100
Salary Alignment	15%	Overlap entre rangos salariales normalizado	0-100
Skills Preferred	10%	|skills_candidato ∩ skills_preferred| / |skills_preferred| × 100	0-100
Availability	5%	100 si immediate, decay por semanas de espera	0-100
2.3.2 Servicio PHP: MatchingService.php
Ubicación: /modules/custom/jaraba_matching/src/Service/MatchingService.php
<?php namespace Drupal\jaraba_matching\Service;  class MatchingService {      public function calculateRuleScore(array $job, array $candidate): float {     $skillsMatch = $this->calculateSkillsMatch(       $job['skills_required'],        $candidate['skills']     );     $experienceFit = $this->calculateExperienceFit(       $job['experience_level'],        $candidate['experience_years']     );     $locationMatch = $this->calculateLocationMatch(       $job['location_city'],        $job['remote_type'],       $candidate['location_city'],        $candidate['willing_to_relocate']     );     $salaryAlignment = $this->calculateSalaryAlignment(       $job['salary_min'],        $job['salary_max'],       $candidate['desired_salary_min'],        $candidate['desired_salary_max']     );          return ($skillsMatch * 0.35)           + ($experienceFit * 0.20)           + ($locationMatch * 0.15)           + ($salaryAlignment * 0.15)          + ($skillsPreferred * 0.10)           + ($availability * 0.05);   }      private function calculateSkillsMatch(array $required, array $candidate): float {     if (empty($required)) return 100.0;     $intersection = array_intersect($required, $candidate);     return (count($intersection) / count($required)) * 100;   } }
 
2.4 Implementación del Semantic Matching (Fase 2)
Una vez validado el Rule-Based, se añade el matching semántico reutilizando la infraestructura de embeddings del AI Copilot.
2.4.1 Pipeline de Embeddings
Reutilizar el servicio de embeddings existente en jaraba_ai_core:
// Texto para embedding de Job (concatenación de campos relevantes) $embedding_text = sprintf(   "Job Title: %s\nCompany: %s (%s)\nLocation: %s, %s\n" .   "Type: %s, %s\nDescription: %s\nRequirements: %s\n" .   "Skills Required: %s\nSkills Preferred: %s",   $job->title, $employer->company_name, $employer->industry,   $job->location_city, $job->remote_type,   $job->job_type, $job->experience_level,   $job->description, $job->requirements,   implode(', ', $job->skills_required),   implode(', ', $job->skills_preferred) );  // Llamar al servicio de embeddings existente $vector = $this->embeddingService->generate($embedding_text);  // Upsert en Qdrant $this->qdrantClient->upsert('job_vectors', [   'id' => $job->uuid,   'vector' => $vector,   'payload' => [     'tenant_id' => $job->tenant_id,     'status' => $job->status,     'location_city' => $job->location_city,     // ... resto de payload fields   ] ]);
2.4.2 Fórmula de Score Híbrido
final_score = (semantic_score × α) + (rule_score × β) + (boost_score × γ)
Donde α + β + γ = 1.0 | Configuración inicial: α=0.4, β=0.5, γ=0.1
Componente	Peso Inicial	Descripción	Ajustable
semantic_score (α)	0.40	Cosine similarity de embeddings en Qdrant	Por tenant
rule_score (β)	0.50	Score estructurado calculado por reglas	Por tenant
boost_score (γ)	0.10	Bonificaciones ecosistema (certificaciones, etc)	Por vertical
2.5 APIs REST del Matching Engine
Método	Endpoint	Descripción
GET	/api/v1/match/jobs/{job_id}/candidates	Top-N candidatos para una oferta
GET	/api/v1/match/candidates/{profile_id}/jobs	Ofertas recomendadas para candidato
GET	/api/v1/match/score?job={id}&candidate={id}	Score específico job-candidate
GET	/api/v1/match/jobs/{job_id}/similar	Ofertas similares (para 'También te puede interesar')
POST	/api/v1/match/feedback	Feedback sobre match (hired/rejected)
POST	/api/v1/match/reindex/job/{job_id}	Forzar re-indexación de oferta
 
2.6 Flujos ECA de Automatización
ECA-MATCH-001: Indexación de Nueva Oferta
Trigger: job_posting.status cambia a 'published'
•	1. Generar embedding_text concatenando campos del job
•	2. Llamar a embedding API (text-embedding-3-small)
•	3. Upsert en Qdrant colección job_vectors con tenant_id
•	4. Invalidar cache Redis de matches relacionados
•	5. Pre-calcular top-50 candidatos y cachear (background job)
ECA-MATCH-002: Actualización de Perfil
Trigger: candidate_profile actualizado (campos relevantes)
•	1. Verificar si cambió headline, summary, skills o experiencias
•	2. Si cambió: regenerar embedding del perfil
•	3. Upsert en Qdrant colección candidate_vectors
•	4. Invalidar cache de recomendaciones del candidato
•	5. Recalcular 'Jobs For You' en background
ECA-MATCH-003: Feedback Loop
Trigger: job_application.status cambia a 'hired' o 'rejected'
•	1. Crear registro match_feedback con outcome y scores
•	2. Actualizar estadísticas de accuracy del modelo
•	3. Si acumulados > 100 feedbacks: marcar para retraining
•	4. Ajustar pesos de boosting si hay patrón claro
 
2.7 Roadmap de Implementación Matching Engine
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semanas 1-2	Colecciones Qdrant. Entidades match_result, match_feedback. Schema de vectores.	Job Board, Candidate Profile
Sprint 2	Semanas 3-4	MatchingService PHP. Rule-Based scoring. APIs básicas GET.	Sprint 1
Sprint 3	Semanas 5-6	Pipeline embeddings. Integración con servicio AI existente. Indexación inicial.	Sprint 2
Sprint 4	Semanas 7-8	Score híbrido. Boost factors. Integración UI (Jobs For You widget).	Sprint 3
Sprint 5	Semanas 9-10	Feedback loop. Flujos ECA. Caching Redis. QA. Go-live.	Sprint 4

ESTIMACIÓN DE ESFUERZO
10 semanas (5 sprints) con 1 desarrollador senior. El 60% del trabajo es integración con componentes existentes. No hay desarrollo de infraestructura nueva.
 
3. Video Player LMS: Especificación de Implementación
3.1 Análisis del Argumento de Descarte
El argumento 'requiere hosting video' asume que es necesaria infraestructura costosa. Análisis de alternativas:
Solución	Coste Mensual	Complejidad	Tracking xAPI	Limitaciones
YouTube Unlisted	€0	Cero	Sí (via H5P)	Branding YouTube, ads posibles
Vimeo Basic	€7/mes	Muy baja	Sí (via H5P)	Almacenamiento limitado
Bunny.net Stream	~€5-20/mes	Baja	Sí (via H5P)	Configuración inicial
Vimeo OTT	€500+/mes	Media	Nativo	Overengineering para MVP
AWS MediaConvert	Variable	Alta	Custom	Complejidad innecesaria

RECOMENDACIÓN
Implementar con YouTube Unlisted para MVP (coste €0). El tracking xAPI funciona igualmente porque H5P Interactive Video captura los eventos de reproducción. Migrar a Bunny.net cuando haya volumen significativo.
3.2 Arquitectura de Video en LMS
El documento 08_Empleabilidad_LMS_Core_v1 ya especifica H5P como motor de contenido interactivo. El video se integra como un tipo de actividad dentro de H5P.
3.2.1 Entidad: activity (campo video)
Campo	Tipo	Descripción	Valores
activity_type	VARCHAR(32)	Tipo de actividad	video|reading|h5p|quiz|...
video_url	VARCHAR(512)	URL del video	YouTube/Vimeo/Bunny URL
video_provider	VARCHAR(32)	Proveedor de video	youtube|vimeo|bunny|self_hosted
video_duration	INT	Duración en segundos	Calculado automáticamente
h5p_content_id	INT	ID contenido H5P	FK a h5p_content si es interactivo
3.2.2 Flujo de Contenido de Video
1. Administrador sube video a YouTube (unlisted) o Bunny.net
2. Crea actividad tipo 'video' con URL del video
3. Opcionalmente, crea H5P Interactive Video con checkpoints
4. H5P emite eventos xAPI que se capturan en progress_record
5. Al completar video (>90% visto), se marca actividad como completada
 
3.3 Implementación con H5P Interactive Video
H5P permite crear videos interactivos con preguntas en puntos específicos. Esto es más valioso pedagógicamente que un simple reproductor.
3.3.1 Tipos de Interacción en H5P Video
Interacción	Descripción	xAPI Verb	Uso Pedagógico
Single Choice	Pregunta con 1 respuesta correcta	answered	Verificar comprensión
Multiple Choice	Pregunta con varias correctas	answered	Evaluación intermedia
Fill in Blanks	Completar texto	answered	Memorización de términos
Drag and Drop	Arrastrar elementos	answered	Asociación de conceptos
Mark the Words	Marcar palabras clave	answered	Identificación
Statements	Texto informativo	interacted	Refuerzo de conceptos
Navigation Hotspots	Áreas clickables	interacted	Navegación no lineal
3.3.2 Configuración xAPI para H5P
H5P emite statements xAPI automáticamente. Configuración en LMS:
# Configuración xAPI para H5P en Drupal # /admin/config/h5p/settings  xapi_endpoint: /api/v1/xapi/statements xapi_actor_type: mbox_sha1 xapi_context_registration: enrollment.uuid xapi_context_extensions:   tenant_id: {tenant.id}   course_id: {course.id}   lesson_id: {lesson.id}   activity_id: {activity.id}  # Verbos capturados verbs_to_track:   - played   - paused     - seeked   - completed   - answered   - interacted   - passed   - failed
3.4 Integración YouTube Unlisted (MVP)
Para MVP, usar YouTube como CDN gratuito con videos 'unlisted' (no indexados públicamente).
3.4.1 Flujo de Publicación
•	1. Subir video a canal YouTube de Jaraba (unlisted)
•	2. Copiar URL: https://www.youtube.com/watch?v=VIDEO_ID
•	3. En Drupal: crear actividad tipo 'video' con video_provider='youtube'
•	4. Opcionalmente: crear H5P Interactive Video usando misma URL
•	5. H5P embebe el video y añade capa de interactividad
3.4.2 Template de Embed (Twig)
{# templates/activity--video.html.twig #}  {% if activity.video_provider == 'youtube' %}   <div class="video-wrapper video-wrapper--youtube">     {% if activity.h5p_content_id %}       {# H5P Interactive Video con tracking #}       {{ drupal_entity('h5p_content', activity.h5p_content_id) }}     {% else %}       {# Embed básico con tracking manual #}       <div class="video-player"             data-video-id="{{ activity.video_url|extract_youtube_id }}"            data-activity-id="{{ activity.id }}"            data-xapi-endpoint="/api/v1/xapi/statements">         <iframe src="https://www.youtube.com/embed/{{ activity.video_url|extract_youtube_id }}?enablejsapi=1"                 frameborder="0" allowfullscreen></iframe>       </div>     {% endif %}   </div> {% endif %}
 
3.5 Migración Futura a Bunny.net (Post-MVP)
Cuando el volumen justifique costes (~1000+ reproducciones/mes), migrar a Bunny.net Stream:
Aspecto	YouTube Unlisted	Bunny.net Stream
Coste	€0	~€0.005/min entregado
Branding	Logo YouTube visible	Sin branding externo
Control	Limitado	Total (player custom)
Analytics	YouTube Studio	API propia + integración
Velocidad	CDN Google	CDN Global optimizado
Privacidad	Términos Google	GDPR compliant
3.5.1 Script de Migración
#!/bin/bash # migrate_to_bunny.sh - Migrar videos de YouTube a Bunny.net  # 1. Descargar video de YouTube (requiere youtube-dl) youtube-dl -f best -o "%(id)s.%(ext)s" "$YOUTUBE_URL"  # 2. Subir a Bunny.net via API curl -X PUT "https://video.bunnycdn.com/library/{library_id}/videos/{video_id}" \   -H "AccessKey: $BUNNY_API_KEY" \   -H "Content-Type: application/octet-stream" \   --data-binary @"$VIDEO_FILE"  # 3. Actualizar URL en Drupal drush sql:query "UPDATE activity SET video_url='$BUNNY_URL', video_provider='bunny' WHERE id=$ACTIVITY_ID"
3.6 Tracking de Progreso de Video
Independientemente del proveedor, el tracking se hace via xAPI:
3.6.1 Eventos xAPI de Video
Evento	xAPI Verb	Trigger	Datos Capturados
Inicio	played	Usuario inicia reproducción	timestamp, video_id
Pausa	paused	Usuario pausa video	current_time, duration
Seek	seeked	Usuario salta a otro punto	from_time, to_time
Completado	completed	Video llega al final (>90%)	completion_time, watch_time
Checkpoint	progressed	Usuario alcanza 25/50/75%	progress_percent
3.6.2 Criterio de Completitud
// Configuración de completitud de video $completion_criteria = [   'minimum_watch_percent' => 90,  // Debe ver al menos 90%   'allow_seeking' => true,        // Permite saltar (no obliga lineal)   'require_interactions' => false, // Si H5P, puede requerir respuestas   'passing_score' => 70,          // Para H5P con quiz: score mínimo ];  // Evento de completitud if ($watch_percent >= $completion_criteria['minimum_watch_percent']) {   $this->progressService->markActivityComplete($activity_id, $user_id, [     'watch_time' => $total_watch_time,     'watch_percent' => $watch_percent,     'interactions_completed' => $interactions_count,   ]); }
 
3.7 Roadmap de Implementación Video LMS
Sprint	Timeline	Entregables	Dependencias
Sprint 1	Semanas 1-2	Campo video_provider en activity. Template embed YouTube. CSS responsive.	LMS Core
Sprint 2	Semanas 3-4	H5P Interactive Video configurado. xAPI statements capturados. progress_record actualizado.	Sprint 1
Sprint 3	Semanas 5-6	UI de creación de actividad video. Validación URLs. Duración auto-detectada.	Sprint 2
Sprint 4	Semanas 7-8	Tracking completo. Dashboard de video analytics. QA. Go-live.	Sprint 3

ESTIMACIÓN DE ESFUERZO
8 semanas (4 sprints) con 1 desarrollador senior. El componente principal (H5P) ya existe como módulo contrib. El trabajo es integración y configuración.
 
4. Resumen y Plan de Acción
4.1 Comparativa de Esfuerzo
Componente	Sprints	Semanas	Desarrolladores	Infraestructura Nueva
Matching Engine	5	10	1 Senior	Ninguna (usa Qdrant existente)
Video Player LMS	4	8	1 Senior	Ninguna (usa YouTube/H5P)
TOTAL	9*	18*	1-2 Senior	€0 adicional
* Los sprints pueden ejecutarse en paralelo con equipos distintos, reduciendo timeline total a ~10-12 semanas.
4.2 Dependencias entre Componentes
Orden óptimo de implementación considerando dependencias:
•	Fase 1 (Semanas 1-4): Video LMS Sprint 1-2 + Matching Engine Sprint 1
•	Fase 2 (Semanas 5-8): Video LMS Sprint 3-4 + Matching Engine Sprint 2-3
•	Fase 3 (Semanas 9-12): Matching Engine Sprint 4-5 + Integración completa
4.3 Métricas de Éxito
Componente	Métrica	Target MVP	Target 6 meses
Matching Engine	Accuracy (hired matches)	>60%	>75%
Matching Engine	Time to first match	<5 segundos	<2 segundos
Video LMS	Completion rate videos	>70%	>85%
Video LMS	xAPI statements/día	>100	>1000
Combinado	NPS candidatos	>40	>60
4.4 Recomendación Final

ACCIÓN RECOMENDADA
Proceder con implementación de ambos componentes (Matching Engine + Video LMS) en Fase 1 del vertical de Empleabilidad. La infraestructura necesaria ya existe. El único componente correctamente descartado es el Agente RPA de Postulación, que requiere análisis legal y técnico adicional para fases posteriores.

--- Fin del Documento ---
Contrapropuesta Técnica | Jaraba Impact Platform | Enero 2026
