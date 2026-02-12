# ADDENDUM TÉCNICO v2.1
## Actualización del Copiloto de Emprendimiento
### Nuevos Modos: Experto Tributario + Experto Seguridad Social

**Documento para:** EDI Google Antigravity  
**Proyecto:** Jaraba Impact Platform | Andalucía +ei  
**Fecha:** Enero 2026  
**Versión:** 2.1  

---

## 1. RESUMEN DE CAMBIOS

### 1.1 Nuevos Modos de Interacción

| ID | Modo | Icono | Descripción |
|----|------|-------|-------------|
| 6 | `TAX_EXPERT` | 🏛️ | Orientación fiscal y tributaria (Hacienda) |
| 7 | `SS_EXPERT` | 🛡️ | Orientación Seguridad Social (RETA) |

### 1.2 Archivos a Actualizar

| Archivo | Cambio Requerido |
|---------|------------------|
| `migraciones_sql_copiloto_v2.sql` | Añadir nuevos modos al ENUM |
| `openapi_copiloto_v2.yaml` | Actualizar schema de respuestas |
| `copilot_prompt_master_v2.md` | → Reemplazar por `v2_1.md` |
| `CopilotChatWidget.jsx` | Añadir iconos y estilos nuevos modos |
| `copilot_integration.module` | Añadir lógica de detección |

---

## 2. ACTUALIZACIONES DE BASE DE DATOS

### 2.1 Migración SQL - Nuevos Modos

