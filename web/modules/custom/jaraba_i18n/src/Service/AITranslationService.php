<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_ai_agents\Service\AgentOrchestrator;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de traducción asistida por IA.
 *
 * ¿QUÉ PROBLEMA RESUELVE?
 * =======================
 * Traducir contenido manualmente es lento y costoso. Además, las traducciones
 * automáticas genéricas (Google Translate, DeepL) no mantienen:
 * - El tono de marca (Brand Voice)
 * - Terminología específica del dominio
 * - Estructura HTML/Markdown
 *
 * Este servicio utiliza el sistema de agentes IA de Jaraba para:
 * 1. Traducir contenido preservando el Brand Voice del tenant
 * 2. Mantener un glosario de términos específicos
 * 3. Preservar estructura HTML y Markdown
 * 4. Adaptar el tono según el tipo de contenido
 *
 * ¿CÓMO FUNCIONA?
 * ===============
 * 1. Recibe texto a traducir con idiomas origen/destino
 * 2. Construye un prompt optimizado para traducción de marca
 * 3. Usa el AgentOrchestrator para ejecutar un agente de traducción
 * 4. Post-procesa para validar estructura HTML
 * 5. Retorna texto traducido listo para guardar
 *
 * INTEGRACIÓN CON jaraba_ai_agents
 * =================================
 * Utiliza:
 * - AgentOrchestrator: Para ejecutar el agente de traducción
 * - ModelRouterService: Para seleccionar el modelo óptimo (fast para traducciones)
 * - TenantBrandVoiceService: Heredado del orquestador para contexto de marca
 *
 * @see docs/planificacion/20260202-Gap_E_i18n_UI_v1.md
 */
class AITranslationService
{

    /**
     * Prompt base para traducción de contenido.
     *
     * Las variables {source_lang}, {target_lang}, {brand_context} y {text}
     * se sustituyen en runtime.
     */
    private const TRANSLATION_PROMPT = <<<'PROMPT'
Eres un traductor profesional especializado en contenido web y marketing.

CONTEXTO DE MARCA:
{brand_context}

TAREA:
Traduce el siguiente texto de {source_lang} a {target_lang}.

REGLAS OBLIGATORIAS:
1. Mantén el tono de marca indicado arriba.
2. Preserva EXACTAMENTE todas las etiquetas HTML y Markdown.
3. No traduzcas nombres propios, marcas ni URLs.
4. Adapta expresiones idiomáticas al idioma destino.
5. Mantén la misma longitud aproximada (±20%).

GLOSARIO (usar estos términos exactos):
{glossary}

TEXTO A TRADUCIR:
---
{text}
---

Responde ÚNICAMENTE con el texto traducido, sin explicaciones.
PROMPT;

    /**
     * Constructor del servicio.
     *
     * @param \Drupal\jaraba_ai_agents\Service\AgentOrchestrator $orchestrator
     *   Orquestador de agentes IA para ejecutar traducciones.
     * @param \Drupal\jaraba_ai_agents\Service\ModelRouterService $modelRouter
     *   Router de modelos para seleccionar modelo óptimo.
     * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
     *   Fábrica de configuración para opciones del módulo.
     * @param \Psr\Log\LoggerInterface $logger
     *   Logger para registro de operaciones y errores.
     */
    public function __construct(
        protected AgentOrchestrator $orchestrator,
        protected ModelRouterService $modelRouter,
        protected ConfigFactoryInterface $configFactory,
        protected LoggerInterface $logger,
        protected TenantContextService $tenantContext,
    ) {
    }

