# Guía de Usuario: Entrenamiento del Copiloto IA

Sistema de entrenamiento del conocimiento del negocio para personalizar las respuestas del Copiloto IA.

---

## 📍 Acceso

**Dashboard principal:** `/knowledge`

---

## 🎯 Secciones del Panel

### 1. Información del Negocio
Configura los datos básicos que el Copiloto usará para contextualizar sus respuestas:

| Campo | Descripción |
|-------|-------------|
| Nombre del negocio | Cómo se presentará el Copiloto |
| Descripción | Contexto sobre tu empresa |
| Industria | Sector para respuestas especializadas |
| Tono de comunicación | Formal, casual, amigable, etc. |
| Horario de atención | Para consultas de disponibilidad |
| Ubicación | Para referencias geográficas |

### 2. FAQs (Preguntas Frecuentes)
Enseña al Copiloto las preguntas más comunes:

- **Categorías:** Organiza por temas (General, Productos, Envíos...)
- **Prioridad:** Las FAQs con mayor prioridad se sugieren primero
- **Indexación:** Las FAQs se indexan automáticamente para búsqueda semántica

### 3. Políticas y Procedimientos
Documenta las políticas del negocio:

- Política de devoluciones
- Términos de servicio
- Procedimientos de atención
- Normativas aplicables

### 4. Documentos (PDFs/DOCs)
Sube documentos para que el Copiloto extraiga conocimiento:

- **Formatos soportados:** PDF, DOC, DOCX, TXT
- **Procesamiento:** Apache Tika extrae el texto
- **Chunking:** Documentos grandes se dividen en fragmentos

### 5. Productos y Servicios
Enriquece la información de tus productos:

- Descripción detallada
- Especificaciones técnicas
- Beneficios y casos de uso
- FAQs específicas del producto

### 6. Correcciones de IA
Cuando el Copiloto se equivoque, registra la corrección:

1. Copia la pregunta original
2. Pega la respuesta incorrecta
3. Escribe la respuesta correcta
4. El sistema generará una regla automática

---

## 🧪 Consola de Pruebas

**Ruta:** `/knowledge/test`

Prueba cómo responde el Copiloto con tu conocimiento:

1. Escribe una pregunta de ejemplo
2. El sistema busca en tu base de conocimiento
3. Muestra la respuesta + fuentes utilizadas
4. Verifica que las respuestas son correctas

### Estadísticas de Cobertura
- **FAQs:** Número de preguntas configuradas
- **Políticas:** Documentos de políticas
- **Documentos:** Archivos procesados
- **Productos:** Información enriquecida
- **Cobertura %:** Porcentaje de secciones completadas

---

## 💡 Mejores Prácticas

### Para FAQs
- ✅ Usa preguntas naturales (como las haría un cliente)
- ✅ Respuestas concisas pero completas
- ✅ Incluye variaciones de la misma pregunta
- ❌ Evita jerga técnica innecesaria

### Para Políticas
- ✅ Títulos descriptivos
- ✅ Estructura por secciones claras
- ✅ Fecha de última actualización
- ❌ Evita documentos muy largos sin estructura

### Para Correcciones
- ✅ Registra errores inmediatamente
- ✅ Sé específico en la corrección
- ✅ Indica el contexto del error
- ❌ No corrijas estilos, solo errores factuales

---

## 🔗 API Disponible

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/api/v1/knowledge/context` | GET | Contexto XML del tenant |
| `/api/v1/knowledge/search` | GET | Búsqueda semántica |
| `/api/v1/knowledge/test` | POST | Probar pregunta |

---

## 📊 Arquitectura del Prompt

El Copiloto construye su contexto así:

```
1. Brand Voice       → Personalidad configurada
2. Skills            → Cómo actuar (jerárquico)
3. Business Context  → Info de tu negocio
4. Corrections       → Reglas de errores previos
5. RAG Results       → Conocimiento relevante per-query
```

---

*Última actualización: 2026-02-06*