```sql
-- ============================================================
-- MIGRACIÓN v2.1: Añadir modos Experto Tributario y Seg. Social
-- Ejecutar DESPUÉS de las migraciones v2.0
-- ============================================================

-- 2.1.1 Modificar ENUM de modos en copilot_conversation
ALTER TABLE copilot_conversation 
MODIFY COLUMN mode_detected ENUM(
    'COACH_EMOCIONAL',
    'CONSULTOR_TACTICO', 
    'SPARRING_PARTNER',
    'CFO_SINTETICO',
    'ABOGADO_DIABLO',
    'TAX_EXPERT',      -- NUEVO v2.1
    'SS_EXPERT'        -- NUEVO v2.1
) DEFAULT NULL;

-- 2.1.2 Crear tabla de base de conocimiento normativo
CREATE TABLE IF NOT EXISTS normative_knowledge_base (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain ENUM('TAX', 'SOCIAL_SECURITY') NOT NULL,
    topic VARCHAR(100) NOT NULL,
    content_key VARCHAR(100) NOT NULL UNIQUE,
    content_es TEXT NOT NULL,
    legal_reference VARCHAR(255) DEFAULT NULL,
    valid_from DATE NOT NULL,
    valid_until DATE DEFAULT NULL,
    last_verified DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_domain (domain),
    INDEX idx_topic (topic),
    INDEX idx_validity (valid_from, valid_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2.1.3 Insertar conocimiento base tributario
INSERT INTO normative_knowledge_base (domain, topic, content_key, content_es, legal_reference, valid_from, last_verified) VALUES

-- MODELOS AEAT
('TAX', 'ALTA_CENSAL', 'modelo_036', 'Declaración censal completa para inicio, modificación o cese de actividad. Obligatorio para sociedades y actividades complejas.', 'Orden EHA/1274/2007', '2007-05-10', '2025-01-15'),
('TAX', 'ALTA_CENSAL', 'modelo_037', 'Declaración censal simplificada. Para personas físicas residentes, sin IVA diferenciado ni grandes empresas.', 'Orden EHA/1274/2007', '2007-05-10', '2025-01-15'),
('TAX', 'IVA', 'modelo_303', 'Autoliquidación trimestral de IVA. Plazos: 1-20 abril/julio/octubre, 1-30 enero.', 'Orden HAP/2194/2013', '2014-01-01', '2025-01-15'),
('TAX', 'IRPF', 'modelo_130', 'Pago fraccionado IRPF en estimación directa. 20% del rendimiento neto acumulado.', 'Art. 109 LIRPF', '2007-01-01', '2025-01-15'),
('TAX', 'IRPF', 'modelo_131', 'Pago fraccionado IRPF en estimación objetiva (módulos).', 'Art. 109 LIRPF', '2007-01-01', '2025-01-15'),

-- TIPOS IVA
('TAX', 'IVA', 'tipo_general', 'Tipo general: 21%. Aplicable a la mayoría de bienes y servicios.', 'Art. 90 LIVA', '2012-09-01', '2025-01-15'),
('TAX', 'IVA', 'tipo_reducido', 'Tipo reducido: 10%. Alimentos, transporte, hostelería, vivienda.', 'Art. 91.Uno LIVA', '2012-09-01', '2025-01-15'),
('TAX', 'IVA', 'tipo_superreducido', 'Tipo superreducido: 4%. Pan, leche, frutas, verduras, libros, medicamentos.', 'Art. 91.Dos LIVA', '2012-09-01', '2025-01-15'),

-- GASTOS DEDUCIBLES
('TAX', 'IRPF', 'gastos_suministros', 'Suministros del hogar (autónomo en casa): 30% de la parte proporcional.', 'Art. 30.2.5ª LIRPF', '2018-01-01', '2025-01-15'),
('TAX', 'IRPF', 'gastos_vehiculo', 'Vehículo: Solo 100% deducible si uso exclusivo profesional demostrable (difícil). Regla práctica: 50%.', 'Art. 29 LIRPF + Jurisprudencia', '2007-01-01', '2025-01-15'),

-- CALENDARIO FISCAL
('TAX', 'CALENDARIO', 'trimestre_1', 'Modelo 303 y 130: Del 1 al 20 de abril.', 'Orden HAP/2194/2013', '2014-01-01', '2025-01-15'),
('TAX', 'CALENDARIO', 'trimestre_2', 'Modelo 303 y 130: Del 1 al 20 de julio.', 'Orden HAP/2194/2013', '2014-01-01', '2025-01-15'),
('TAX', 'CALENDARIO', 'trimestre_3', 'Modelo 303 y 130: Del 1 al 20 de octubre.', 'Orden HAP/2194/2013', '2014-01-01', '2025-01-15'),
('TAX', 'CALENDARIO', 'trimestre_4', 'Modelo 303, 130 y resúmenes anuales: Del 1 al 30 de enero.', 'Orden HAP/2194/2013', '2014-01-01', '2025-01-15'),

-- VERIFACTU
('TAX', 'FACTURACION', 'verifactu_2025', 'Sistema Verifactu: Facturación electrónica obligatoria progresiva desde 2025. Empresas >8M€ primero.', 'RD 1007/2023 + Ley 18/2022', '2025-07-01', '2025-01-15');

-- 2.1.4 Insertar conocimiento base Seguridad Social
INSERT INTO normative_knowledge_base (domain, topic, content_key, content_es, legal_reference, valid_from, last_verified) VALUES

-- TARIFA PLANA
('SOCIAL_SECURITY', 'CUOTA', 'tarifa_plana_2024', 'Cuota reducida: 80€/mes durante 12 meses. Prórroga 12 meses más si rendimientos < SMI.', 'RDL 13/2022 + Ley 31/2022', '2023-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'CUOTA', 'tarifa_plana_requisitos', 'Requisitos: No haber sido autónomo en últimos 2 años (3 si disfrutó bonificación previa). No ser autónomo societario.', 'Art. 38 ter LETA', '2023-01-01', '2025-01-15'),

-- COTIZACIÓN POR INGRESOS
('SOCIAL_SECURITY', 'CUOTA', 'cotizacion_ingresos_2024', 'Sistema de cotización por ingresos reales desde 2023. 15 tramos según rendimiento neto.', 'RDL 13/2022', '2023-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'CUOTA', 'base_minima_2024', 'Base mínima cotización 2024: 950,98€/mes', 'Orden PJC/51/2024', '2024-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'CUOTA', 'base_maxima_2024', 'Base máxima cotización 2024: 4.720,50€/mes', 'Orden PJC/51/2024', '2024-01-01', '2025-01-15'),

-- TRAMOS 2024
('SOCIAL_SECURITY', 'CUOTA', 'tramo_1_2024', 'Rendimiento ≤670€/mes: Base 735,29€ → Cuota ~230€', 'RDL 13/2022 + Orden 2024', '2024-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'CUOTA', 'tramo_2_2024', 'Rendimiento 670-900€/mes: Base 816,98€ → Cuota ~260€', 'RDL 13/2022 + Orden 2024', '2024-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'CUOTA', 'tramo_3_2024', 'Rendimiento 900-1.166€/mes: Base 872,55€ → Cuota ~280€', 'RDL 13/2022 + Orden 2024', '2024-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'CUOTA', 'tramo_medio_2024', 'Rendimiento 1.300-1.500€/mes: Base 1.045,75€ → Cuota ~335€', 'RDL 13/2022 + Orden 2024', '2024-01-01', '2025-01-15'),

-- PRESTACIONES
('SOCIAL_SECURITY', 'PRESTACIONES', 'it_autonomos', 'Incapacidad Temporal: Desde día 4. Días 4-20: 60% base. Desde día 21: 75% base.', 'Art. 169 LGSS', '2019-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'PRESTACIONES', 'cese_actividad', 'Cese de actividad: Mínimo 12 meses cotización. Duración según cotización acumulada. 70% base reguladora.', 'Ley 32/2010 + RDL 28/2018', '2019-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'PRESTACIONES', 'maternidad_paternidad', 'Maternidad/Paternidad: 16 semanas, 100% base reguladora. Obligatorias 6 semanas inmediatas.', 'RDL 6/2019', '2021-01-01', '2025-01-15'),

-- BONIFICACIONES
('SOCIAL_SECURITY', 'BONIFICACIONES', 'conciliacion', 'Bonificación 100% cuota por conciliación: Hijos < 12 años, 12 meses.', 'Art. 38 LETA', '2019-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'BONIFICACIONES', 'discapacidad', 'Discapacidad ≥33%: Tarifa plana extendida 5 años (80€ primeros 12 meses, luego 50%).', 'Art. 38 ter LETA', '2023-01-01', '2025-01-15'),

-- COMPATIBILIDADES
('SOCIAL_SECURITY', 'COMPATIBILIDAD', 'pluriactividad', 'Pluriactividad (autónomo + cuenta ajena): Posible bonificación 50% base mínima si cotiza >50% en RG.', 'DA 16ª LGSS', '2013-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'COMPATIBILIDAD', 'jubilacion_activa', 'Jubilación activa: Compatible con 50% pensión si cumple requisitos de carrera completa.', 'Art. 214 LGSS', '2013-01-01', '2025-01-15'),

-- ALTA
('SOCIAL_SECURITY', 'ALTA', 'plazo_alta', 'Plazo alta RETA: Hasta 60 días ANTES o hasta 30 días DESPUÉS del inicio real de actividad.', 'Art. 30 LETA', '2007-01-01', '2025-01-15'),
('SOCIAL_SECURITY', 'ALTA', 'documentacion', 'Documentación alta: DNI/NIE, modelo TA.0521, modelo 036/037 de Hacienda. Preferiblemente con certificado digital.', 'TGSS', '2007-01-01', '2025-01-15');

-- 2.1.5 Crear índice de triggers para los nuevos modos
CREATE TABLE IF NOT EXISTS copilot_mode_triggers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mode ENUM(
        'COACH_EMOCIONAL',
        'CONSULTOR_TACTICO',
        'SPARRING_PARTNER',
        'CFO_SINTETICO',
        'ABOGADO_DIABLO',
        'TAX_EXPERT',
        'SS_EXPERT'
    ) NOT NULL,
    trigger_word VARCHAR(50) NOT NULL,
    weight INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_mode (mode),
    INDEX idx_trigger (trigger_word)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar triggers para nuevos modos
INSERT INTO copilot_mode_triggers (mode, trigger_word, weight) VALUES
-- TAX_EXPERT triggers
('TAX_EXPERT', 'hacienda', 10),
('TAX_EXPERT', 'iva', 10),
('TAX_EXPERT', 'irpf', 10),
('TAX_EXPERT', 'modelo', 8),
('TAX_EXPERT', '303', 10),
('TAX_EXPERT', '130', 10),
('TAX_EXPERT', '131', 10),
('TAX_EXPERT', '036', 10),
('TAX_EXPERT', '037', 10),
('TAX_EXPERT', 'declaracion', 7),
('TAX_EXPERT', 'factura', 8),
('TAX_EXPERT', 'facturacion', 8),
('TAX_EXPERT', 'impuestos', 9),
('TAX_EXPERT', 'fiscal', 9),
('TAX_EXPERT', 'tributario', 9),
('TAX_EXPERT', 'aeat', 10),
('TAX_EXPERT', 'agencia tributaria', 10),
('TAX_EXPERT', 'epigrafe', 9),
('TAX_EXPERT', 'iae', 9),
('TAX_EXPERT', 'trimestre', 6),
('TAX_EXPERT', 'deducir', 8),
('TAX_EXPERT', 'deducible', 8),
('TAX_EXPERT', 'gastos deducibles', 10),
('TAX_EXPERT', 'verifactu', 10),
('TAX_EXPERT', 'retencion', 8),

-- SS_EXPERT triggers
('SS_EXPERT', 'autonomo', 8),
('SS_EXPERT', 'cuota', 7),
('SS_EXPERT', 'reta', 10),
('SS_EXPERT', 'tarifa plana', 10),
('SS_EXPERT', 'seguridad social', 10),
('SS_EXPERT', 'cotizacion', 9),
('SS_EXPERT', 'cotizar', 8),
('SS_EXPERT', 'alta autonomo', 10),
('SS_EXPERT', 'baja autonomo', 10),
('SS_EXPERT', 'pluriactividad', 10),
('SS_EXPERT', 'prestacion', 7),
('SS_EXPERT', 'incapacidad', 9),
('SS_EXPERT', 'maternidad', 8),
('SS_EXPERT', 'paternidad', 8),
('SS_EXPERT', 'cese actividad', 10),
('SS_EXPERT', 'jubilacion', 8),
('SS_EXPERT', 'pension', 7),
('SS_EXPERT', 'bonificacion', 8),
('SS_EXPERT', 'base cotizacion', 10),
('SS_EXPERT', 'tesoreria', 9),
('SS_EXPERT', 'tgss', 10);

-- 2.1.6 Stored Procedure para consultar base de conocimiento
DELIMITER //

CREATE PROCEDURE sp_get_normative_knowledge(
    IN p_domain VARCHAR(20),
    IN p_topic VARCHAR(100)
)
BEGIN
    SELECT 
        content_key,
        content_es,
        legal_reference,
        valid_from,
        valid_until,
        last_verified
    FROM normative_knowledge_base
    WHERE domain = p_domain
    AND (p_topic IS NULL OR topic = p_topic)
    AND (valid_until IS NULL OR valid_until >= CURDATE())
    ORDER BY topic, content_key;
END //

DELIMITER ;
```

