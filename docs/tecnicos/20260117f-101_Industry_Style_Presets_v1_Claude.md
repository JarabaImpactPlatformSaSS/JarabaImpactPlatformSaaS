INDUSTRY STYLE PRESETS
Experiencia Premium Contextualizada por Sector
Extension del Documento 100_Frontend_Architecture
Especificacion Tecnica de Implementacion
Campo	Valor
Version	1.0
Fecha	Enero 2026
Codigo	101_Industry_Style_Presets_v1
Dependencias	100_Frontend_Architecture
 
1. Resumen Ejecutivo
Este documento especifica el sistema de Industry Style Presets: plantillas de diseno predefinidas por sector que permiten a cada tenant comenzar con una experiencia visual PREMIUM y CONTEXTUALIZADA a su tipo de negocio.
1.1 El Problema: Premium NO es Universal
Una bodega gourmet y un despacho de abogados tienen expectativas de 'premium' completamente diferentes. Los Industry Style Presets resuelven esto proporcionando puntos de partida expertos.
Contexto	Expectativa Visual	Sensacion Buscada	Paleta Tipo
Bodega gourmet	Luxury, serifas elegantes, oscuro	Exclusividad, craft, tradicion	Negro + Dorado + Borgona
Abogado	Sobrio, profesional, espacios	Confianza, seriedad, competencia	Azul oscuro + Marron + Blanco
Comercio barrio	Cercano, colorido, amigable	Proximidad, trato personal	Naranja + Crema + Verde
Cooperativa eco	Natural, rural, terroso	Origen, transparencia, compromiso	Verde + Terracota + Crema
Consultor tech	Moderno, minimalista, gradientes	Innovacion, eficiencia, expertise	Indigo + Cyan + Blanco
 
2. Taxonomia de Industry Presets
Los presets se organizan jerarquicamente: Vertical > Sector > Subsector. El usuario selecciona durante el onboarding y el sistema aplica automaticamente.
2.1 AgroConecta - Presets de Productor
Preset ID	Nombre	Mood	Ejemplos
agro_gourmet	Gourmet Artesanal	Luxury, craft	Bodegas premium, quesos DOP, AOVE
agro_organic	Ecologico Sostenible	Natural, green	Huertos eco, cestas km0
agro_traditional	Tradicion Familiar	Autentico, rural	Fincas generacionales
agro_cooperative	Cooperativa Social	Comunidad, impacto	Cooperativas, comercio justo
agro_modern	Agritech Moderno	Tech, innovacion	Agricultura precision, IoT
2.2 ServiciosConecta - Presets Profesionales
Preset ID	Nombre	Mood	Ejemplos
servicios_legal	Legal & Juridico	Serio, confiable	Bufetes, notarias, asesoria legal
servicios_salud	Salud & Bienestar	Limpio, cuidado	Clinicas, fisioterapia, psicologia
servicios_creative	Creativo & Diseno	Artistico, bold	Estudios diseno, arquitectura
servicios_consulting	Consultoria Business	Profesional, corporate	Consultoras, coaches negocio
servicios_tech	Tech & Digital	Moderno, innovador	Agencias digitales, software
servicios_education	Educacion & Formacion	Accesible, didactico	Academias, tutores, formadores
2.3 ComercioConecta - Presets de Comercio
Preset ID	Nombre	Mood	Ejemplos
comercio_boutique	Boutique Premium	Elegante, exclusivo	Moda selecta, joyeria, decoracion
comercio_barrio	Comercio de Barrio	Cercano, familiar	Tienda toda la vida, trato personal
comercio_gastro	Gastronomia Local	Foodie, gourmet	Delicatessen, panaderias artesanas
comercio_tech	Tech & Gaming	Moderno, digital	Electronica, informatica, gaming
comercio_wellness	Bienestar & Salud	Zen, natural	Herbolarios, parafarmacias
 
3. Anatomia de un Preset: agro_gourmet
Ejemplo completo del preset para bodegas, productores de quesos DOP, y AOVE premium:
3.1 Design Tokens
Token	Valor	Justificacion
--color-primary	#1A1A2E (Casi negro)	Elegancia, sofisticacion
--color-secondary	#C9A227 (Dorado)	Lujo, calidad artesanal
--color-accent	#722F37 (Borgona)	Vino, tradicion, calidez
--surface-bg	#FAFAF8 (Crema suave)	Papel artesanal, calidez
--font-headings	Playfair Display, serif	Elegancia clasica, editorial
--font-body	Lora, serif	Lectura elegante, storytelling
--radius-md	2px	Minimo: aspecto refinado
--shadow-md	0 4px 20px rgba(0,0,0,0.08)	Sombras sutiles, casi imperceptibles
3.2 Componentes Seleccionados
Componente	Variante	Razon
Header	header--transparent	Protagonismo a la fotografia de producto
Hero	hero--fullscreen	Impacto visual inmediato: vinedo, bodega
Cards	card--product-minimal	Producto protagonista, precio discreto
Footer	footer--elegant	Informacion de contacto premium
3.3 Directrices de Contenido
Fotografia: Luz natural, fondos neutros o contexto (campo, bodega), productos en situacion de consumo premium. Evitar flash directo.
Copywriting: Storytelling del origen, referencias a tradicion y territorio. Vocabulario: 'cosecha', 'terroir', 'artesanal', 'seleccion'.
Iconografia: Lineal fina, estilo editorial. Evitar iconos genericos de stock. Preferir ilustraciones custom.
 