    /**
     * Traduce un texto al idioma especificado.
     *
     * @param string $text
     *   Texto a traducir.
     * @param string $sourceLang
     *   Código de idioma origen (ej: 'es').
     * @param string $targetLang
     *   Código de idioma destino (ej: 'en').
     * @param array $options
     *   Opciones adicionales:
     *   - 'preserve_html': bool - Preservar etiquetas HTML (default: TRUE)
     *   - 'glossary': array - Glosario de términos [original => traducido]
     *   - 'tone': string - Tono específico (profesional, casual, técnico)
     *   - 'tenant_id': string - ID del tenant para Brand Voice
     *
     * @return string
     *   Texto traducido.
     *
     * @throws \RuntimeException
     *   Si la traducción falla.
     */
    public function translate(
        string $text,
        string $sourceLang,
        string $targetLang,
        array $options = []
    ): string {
        // Validaciones básicas.
        if (empty(trim($text))) {
            return $text;
        }

        if ($sourceLang === $targetLang) {
            return $text;
        }

        // Construir el prompt.
        $prompt = $this->buildPrompt($text, $sourceLang, $targetLang, $options);

        try {
            // Usar el orquestador para ejecutar la traducción.
            // El agente 'smart_marketing_agent' soporta traducciones con Brand Voice.
            $result = $this->orchestrator->execute(
                'smart_marketing_agent',
                'generate_content',  // Acción genérica de generación
                [
                    'prompt' => $prompt,
                    'type' => 'translation',
                    'source_lang' => $sourceLang,
                    'target_lang' => $targetLang,
                ],
                $options['tenant_id'] ?? NULL
            );

            $translatedText = $result['content'] ?? '';

            // Post-procesamiento.
            $translatedText = $this->postProcess($translatedText, $text, $options);

            $this->logger->info('Traducción completada: @source_lang → @target_lang, @chars chars', [
                '@source_lang' => $sourceLang,
                '@target_lang' => $targetLang,
                '@chars' => strlen($text),
            ]);

            return $translatedText;

        } catch (\Exception $e) {
            $this->logger->error('Error en traducción IA: @message', [
                '@message' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Error en traducción: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Traduce todos los campos traducibles de una entidad.
     *
     * Optimizado para traducción en lote, reduce llamadas a la API
     * combinando múltiples campos en una sola petición.
     *
     * @param \Drupal\Core\Entity\ContentEntityInterface $entity
     *   La entidad con la traducción existente (creada pero vacía).
     * @param string $sourceLang
     *   Idioma origen.
     * @param string $targetLang
     *   Idioma destino.
     * @param array $fieldNames
     *   Lista de campos a traducir. Si vacío, traduce todos los campos texto.
     *
     * @return \Drupal\Core\Entity\ContentEntityInterface
     *   La entidad con campos traducidos (sin guardar).
     */
    public function translateEntity(
        ContentEntityInterface $entity,
        string $sourceLang,
        string $targetLang,
        array $fieldNames = []
    ): ContentEntityInterface {
        // Obtener la entidad original para extraer textos.
        $original = $entity->getUntranslated();

        // Si no se especifican campos, detectar campos de texto automáticamente.
        if (empty($fieldNames)) {
            $fieldNames = $this->getTranslatableTextFields($original);
        }

        // Preparar textos para traducción en lote.
        $textsToTranslate = [];
        foreach ($fieldNames as $fieldName) {
            if (!$original->hasField($fieldName)) {
                continue;
            }

            $field = $original->get($fieldName);
            $value = $field->value ?? $field->getString();

            if (!empty($value)) {
                $textsToTranslate[$fieldName] = $value;
            }
        }

        // Traducir en lote.
        $translations = $this->translateBatch($textsToTranslate, $sourceLang, $targetLang);

        // Aplicar traducciones a la entidad.
        foreach ($translations as $fieldName => $translatedValue) {
            $entity->set($fieldName, $translatedValue);
        }

        return $entity;
    }

    /**
     * Traduce múltiples textos en una sola llamada a la API.
     *
     * @param array<string, string> $texts
     *   Array de [key => texto] a traducir.
     * @param string $sourceLang
     *   Idioma origen.
     * @param string $targetLang
     *   Idioma destino.
     *
     * @return array<string, string>
     *   Array de [key => texto_traducido].
     */
    public function translateBatch(
        array $texts,
        string $sourceLang,
        string $targetLang
    ): array {
        if (empty($texts)) {
            return [];
        }

        // Para lotes grandes, construimos un formato estructurado.
        $batchText = "TEXTOS A TRADUCIR (formato JSON):\n";
        $batchText .= json_encode($texts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $prompt = $this->buildPrompt($batchText, $sourceLang, $targetLang, [
            'batch_mode' => TRUE,
        ]);

        try {
            $result = $this->orchestrator->execute(
                'smart_marketing_agent',
                'generate_content',
                [
                    'prompt' => $prompt,
                    'type' => 'batch_translation',
                ]
            );

            $content = $result['content'] ?? '';

            // Parsear respuesta JSON.
            $translated = json_decode($content, TRUE);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Si falla el JSON, traducir uno a uno.
                return $this->translateOneByOne($texts, $sourceLang, $targetLang);
            }

            return $translated;

        } catch (\Exception $e) {
            $this->logger->warning('Traducción batch fallida, reintentando uno a uno: @error', [
                '@error' => $e->getMessage(),
            ]);
            return $this->translateOneByOne($texts, $sourceLang, $targetLang);
        }
    }

    /**
     * Construye el prompt para traducción.
     *
     * @param string $text
     *   Texto a traducir.
     * @param string $sourceLang
     *   Idioma origen.
     * @param string $targetLang
     *   Idioma destino.
     * @param array $options
     *   Opciones adicionales.
     *
     * @return string
     *   Prompt completo.
     */
    protected function buildPrompt(
        string $text,
        string $sourceLang,
        string $targetLang,
        array $options = []
    ): string {
        // Obtener contexto de marca si hay tenant.
        $brandContext = $this->getBrandContext($options['tenant_id'] ?? NULL);

        // Construir glosario.
        $glossary = $this->formatGlossary($options['glossary'] ?? []);

        // Nombres legibles de idiomas.
        $langNames = [
            'es' => 'español',
            'en' => 'inglés',
            'ca' => 'catalán',
            'eu' => 'euskera',
            'gl' => 'gallego',
            'fr' => 'francés',
            'de' => 'alemán',
            'pt' => 'portugués',
        ];

        $prompt = self::TRANSLATION_PROMPT;
        $prompt = str_replace('{source_lang}', $langNames[$sourceLang] ?? $sourceLang, $prompt);
        $prompt = str_replace('{target_lang}', $langNames[$targetLang] ?? $targetLang, $prompt);
        $prompt = str_replace('{brand_context}', $brandContext, $prompt);
        $prompt = str_replace('{glossary}', $glossary ?: '(No hay glosario específico)', $prompt);
        $prompt = str_replace('{text}', $text, $prompt);

        // Ajustar para modo batch.
        if (!empty($options['batch_mode'])) {
            $prompt .= "\n\nIMPORTANTE: Responde con un JSON válido manteniendo las mismas claves.";
        }

        return $prompt;
    }

    /**
     * Obtiene el contexto de marca del tenant.
     *
     * @param string|null $tenantId
     *   ID del tenant.
     *
     * @return string
     *   Descripción del Brand Voice.
     */
    protected function getBrandContext(?string $tenantId): string
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        if ($tenant) {
            $siteName = $tenant->label() ?? '';
            $slogan = $tenant->get('slogan')->value ?? '';
            $vertical = $tenant->get('vertical')->value ?? '';

            $parts = [];
            if ($siteName) {
                $parts[] = "Marca: {$siteName}.";
            }
            if ($slogan) {
                $parts[] = "Eslogan: {$slogan}.";
            }
            if ($vertical) {
                $parts[] = "Vertical: {$vertical}.";
            }
            if (!empty($parts)) {
                return implode(' ', $parts) . ' Tono profesional pero accesible.';
            }
        }

        return "Tono profesional pero accesible. Enfocado en impacto social y empleabilidad.";
    }

    /**
     * Formatea el glosario para el prompt.
     *
     * @param array $glossary
     *   Array [término => traducción].
     *
     * @return string
     *   Glosario formateado.
     */
    protected function formatGlossary(array $glossary): string
    {
        if (empty($glossary)) {
            return '';
        }

        $lines = [];
        foreach ($glossary as $term => $translation) {
            $lines[] = "- {$term} → {$translation}";
        }

        return implode("\n", $lines);
    }

    /**
     * Post-procesa el texto traducido.
     *
     * @param string $translated
     *   Texto traducido por IA.
     * @param string $original
     *   Texto original para comparación.
     * @param array $options
     *   Opciones de traducción.
     *
     * @return string
     *   Texto limpio.
     */
    protected function postProcess(string $translated, string $original, array $options): string
    {
        // Limpiar whitespace extra.
        $translated = trim($translated);

        // Validar estructura HTML si es necesario.
        if ($options['preserve_html'] ?? TRUE) {
            // Contar tags HTML en original y traducido.
            $originalTags = preg_match_all('/<[^>]+>/', $original);
            $translatedTags = preg_match_all('/<[^>]+>/', $translated);

            if ($originalTags !== $translatedTags) {
                $this->logger->warning('Discrepancia en tags HTML: original @orig, traducido @trans', [
                    '@orig' => $originalTags,
                    '@trans' => $translatedTags,
                ]);
            }
        }

        return $translated;
    }

    /**
     * Traduce textos uno a uno (fallback).
     *
     * @param array $texts
     *   Textos a traducir.
     * @param string $sourceLang
     *   Idioma origen.
     * @param string $targetLang
     *   Idioma destino.
     *
     * @return array
     *   Textos traducidos.
     */
    protected function translateOneByOne(array $texts, string $sourceLang, string $targetLang): array
    {
        $results = [];

        foreach ($texts as $key => $text) {
            try {
                $results[$key] = $this->translate($text, $sourceLang, $targetLang);
            } catch (\Exception $e) {
                // En caso de error, mantener original.
                $results[$key] = $text;
                $this->logger->error('Error traduciendo campo @key: @error', [
                    '@key' => $key,
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Obtiene campos de texto traducibles de una entidad.
     *
     * @param \Drupal\Core\Entity\ContentEntityInterface $entity
     *   La entidad.
     *
     * @return array
     *   Lista de nombres de campos de texto.
     */
    protected function getTranslatableTextFields(ContentEntityInterface $entity): array
    {
        $textFields = [];
        $fieldDefinitions = $entity->getFieldDefinitions();

        foreach ($fieldDefinitions as $fieldName => $definition) {
            // Ignorar campos base de entidad.
            if (str_starts_with($fieldName, 'field_') || in_array($fieldName, ['title', 'name', 'label'])) {
                $type = $definition->getType();
                // Campos de texto.
                if (in_array($type, ['string', 'string_long', 'text', 'text_long', 'text_with_summary'])) {
                    $textFields[] = $fieldName;
                }
            }
        }

        return $textFields;
    }

}