---

## 3. ACTUALIZACIÓN OPENAPI

### 3.1 Nuevos valores en schema `CopilotMode`

```yaml
# Añadir en components/schemas/CopilotMode
CopilotMode:
  type: string
  enum:
    - COACH_EMOCIONAL
    - CONSULTOR_TACTICO
    - SPARRING_PARTNER
    - CFO_SINTETICO
    - ABOGADO_DIABLO
    - TAX_EXPERT        # NUEVO v2.1
    - SS_EXPERT         # NUEVO v2.1
  description: |
    Modo de interacción detectado:
    - COACH_EMOCIONAL: Soporte emocional y motivacional
    - CONSULTOR_TACTICO: Instrucciones paso a paso
    - SPARRING_PARTNER: Simulación y práctica
    - CFO_SINTETICO: Análisis financiero y precios
    - ABOGADO_DIABLO: Desafío de hipótesis
    - TAX_EXPERT: Orientación tributaria (Hacienda)
    - SS_EXPERT: Orientación Seguridad Social (RETA)
```

### 3.2 Nuevo endpoint para consulta normativa

```yaml
# Añadir en paths
/api/copilot/normative/{domain}:
  get:
    summary: Consulta base de conocimiento normativo
    description: Obtiene información fiscal o de Seguridad Social para enriquecer respuestas
    tags:
      - Copilot
    parameters:
      - name: domain
        in: path
        required: true
        schema:
          type: string
          enum: [TAX, SOCIAL_SECURITY]
      - name: topic
        in: query
        required: false
        schema:
          type: string
        description: Filtrar por tema (ej. IVA, CUOTA, ALTA)
    responses:
      '200':
        description: Lista de conocimiento normativo
        content:
          application/json:
            schema:
              type: array
              items:
                $ref: '#/components/schemas/NormativeKnowledge'

# Añadir en components/schemas
NormativeKnowledge:
  type: object
  properties:
    content_key:
      type: string
      example: "tarifa_plana_2024"
    content_es:
      type: string
      example: "Cuota reducida: 80€/mes durante 12 meses..."
    legal_reference:
      type: string
      example: "RDL 13/2022"
    valid_from:
      type: string
      format: date
    valid_until:
      type: string
      format: date
      nullable: true
    last_verified:
      type: string
      format: date
```

