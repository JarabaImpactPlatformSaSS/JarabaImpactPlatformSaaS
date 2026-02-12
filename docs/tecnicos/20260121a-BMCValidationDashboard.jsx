/**
 * BMC VALIDATION DASHBOARD v2.0
 * Dashboard interactivo del Business Model Canvas con estado de validación
 * Programa Andalucía +ei | Jaraba Impact Platform
 * 
 * Dependencias:
 * - React 18+
 * - Tailwind CSS
 * - Lucide React
 */

import React, { useState, useEffect, useMemo } from 'react';
import {
  Target,
  Users,
  Megaphone,
  Heart,
  DollarSign,
  Key,
  Settings,
  Handshake,
  PiggyBank,
  ChevronRight,
  Plus,
  TrendingUp,
  AlertCircle,
  CheckCircle2,
  Clock,
  Beaker,
  Lightbulb,
  X,
  ExternalLink
} from 'lucide-react';

// ============================================================================
// CONFIGURACIÓN DE BLOQUES BMC
// ============================================================================

const BMC_BLOCKS = {
  KP: {
    id: 'KP',
    name: 'Alianzas Clave',
    nameShort: 'Alianzas',
    icon: Handshake,
    color: 'indigo',
    questions: [
      '¿Quiénes son tus socios estratégicos?',
      '¿Qué recursos obtienes de ellos?',
      '¿Qué actividades realizan ellos?'
    ],
    position: { row: '1/3', col: '1/2' }
  },
  KA: {
    id: 'KA',
    name: 'Actividades Clave',
    nameShort: 'Actividades',
    icon: Settings,
    color: 'violet',
    questions: [
      '¿Qué actividades requiere tu propuesta de valor?',
      '¿Qué haces para llegar a los clientes?',
      '¿Qué procesos son críticos?'
    ],
    position: { row: '1/2', col: '2/3' }
  },
  KR: {
    id: 'KR',
    name: 'Recursos Clave',
    nameShort: 'Recursos',
    icon: Key,
    color: 'fuchsia',
    questions: [
      '¿Qué recursos necesitas para crear valor?',
      '¿Recursos físicos, intelectuales, humanos, financieros?'
    ],
    position: { row: '2/3', col: '2/3' }
  },
  VP: {
    id: 'VP',
    name: 'Propuesta de Valor',
    nameShort: 'Propuesta',
    icon: Target,
    color: 'emerald',
    questions: [
      '¿Qué valor entregas al cliente?',
      '¿Qué problema resuelves?',
      '¿Qué necesidad satisfaces?'
    ],
    position: { row: '1/3', col: '3/4' }
  },
  CR: {
    id: 'CR',
    name: 'Relación con Clientes',
    nameShort: 'Relaciones',
    icon: Heart,
    color: 'pink',
    questions: [
      '¿Qué tipo de relación espera cada segmento?',
      '¿Cómo mantienes la relación?',
      '¿Qué coste tiene?'
    ],
    position: { row: '1/2', col: '4/5' }
  },
  CH: {
    id: 'CH',
    name: 'Canales',
    nameShort: 'Canales',
    icon: Megaphone,
    color: 'orange',
    questions: [
      '¿Cómo llegas a tus clientes?',
      '¿Cómo se enteran de ti?',
      '¿Cómo compran y reciben el producto?'
    ],
    position: { row: '2/3', col: '4/5' }
  },
  CS: {
    id: 'CS',
    name: 'Segmentos de Clientes',
    nameShort: 'Clientes',
    icon: Users,
    color: 'blue',
    questions: [
      '¿Para quién creas valor?',
      '¿Quiénes son tus clientes más importantes?',
      '¿Qué características tienen?'
    ],
    position: { row: '1/3', col: '5/6' }
  },
  'C$': {
    id: 'C$',
    name: 'Estructura de Costes',
    nameShort: 'Costes',
    icon: PiggyBank,
    color: 'red',
    questions: [
      '¿Cuáles son los costes más importantes?',
      '¿Qué recursos son más caros?',
      '¿Qué actividades cuestan más?'
    ],
    position: { row: '3/4', col: '1/4' }
  },
  RS: {
    id: 'RS',
    name: 'Fuentes de Ingresos',
    nameShort: 'Ingresos',
    icon: DollarSign,
    color: 'green',
    questions: [
      '¿Por qué valor pagan los clientes?',
      '¿Cómo pagan actualmente?',
      '¿Cómo preferirían pagar?'
    ],
    position: { row: '3/4', col: '4/6' }
  }
};

