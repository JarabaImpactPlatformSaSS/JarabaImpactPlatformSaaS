/**
 * COPILOTO CHAT WIDGET v2.0
 * Componente React para el chat con el Copiloto de Emprendimiento
 * Programa Andalucía +ei | Jaraba Impact Platform
 * 
 * Dependencias:
 * - React 18+
 * - Tailwind CSS
 * - Lucide React (iconos)
 */

import React, { useState, useEffect, useRef, useCallback } from 'react';
import { 
  Send, 
  Loader2, 
  Bot, 
  User, 
  Lightbulb, 
  Target, 
  Heart,
  Calculator,
  Scale,
  Sparkles,
  ChevronDown,
  X,
  Maximize2,
  Minimize2,
  RefreshCw,
  AlertCircle
} from 'lucide-react';

// ============================================================================
// TIPOS Y CONSTANTES
// ============================================================================

const MODES = {
  COACH_EMOCIONAL: { 
    icon: Heart, 
    label: 'Coach Emocional', 
    color: 'text-pink-500',
    bg: 'bg-pink-50'
  },
  CONSULTOR_TACTICO: { 
    icon: Target, 
    label: 'Consultor Táctico', 
    color: 'text-blue-500',
    bg: 'bg-blue-50'
  },
  SPARRING_PARTNER: { 
    icon: Scale, 
    label: 'Sparring Partner', 
    color: 'text-purple-500',
    bg: 'bg-purple-50'
  },
  CFO_SINTETICO: { 
    icon: Calculator, 
    label: 'CFO Sintético', 
    color: 'text-green-500',
    bg: 'bg-green-50'
  },
  ABOGADO_DIABLO: { 
    icon: Lightbulb, 
    label: 'Abogado del Diablo', 
    color: 'text-amber-500',
    bg: 'bg-amber-50'
  }
};

const EMOTIONS = {
  impostor: '🎭 Síndrome del Impostor',
  miedo_precio: '💰 Miedo al Precio',
  miedo_rechazo: '😰 Miedo al Rechazo',
  tecnofobia: '🖥️ Tecnofobia',
  paralisis: '🧊 Parálisis por Análisis'
};

// ============================================================================
// COMPONENTE PRINCIPAL
// ============================================================================