---

## 4. ACTUALIZACIÓN COMPONENTE REACT

### 4.1 Añadir iconos y estilos para nuevos modos

```jsx
// Añadir en CopilotChatWidget.jsx

// En la constante MODE_CONFIG (alrededor de línea 50)
const MODE_CONFIG = {
  COACH_EMOCIONAL: {
    icon: '🩷',
    color: '#EC4899',
    label: 'Coach Emocional',
    bgColor: 'bg-pink-50'
  },
  CONSULTOR_TACTICO: {
    icon: '🎯',
    color: '#3B82F6',
    label: 'Consultor Táctico',
    bgColor: 'bg-blue-50'
  },
  SPARRING_PARTNER: {
    icon: '🥊',
    color: '#F97316',
    label: 'Sparring Partner',
    bgColor: 'bg-orange-50'
  },
  CFO_SINTETICO: {
    icon: '💰',
    color: '#10B981',
    label: 'CFO Sintético',
    bgColor: 'bg-emerald-50'
  },
  ABOGADO_DIABLO: {
    icon: '😈',
    color: '#8B5CF6',
    label: 'Abogado del Diablo',
    bgColor: 'bg-purple-50'
  },
  // NUEVOS v2.1
  TAX_EXPERT: {
    icon: '🏛️',
    color: '#0EA5E9',
    label: 'Experto Tributario',
    bgColor: 'bg-sky-50',
    disclaimer: true
  },
  SS_EXPERT: {
    icon: '🛡️',
    color: '#14B8A6',
    label: 'Experto Seg. Social',
    bgColor: 'bg-teal-50',
    disclaimer: true
  }
};

// Componente de disclaimer para modos expertos
const ExpertDisclaimer = () => (
  <div className="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-800">
    <span className="font-medium">⚠️ Aviso:</span> Esta información es orientativa y de carácter general. 
    La normativa puede cambiar y cada situación es única. Para decisiones importantes, 
    consulta con un profesional colegiado.
  </div>
);

// En el renderizado del mensaje, añadir después del contenido:
{MODE_CONFIG[message.mode]?.disclaimer && <ExpertDisclaimer />}
```

