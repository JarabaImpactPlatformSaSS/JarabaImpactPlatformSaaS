171
ESPECIFICACIÓN TÉCNICA
Content Hub + Page Builder
Generación IA | Variantes | Imágenes
Ecosistema Jaraba | 26 Enero 2026 | 30-40h
 
1. Resumen
Integración AI Content Hub (Doc 128) con Page Builder para generación automática de contenido por bloque.
Capacidades IA
Generar headlines | Variantes copy | CTAs | Sugerencias imágenes | SEO optimize
1.1 Objetivos
•	Botón 'Generar IA' en cada campo texto
•	Prompts contextuales por bloque/vertical
•	Múltiples variantes para A/B
•	Sugerencias imágenes de biblioteca
 
2. APIs Generación
Método	Endpoint	Descripción
POST	/api/v1/ai/generate/text	Generar texto
POST	/api/v1/ai/generate/variants	N variantes
POST	/api/v1/ai/optimize/seo	Optimizar SEO
POST	/api/v1/ai/suggest/images	Sugerir imágenes
3. Request/Response
 generate-api.json
// POST /api/v1/ai/generate/text
{
  "field_type": "headline",
  "block_type": "hero_fullscreen",
  "vertical": "empleabilidad",
  "context": { "tone": "professional", "keywords": ["empleo"] },
  "variants_count": 3
}
// Response
{
  "variants": [
    { "text": "Impulsa tu Carrera Hoy", "score": 0.92 },
    { "text": "Tu Próximo Empleo Te Espera", "score": 0.88 }
  ],
  "tokens_used": 340
}

4. Prompts por Bloque
Bloque	Campo	Prompt
hero	headline	Titular impactante max 60 chars
hero	cta	CTA 2-4 palabras
features	description	Beneficio en 1-2 frases
faq	answer	Respuesta clara y concisa
5. Límites Plan
Capacidad	Starter	Professional	Enterprise
Generaciones/mes	0	500	Ilimitadas
Variantes/request	—	3	10
Imágenes stock	—	—	✓
6. Roadmap
Sprint	Componente	Horas
1	UI + API generación	18-22h
2	Imágenes + SEO + Límites	12-18h
Total: 30-40h (€2,400-€3,200)
Integración
Extiende Doc 128 (AI Content Hub) para contexto Page Builder
Fin documento.