export default function CopilotChatWidget({
  entrepreneurId,
  entrepreneurName = 'Emprendedor',
  carril = 'IMPULSO',
  position = 'bottom-right', // bottom-right | inline | fullscreen
  theme = 'light',
  apiEndpoint = '/api/copilot/chat',
  welcomeMessage = null,
  suggestedActions = [],
  onExperimentSuggested = null,
  onHypothesisCreated = null,
  onEmotionDetected = null,
  onModeChanged = null
}) {
  // Estado del chat
  const [messages, setMessages] = useState([]);
  const [inputValue, setInputValue] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isStreaming, setIsStreaming] = useState(false);
  const [streamingContent, setStreamingContent] = useState('');
  const [error, setError] = useState(null);
  
  // Estado de UI
  const [isOpen, setIsOpen] = useState(position === 'inline' || position === 'fullscreen');
  const [isExpanded, setIsExpanded] = useState(position === 'fullscreen');
  const [currentMode, setCurrentMode] = useState(null);
  const [detectedEmotion, setDetectedEmotion] = useState(null);
  
  // Refs
  const messagesEndRef = useRef(null);
  const inputRef = useRef(null);
  const sessionIdRef = useRef(`session-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`);

  // ============================================================================
  // EFECTOS
  // ============================================================================

  // Scroll automático al final
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, streamingContent]);

  // Mensaje de bienvenida inicial
  useEffect(() => {
    const defaultWelcome = carril === 'IMPULSO' 
      ? `¡Hola ${entrepreneurName}! 👋 Soy tu Copiloto de Negocio. Estoy aquí para ayudarte paso a paso. ¿En qué puedo ayudarte hoy?`
      : `¡Hola ${entrepreneurName}! 👋 Soy tu Copiloto de Negocio. Listo para ayudarte a validar y escalar. ¿Qué reto tienes entre manos?`;
    
    const welcome = welcomeMessage || defaultWelcome;
    
    setMessages([{
      id: 'welcome',
      role: 'assistant',
      content: welcome,
      timestamp: new Date(),
      mode: null
    }]);
  }, [entrepreneurName, carril, welcomeMessage]);

  // Focus en input cuando se abre
  useEffect(() => {
    if (isOpen) {
      setTimeout(() => inputRef.current?.focus(), 100);
    }
  }, [isOpen]);

  // ============================================================================
  // HANDLERS
  // ============================================================================

  const sendMessage = useCallback(async (messageText = inputValue) => {
    if (!messageText.trim() || isLoading) return;

    const userMessage = {
      id: `user-${Date.now()}`,
      role: 'user',
      content: messageText.trim(),
      timestamp: new Date()
    };

    setMessages(prev => [...prev, userMessage]);
    setInputValue('');
    setIsLoading(true);
    setError(null);
    setStreamingContent('');

    try {
      const response = await fetch(apiEndpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
        },
        body: JSON.stringify({
          user_id: entrepreneurId,
          session_id: sessionIdRef.current,
          message: messageText.trim()
        })
      });

      if (!response.ok) {
        throw new Error(`Error ${response.status}: ${response.statusText}`);
      }

      // Verificar si es streaming
      const contentType = response.headers.get('content-type');
      
      if (contentType?.includes('text/event-stream')) {
        // Modo streaming
        setIsStreaming(true);
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let fullContent = '';
        let metadata = {};

        while (true) {
          const { done, value } = await reader.read();
          if (done) break;
          
          const chunk = decoder.decode(value);
          const lines = chunk.split('\n');
          
          for (const line of lines) {
            if (line.startsWith('data: ')) {
              const data = line.slice(6);
              if (data === '[DONE]') continue;
              
              try {
                const parsed = JSON.parse(data);
                if (parsed.content) {
                  fullContent += parsed.content;
                  setStreamingContent(fullContent);
                }
                if (parsed.mode_detected) metadata.mode = parsed.mode_detected;
                if (parsed.emotion_detected) metadata.emotion = parsed.emotion_detected;
                if (parsed.experiment_suggested) metadata.experiment = parsed.experiment_suggested;
                if (parsed.suggested_actions) metadata.actions = parsed.suggested_actions;
              } catch (e) {
                // Texto plano
                fullContent += data;
                setStreamingContent(fullContent);
              }
            }
          }
        }

        handleAssistantResponse(fullContent, metadata);
        setIsStreaming(false);
      } else {
        // Respuesta JSON normal
        const data = await response.json();
        handleAssistantResponse(data.response, {
          mode: data.mode_detected,
          emotion: data.emotion_detected,
          experiment: data.experiment_suggested,
          actions: data.suggested_actions
        });
      }
    } catch (err) {
      console.error('Error en chat:', err);
      setError(err.message || 'Error al conectar con el Copiloto');
      
      // Mensaje de error amigable
      setMessages(prev => [...prev, {
        id: `error-${Date.now()}`,
        role: 'assistant',
        content: 'Ups, parece que tuve un problema técnico. ¿Puedes intentarlo de nuevo? 🔧',
        timestamp: new Date(),
        isError: true
      }]);
    } finally {
      setIsLoading(false);
      setStreamingContent('');
    }
  }, [inputValue, isLoading, entrepreneurId, apiEndpoint]);

  const handleAssistantResponse = (content, metadata) => {
    const assistantMessage = {
      id: `assistant-${Date.now()}`,
      role: 'assistant',
      content,
      timestamp: new Date(),
      mode: metadata.mode,
      emotion: metadata.emotion,
      experiment: metadata.experiment,
      actions: metadata.actions
    };

    setMessages(prev => [...prev, assistantMessage]);

    // Actualizar modo detectado
    if (metadata.mode && metadata.mode !== currentMode) {
      setCurrentMode(metadata.mode);
      onModeChanged?.(metadata.mode);
    }

    // Notificar emoción detectada
    if (metadata.emotion) {
      setDetectedEmotion(metadata.emotion);
      onEmotionDetected?.(metadata.emotion);
    }

    // Notificar experimento sugerido
    if (metadata.experiment) {
      onExperimentSuggested?.(metadata.experiment);
    }
  };

  const handleKeyDown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  };

  const handleSuggestedAction = (action) => {
    if (action.type === 'message') {
      sendMessage(action.text);
    } else if (action.type === 'link') {
      window.open(action.url, '_blank');
    }
  };

  const clearChat = () => {
    sessionIdRef.current = `session-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    setMessages([]);
    setCurrentMode(null);
    setDetectedEmotion(null);
    // Re-trigger welcome message
    const welcome = welcomeMessage || `¡Hola ${entrepreneurName}! 👋 ¿En qué puedo ayudarte?`;
    setMessages([{
      id: 'welcome',
      role: 'assistant',
      content: welcome,
      timestamp: new Date()
    }]);
  };

  // ============================================================================
  // RENDER HELPERS
  // ============================================================================

  const renderMessage = (message) => {
    const isUser = message.role === 'user';
    const modeInfo = message.mode ? MODES[message.mode] : null;
    const ModeIcon = modeInfo?.icon;

    return (
      <div 
        key={message.id}
        className={`flex ${isUser ? 'justify-end' : 'justify-start'} mb-4`}
      >
        <div className={`flex max-w-[85%] ${isUser ? 'flex-row-reverse' : 'flex-row'} gap-2`}>
          {/* Avatar */}
          <div className={`flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ${
            isUser ? 'bg-blue-600' : 'bg-gradient-to-br from-emerald-500 to-teal-600'
          }`}>
            {isUser ? (
              <User className="w-4 h-4 text-white" />
            ) : (
              <Bot className="w-4 h-4 text-white" />
            )}
          </div>

          {/* Contenido */}
          <div className={`flex flex-col ${isUser ? 'items-end' : 'items-start'}`}>
            {/* Badge de modo */}
            {modeInfo && !isUser && (
              <div className={`flex items-center gap-1 text-xs ${modeInfo.color} mb-1`}>
                <ModeIcon className="w-3 h-3" />
                <span>{modeInfo.label}</span>
              </div>
            )}

            {/* Burbuja de mensaje */}
            <div className={`px-4 py-3 rounded-2xl ${
              isUser 
                ? 'bg-blue-600 text-white rounded-tr-sm' 
                : message.isError
                  ? 'bg-red-50 text-red-800 border border-red-200 rounded-tl-sm'
                  : 'bg-gray-100 text-gray-800 rounded-tl-sm'
            }`}>
              <div className="whitespace-pre-wrap text-sm leading-relaxed">
                {message.content}
              </div>
            </div>

            {/* Emoción detectada */}
            {message.emotion && (
              <div className="flex items-center gap-1 text-xs text-pink-600 mt-1">
                <Heart className="w-3 h-3" />
                <span>{EMOTIONS[message.emotion] || message.emotion}</span>
              </div>
            )}

            {/* Acciones sugeridas */}
            {message.actions && message.actions.length > 0 && (
              <div className="flex flex-wrap gap-2 mt-2">
                {message.actions.map((action, idx) => (
                  <button
                    key={idx}
                    onClick={() => handleSuggestedAction(action)}
                    className="px-3 py-1.5 text-xs bg-white border border-gray-200 rounded-full hover:bg-gray-50 hover:border-gray-300 transition flex items-center gap-1"
                  >
                    {action.icon && <span>{action.icon}</span>}
                    {action.label}
                  </button>
                ))}
              </div>
            )}

            {/* Experimento sugerido */}
            {message.experiment && (
              <div className="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-xl max-w-xs">
                <div className="flex items-center gap-2 text-amber-700 text-xs font-medium mb-1">
                  <Sparkles className="w-3 h-3" />
                  Experimento sugerido
                </div>
                <div className="text-sm font-medium text-amber-900">
                  {message.experiment.name}
                </div>
                <div className="text-xs text-amber-700 mt-1">
                  {message.experiment.reason}
                </div>
                <button 
                  onClick={() => onExperimentSuggested?.(message.experiment)}
                  className="mt-2 text-xs text-amber-600 hover:text-amber-800 font-medium"
                >
                  Ver detalles →
                </button>
              </div>
            )}

            {/* Timestamp */}
            <span className="text-[10px] text-gray-400 mt-1">
              {message.timestamp.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' })}
            </span>
          </div>
        </div>
      </div>
    );
  };

  const renderStreamingMessage = () => {
    if (!isStreaming || !streamingContent) return null;

    return (
      <div className="flex justify-start mb-4">
        <div className="flex max-w-[85%] gap-2">
          <div className="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
            <Bot className="w-4 h-4 text-white" />
          </div>
          <div className="px-4 py-3 bg-gray-100 text-gray-800 rounded-2xl rounded-tl-sm">
            <div className="whitespace-pre-wrap text-sm leading-relaxed">
              {streamingContent}
              <span className="inline-block w-1.5 h-4 bg-gray-400 ml-1 animate-pulse" />
            </div>
          </div>
        </div>
      </div>
    );
  };

  const renderTypingIndicator = () => {
    if (!isLoading || isStreaming) return null;

    return (
      <div className="flex justify-start mb-4">
        <div className="flex gap-2">
          <div className="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center">
            <Bot className="w-4 h-4 text-white" />
          </div>
          <div className="px-4 py-3 bg-gray-100 rounded-2xl rounded-tl-sm">
            <div className="flex gap-1">
              <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
              <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
              <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
            </div>
          </div>
        </div>
      </div>
    );
  };

  // ============================================================================
  // RENDER PRINCIPAL
  // ============================================================================

  // Botón flotante para abrir (solo si position es bottom-right)
  if (position === 'bottom-right' && !isOpen) {
    return (
      <button
        onClick={() => setIsOpen(true)}
        className="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-full shadow-lg hover:shadow-xl transition-all hover:scale-105 flex items-center justify-center z-50"
        aria-label="Abrir Copiloto"
      >
        <Bot className="w-7 h-7 text-white" />
        {/* Indicador de notificación */}
        <span className="absolute top-0 right-0 w-3 h-3 bg-red-500 rounded-full border-2 border-white" />
      </button>
    );
  }

  const containerClasses = position === 'bottom-right'
    ? `fixed ${isExpanded ? 'inset-4' : 'bottom-6 right-6 w-96 h-[600px]'} z-50`
    : position === 'fullscreen'
      ? 'fixed inset-0 z-50'
      : 'w-full h-full';

  return (
    <div className={containerClasses}>
      <div className={`flex flex-col h-full bg-white ${position !== 'inline' ? 'rounded-2xl shadow-2xl border border-gray-200' : ''} overflow-hidden`}>
        {/* Header */}
        <div className="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
              <Bot className="w-6 h-6" />
            </div>
            <div>
              <h3 className="font-semibold">Copiloto de Negocio</h3>
              <p className="text-xs text-white/80">
                {currentMode ? MODES[currentMode]?.label : 'Siempre disponible'}
              </p>
            </div>
          </div>
          
          <div className="flex items-center gap-1">
            {/* Indicador de carril */}
            <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${
              carril === 'IMPULSO' ? 'bg-blue-500' : 'bg-purple-500'
            }`}>
              {carril}
            </span>
            
            {/* Botón limpiar */}
            <button
              onClick={clearChat}
              className="p-2 hover:bg-white/20 rounded-lg transition"
              title="Nueva conversación"
            >
              <RefreshCw className="w-4 h-4" />
            </button>
            
            {/* Botón expandir (solo bottom-right) */}
            {position === 'bottom-right' && (
              <button
                onClick={() => setIsExpanded(!isExpanded)}
                className="p-2 hover:bg-white/20 rounded-lg transition"
              >
                {isExpanded ? <Minimize2 className="w-4 h-4" /> : <Maximize2 className="w-4 h-4" />}
              </button>
            )}
            
            {/* Botón cerrar (solo bottom-right) */}
            {position === 'bottom-right' && (
              <button
                onClick={() => setIsOpen(false)}
                className="p-2 hover:bg-white/20 rounded-lg transition"
              >
                <X className="w-4 h-4" />
              </button>
            )}
          </div>
        </div>

        {/* Error banner */}
        {error && (
          <div className="px-4 py-2 bg-red-50 border-b border-red-100 flex items-center gap-2 text-red-700 text-sm">
            <AlertCircle className="w-4 h-4" />
            <span>{error}</span>
            <button onClick={() => setError(null)} className="ml-auto">
              <X className="w-4 h-4" />
            </button>
          </div>
        )}

        {/* Mensajes */}
        <div className="flex-1 overflow-y-auto p-4">
          {messages.map(renderMessage)}
          {renderStreamingMessage()}
          {renderTypingIndicator()}
          <div ref={messagesEndRef} />
        </div>

        {/* Acciones rápidas sugeridas */}
        {suggestedActions.length > 0 && messages.length <= 1 && (
          <div className="px-4 pb-2">
            <p className="text-xs text-gray-500 mb-2">Sugerencias:</p>
            <div className="flex flex-wrap gap-2">
              {suggestedActions.map((action, idx) => (
                <button
                  key={idx}
                  onClick={() => sendMessage(action.text)}
                  className="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition"
                >
                  {action.label}
                </button>
              ))}
            </div>
          </div>
        )}

        {/* Input */}
        <div className="p-4 border-t border-gray-100">
          <div className="flex items-end gap-2">
            <div className="flex-1 relative">
              <textarea
                ref={inputRef}
                value={inputValue}
                onChange={(e) => setInputValue(e.target.value)}
                onKeyDown={handleKeyDown}
                placeholder="Escribe tu mensaje..."
                rows={1}
                className="w-full px-4 py-3 pr-12 bg-gray-100 rounded-xl resize-none focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:bg-white transition text-sm"
                style={{ maxHeight: '120px' }}
                disabled={isLoading}
              />
              <button
                onClick={() => sendMessage()}
                disabled={!inputValue.trim() || isLoading}
                className="absolute right-2 bottom-2 p-2 bg-emerald-500 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-emerald-600 transition"
              >
                {isLoading ? (
                  <Loader2 className="w-4 h-4 animate-spin" />
                ) : (
                  <Send className="w-4 h-4" />
                )}
              </button>
            </div>
          </div>
          <p className="text-[10px] text-gray-400 mt-2 text-center">
            Copiloto Andalucía +ei • Jaraba Impact Platform
          </p>
        </div>
      </div>
    </div>
  );
}

// ============================================================================
// EXPORTACIONES ADICIONALES
// ============================================================================

// Hook para usar el copiloto programáticamente
export function useCopilot(entrepreneurId, apiEndpoint = '/api/copilot/chat') {
  const [isLoading, setIsLoading] = useState(false);
  const sessionId = useRef(`session-${Date.now()}`);

  const sendMessage = async (message) => {
    setIsLoading(true);
    try {
      const response = await fetch(apiEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          user_id: entrepreneurId,
          session_id: sessionId.current,
          message
        })
      });
      return await response.json();
    } finally {
      setIsLoading(false);
    }
  };

  return { sendMessage, isLoading };
}

// Componente de mensaje standalone
export function CopilotMessage({ content, mode, isUser = false }) {
  const modeInfo = mode ? MODES[mode] : null;
  
  return (
    <div className={`flex ${isUser ? 'justify-end' : 'justify-start'}`}>
      <div className={`max-w-[80%] px-4 py-3 rounded-2xl ${
        isUser ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800'
      }`}>
        {modeInfo && !isUser && (
          <div className={`text-xs ${modeInfo.color} mb-1`}>{modeInfo.label}</div>
        )}
        <div className="text-sm">{content}</div>
      </div>
    </div>
  );
}