### 4.2 Añadir indicador de fuente normativa

```jsx
// Componente para mostrar referencia legal
const LegalReference = ({ reference, lastVerified }) => (
  <div className="mt-2 text-xs text-gray-500 flex items-center gap-2">
    <span>📜 {reference}</span>
    <span>•</span>
    <span>Verificado: {lastVerified}</span>
  </div>
);
```

---

## 5. LÓGICA DE DETECCIÓN DE MODOS

### 5.1 Actualizar Rules Engine

```php
// En copilot_integration.module

/**
 * Detecta el modo apropiado basándose en el mensaje del usuario.
 * ACTUALIZADO v2.1: Añadidos TAX_EXPERT y SS_EXPERT
 */
function _copilot_detect_mode(string $message, array $entrepreneur_profile): string {
  $message_lower = mb_strtolower($message);
  $scores = [];
  
  // Cargar triggers desde BD
  $triggers = \Drupal::database()->select('copilot_mode_triggers', 't')
    ->fields('t', ['mode', 'trigger_word', 'weight'])
    ->execute()
    ->fetchAll();
  
  foreach ($triggers as $trigger) {
    if (strpos($message_lower, $trigger->trigger_word) !== FALSE) {
      $scores[$trigger->mode] = ($scores[$trigger->mode] ?? 0) + $trigger->weight;
    }
  }
  
  // Priorizar modos expertos si hay triggers claros
  if (isset($scores['TAX_EXPERT']) && $scores['TAX_EXPERT'] >= 8) {
    return 'TAX_EXPERT';
  }
  if (isset($scores['SS_EXPERT']) && $scores['SS_EXPERT'] >= 8) {
    return 'SS_EXPERT';
  }
  
  // Lógica existente para otros modos...
  // [código existente]
  
  // Default
  return 'CONSULTOR_TACTICO';
}

/**
 * Enriquece el contexto con conocimiento normativo para modos expertos.
 * NUEVO v2.1
 */
function _copilot_enrich_with_normative(string $mode, string $message): array {
  if (!in_array($mode, ['TAX_EXPERT', 'SS_EXPERT'])) {
    return [];
  }
  
  $domain = ($mode === 'TAX_EXPERT') ? 'TAX' : 'SOCIAL_SECURITY';
  
  // Detectar temas mencionados
  $topics = _copilot_detect_normative_topics($message, $domain);
  
  // Consultar base de conocimiento
  $knowledge = [];
  foreach ($topics as $topic) {
    $result = \Drupal::database()->query(
      'CALL sp_get_normative_knowledge(:domain, :topic)',
      [':domain' => $domain, ':topic' => $topic]
    )->fetchAll();
    
    $knowledge = array_merge($knowledge, $result);
  }
  
  return $knowledge;
}
```

