170
ESPECIFICACIÓN TÉCNICA
Accesibilidad WCAG 2.1 AA
ARIA | Contraste | Teclado | Auditoría
Ecosistema Jaraba | 26 Enero 2026 | 40-50h
 
1. Resumen
WCAG 2.1 AA para todos los bloques, cumpliendo RD 1112/2018 España.
Normativa
WCAG 2.1 AA | RD 1112/2018 | EN 301 549 Europa
1.1 Principios
•	Perceptible: Alt text, contraste 4.5:1
•	Operable: Teclado, focus visible
•	Comprensible: Labels, errores claros
•	Robusto: ARIA roles correctos
 
2. Checklist por Bloque
2.1 Hero
Criterio	Requisito	Implementación
1.1.1	Alt text imágenes	Campo obligatorio
1.4.3	Contraste 4.5:1	Validación auto
2.1.1	Keyboard	tabindex, focus
2.2 Forms
Criterio	Requisito	Implementación
1.3.1	Labels asociados	for/id auto
3.3.1	Errores identificados	aria-invalid
4.1.2	Name Role Value	ARIA roles
3. Validación Contraste
 contrast.js
function checkContrast(fg, bg) {
  // Cálculo luminancia y ratio
  const ratio = calculateRatio(fg, bg);
  return {
    ratio: ratio.toFixed(2),
    passAA_normal: ratio >= 4.5,
    passAA_large: ratio >= 3
  };
}

4. APIs
Método	Endpoint	Descripción
GET	/api/v1/accessibility/audit/{id}	Auditoría WCAG
GET	/api/v1/accessibility/contrast	Verificar colores
5. Roadmap
Sprint	Componente	Horas
1	ARIA templates bloques	18-22h
2	Contraste + Teclado	15-18h
3	API auditoría	10-12h
Total: 40-50h (€3,200-€4,000)
Fin documento.
