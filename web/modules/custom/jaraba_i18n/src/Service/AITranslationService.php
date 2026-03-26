<?php

declare(strict_types=1);

namespace Drupal\jaraba_i18n\Service;

use Drupal\ai\OperationType\Chat\ChatInput;
use Drupal\ai\OperationType\Chat\ChatMessage;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_ai_agents\Service\ModelRouterService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de traduccion asistida por IA.
 *
 * Usa el AI Provider framework de Drupal directamente (anthropic/claude)
 * para traducir contenido preservando Brand Voice, estructura HTML y
 * terminologia especifica del dominio.
 *
 * Patron: identico a scripts/maintenance/translate-all-pages.php que
 * esta validado en produccion.
 */
class AITranslationService {

  /**
   * Modelo por defecto para traducciones (fast tier).
   */
  private const DEFAULT_MODEL = 'claude-haiku-4-5-20251001';

  /**
   * Proveedor por defecto.
   */
  private const DEFAULT_PROVIDER = 'anthropic';

  /**
   * Prompt del sistema para traducciones.
   */
  private const SYSTEM_PROMPT = <<<'PROMPT'
Eres un traductor profesional especializado en contenido web corporativo.
Traduce de {source_lang} a {target_lang}.

CONTEXTO DE MARCA:
{brand_context}

Reglas:
1. Preserva EXACTAMENTE todas las etiquetas HTML, atributos, clases CSS y URLs.
2. No traduzcas nombres propios, marcas, URLs, direcciones de email ni codigos.
3. Adapta expresiones idiomaticas al idioma destino.
4. Manten longitud similar (±20%).
5. Tono profesional pero accesible.
6. Responde UNICAMENTE con el texto traducido, sin explicaciones ni comentarios.
PROMPT;

  /**
   * Nombres legibles de idiomas para prompts.
   *
   * @var array<string, string>
   */
  private const LANG_NAMES = [
    'es' => 'español',
    'en' => 'inglés',
    'ca' => 'catalán',
    'eu' => 'euskera',
    'gl' => 'gallego',
    'fr' => 'francés',
    'de' => 'alemán',
    'pt' => 'portugués',
    'pt-br' => 'portugués brasileño',
  ];

  /**
   * Patrones de alucinacion IA que invalidan la traduccion.
   *
   * @var string[]
   */
  private const HALLUCINATION_PATTERNS = [
    'I\'m ready to',
    'I\'ll translate',
    'I don\'t see',
    'please provide',
    'However, I notice',
    'Could you please',
    'I\'ll deliver only',
  ];

  /**
   * Instancia cacheada del proveedor IA.
   *
   * @var object|null
   */
  private ?object $providerInstance = NULL;

  public function __construct(
    protected ?object $orchestrator,
    protected ?ModelRouterService $modelRouter,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
    protected ?TenantContextService $tenantContext = NULL,
    protected ?ApiKeyHealthService $apiKeyHealth = NULL,
  ) {}

