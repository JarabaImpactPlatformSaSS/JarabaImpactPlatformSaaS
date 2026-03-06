# Investigacion de Mercado y Estrategia de Marketing: Meta-Sitio Corporativo plataformadeecosistemas.es

> **Codigo:** MKTG-METASITE-001
> **Version:** 1.0
> **Fecha:** 2026-03-06
> **Estado:** Diagnostico Estrategico Completo para Implementacion
> **Autor:** Analisis multidisciplinar (Consultor de Negocio, Analista de Mercado, Experto en Marketing SaaS, Arquitecto UX, Estratega de Marca)
> **Prioridad:** BLOCKER -- Prerrequisito para go-to-market efectivo
> **Dependencias:** 178_MetaSite_SaaS_Remediation_v1, UnifiedThemeResolverService, TenantThemeConfig, SiteConfig
> **Directrices aplicadas:** DOC-GUARD-001, IMPLEMENTATION-CHECKLIST-001, CSS-VAR-ALL-COLORS-001

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Perfil del Fundador y Activos Estrategicos](#2-perfil-del-fundador-y-activos-estrategicos)
3. [Analisis del Estado Actual del Meta-Sitio](#3-analisis-del-estado-actual-del-meta-sitio)
4. [Investigacion de Mercado por Vertical](#4-investigacion-de-mercado-por-vertical)
5. [Matriz de Priorizacion de Audiencias](#5-matriz-de-priorizacion-de-audiencias)
6. [Estrategia de Posicionamiento y Mensajes](#6-estrategia-de-posicionamiento-y-mensajes)
7. [Arquitectura de Conversion del Meta-Sitio](#7-arquitectura-de-conversion-del-meta-sitio)
8. [Plan de Implementacion por Fases](#8-plan-de-implementacion-por-fases)
9. [KPIs y Metricas de Exito](#9-kpis-y-metricas-de-exito)
10. [Fuentes y Referencias](#10-fuentes-y-referencias)

---

## 1. Resumen Ejecutivo

### 1.1 Diagnostico

La **Plataforma de Ecosistemas Digitales** (plataformadeecosistemas.es) es el meta-sitio corporativo de un SaaS multi-vertical con 10 verticales canonicos, 80+ modulos custom, 11 agentes IA Gen 2 y arquitectura multi-tenant sobre Drupal 11. El fundador, Jose Jaraba Munoz, aporta **25+ anos gestionando fondos europeos por mas de 100 millones EUR**, experiencia en 5 programas FSE, doctorado en curso, y una trayectoria que abarca desde el ejercicio de la abogacia hasta la direccion de sociedades publicas municipales.

**El problema central**: El meta-sitio actual no capitaliza estos activos. La web de produccion (plataformadeecosistemas.es) muestra un hero generico ("Ecosistemas digitales") con un subtitulo institucional que no comunica valor concreto para ninguna audiencia. La version SaaS en desarrollo mejora significativamente la propuesta con partials dinamicos, tracking, Schema.org y A/B testing, pero aun falla en los **primeros 3 segundos criticos** porque:

1. **No segmenta por audiencia**: Un abogado, un emprendedor y un funcionario ven la misma pagina
2. **Habla desde la plataforma, no desde el usuario**: Lista features (10 verticales, 11 agentes) en vez de prometer resultados
3. **No prioriza los verticales con mayor TAM**: Trata los 10 verticales por igual cuando JarabaLex (legaltech) y Empleabilidad tienen mercados 10x mayores
4. **Desperdicia la credencial mas potente**: 100M EUR en fondos europeos gestionados aparece como eyebrow pero no se conecta con beneficio tangible

### 1.2 Oportunidad

| Vertical | TAM Espana | Crecimiento | Competencia | **Score** |
|----------|-----------|-------------|-------------|-----------|
| JarabaLex (LegalTech) | 1.200M EUR | 18-28% CAGR | Media-Alta | **9.2** |
| Empleabilidad (HRTech) | 3.500M EUR | 12% CAGR | Alta | **8.5** |
| B2G (Instituciones) | 20.000M EUR (NGEU) | Unico ciclo | Baja | **9.0** |
| ComercioConecta | 405K autonomos | -2% (declive) | Alta | **6.5** |
| AgroConecta | 2.500M EUR | 14% CAGR | Baja | **7.8** |
| ServiciosConecta | 3.4M autonomos | 3% | Media | **7.0** |
| Formacion (LMS) | 800M EUR | 15% CAGR | Muy Alta | **5.5** |

**Recomendacion**: Priorizar **JarabaLex + B2G + Empleabilidad** como audiencias primarias del meta-sitio, con AgroConecta como vertical diferenciador.

### 1.3 Meta

Convertir plataformadeecosistemas.es de una web institucional generica a una **maquina de generacion de leads segmentada** que conecte con cada audiencia en los primeros 3 segundos mediante mensajes especificos, prueba social verificable y CTAs contextualizados por perfil.

---

## 2. Perfil del Fundador y Activos Estrategicos

### 2.1 Jose Jaraba Munoz -- Trayectoria

| Periodo | Rol | Organizacion | Impacto Cuantificable |
|---------|-----|-------------|----------------------|
| 2019-presente | Fundador y CEO | Plataforma de Ecosistemas Digitales S.L. | SaaS multi-vertical, 10 verticales, 80+ modulos |
| 2019-2024 | Asesor Fondos Europeos | Ayuntamiento Alhaurin el Grande (POEFE) | **2.666.244 EUR** (80% FSE) |
| 2021 | Asesor Fondos Europeos | Diputacion de Caceres (POEFE Isla IV) | **4.858.278 EUR** (80% FSE) |
| 2015-2019 | Director Gerente | SODEPO (Soc. Publica Puente Genil) | **2.888.126 EUR** en 2 proyectos FSE |
| 1997-2015 | Director Gerente | Asociacion Grupo Campina Sur Cordobesa | **15.501.225 EUR** en proyectos europeos; +**100M EUR** dinamizados |
| 2001-2015 | Director Gerente | Ingenova Consulting S.L.U. | Implantacion ERP en sectores: agroalimentario, joyeria, mueble |
| 2014 | Experto independiente | Fundacion MADECA / Diputacion Malaga | Dictamen Sector Agroalimentario - II Plan Estrategico Malaga |
| 2011-2014 | Profesor Visitante | Universidad de Cordoba / Universidad de Jaen | Master Desarrollo Rural, Catedra Planificacion Estrategica |
| 1990-1997 | Abogado y Consultor | Colegios de Abogados de Sevilla y Cordoba | Ejercicio profesional, consultoria fiscal |

### 2.2 Formacion

- **Licenciado en Derecho** -- Universidad de Cordoba (1984-1989)
- **Doctorando** -- Universidad de Cordoba, Dpto. Economia, Sociologia y Politica Agrarias (en curso)
- **MBA** -- Universidad Politecnica de Madrid, CEPADE (en terminacion)
- **Master en Desarrollo Rural Territorial** -- Universidad de Cordoba (2007-2009, acreditacion investigadora)
- **Master en Gestion del Turismo Ambiental** -- Universidad Politecnica de Madrid (1996)
- **EOQ Quality System Manager** -- European Organization for Quality (Cert. ES06QSM-672)
- **ITIL Foundation** -- itSMF International (Cert. 225311-7591)

### 2.3 Activos Estrategicos Diferenciadores

Estos son los activos que NINGUN competidor SaaS en Espana puede replicar:

| Activo | Descripcion | Valor para Marketing |
|--------|-------------|---------------------|
| **+100M EUR dinamizados** | Proyectos publicos y privados en 18 anos de desarrollo territorial | Credencial de confianza sin precedentes para B2G |
| **25.9M EUR en FSE gestionados** | 5 proyectos concretos con importes verificables | Prueba de capacidad de ejecucion |
| **Jurista + Tecnologia** | Licenciado en Derecho + EOQ + ITIL + SaaS builder | Posicionamiento unico para JarabaLex |
| **Universidad** | Profesor visitante en 2 universidades, evaluador de TFM | Credibilidad academica |
| **Red institucional** | Junta de Andalucia, Diputaciones, CADE, Ayuntamientos | Pipeline B2G directo |
| **3 casos de exito reales** | Marcela Calabia, Angel Martinez (Camino Viejo), Luis Miguel Criado | Social proof verificable |
| **Primera red WiFi rural** | Creacion de infraestructura telematica en Campina Sur (repercusion nacional) | Narrativa de innovador pionero |

### 2.4 Propuesta de Valor Unica (UVP)

> **La unica plataforma SaaS en Espana construida por un jurista con 25 anos gestionando +100M EUR en fondos europeos, que combina inteligencia legal IA, herramientas de empleo/emprendimiento y gestion de programas publicos en un ecosistema multi-vertical con 11 agentes IA.**

Esta UVP es inimitable: requiere la interseccion de experiencia juridica, gestion de fondos europeos, conocimiento del sector publico andaluz y capacidad tecnica para construir un SaaS desde cero.

---

## 3. Analisis del Estado Actual del Meta-Sitio

### 3.1 Web de Produccion (plataformadeecosistemas.es)

**Estado**: Web estatica WordPress con contenido institucional minimo.

| Elemento | Estado | Problema |
|----------|--------|----------|
| Hero | "Ecosistemas digitales" | Generico, no comunica valor |
| Navegacion | Nosotros / Plataformas / Proyectos / Contactenos | No segmenta por audiencia |
| Propuesta valor | "Ayudamos a personas y entidades..." | Institucional, sin diferenciacion |
| CTA principal | "Descubre como!" | Sin destino claro |
| Pricing | Inexistente | No hay pagina /planes |
| Social proof | Ninguno visible | 0 testimonios, 0 metricas |
| SEO | Copyright 2020, sin schema.org | Desactualizado |

### 3.2 Version SaaS en Desarrollo (jaraba-saas.lndo.site)

**Estado**: Arquitectura de clase mundial con 12+ partials, tracking, Schema.org, A/B testing, pero con problemas de contenido y priorizacion.

| Componente | Calidad Tecnica | Calidad de Contenido | Nota |
|------------|----------------|---------------------|------|
| Hero (_hero.html.twig) | Excelente: eyebrow, h1, sub, CTAs, trust bar, scroll indicator | **Media**: Lista features en vez de resultados | Necesita reescritura UVP |
| Trust Bar (_trust-bar.html.twig) | Buena: SVG logos, badges | **Baja**: Logos como texto SVG, no iconos reales | Necesita logos reales |
| Vertical Selector | Excelente: cards interactivas, tracking, deteccion avatar | **Media**: 6 verticales sin priorizar | Reordenar por TAM |
| Features (_features.html.twig) | Buena: 3 cards animadas | **Media**: "Configuracion en minutos" generico | Orientar a resultado |
| Stats (_stats.html.twig) | Buena: counter animation, IntersectionObserver | **Baja**: Metricas tecnicas (80+ modulos) | Cambiar a metricas de impacto |
| Product Demo | Excelente: 3 tabs, mockup browser, chart SVG | Buena | Mantener |
| Lead Magnet | Excelente: formulario, avatar select, GDPR | Buena | Mantener |
| Cross-Pollination | Buena: 6 cards por vertical | **Redundante** con Vertical Selector | Eliminar o fusionar |
| Testimonials | Excelente: 3 casos reales, blockquote, resultado | **Excelente**: Datos verificables | Subir en el funnel |
| PED SCSS | Excelente: paleta corporativa azul+dorado | **No activa**: Clases .ped-* no usadas en partials | Activar |

### 3.3 Gap Critico: Desconexion Estilos PED

El fichero `_ped-metasite.scss` (579 lineas) define una identidad visual premium con:
- Gradiente corporativo azul (#233D63) + dorado (#C5A55A)
- Secciones especificas: `.ped-hero`, `.ped-cifras`, `.ped-motores`, `.ped-audiencia`, `.ped-partners`, `.ped-cta-saas`
- Override de header/footer para `.meta-site-tenant-7`

Pero `page--front.html.twig` usa los partials genericos (`.hero-landing`, `.features-section`, `.stats-section`) que tienen estilos naranja-impulso, NO la paleta PED. **El meta-sitio corporativo se ve identico a cualquier landing de vertical.**

---

## 4. Investigacion de Mercado por Vertical

### 4.1 JarabaLex -- LegalTech (PRIORIDAD MAXIMA)

#### Tamano de Mercado

| Metrica | Valor | Fuente |
|---------|-------|--------|
| Mercado LegalTech Espana | **1.200M USD** | Research and Markets 2025 |
| Mercado IA LegalTech Espana | **265M USD** | Research and Markets 2025 |
| CAGR IA Legal 2025-2030 | **18-28.5%** | Ken Research / Grand View Research |
| Abogados colegiados Espana | **154.573** | Abogacia Espanola (Censo 2025) |
| Despachos de abogados | **~90.000** | LegalToday |
| % en despachos pequenos/unipersonales | **73%** (35% unipersonal + 38% pequeno) | ICAM 2025 |
| % despachos con 1-3 socios | **89%** | LegalToday |
| % abogados autonomos (ejercientes) | **68%** | ICAM 2025 |
| Inversion tech despachos (media sectorial) | **2-3% del presupuesto** | Estudio ICAM |
| Ahorro tiempo con IA legal | **30%** promedio | Spanish Bar Association |
| % creen que IA mejora revision documental | **66%** | Spanish Bar Association |
| % tiempo en revision documental | **39%** | Spanish Bar Association |
| Obstaculo #1 digitalizacion | **Alto coste (44%)** | GLTH Index 2026 |
| Obstaculo #2 digitalizacion | **Falta tiempo/recursos (42%)** | GLTH Index 2026 |
| Obstaculo #3 digitalizacion | **Resistencia cultural (33%)** | GLTH Index 2026 |

#### Competencia

| Competidor | Foco | Precio Ref. | Debilidad vs JarabaLex |
|-----------|------|-------------|----------------------|
| vLex | Busqueda jurisprudencial | ~100-300 EUR/mes | Solo busqueda, no gestion integral |
| Aranzadi / Thomson Reuters | BD juridica premium | ~200-500 EUR/mes | Costoso, sin IA copilot |
| Tirant Lo Blanch | BD juridica + formacion | ~150-350 EUR/mes | Menos IA, sin gestion expedientes |
| Lefebvre / El Derecho | BD + compliance | ~200-400 EUR/mes | Enterprise, no accesible a pequenos |
| Lexnet (Ministerio) | Comunicaciones judiciales | Gratuito (publico) | Solo comunicacion, no gestion |
| Bigle Legal | Automatizacion contratos | ~80-200 EUR/mes | Nicho contratos, no busqueda legal |
| Signaturit | Firma digital | ~25-100 EUR/mes | Solo firma, no inteligencia legal |

#### Oportunidad JarabaLex

**JarabaLex es la unica plataforma que combina** busqueda semantica multi-fuente (CENDOJ, EUR-Lex, CURIA, HUDOC, BOE, DGT, TEAC, EDPB) + gestion de expedientes + agenda juridica + boveda documental + facturacion + integracion LexNET + copiloto IA legal + plantillas procesales **a un precio de 29-59 EUR/mes**.

**TAM accesible**: 90.000 despachos x 73% pequenos = **65.700 despachos** que NO pueden pagar Aranzadi/vLex premium. A 59 EUR/mes = **46.5M EUR/ano** solo en Espana.

**SAM realista (5% penetracion en 3 anos)**: 3.285 despachos x 59 EUR/mes x 12 = **2.3M EUR ARR**.

**Mensaje clave para abogados**: "Todo lo que Aranzadi te cobra 300 EUR/mes, JarabaLex te lo da por 59 EUR con IA incluida. Gestion de expedientes, busqueda en CENDOJ/BOE/EUR-Lex, agenda, facturacion y un copiloto legal que te ahorra el 30% de tu tiempo."

#### Regulacion favorable

- **EU AI Act (2024-2026)**: Los despachos necesitan herramientas IA que cumplan normativa europea. JarabaLex tiene LCIS (Legal Coherence Intelligence System) con audit trail para Art. 12.
- **Digitalizacion de la Justicia**: El Plan de Justicia 2030 impulsa la digitalizacion integral del sistema judicial espanol.
- **Ley de Eficiencia Digital**: Requiere interoperabilidad entre sistemas juridicos.

### 4.2 B2G -- Instituciones Publicas (PRIORIDAD ALTA)

#### Tamano de Mercado

| Metrica | Valor | Fuente |
|---------|-------|--------|
| Fondos NGEU para Espana | **163.000M EUR** (80.000 transferencias + 83.000 prestamos) | Espana Digital 2026 |
| Partida digitalizacion NGEU | **20.000M EUR** (26% del total) | Gobierno de Espana |
| Kit Digital (cerrado oct 2025) | **3.067M EUR** distribuidos, 880.000 beneficiarios | Red.es |
| Plan Digitalizacion AAPP | **2.600M EUR** en 3 anos | Gobierno de Espana |
| Plan Conectividad | **2.300M EUR** | Espana Digital 2026 |
| Presupuesto agencias colocacion | **+60M EUR/ano** via SEPE | SEPE |
| Programas POEFE/POEJ vigentes | Multiples convocatorias FSE+ 2021-2027 | Junta de Andalucia |

#### Audiencia B2G Especifica

| Segmento | Cantidad Est. | Necesidad | Vertical Jaraba |
|----------|--------------|-----------|----------------|
| Ayuntamientos con AEDL | ~2.000 | Gestion programas empleo/emprendimiento | Empleabilidad + Emprendimiento |
| Diputaciones Provinciales | 52 | Programas provinciales de desarrollo | Todos |
| Gobiernos Autonomicos | 17 | Servicios publicos de empleo | Empleabilidad |
| CADE / Andalucia Emprende | ~200 centros | Apoyo a emprendedores | Emprendimiento + Andalucia_EI |
| Agencias de Colocacion | ~1.500 autorizadas | Intermediacion laboral digital | Empleabilidad |
| Entidades del Tercer Sector | ~3.000 activas en empleo | Gestion de programas sociales | Empleabilidad + Formacion |

#### Ventaja Competitiva Unica

Jose Jaraba es **la persona con mas experiencia demostrable en la interseccion exacta** de fondos europeos + tecnologia + empleo/emprendimiento:
- 5 proyectos FSE ejecutados con exito (25.9M EUR)
- Director Gerente de sociedad publica municipal
- Red institucional directa con Junta de Andalucia, Diputaciones, CADEs
- Conocimiento intimo de los pain points de la gestion publica de empleo

**Ningun SaaS competidor tiene un fundador que haya sido Director Gerente de una sociedad publica municipal Y abogado Y gestor de fondos europeos.**

**Mensaje clave para instituciones**: "Construida por quien ha gestionado 25.9M EUR en programas FSE para Ayuntamientos y Diputaciones. Herramientas digitales para la gestion integral de empleo, emprendimiento y formacion con justificacion de fondos europeos incorporada."

### 4.3 Empleabilidad -- HRTech

#### Tamano de Mercado

| Metrica | Valor | Fuente |
|---------|-------|--------|
| Mercado HRTech Espana (est.) | **~3.500M EUR** | Estimacion basada en empleo tech UE |
| Ofertas empleo IA (crecimiento 5 anos) | **+454%** | Asociacion DigitalES |
| Demanda perfiles tech 2026 | IA, datos, ciberseguridad lideran | InfoJobs / AHK |
| Autonomos en Espana | **3.421.659** | TGSS 2025 |
| PYMES en Espana | **2.901.920** | Ministerio Industria (mayo 2025) |
| % microempresas | **95.1%** del tejido empresarial | Ministerio Industria |

#### Competencia HRTech

| Competidor | Foco | Precio | Debilidad |
|-----------|------|--------|-----------|
| InfoJobs | Portal empleo generalista | Publicacion ofertas ~100-300 EUR | Sin IA copilot, sin diagnostico |
| LinkedIn Recruiter | Reclutamiento enterprise | ~500-1000+ EUR/mes | Costoso, no adaptado a PYME |
| Factorial | HR SaaS PYME | ~6-10 EUR/empleado/mes | RRHH interno, no matching |
| Talentia | HCM enterprise | Custom | Enterprise, no accesible |
| Bizneo | ATS + RRHH | ~100-300 EUR/mes | Sin copiloto IA, sin formacion integrada |

#### Diferenciacion Empleabilidad Jaraba

JarabaLex integra en una sola plataforma: diagnostico de competencias, CV builder IA, matching inteligente 5D, simulador de entrevistas, credenciales digitales, health score, copiloto IA con 6 modos, rutas formativas gamificadas y portal de empleo completo. A 29-79 EUR/mes.

### 4.4 AgroConecta -- AgriTech

#### Tamano de Mercado

| Metrica | Valor | Fuente |
|---------|-------|--------|
| Mercado AgriTech Espana | **2.500M USD** | Ken Research |
| PAC 2023-2027 para precision farming | **1.500M EUR** | Plan Estrategico PAC |
| CAGR software AgriTech 2025-2033 | **14.2%** | Market Data Forecast |
| Fondos gobierno para agritech | **+500M EUR** | Plan Recuperacion |
| Kit Digital ampliacion a explotaciones agrarias | **100M EUR** | Gobierno de Espana |
| Regiones lideres | Cataluna, **Andalucia**, Valencia | Ken Research |

#### Activos de Jose Jaraba en AgriTech

- **18 anos en Campina Sur Cordobesa**: Territorio eminentemente agroalimentario
- **Impulsor del sector ecologico andaluz**: Creacion de EPEA (Asociacion Empresas Productos Ecologicos Andalucia)
- **ERP agroalimentarios**: Implantacion en bodegas y almazaras via Ingenova Consulting
- **Dictamen Agroalimentario Malaga**: Experto independiente para Fundacion MADECA

### 4.5 ComercioConecta -- Comercio de Proximidad

| Metrica | Valor |
|---------|-------|
| Autonomos en comercio | **404.713** (pero en declive: -8.567 en 2025) |
| Kit Digital bonos distribuidos | 880.000 (programa cerrado) |

**Evaluacion**: Mercado grande pero en contraccion. Alta competencia (Shopify, PrestaShop, Tiendanube). ComercioConecta se diferencia por el enfoque "sistema operativo de barrio" con QR dinamico y ofertas flash, pero el TAM efectivo es menor que LegalTech o B2G.

### 4.6 ServiciosConecta -- Servicios Profesionales

| Metrica | Valor |
|---------|-------|
| Autonomos actividades profesionales/tecnicas | Crecimiento +10.065 en 2025 |
| Segmentos: gestorias, asesorias, terapeutas, consultores | ~500.000 potenciales |

**Evaluacion**: Mercado fragmentado con alta demanda de reservas online + gestion de clientes. ServiciosConecta compite con Calendly, Acuity, Doctolib pero ofrece valor integrado (firma digital PAdES, triaje IA, videoconsultas).

### 4.7 Formacion -- LMS

**Evaluacion**: Mercado saturado (Moodle, Canvas, Teachable, Thinkific, Hotmart). Formacion como vertical standalone es dificil de vender, pero como **addon** dentro de otros verticales (Empleabilidad + Formacion, JarabaLex + Formacion) es muy potente.

---

## 5. Matriz de Priorizacion de Audiencias

### 5.1 Modelo de Scoring (1-10 por criterio)

| Audiencia | TAM | Crecimiento | Ventaja Competitiva | Fit con Fundador | Facilidad Venta | ARPU | **TOTAL** |
|-----------|-----|-------------|--------------------|--------------------|-----------------|------|-----------|
| **Abogados (JarabaLex)** | 8 | 9 | 9 | 10 (jurista) | 7 | 8 | **51** |
| **Instituciones (B2G)** | 10 | 8 | 10 | 10 (25 anos FSE) | 6 | 9 | **53** |
| **Profesionales empleo** | 9 | 7 | 7 | 8 | 7 | 7 | **45** |
| **Productores agro** | 7 | 8 | 8 | 9 (18 anos rural) | 5 | 7 | **44** |
| **Comerciantes** | 7 | 4 | 6 | 5 | 6 | 6 | **34** |
| **Servicios prof.** | 7 | 6 | 6 | 6 | 6 | 6 | **37** |
| **Emprendedores** | 6 | 7 | 7 | 8 | 7 | 5 | **40** |
| **Formadores/LMS** | 5 | 7 | 4 | 5 | 5 | 5 | **31** |

### 5.2 Audiencias Primarias del Meta-Sitio (Top 3)

#### AUDIENCIA 1: Abogados y Despachos (JarabaLex)

- **Tamano**: 154.573 colegiados, 90.000 despachos, 73% en despachos pequenos
- **Pain point**: Herramientas caras (Aranzadi 300+ EUR/mes), digitalizacion lenta, 39% del tiempo en revision documental
- **Solucion**: JarabaLex a 29-59 EUR/mes con IA, busqueda multi-fuente, gestion expedientes, LexNET
- **Mensaje**: "La inteligencia legal que tu despacho necesita. Sin el precio de las grandes. Con IA que te ahorra el 30% de tu tiempo."
- **Por que priorizar**: CAGR 18-28%, fundador es jurista, baja competencia en el segmento precio 29-59 EUR

#### AUDIENCIA 2: Instituciones Publicas (B2G)

- **Tamano**: 2.000+ Ayuntamientos con AEDL, 52 Diputaciones, 200 CADEs, 1.500 agencias colocacion
- **Pain point**: Gestion compleja de programas de empleo/emprendimiento con fondos europeos, justificacion FSE
- **Solucion**: Plataforma integral empleo + emprendimiento + formacion + justificacion fondos
- **Mensaje**: "Construida por quien ha gestionado 25.9M EUR en programas FSE. La herramienta que los tecnicos de empleo necesitan."
- **Por que priorizar**: NGEU 20.000M EUR en digitalizacion, fundador tiene red directa, baja competencia SaaS

#### AUDIENCIA 3: Profesionales en Transicion (Empleabilidad)

- **Tamano**: 2.6M desempleados + millones en busqueda activa de mejora profesional
- **Pain point**: Portales de empleo impersonales, sin orientacion IA, sin diagnostico de competencias
- **Solucion**: Diagnostico + CV IA + matching inteligente + simulador entrevistas + copiloto carrera
- **Mensaje**: "Tu proximo paso profesional empieza aqui. Con un copiloto IA que te guia."
- **Por que priorizar**: Funnel de entrada al ecosistema (freemium), alta viralidad, casos de exito reales

### 5.3 Audiencias Secundarias

| Audiencia | Estrategia |
|-----------|-----------|
| **Productores agroalimentarios** | Landing vertical especifica, no en hero principal |
| **Emprendedores** | Addon cross-sell desde Empleabilidad |
| **Comerciantes** | Landing vertical especifica, push via partnerships locales |
| **Servicios profesionales** | Addon cross-sell, especialmente gestorias/asesorias (puente con JarabaLex) |
| **Inversores/Prensa** | Seccion dedicada "Para Inversores" con deck y metricas |

---

## 6. Estrategia de Posicionamiento y Mensajes

### 6.1 Posicionamiento General

**Categoria**: Plataforma SaaS multi-vertical de impacto con IA

**Diferenciacion**: "La unica plataforma construida por un jurista con 25 anos gestionando fondos europeos, que ofrece inteligencia legal IA, herramientas de empleo y emprendimiento, y gestion de programas publicos -- todo en un ecosistema integrado con 11 agentes IA."

**Tono**: Profesional-cercano, institucional pero accesible. "Sin humo" -- datos verificables, sin metricas infladas.

### 6.2 Mensajes por Audiencia (Hero Dinamico)

El hero del meta-sitio debe detectar la audiencia (via UTM, referrer, o seleccion manual) y mostrar un mensaje personalizado.

#### Hero Default (Audiencia No Identificada)

```
Eyebrow: Plataforma SaaS multi-vertical con IA
H1: Empleo, justicia y negocio digital -- impulsados por 11 agentes IA
Subtitulo: Para abogados, instituciones publicas, profesionales y emprendedores.
           10 verticales, copiloto integrado, firma digital PAdES.
CTA primario: Empieza gratis -->
CTA secundario: Agenda una demo personalizada
```

#### Hero para Abogados (UTM: ?audience=legal)

```
Eyebrow: +154.000 abogados en Espana aun sin IA en su despacho
H1: Tu despacho merece inteligencia legal de verdad -- a precio de autonomo
Subtitulo: Busca en CENDOJ, BOE y EUR-Lex con IA. Gestiona expedientes,
           agenda, facturacion y LexNET. Desde 29 EUR/mes.
CTA primario: Prueba JarabaLex gratis -->
CTA secundario: Ver demo de 2 minutos
Trust: RGPD · EU AI Act compliant · Busqueda en 8 fuentes oficiales
```

#### Hero para Instituciones (UTM: ?audience=b2g)

```
Eyebrow: 25.9M EUR en programas FSE gestionados con exito
H1: La plataforma digital para programas de empleo, emprendimiento y formacion con fondos europeos
Subtitulo: Gestion integral de participantes, fases, horas IA-tracked, justificacion
           FSE y copiloto para tecnicos. Usada en POEFE, POEJ y Andalucia +ei.
CTA primario: Solicitar demo institucional -->
CTA secundario: Descargar dossier de capacidades
Trust: 5 programas FSE ejecutados · RGPD · Firma digital PAdES
```

#### Hero para Profesionales (UTM: ?audience=empleo)

```
Eyebrow: 3 emprendedores lanzados en el programa Andalucia +ei
H1: Tu proximo paso profesional empieza aqui -- con IA que te guia
Subtitulo: Diagnostico de competencias, CV con IA, preparacion de entrevistas
           y matching inteligente. Gratis para empezar.
CTA primario: Empieza gratis -->
CTA secundario: Conoce el metodo
Trust: Casos reales · Metodologia probada · 100% recomendarian el programa
```

### 6.3 Secciones Especificas por Audiencia

#### Seccion "Para Abogados" (nueva)

```
Titulo: JarabaLex -- Inteligencia legal accesible
Cards:
  1. Busqueda IA en 8 fuentes: CENDOJ, EUR-Lex, CURIA, HUDOC, BOE, DGT, TEAC, EDPB
  2. Gestion integral del despacho: expedientes, agenda, boveda, facturacion
  3. Integracion LexNET: comunicaciones judiciales sin salir de la plataforma
  4. Copiloto legal IA: redaccion, analisis jurisprudencial, alertas normativas
Comparativa:
  Aranzadi: 300+ EUR/mes, solo base de datos
  vLex: 150+ EUR/mes, solo busqueda
  JarabaLex: 59 EUR/mes, TODO integrado con IA
CTA: Prueba gratis 14 dias -->
```

#### Seccion "Para Instituciones" (nueva)

```
Titulo: Herramientas para programas de empleo y emprendimiento con fondos europeos
Cards:
  1. Dashboard de programa: participantes, fases, horas, indicadores FSE
  2. Empleabilidad: diagnostico, matching, CV IA para beneficiarios
  3. Emprendimiento: BMC, lean canvas, mentor IA para emprendedores del programa
  4. Justificacion: tracking de horas, informes por fase, exportacion para auditorias
Credenciales: 5 proyectos FSE, 25.9M EUR gestionados, 3 casos documentados
CTA: Solicitar demo institucional -->
```

---

## 7. Arquitectura de Conversion del Meta-Sitio

### 7.1 Flujo Optimizado de Secciones

El orden actual del `page--front.html.twig` debe reorganizarse siguiendo el modelo AIDA (Atencion, Interes, Deseo, Accion):

#### Flujo Propuesto

```
1. HERO (Atencion) -- 3 segundos: UVP + CTA
     Dinamico por audiencia (UTM/deteccion)
     Paleta PED: azul-corporativo + dorado

2. AUDIENCIA SELECTOR (Interes) -- 5 segundos: "Soy..."
     4 cards: Abogado | Institucion | Profesional | Emprendedor/Empresa
     Al seleccionar, scroll suave a seccion especifica

3. STATS DE IMPACTO (Interes) -- 10 segundos: Credibilidad
     100M+ EUR dinamizados | 25+ anos experiencia | 3 casos de exito | 11 agentes IA
     (NO "80+ modulos" -- metricas de impacto, no de producto)

4. VERTICALES DESTACADOS (Deseo) -- 20 segundos: Profundidad
     JarabaLex | Instituciones | Empleabilidad (los 3 top)
     Cada uno con 3-4 features + CTA especifico

5. PRODUCT DEMO (Deseo) -- 30 segundos: Ver antes de probar
     Dashboard + Copiloto IA + Analytics (mantener tabs actuales)

6. TESTIMONIOS (Deseo) -- 40 segundos: Prueba social
     Marcela, Angel, Luis Miguel (SUBIR desde posicion actual)
     Agregar logos institucionales: Junta Andalucia, Diputacion Caceres, Ayto. Alhaurin

7. TRUST BAR (Confianza) -- Refuerzo
     Logos reales de tecnologias + credenciales

8. LEAD MAGNET (Accion) -- Captura
     Kit de Impulso Digital (mantener formulario actual)

9. CTA BANNER FINAL (Accion) -- Urgencia
     "Empieza gratis hoy. Sin tarjeta de credito."
```

#### Secciones a Eliminar/Fusionar

| Seccion Actual | Accion | Razon |
|---------------|--------|-------|
| Vertical Selector (6 cards) | **Reemplazar** por Audiencia Selector (4 cards) | Segmentar por "quien soy" no por vertical |
| Cross-Pollination (6 cards) | **Eliminar** | Redundante con Vertical Selector |
| Trust Bar (posicion actual) | **Mover** despues de testimonios | Credibilidad despues de prueba social |
| Features (3 cards genericas) | **Reemplazar** por 3 verticales destacados | Valor especifico > features genericas |

### 7.2 Implementacion Tecnica

#### Archivos a Modificar

| Archivo | Cambio |
|---------|--------|
| `page--front.html.twig` | Reordenar includes, agregar `_audience-selector.html.twig`, eliminar `_cross-pollination.html.twig` |
| `_hero.html.twig` | Agregar logica de audiencia dinamica via `drupalSettings.audience` o UTM |
| `_stats.html.twig` | Cambiar metricas de producto a metricas de impacto |
| `_features.html.twig` | Reemplazar por 3 secciones de verticales destacados |
| `_trust-bar.html.twig` | Logos SVG reales (no texto) + mover posicion |
| `_ped-metasite.scss` | Verificar que las clases `.ped-*` se aplican correctamente |
| `ecosistema_jaraba_theme.theme` | Inyectar `audience` variable desde UTM/cookie/avatar detection |

#### Archivos Nuevos

| Archivo | Proposito |
|---------|----------|
| `_audience-selector.html.twig` | 4 cards: Abogado, Institucion, Profesional, Empresa |
| `_vertical-highlight-legal.html.twig` | Seccion destacada JarabaLex con comparativa precios |
| `_vertical-highlight-b2g.html.twig` | Seccion destacada Instituciones con credenciales FSE |
| `_vertical-highlight-empleo.html.twig` | Seccion destacada Empleabilidad con funnel |
| `_partners-institucional.html.twig` | Logos institucionales (Junta, Diputaciones, CADEs) |

---

## 8. Plan de Implementacion por Fases

### Fase 1 -- "Los 3 Segundos" (Hero + Audiencia Selector) -- Sprint 1

**Objetivo**: Que cualquier visitante sepa en 3 segundos que es la plataforma y si es para el.

| Tarea | Archivos | Esfuerzo |
|-------|----------|----------|
| 1.1 Activar paleta PED en hero | `_hero.html.twig`, `_ped-metasite.scss` | Medio |
| 1.2 Reescribir hero con UVP por audiencia | `_hero.html.twig`, `.theme` (preprocess) | Medio |
| 1.3 Crear Audiencia Selector | Nuevo `_audience-selector.html.twig` + SCSS | Alto |
| 1.4 Reordenar secciones en page--front | `page--front.html.twig` | Bajo |
| 1.5 Cambiar stats a metricas de impacto | `_stats.html.twig` | Bajo |

### Fase 2 -- Verticales Destacados + Social Proof -- Sprint 2

| Tarea | Archivos | Esfuerzo |
|-------|----------|----------|
| 2.1 Seccion JarabaLex destacada con comparativa | Nuevo partial + SCSS | Alto |
| 2.2 Seccion Instituciones con credenciales FSE | Nuevo partial + SCSS | Alto |
| 2.3 Seccion Empleabilidad con funnel | Nuevo partial + SCSS | Alto |
| 2.4 Subir testimonios en el flujo | `page--front.html.twig` | Bajo |
| 2.5 Agregar logos institucionales | Nuevo `_partners-institucional.html.twig` | Medio |

### Fase 3 -- Conversion + Analytics -- Sprint 3

| Tarea | Archivos | Esfuerzo |
|-------|----------|----------|
| 3.1 Hero dinamico por UTM/cookie | JS + `.theme` preprocess | Alto |
| 3.2 Landing pages por audiencia (/abogados, /instituciones) | Nuevas rutas + templates | Alto |
| 3.3 A/B testing de hero copy | Infraestructura `ab_variants` existente | Medio |
| 3.4 Analytics de funnel por audiencia | `funnel-analytics.js` existente | Medio |
| 3.5 CTA banner final con urgencia | Nuevo partial + SCSS | Bajo |

---

## 9. KPIs y Metricas de Exito

### 9.1 Metricas de Conversion

| KPI | Baseline (estimado) | Objetivo 3 meses | Objetivo 6 meses |
|-----|---------------------|-------------------|-------------------|
| Bounce rate homepage | ~70% | <50% | <40% |
| Time on page (hero) | ~5s | >15s | >25s |
| CTA hero click rate | ~1% | >3% | >5% |
| Lead magnet conversion | ~0.5% | >2% | >4% |
| Audiencia selector engagement | N/A | >30% clics | >40% clics |
| Demo requests (B2G) | 0 | 5/mes | 15/mes |
| Registros JarabaLex | 0 | 20/mes | 50/mes |
| Registros Empleabilidad | ~10/mes | 50/mes | 150/mes |

### 9.2 Metricas de Negocio

| KPI | Objetivo 6 meses | Objetivo 12 meses |
|-----|-------------------|-------------------|
| MRR desde JarabaLex | 2.000 EUR | 10.000 EUR |
| MRR desde B2G | 5.000 EUR | 25.000 EUR |
| MRR desde Empleabilidad | 1.000 EUR | 5.000 EUR |
| CAC (coste adquisicion) | <50 EUR | <30 EUR |
| LTV/CAC ratio | >3 | >5 |
| NPS | >40 | >60 |

### 9.3 Metricas de SEO

| KPI | Objetivo 3 meses | Objetivo 6 meses |
|-----|-------------------|-------------------|
| Paginas indexadas | 20+ | 50+ |
| Keywords top 10 (ES) | 10 | 30 |
| Organic traffic | 500/mes | 2.000/mes |
| Domain authority | 15 | 25 |
| Featured snippets (JarabaLex) | 0 | 3+ |

---

## 10. Fuentes y Referencias

### Mercado LegalTech

- [Spain Online LegalTech Platforms Market](https://www.researchandmarkets.com/reports/6208880/spain-online-legaltech-platforms-market) - Research and Markets
- [Spain AI in LegalTech and Contract Automation Market](https://www.researchandmarkets.com/reports/6210402/spain-ai-in-legaltech-contract-automation-market) - Research and Markets
- [Spain AI LegalTech Market 2019-2030](https://www.kenresearch.com/spain-legaltech-contract-automation-market-analysis) - Ken Research
- [Spain Legal AI Market Size 2025-2030](https://www.grandviewresearch.com/horizon/outlook/legal-ai-market/spain) - Grand View Research
- [69 Best Legal Tech Startups in Spain](https://www.seedtable.com/best-legal-tech-startups-in-spain) - SeedTable
- [Top 100 Legal AI Companies in Spain 2026](https://ensun.io/search/legal-ai/spain) - ensun
- [GLTH Index: Adopcion tecnologica sector juridico](https://www.murciastartup.com/articulo/nuevas-tecnologias/legaltech-toma-bolsa-barcelona-global-legaltech-hub-presenta-primer-indice-madurez-tecnologica-sector-juridico/20260305044510007627.html) - GLTH 2026
- [Censo Numerico de Abogados](https://www.abogacia.es/publicaciones/abogacia-en-datos/censo-numerico-de-abogados/) - Abogacia Espanola
- [Mercado servicios juridicos Espana 2025](https://modelosdeplandenegocios.com/blogs/news/analisis-mercado-servicios-juridicos-espana) - Modelos Plan Negocios
- [I Estudio situacion abogacia madrilena](https://web.icam.es/i-estudio-integral-sobre-la-situacion-de-la-abogacia-el-71-de-los-abogados-ha-sufrido-algun-tipo-de-maltrato-descortesia-o-restriccion-en-el-ejercicio-profesional/) - ICAM

### Mercado Empleo y Digitalizacion

- [Digitalizacion y talento en 2026](https://talento.ahk.es/noticias/mercado-laboral/digitalizacion-talento-ejes-que-marcaran-mercado-laboral-2026) - AHK Talent
- [Mercado laboral espanol encrucijada 2026](https://digitalinside.es/el-mercado-laboral-espanol-ante-su-gran-encrucijada-hacia-2026/) - Digital Inside
- [Ofertas empleo IA crecen 454%](https://www.digitales.es/la-transformacion-digital-reconfigura-el-mercado-laboral-espanol-las-ofertas-de-empleo-en-ia-crecen-un-454-en-los-ultimos-cinco-anos/) - DigitalES
- [Cuantos autonomos hay en Espana 2026](https://ayudatpymes.com/gestron/cuantos-autonomos-hay-en-espana/) - Gestron
- [Evolucion autonomos Espana](https://www.infoautonomos.com/autonomos-espana-ley/autonomos-en-espana-evolucion-numero-altas-bajas/) - Infoautonomos
- [Cifras PYME mayo 2025](https://ipyme.org/Publicaciones/Cifras%20PYME/CifrasPyme_mayo_2025.pdf) - Ministerio Industria

### Fondos Europeos y B2G

- [Espana Digital 2026](http://espanadigital.gob.es/en/implementation-agenda) - Gobierno de Espana
- [Kit Digital: 3.067M EUR](https://espanadigital.gob.es/en/actualidad/presentado-el-programa-kit-digital-dotado-con-3000-millones-de-euros-para-la) - Red.es
- [Kit Digital 880.000 beneficiarios](https://www.autonomosyemprendedor.es/articulo/hacienda/medio-millon-autonomos-pymes-deberan-declarar-2026-ayudas-que-cobraron-kit-digital/20260119150700047862.html) - Autonomos y Emprendedor
- [NGEU Spain: 80.000M + 83.000M](https://www.caixabankresearch.com/en/economics-markets/public-sector/how-ngeu-funds-are-going-spain-bread-today-and-hope-tomorrow) - CaixaBank Research
- [Agencias de Colocacion](https://www.sepe.es/HomeSepe/empresas/ofertas-puestos-trabajo-en-empresas/agencias-colocacion.html) - SEPE

### Mercado AgriTech

- [Spain Agritech Market](https://www.kenresearch.com/spain-agritech-and-precision-farming-market) - Ken Research
- [Agri-Food Industry in Spain](https://www.investinspain.org/en/industries/agri-food) - Invest in Spain
- [Data-Driven Farming Spain](https://www.agroinvestspain.com/how-data-driven-farming-is-redefining-profitability-in-spain/) - AgroInvest Spain
- [AgriTech Europe 2026](https://tracxn.com/d/explore/agritech-startups-in-europe/__ctWV8WGqHQPSqedEvgPyJP2QDvpYCPzwKMd9cWLIcxE) - Tracxn

### SaaS Benchmarks

- [2025 SaaS Benchmarks Report](https://www.highalpha.com/saas-benchmarks) - High Alpha
- [SaaS Pricing Benchmarks 2025](https://www.getmonetizely.com/articles/saas-pricing-benchmarks-2025-how-do-your-monetization-metrics-stack-up) - Monetizely
- [B2B SaaS Benchmarks 2026](https://42dm.net/b2b-saas-benchmarks-to-track/) - 42DM

### Perfil Profesional del Fundador

- CV Fondos Europeos (sept 2024): `/mnt/f/DATOS/JJM/Gestion administrativa/Curriculos/Fondos Europeos/Pepe Jaraba - Curriculum Fondos Europeos_20240927.pdf`
- CV Profesional (nov 2023): `/mnt/f/DATOS/JJM/Gestion administrativa/Curriculos/Jose Jaraba Munoz-Curriculo Profesional 20231121.pdf`
- Europass EN (feb 2018): `/mnt/f/DATOS/JJM/Gestion administrativa/Curriculos/20180227e-Jose Jaraba-ecv_en.pdf`

---

## Registro de Cambios

| Version | Fecha | Autor | Cambio |
|---------|-------|-------|--------|
| 1.0 | 2026-03-06 | Claude Opus 4.6 | Documento inicial con investigacion de mercado completa |