---

## 6. ACTUALIZACIÓN DEL PROMPT DINÁMICO

### 6.1 Inyección de contexto normativo

```
### CONOCIMIENTO NORMATIVO RELEVANTE (Solo para modos TAX_EXPERT/SS_EXPERT)

{{#if mode == "TAX_EXPERT" || mode == "SS_EXPERT"}}
{{#each normative_context as item}}
📜 {{item.content_key}}: {{item.content_es}}
   Ref: {{item.legal_reference}} | Verificado: {{item.last_verified}}
{{/each}}

⚠️ RECORDATORIO OBLIGATORIO:
Toda respuesta en este modo DEBE terminar con el disclaimer:
"Esta información es orientativa. La normativa puede cambiar y cada caso es único. 
Para decisiones importantes, consulta con un profesional colegiado."
{{/if}}
```

---

## 7. TESTS Y VALIDACIÓN

### 7.1 Casos de prueba para nuevos modos

```javascript
// tests/copilot_modes_v21.test.js

describe('TAX_EXPERT mode detection', () => {
  const taxTriggers = [
    'Tengo que presentar el modelo 303',
    '¿Qué IVA aplico a mis servicios?',
    '¿Cómo deduzco los gastos del coche?',
    'No entiendo cómo funciona Verifactu',
    '¿Cuándo tengo que hacer la declaración trimestral?'
  ];
  
  taxTriggers.forEach(message => {
    it(`should detect TAX_EXPERT for: "${message}"`, async () => {
      const result = await copilotService.detectMode(message);
      expect(result.mode).toBe('TAX_EXPERT');
    });
  });
});

describe('SS_EXPERT mode detection', () => {
  const ssTriggers = [
    '¿Cuánto cuesta la cuota de autónomo?',
    '¿Puedo acogerme a la tarifa plana?',
    '¿Cómo me doy de alta en el RETA?',
    '¿Qué pasa si trabajo por cuenta ajena y quiero ser autónomo?',
    '¿Tengo derecho a baja por maternidad?'
  ];
  
  ssTriggers.forEach(message => {
    it(`should detect SS_EXPERT for: "${message}"`, async () => {
      const result = await copilotService.detectMode(message);
      expect(result.mode).toBe('SS_EXPERT');
    });
  });
});

describe('Expert modes include disclaimer', () => {
  it('TAX_EXPERT response should include disclaimer', async () => {
    const response = await copilotService.chat({
      message: '¿Qué modelo presento para el IVA?',
      userId: 'test-user'
    });
    
    expect(response.text).toContain('orientativa');
    expect(response.text).toContain('profesional');
  });
  
  it('SS_EXPERT response should include disclaimer', async () => {
    const response = await copilotService.chat({
      message: '¿Cuánto es la tarifa plana?',
      userId: 'test-user'
    });
    
    expect(response.text).toContain('orientativa');
    expect(response.text).toContain('profesional');
  });
});
```