  /**
   * Traduce un texto al idioma especificado.
   *
   * @param string $text
   *   Texto a traducir.
   * @param string $sourceLang
   *   Codigo de idioma origen (ej: 'es').
   * @param string $targetLang
   *   Codigo de idioma destino (ej: 'en').
   * @param array $options
   *   Opciones adicionales.
   *
   * @return string
   *   Texto traducido.
   *
   * @throws \RuntimeException
   *   Si la traduccion falla.
   */
  public function translate(
    string $text,
    string $sourceLang,
    string $targetLang,
    array $options = [],
  ): string {
    if (trim($text) === '' || $sourceLang === $targetLang) {
      return $text;
    }

    // API-KEY-ROTATION-001: Circuit breaker — no intentar si la API esta caida.
    if ($this->apiKeyHealth?->isCircuitOpen()) {
      throw new \RuntimeException('API de traduccion temporalmente no disponible (circuit breaker abierto).');
    }

    $systemPrompt = $this->buildSystemPrompt($sourceLang, $targetLang);

    try {
      $result = $this->callAiProvider($systemPrompt, $text);
      $result = $this->cleanResult($result, $text);

      $this->apiKeyHealth?->recordSuccess();

      $this->logger->info('Traduccion completada: @source_lang → @target_lang, @chars chars', [
        '@source_lang' => $sourceLang,
        '@target_lang' => $targetLang,
        '@chars' => strlen($text),
      ]);

      return $result;
    }
    catch (\Throwable $e) {
      $this->apiKeyHealth?->recordFailure($e->getMessage());

      $this->logger->error('Error en traduccion IA: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw new \RuntimeException('Error en traduccion: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Traduce multiples textos en una sola llamada.
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
    string $targetLang,
  ): array {
    if ($texts === []) {
      return [];
    }

    $systemPrompt = $this->buildSystemPrompt($sourceLang, $targetLang);
    $batchPrompt = "Traduce los siguientes textos. Responde SOLO con un JSON valido manteniendo las mismas claves:\n\n"
      . json_encode($texts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    try {
      $result = $this->callAiProvider($systemPrompt, $batchPrompt);
      $result = $this->cleanResult($result, '');

      $translated = json_decode($result, TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $this->logger->warning('Batch JSON parse failed, translating one by one.');
        return $this->translateOneByOne($texts, $sourceLang, $targetLang);
      }

      return $translated;
    }
    catch (\Throwable $e) {
      $this->logger->warning('Traduccion batch fallida, reintentando uno a uno: @error', [
        '@error' => $e->getMessage(),
      ]);
      return $this->translateOneByOne($texts, $sourceLang, $targetLang);
    }
  }

  /**
   * Traduce todos los campos traducibles de una entidad.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   La entidad con la traduccion existente.
   * @param string $sourceLang
   *   Idioma origen.
   * @param string $targetLang
   *   Idioma destino.
   * @param array $fieldNames
   *   Lista de campos a traducir. Si vacio, detecta automaticamente.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   La entidad con campos traducidos (sin guardar).
   */
  public function translateEntity(
    ContentEntityInterface $entity,
    string $sourceLang,
    string $targetLang,
    array $fieldNames = [],
  ): ContentEntityInterface {
    $original = $entity->getUntranslated();

    if ($fieldNames === []) {
      $fieldNames = $this->getTranslatableTextFields($original);
    }

    $textsToTranslate = [];
    foreach ($fieldNames as $fieldName) {
      if (!$original->hasField($fieldName)) {
        continue;
      }
      $field = $original->get($fieldName);
      $value = (string) ($field->value ?? $field->getString());
      if ($value !== '') {
        $textsToTranslate[$fieldName] = $value;
      }
    }

    $translations = $this->translateBatch($textsToTranslate, $sourceLang, $targetLang);

    foreach ($translations as $fieldName => $translatedValue) {
      $translatedValue = $this->sanitizeTranslatedValue($fieldName, $translatedValue, $entity);
      $entity->set($fieldName, $translatedValue);
    }

    return $entity;
  }

  /**
   * Llama al AI Provider directamente via Chat API.
   */
  protected function callAiProvider(string $systemPrompt, string $userMessage): string {
    $provider = $this->getProvider();

    $chatInput = new ChatInput([
      new ChatMessage('system', $systemPrompt),
      new ChatMessage('user', $userMessage),
    ]);

    $response = $provider->chat($chatInput, self::DEFAULT_MODEL, [
      'temperature' => 0.3,
    ]);

    return trim($response->getNormalized()->getText());
  }

  /**
   * Obtiene o crea la instancia del proveedor IA.
   *
   * @return object
   *   El proveedor IA.
   *
   * @throws \RuntimeException
   *   Si el proveedor no esta disponible.
   */
  protected function getProvider(): object {
    if ($this->providerInstance !== NULL) {
      return $this->providerInstance;
    }

    if (!\Drupal::hasService('ai.provider')) {
      throw new \RuntimeException('AI Provider framework no disponible.');
    }

    $providerManager = \Drupal::service('ai.provider');
    $this->providerInstance = $providerManager->createInstance(self::DEFAULT_PROVIDER);

    return $this->providerInstance;
  }

  /**
   * Construye el prompt del sistema con idiomas y brand voice.
   */
  protected function buildSystemPrompt(string $sourceLang, string $targetLang): string {
    $srcName = self::LANG_NAMES[$sourceLang] ?? $sourceLang;
    $tgtName = self::LANG_NAMES[$targetLang] ?? $targetLang;
    $brandContext = $this->getBrandContext();

    $prompt = self::SYSTEM_PROMPT;
    $prompt = str_replace('{source_lang}', $srcName, $prompt);
    $prompt = str_replace('{target_lang}', $tgtName, $prompt);
    $prompt = str_replace('{brand_context}', $brandContext, $prompt);

    return $prompt;
  }

  /**
   * Obtiene el contexto de marca del tenant actual.
   */
  protected function getBrandContext(): string {
    $tenant = $this->tenantContext?->getCurrentTenant();
    if ($tenant) {
      $parts = [];
      $siteName = $tenant->label() ?? '';
      if ($siteName !== '') {
        $parts[] = "Marca: {$siteName}.";
      }
      $slogan = $tenant->get('slogan')->value ?? '';
      if ($slogan !== '') {
        $parts[] = "Eslogan: {$slogan}.";
      }
      $vertical = $tenant->get('vertical')->value ?? '';
      if ($vertical !== '') {
        $parts[] = "Vertical: {$vertical}.";
      }
      if ($parts !== []) {
        return implode(' ', $parts) . ' Tono profesional pero accesible.';
      }
    }

    return 'Tono profesional pero accesible. Enfocado en impacto social y empleabilidad.';
  }

  /**
   * Limpia el resultado de la IA eliminando artefactos comunes.
   */
  protected function cleanResult(string $result, string $originalText): string {
    // Eliminar code fences de markdown.
    $result = preg_replace('/^```(?:html|json)?\s*\n?/i', '', $result) ?? $result;
    $result = preg_replace('/\n?```\s*$/i', '', $result) ?? $result;

    // Eliminar heading markdown si el original no lo tiene.
    if (str_starts_with($result, '# ') && !str_contains($originalText, '# ')) {
      $result = substr($result, 2);
    }

    // Eliminar saltos de linea en campos de una sola linea.
    if ($originalText !== '' && !str_contains($originalText, "\n") && str_contains($result, "\n")) {
      $result = str_replace(["\n\n", "\n"], [' ', ' '], $result);
    }

    // Detectar alucinaciones IA.
    foreach (self::HALLUCINATION_PATTERNS as $pattern) {
      if (stripos($result, $pattern) !== FALSE) {
        $this->logger->warning('Alucinacion IA detectada, devolviendo texto original.');
        return $originalText;
      }
    }

    return trim($result);
  }

  /**
   * Traduce textos uno a uno (fallback).
   *
   * @param array<string, string> $texts
   *   Textos a traducir.
   * @param string $sourceLang
   *   Idioma origen.
   * @param string $targetLang
   *   Idioma destino.
   *
   * @return array<string, string>
   *   Textos traducidos.
   */
  protected function translateOneByOne(array $texts, string $sourceLang, string $targetLang): array {
    $results = [];

    foreach ($texts as $key => $text) {
      try {
        $results[$key] = $this->translate($text, $sourceLang, $targetLang);
      }
      catch (\Throwable $e) {
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
   * Sanitiza un valor traducido segun el tipo de campo.
   */
  protected function sanitizeTranslatedValue(string $fieldName, string $value, ContentEntityInterface $entity): string {
    // Detectar alucinacion IA.
    foreach (self::HALLUCINATION_PATTERNS as $pattern) {
      if (stripos($value, $pattern) !== FALSE) {
        $this->logger->warning('Alucinacion IA en campo @field, usando original.', ['@field' => $fieldName]);
        $original = $entity->getUntranslated();
        if ($original->hasField($fieldName)) {
          return $original->get($fieldName)->value ?? $original->get($fieldName)->getString();
        }
        return '';
      }
    }

    // path_alias: formato URL-safe.
    if ($fieldName === 'path_alias') {
      $value = preg_replace('#/{2,}#', '/', $value) ?? $value;
      if (!str_starts_with($value, '/')) {
        $value = '/' . $value;
      }
      $parts = explode('/', $value);
      $parts = array_map(function ($part) {
        if ($part === '') {
          return '';
        }
        $slug = mb_strtolower($part);
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug) ?? $slug;
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        return trim($slug, '-');
      }, $parts);
      $value = implode('/', $parts);
      $value = rtrim($value, '/');
      if ($value === '') {
        $value = '/';
      }
      if (mb_strlen($value) > 255) {
        $value = mb_substr($value, 0, 255);
      }
    }

    // Respetar max_length del campo.
    if ($entity->hasField($fieldName)) {
      $definition = $entity->getFieldDefinition($fieldName);
      $maxLength = $definition?->getSetting('max_length');
      if ($maxLength !== NULL && mb_strlen($value) > (int) $maxLength) {
        $value = mb_substr($value, 0, (int) $maxLength);
      }
    }

    return $value;
  }

  /**
   * Obtiene campos de texto traducibles de una entidad.
   *
   * @return string[]
   */
  protected function getTranslatableTextFields(ContentEntityInterface $entity): array {
    $textFields = [];
    $fieldDefinitions = $entity->getFieldDefinitions();

    foreach ($fieldDefinitions as $fieldName => $definition) {
      if (str_starts_with($fieldName, 'field_') || in_array($fieldName, ['title', 'name', 'label'], TRUE)) {
        $type = $definition->getType();
        if (in_array($type, ['string', 'string_long', 'text', 'text_long', 'text_with_summary'], TRUE)) {
          $textFields[] = $fieldName;
        }
      }
    }

    return $textFields;
  }

}