4. Contraste: Preset servicios_legal
El mismo sistema aplicado a un contexto completamente diferente: un despacho de abogados.
4.1 Design Tokens
Token	Valor	Justificacion
--color-primary	#1E3A5F (Azul oscuro)	Confianza, seriedad, profesionalidad
--color-secondary	#8B7355 (Marron cuero)	Libros, tradicion juridica
--color-accent	#B8860B (Dorado oscuro)	Autoridad, exito, calidad
--surface-bg	#FFFFFF (Blanco puro)	Limpieza, claridad, transparencia
--font-headings	Libre Baskerville, serif	Clasica, seria, juridica
--font-body	Source Sans Pro, sans-serif	Legibilidad, modernidad equilibrada
--radius-md	4px	Minimo: seriedad, profesionalidad
4.2 Componentes Seleccionados
Componente	Variante	Razon
Header	header--classic	Navegacion clara, profesional, no invasiva
Hero	hero--split	Texto + imagen del equipo/oficina
Cards	card--profile-formal	Mostrar equipo de abogados con credenciales
Footer	footer--corporate	Datos contacto, colegiaciones, privacidad
4.3 Directrices de Contenido
Fotografia: Retratos profesionales del equipo, despacho, biblioteca juridica. Evitar imagenes de stock genericas con 'martillo de juez'.
Copywriting: Lenguaje formal pero accesible. Enfasis en experiencia y casos de exito (anonimizados). Evitar jerga excesiva.
Iconografia: Solida, formal: balanza, libro, contrato. Iconos con relleno (no lineal). Colores sobrios.
 
5. Comparativa Visual: Mismo Componente, Diferente Preset
Como el MISMO componente (card--profile) se renderiza de forma completamente diferente:
Aspecto	agro_gourmet	servicios_legal	comercio_barrio
Forma avatar	Circular + borde dorado	Rectangular formal	Circular + borde colorido
Tipografia nombre	Playfair Display italic	Libre Baskerville bold	Inter semibold
Color fondo	#FAFAF8 (crema)	#FFFFFF (blanco)	#FFF7ED (naranja claro)
Sombra	Casi inexistente	Sutil, uniforme	Pronunciada, calida
Border radius	2px (casi recto)	4px (sutil)	12px (redondeado)
Boton CTA	'Descubrir historia'	'Solicitar consulta'	'Visitanos!'
 
6. Implementacion Tecnica
6.1 Estructura JSON del Preset
// presets/agro_gourmet.json
{
  "id": "agro_gourmet",
  "name": "Gourmet Artesanal",
  "vertical": "agroconecta",
  "mood": ["luxury", "craft", "premium"],
  "tokens": {
    "colors": { "primary": "#1A1A2E", "secondary": "#C9A227" },
    "typography": { "headings": "Playfair Display", "body": "Lora" },
    "radius": { "md": "2px" }
  },
  "components": {
    "header": "header--transparent",
    "hero": "hero--fullscreen",
    "cards": "card--product-minimal"
  }
}
6.2 Flujo de Onboarding
1. Usuario selecciona vertical (AgroConecta, ComercioConecta, ServiciosConecta)
2. Sistema muestra presets disponibles con MINIATURAS de preview
3. Usuario selecciona preset que mas se acerca a su negocio
4. Sistema aplica AUTOMATICAMENTE todos los tokens y components
5. Usuario puede personalizar sobre el preset (logo, colores) en Visual Picker
 
7. Roadmap de Implementacion
Sprint	Timeline	Entregables	Dependencias
1	Semana 1-2	Estructura JSON. 3 presets piloto (agro_gourmet, servicios_legal, comercio_barrio)	Doc 100 completado
2	Semana 3-4	StylePresetService PHP. Integracion onboarding	Sprint 1
3	Semana 5-6	Miniaturas preview generadas. Galeria de presets	Sprint 2
4	Semana 7-8	12 presets adicionales (total 15). Content starters	Sprint 3
5	Semana 9-10	Testing con usuarios reales. Ajustes finos. Docs	Sprint 4
8. Conclusion
Los Industry Style Presets transforman la experiencia de onboarding de 'configurar desde cero' a 'elegir y personalizar'. Un productor de quesos selecciona 'Gourmet Artesanal' y obtiene instantaneamente una web que transmite premium, tradicion y calidad. Un abogado selecciona 'Legal & Juridico' y obtiene presencia que inspira confianza.
Este sistema resuelve el problema de que 'premium' significa cosas diferentes segun el contexto, proporcionando a cada tenant un punto de partida experto.
---
Documento preparado para el Ecosistema Jaraba | Enero 2026
