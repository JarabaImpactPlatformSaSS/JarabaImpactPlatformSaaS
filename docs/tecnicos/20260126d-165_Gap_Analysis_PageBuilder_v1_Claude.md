165
GAP ANALYSIS
Page Builder: Documentación Pendiente
Análisis de Gaps para Consistencia del Ecosistema SaaS
26 de Enero de 2026
 
Resumen Ejecutivo
Este documento identifica los gaps de documentación técnica necesarios para completar el sistema Page Builder y mantener consistencia con el resto del Ecosistema Jaraba (195 documentos existentes).

Estado Actual del Page Builder
Documentos completados: 162 (Sistema Completo), 163 (Anexo Código), 164 (SEO/GEO Avanzado). Gaps identificados: 6 documentos adicionales necesarios para paridad con otras áreas del ecosistema.

Matriz de Gaps Identificados
#	Documento Propuesto	Prioridad	Horas Est.	Dependencias
166	Platform_i18n_Multilanguage_v1	ALTA	60-80h	Doc 162
167	Platform_Analytics_PageBuilder_v1	ALTA	40-50h	Doc 116
168	Platform_AB_Testing_Pages_v1	MEDIA	50-60h	Docs 156, 162
169	Platform_Page_Versioning_v1	MEDIA	30-40h	Doc 162
170	Platform_Accessibility_WCAG_v1	ALTA	40-50h	Doc 163
171	Platform_ContentHub_PageBuilder_v1	MEDIA	30-40h	Docs 128, 162

Total estimado para completar Page Builder: 250-320 horas adicionales (€20,000-€25,600 @ €80/h)
 
Detalle de Gaps
166 - Internacionalización (i18n) Multi-idioma
Sistema completo de traducción y localización para el Page Builder.
Contenido Requerido
•	Arquitectura multi-idioma para content_data JSON
•	UI del Form Builder en español, inglés, catalán, euskera, gallego
•	Gestión de traducciones de plantillas
•	Hreflang automático y URL aliases por idioma
•	RTL support (futuro árabe)
•	Integración con IA para traducción automática
Justificación
Necesario para tenants con audiencia multi-regional (Cataluña, País Vasco, Galicia) y futura expansión internacional.

167 - Analytics Integrado en Page Builder
Sistema de tracking y métricas específico para páginas creadas con el constructor.
Contenido Requerido
•	Eventos GA4 automáticos por tipo de bloque
•	Heatmaps integrados (Hotjar/Microsoft Clarity)
•	Funnel de conversión por plantilla
•	Dashboard de rendimiento de páginas
•	Tracking de CTAs y formularios
•	Integración con Doc 116 (Platform Analytics)
Justificación
El Doc 116 cubre analytics general pero no especifica tracking de bloques ni métricas de conversión por página del constructor.

168 - A/B Testing para Páginas
Sistema de experimentación y variantes para optimización de conversión.
Contenido Requerido
•	Creación de variantes de página
•	Split testing con distribución configurable
•	Métricas de significancia estadística
•	Integración con Doc 156 (A/B Testing Framework)
•	UI para gestión de experimentos
•	Reportes de ganador y auto-promoción
Justificación
Doc 156 cubre A/B testing general pero no la integración específica con el Page Builder para testear variantes de bloques y layouts.
 
169 - Versionado de Páginas
Sistema de historial, rollback y comparación de versiones.
Contenido Requerido
•	Historial automático de cambios en content_data
•	Vista de diff entre versiones
•	Rollback a versión anterior
•	Programación de publicación (scheduled publish)
•	Workflow de aprobación (draft → review → published)
•	Límite de versiones por plan (5/20/ilimitado)
Justificación
Funcionalidad estándar en Page Builders SaaS que permite recuperación de errores y cumple requisitos de auditoría.

170 - Accesibilidad WCAG 2.1 AA
Checklist y validación de accesibilidad por bloque.
Contenido Requerido
•	Checklist WCAG 2.1 AA por tipo de bloque
•	Atributos ARIA requeridos en templates Twig
•	Validación de contraste de colores
•	Navegación por teclado
•	Screen reader testing procedures
•	Campos obligatorios de alt text en imágenes
•	API de auditoría de accesibilidad
Justificación
Requerimiento legal en España (RD 1112/2018) para sitios públicos y buena práctica para todos los tenants.

171 - Integración Content Hub + Page Builder
Conexión con el AI Content Hub para generación automática de contenido de bloques.
Contenido Requerido
•	Botón 'Generar con IA' en cada campo del Form Builder
•	Prompts contextuales por tipo de bloque
•	Generación de variantes de copy
•	Sugerencias de imágenes desde biblioteca
•	Integración con Doc 128 (AI Content Hub)
•	Límites de generación por plan
Justificación
Diferenciador competitivo clave. El Doc 128 especifica el Content Hub pero no su integración con el Page Builder.
 
Priorización Recomendada
Fase 1 - Críticos (Q1 2026)
166 (i18n): Necesario para mercado español multi-regional 170 (WCAG): Requerimiento legal 167 (Analytics): Necesario para medir ROI del constructor

Fase 2 - Importantes (Q2 2026)
168 (A/B Testing): Diferenciador para plan Enterprise 171 (Content Hub): Integración IA para productividad 169 (Versioning): Feature estándar esperado

Verificación de Consistencia con Ecosistema
Documentos del ecosistema que deben actualizarse para referenciar el Page Builder:
Documento	Actualización Necesaria
118 - Roadmap Implementación v2	Añadir sprints del Page Builder
141 - Índice Maestro Consolidado	Incluir docs 162-171
143 - Presupuesto Actualizado 2026	Añadir costes Page Builder
144 - Matriz Dependencias Técnicas	Añadir dependencias bloques premium
148 - Mapa Arquitectónico Completo	Incluir Page Builder en arquitectura

Conclusión
El Page Builder requiere 6 documentos adicionales (166-171) para alcanzar paridad funcional con otras áreas del ecosistema. La inversión total estimada de 250-320 horas asegura un sistema completo, accesible, medible y optimizable.

Fin del documento.
