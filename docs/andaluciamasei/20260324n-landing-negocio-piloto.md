# 20260324n-landing-negocio-piloto

> Fuente: `04. Campaña inicial/Landing/landing-negocio-piloto.html`
> Convertido: 2026-03-25
> Tipo: Landing page HTML estática para captación de negocios piloto

---

## Propósito

Landing page pública diseñada para que negocios locales soliciten la prueba gratuita del programa Andalucía +ei. Orientada a dueños de negocios con necesidades digitales no cubiertas (sin web, redes abandonadas, reseñas sin responder).

## Estructura (5 secciones)

### 1. Hero
- Titular: "Mejore la presencia digital de su negocio. **Gratis.**"
- Subtítulo: servicio profesional 2-4 semanas, programa público Junta de Andalucía + UE
- CTA: "Solicitar prueba gratuita →" (ancla a #formulario)
- Trust badges: 46% inserción 1ª Ed. | 0€ coste | 0 compromiso
- Cofinanciación: UE · Junta de Andalucía · SAE

### 2. Servicios (5 cards)
Los 5 packs de servicio presentados como opciones:
1. Gestión de Redes Sociales (azul)
2. Creación de Web (morado)
3. Gestión de Reseñas (verde)
4. Asistencia Administrativa (naranja)
5. Tienda Online (rojo)

### 3. Cómo funciona (3 pasos)
1. Rellene el formulario (2 minutos)
2. Propuesta personalizada (ejemplos reales para SU negocio)
3. Prueba gratuita (profesional trabaja para usted 2-4 semanas)

### 4. Garantías
- Sin permanencia
- Sin coste oculto
- Supervisado por expertos
- Datos protegidos (RGPD)
- +30 años de experiencia

### 5. Formulario de captación
Campos del formulario (mapean a NegocioProspectadoEi):

| Campo formulario | Campo entity | Tipo | Requerido |
|-----------------|-------------|------|-----------|
| nombre_negocio | nombre_negocio | string | ✓ |
| persona_contacto | persona_contacto | string | ✓ |
| telefono | telefono | tel | ✓ |
| email | email | email | ✓ |
| provincia | provincia | select (sevilla/malaga) | ✓ |
| municipio | direccion | text | ✓ |
| sector | sector | select (9 opciones) | ✓ |
| empleados | _(nuevo campo potencial)_ | select | ✓ |
| web | url_web | url | |
| rrss | _(nuevo o campo existente)_ | url | |
| servicio[] | pack_compatible | checkbox múltiple | ✓ |
| problema | notas | textarea | |
| fuente | _(nuevo: fuente_captacion)_ | select (8 opciones) | ✓ |
| privacidad | _(consent RGPD)_ | checkbox | ✓ |

Honeypot anti-spam incluido (campo `website` oculto).

### 6. Footer
- PED S.L. · NIF: B93750271
- José Jaraba Muñoz · 623 17 43 04
- WhatsApp flotante (wa.me/34623174304)

## Integración con el SaaS

Para integrar esta landing en Jaraba Impact Platform:
1. Crear ruta pública `/andalucia-ei/prueba-gratuita` (sin auth)
2. Controller con formulario Drupal que mapea a NegocioProspectadoEi
3. estado_embudo = 'identificado' al crear
4. clasificacion_urgencia deducida del formulario (sin web + reseñas = rojo)
5. Notificación WhatsApp/email al coordinador (<2h, doc m)
6. Confirmación en pantalla post-envío
7. Respuesta al diseño de campaña Semana Santa (docs k, l, m)

## Notas de diseño
- CSS custom properties (no --ej-*, usa paleta propia temporal)
- Mobile-first (media queries a 600px)
- Botón WhatsApp flotante (posición fija bottom-right)
- Emojis en cards — REQUIERE migración a jaraba_icon() (ICON-EMOJI-001)