---

## 8. CHECKLIST DE IMPLEMENTACIÓN v2.1

| # | Tarea | Prioridad | Sprint |
|---|-------|-----------|--------|
| 1 | Ejecutar migraciones SQL v2.1 | Alta | Sprint 3+ |
| 2 | Actualizar ENUM de modos en código | Alta | Sprint 3+ |
| 3 | Añadir triggers de detección en BD | Alta | Sprint 3+ |
| 4 | Implementar consulta a base normativa | Media | Sprint 3+ |
| 5 | Actualizar CopilotChatWidget.jsx | Media | Sprint 5+ |
| 6 | Añadir disclaimer en respuestas | Alta | Sprint 3+ |
| 7 | Actualizar OpenAPI con nuevos schemas | Media | Sprint 3+ |
| 8 | Reemplazar prompt_master por v2.1 | Alta | Sprint 3+ |
| 9 | Tests de detección de modos | Media | Sprint 3+ |
| 10 | Tests de contenido de respuestas | Media | Sprint 4+ |

---

## 9. CONSIDERACIONES DE MANTENIMIENTO

### 9.1 Actualización de base de conocimiento normativo

La tabla `normative_knowledge_base` debe actualizarse cuando:
- Cambien tipos impositivos (IVA, IRPF)
- Se publiquen nuevas órdenes de cotización SS
- Cambien plazos o modelos de la AEAT
- Entre en vigor nueva normativa (ej. Verifactu completo)

**Proceso recomendado:**
1. Revisar BOE/BOJA trimestralmente
2. Actualizar registros afectados
3. Actualizar campo `last_verified`
4. Si hay cambio sustancial, crear nuevo registro y marcar `valid_until` en anterior

### 9.2 Disclaimer legal

El disclaimer **NO ES NEGOCIABLE**. Debe aparecer siempre en respuestas de:
- `TAX_EXPERT`
- `SS_EXPERT`

Esto protege tanto al emprendedor como a la plataforma de responsabilidad por consejo específico.

---

*Documento preparado para EDI Google Antigravity*  
*Jaraba Impact Platform | Andalucía +ei v2.1 | Enero 2026*