// Colores de Tailwind para cada estado
const STATUS_COLORS = {
  RED: {
    bg: 'bg-red-50',
    border: 'border-red-200',
    text: 'text-red-700',
    progress: 'bg-red-500',
    icon: AlertCircle,
    label: 'Necesita validación'
  },
  YELLOW: {
    bg: 'bg-amber-50',
    border: 'border-amber-200',
    text: 'text-amber-700',
    progress: 'bg-amber-500',
    icon: Clock,
    label: 'En progreso'
  },
  GREEN: {
    bg: 'bg-emerald-50',
    border: 'border-emerald-200',
    text: 'text-emerald-700',
    progress: 'bg-emerald-500',
    icon: CheckCircle2,
    label: 'Validado'
  }
};

// ============================================================================
// COMPONENTE PRINCIPAL
// ============================================================================

export default function BMCValidationDashboard({
  entrepreneurId,
  entrepreneurName,
  validationData = [],
  hypotheses = [],
  onBlockClick = null,
  onHypothesisClick = null,
  onAddHypothesis = null,
  onStartExperiment = null,
  apiEndpoint = '/api/bmc/validation'
}) {
  const [selectedBlock, setSelectedBlock] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [localValidationData, setLocalValidationData] = useState(validationData);

  // Cargar datos si no se proporcionan
  useEffect(() => {
    if (validationData.length === 0 && entrepreneurId) {
      loadValidationData();
    }
  }, [entrepreneurId]);

  useEffect(() => {
    setLocalValidationData(validationData);
  }, [validationData]);

  const loadValidationData = async () => {
    setIsLoading(true);
    try {
      const response = await fetch(`${apiEndpoint}/${entrepreneurId}`);
      const data = await response.json();
      setLocalValidationData(data.blocks || []);
    } catch (error) {
      console.error('Error cargando datos BMC:', error);
    } finally {
      setIsLoading(false);
    }
  };

  // Mapear datos de validación por bloque
  const validationByBlock = useMemo(() => {
    const map = {};
    localValidationData.forEach(block => {
      map[block.block || block.bmc_block] = block;
    });
    return map;
  }, [localValidationData]);

  // Calcular progreso global
  const overallProgress = useMemo(() => {
    if (localValidationData.length === 0) return 0;
    const total = localValidationData.reduce((sum, b) => sum + (b.validation_percentage || 0), 0);
    return Math.round(total / localValidationData.length);
  }, [localValidationData]);

  // Hipótesis por bloque
  const hypothesesByBlock = useMemo(() => {
    const map = {};
    hypotheses.forEach(h => {
      if (!map[h.bmc_block]) map[h.bmc_block] = [];
      map[h.bmc_block].push(h);
    });
    return map;
  }, [hypotheses]);

  // ============================================================================
  // HANDLERS
  // ============================================================================

  const handleBlockClick = (blockId) => {
    setSelectedBlock(blockId === selectedBlock ? null : blockId);
    onBlockClick?.(blockId);
  };

  // ============================================================================
  // RENDER
  // ============================================================================

  const renderBlock = (blockId) => {
    const config = BMC_BLOCKS[blockId];
    const validation = validationByBlock[blockId] || { 
      validation_percentage: 0, 
      status: 'RED',
      hypotheses_total: 0,
      hypotheses_validated: 0
    };
    const status = STATUS_COLORS[validation.status] || STATUS_COLORS.RED;
    const Icon = config.icon;
    const StatusIcon = status.icon;
    const blockHypotheses = hypothesesByBlock[blockId] || [];
    const isSelected = selectedBlock === blockId;

    return (
      <div
        key={blockId}
        onClick={() => handleBlockClick(blockId)}
        className={`
          relative p-3 rounded-xl cursor-pointer transition-all duration-200
          ${status.bg} ${status.border} border-2
          ${isSelected ? 'ring-2 ring-offset-2 ring-blue-500 scale-[1.02]' : 'hover:scale-[1.01]'}
        `}
        style={{
          gridRow: config.position.row,
          gridColumn: config.position.col
        }}
      >
        {/* Header del bloque */}
        <div className="flex items-start justify-between mb-2">
          <div className="flex items-center gap-2">
            <div className={`p-1.5 rounded-lg bg-${config.color}-100`}>
              <Icon className={`w-4 h-4 text-${config.color}-600`} />
            </div>
            <div>
              <h3 className="font-semibold text-gray-800 text-sm leading-tight">
                {config.nameShort}
              </h3>
              <p className="text-[10px] text-gray-500">{config.name}</p>
            </div>
          </div>
          <StatusIcon className={`w-4 h-4 ${status.text}`} />
        </div>

        {/* Barra de progreso */}
        <div className="mb-2">
          <div className="flex justify-between text-[10px] mb-1">
            <span className={status.text}>{validation.validation_percentage}%</span>
            <span className="text-gray-500">
              {validation.hypotheses_validated || 0}/{validation.hypotheses_total || 0} validadas
            </span>
          </div>
          <div className="h-2 bg-gray-200 rounded-full overflow-hidden">
            <div 
              className={`h-full ${status.progress} transition-all duration-500`}
              style={{ width: `${validation.validation_percentage}%` }}
            />
          </div>
        </div>

        {/* Preview de hipótesis */}
        {blockHypotheses.length > 0 && (
          <div className="space-y-1">
            {blockHypotheses.slice(0, 2).map(h => (
              <div 
                key={h.id}
                className="text-[10px] text-gray-600 truncate flex items-center gap-1"
              >
                <span className={`w-1.5 h-1.5 rounded-full ${
                  h.status === 'VALIDATED' ? 'bg-green-500' :
                  h.status === 'TESTING' ? 'bg-amber-500' :
                  h.status === 'INVALIDATED' ? 'bg-red-500' : 'bg-gray-300'
                }`} />
                {h.statement?.substring(0, 40)}...
              </div>
            ))}
            {blockHypotheses.length > 2 && (
              <p className="text-[10px] text-gray-400">
                +{blockHypotheses.length - 2} más
              </p>
            )}
          </div>
        )}

        {/* Indicador de clic */}
        <div className="absolute bottom-2 right-2">
          <ChevronRight className={`w-4 h-4 text-gray-400 transition-transform ${isSelected ? 'rotate-90' : ''}`} />
        </div>
      </div>
    );
  };

  const renderBlockDetail = () => {
    if (!selectedBlock) return null;

    const config = BMC_BLOCKS[selectedBlock];
    const validation = validationByBlock[selectedBlock] || {};
    const blockHypotheses = hypothesesByBlock[selectedBlock] || [];
    const status = STATUS_COLORS[validation.status] || STATUS_COLORS.RED;
    const Icon = config.icon;

    return (
      <div className="mt-6 bg-white rounded-2xl border border-gray-200 shadow-lg overflow-hidden">
        {/* Header */}
        <div className={`p-4 bg-${config.color}-50 border-b border-${config.color}-100`}>
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className={`p-2 rounded-xl bg-${config.color}-100`}>
                <Icon className={`w-6 h-6 text-${config.color}-600`} />
              </div>
              <div>
                <h3 className="font-bold text-gray-800">{config.name}</h3>
                <p className="text-sm text-gray-500">Bloque del Business Model Canvas</p>
              </div>
            </div>
            <button
              onClick={() => setSelectedBlock(null)}
              className="p-2 hover:bg-white/50 rounded-lg transition"
            >
              <X className="w-5 h-5 text-gray-500" />
            </button>
          </div>

          {/* Progreso grande */}
          <div className="mt-4">
            <div className="flex justify-between mb-2">
              <span className="text-sm font-medium text-gray-700">
                Progreso de validación
              </span>
              <span className={`text-sm font-bold ${status.text}`}>
                {validation.validation_percentage || 0}%
              </span>
            </div>
            <div className="h-3 bg-white/50 rounded-full overflow-hidden">
              <div 
                className={`h-full ${status.progress} transition-all duration-500`}
                style={{ width: `${validation.validation_percentage || 0}%` }}
              />
            </div>
          </div>
        </div>

        {/* Preguntas clave */}
        <div className="p-4 border-b border-gray-100">
          <h4 className="text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
            <Lightbulb className="w-4 h-4 text-amber-500" />
            Preguntas clave para validar
          </h4>
          <ul className="space-y-1">
            {config.questions.map((q, idx) => (
              <li key={idx} className="text-sm text-gray-600 flex items-start gap-2">
                <span className="text-gray-400">•</span>
                {q}
              </li>
            ))}
          </ul>
        </div>

        {/* Hipótesis */}
        <div className="p-4">
          <div className="flex items-center justify-between mb-3">
            <h4 className="text-sm font-semibold text-gray-700 flex items-center gap-2">
              <Target className="w-4 h-4 text-blue-500" />
              Hipótesis ({blockHypotheses.length})
            </h4>
            <button
              onClick={() => onAddHypothesis?.(selectedBlock)}
              className="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-800 font-medium"
            >
              <Plus className="w-4 h-4" />
              Añadir
            </button>
          </div>

          {blockHypotheses.length === 0 ? (
            <div className="text-center py-8 text-gray-500">
              <Target className="w-10 h-10 mx-auto mb-2 text-gray-300" />
              <p className="text-sm">No hay hipótesis para este bloque</p>
              <button
                onClick={() => onAddHypothesis?.(selectedBlock)}
                className="mt-2 text-sm text-blue-600 hover:underline"
              >
                Crear primera hipótesis
              </button>
            </div>
          ) : (
            <div className="space-y-2">
              {blockHypotheses.map(hypothesis => (
                <HypothesisCard
                  key={hypothesis.id}
                  hypothesis={hypothesis}
                  onClick={() => onHypothesisClick?.(hypothesis)}
                  onStartExperiment={() => onStartExperiment?.(hypothesis)}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    );
  };

  return (
    <div className="w-full">
      {/* Header del Dashboard */}
      <div className="mb-6">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-2xl font-bold text-gray-800">
              Validación del Modelo de Negocio
            </h2>
            <p className="text-gray-500">
              {entrepreneurName ? `${entrepreneurName} • ` : ''}
              Business Model Canvas
            </p>
          </div>
          
          {/* Progreso global */}
          <div className="text-right">
            <div className="text-3xl font-bold text-emerald-600">
              {overallProgress}%
            </div>
            <p className="text-sm text-gray-500">Validación global</p>
          </div>
        </div>

        {/* Barra de progreso global */}
        <div className="mt-4 h-2 bg-gray-200 rounded-full overflow-hidden">
          <div 
            className="h-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500"
            style={{ width: `${overallProgress}%` }}
          />
        </div>

        {/* Leyenda */}
        <div className="flex items-center gap-4 mt-3 text-xs">
          {Object.entries(STATUS_COLORS).map(([key, value]) => (
            <div key={key} className="flex items-center gap-1">
              <div className={`w-3 h-3 rounded-full ${value.progress}`} />
              <span className="text-gray-600">{value.label}</span>
            </div>
          ))}
        </div>
      </div>

      {/* Grid del BMC */}
      <div 
        className="grid gap-3"
        style={{
          gridTemplateColumns: 'repeat(5, 1fr)',
          gridTemplateRows: 'repeat(3, minmax(120px, auto))'
        }}
      >
        {Object.keys(BMC_BLOCKS).map(renderBlock)}
      </div>

      {/* Panel de detalle del bloque seleccionado */}
      {renderBlockDetail()}

      {/* Loading overlay */}
      {isLoading && (
        <div className="fixed inset-0 bg-white/50 flex items-center justify-center z-50">
          <div className="flex items-center gap-2 text-gray-600">
            <div className="w-5 h-5 border-2 border-emerald-500 border-t-transparent rounded-full animate-spin" />
            Cargando...
          </div>
        </div>
      )}
    </div>
  );
}

// ============================================================================
// COMPONENTE DE TARJETA DE HIPÓTESIS
// ============================================================================

function HypothesisCard({ hypothesis, onClick, onStartExperiment }) {
  const statusConfig = {
    PENDING: { bg: 'bg-gray-100', text: 'text-gray-600', label: 'Pendiente' },
    TESTING: { bg: 'bg-amber-100', text: 'text-amber-700', label: 'En prueba' },
    VALIDATED: { bg: 'bg-emerald-100', text: 'text-emerald-700', label: 'Validada' },
    INVALIDATED: { bg: 'bg-red-100', text: 'text-red-700', label: 'Invalidada' },
    PIVOTED: { bg: 'bg-purple-100', text: 'text-purple-700', label: 'Pivotada' }
  };

  const typeConfig = {
    DESIRABILITY: { icon: Heart, color: 'text-pink-500', label: 'Deseabilidad' },
    FEASIBILITY: { icon: Settings, color: 'text-blue-500', label: 'Factibilidad' },
    VIABILITY: { icon: DollarSign, color: 'text-green-500', label: 'Viabilidad' }
  };

  const status = statusConfig[hypothesis.status] || statusConfig.PENDING;
  const type = typeConfig[hypothesis.type] || typeConfig.DESIRABILITY;
  const TypeIcon = type.icon;

  return (
    <div 
      onClick={onClick}
      className="p-3 bg-gray-50 rounded-xl border border-gray-200 hover:border-gray-300 hover:shadow-sm transition cursor-pointer"
    >
      <div className="flex items-start justify-between gap-2 mb-2">
        <div className="flex items-center gap-2">
          <TypeIcon className={`w-4 h-4 ${type.color}`} />
          <span className="text-xs text-gray-500">{type.label}</span>
        </div>
        <span className={`text-xs px-2 py-0.5 rounded-full ${status.bg} ${status.text}`}>
          {status.label}
        </span>
      </div>

      <p className="text-sm text-gray-800 mb-2 line-clamp-2">
        {hypothesis.statement}
      </p>

      <div className="flex items-center justify-between">
        <div className="flex items-center gap-3 text-xs text-gray-500">
          <span>Importancia: {hypothesis.importance_score}/5</span>
          <span>Evidencia: {hypothesis.evidence_score}/5</span>
        </div>
        
        {hypothesis.status === 'PENDING' && (
          <button
            onClick={(e) => {
              e.stopPropagation();
              onStartExperiment?.();
            }}
            className="flex items-center gap-1 text-xs text-emerald-600 hover:text-emerald-800 font-medium"
          >
            <Beaker className="w-3 h-3" />
            Validar
          </button>
        )}
      </div>
    </div>
  );
}

// ============================================================================
// COMPONENTE DE MINI CANVAS (para vistas compactas)
// ============================================================================

export function BMCMiniCanvas({ validationData = [], onBlockClick }) {
  const validationByBlock = useMemo(() => {
    const map = {};
    validationData.forEach(block => {
      map[block.block || block.bmc_block] = block;
    });
    return map;
  }, [validationData]);

  const getBlockColor = (blockId) => {
    const validation = validationByBlock[blockId];
    if (!validation) return 'bg-gray-200';
    if (validation.validation_percentage >= 80) return 'bg-emerald-500';
    if (validation.validation_percentage >= 40) return 'bg-amber-500';
    return 'bg-red-400';
  };

  return (
    <div 
      className="grid gap-1 p-2 bg-gray-100 rounded-lg"
      style={{
        gridTemplateColumns: 'repeat(5, 1fr)',
        gridTemplateRows: 'repeat(3, 20px)'
      }}
    >
      {Object.keys(BMC_BLOCKS).map(blockId => {
        const config = BMC_BLOCKS[blockId];
        return (
          <div
            key={blockId}
            onClick={() => onBlockClick?.(blockId)}
            className={`rounded cursor-pointer hover:opacity-80 transition ${getBlockColor(blockId)}`}
            style={{
              gridRow: config.position.row,
              gridColumn: config.position.col
            }}
            title={`${config.name}: ${validationByBlock[blockId]?.validation_percentage || 0}%`}
          />
        );
      })}
    </div>
  );
}

// ============================================================================
// EXPORTACIÓN DE CONSTANTES
// ============================================================================

export { BMC_BLOCKS, STATUS_COLORS };
