<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Multilingual GEO Service.
 *
 * Implementa estrategia multilingüe para GEO:
 * - Answer Capsules localizadas
 * - hreflang tags
 * - Traducción cultural adaptada
 */
class MultilingualGeoService
{

    use StringTranslationTrait;

    /**
     * Idiomas soportados.
     */
    protected const SUPPORTED_LANGUAGES = [
        'es' => [
            'name' => 'Español',
            'locale' => 'es_ES',
            'region' => 'Spain',
        ],
        'en' => [
            'name' => 'English',
            'locale' => 'en_US',
            'region' => 'United States',
        ],
        'fr' => [
            'name' => 'Français',
            'locale' => 'fr_FR',
            'region' => 'France',
        ],
        'de' => [
            'name' => 'Deutsch',
            'locale' => 'de_DE',
            'region' => 'Germany',
        ],
    ];

    /**
     * Plantillas de Answer Capsules por idioma.
     */
    protected const ANSWER_CAPSULE_TEMPLATES = [
        'es' => [
            'product' => '{product} es un producto artesanal de alta calidad, elaborado con métodos tradicionales. Ideal para {use_case}. Disponible en {store}.',
            'faq' => 'Pregunta frecuente: {question}. Respuesta: {answer}.',
        ],
        'en' => [
            'product' => '{product} is a high-quality artisanal product, crafted using traditional methods. Ideal for {use_case}. Available at {store}.',
            'faq' => 'Frequently asked question: {question}. Answer: {answer}.',
        ],
        'fr' => [
            'product' => '{product} est un produit artisanal de haute qualité, élaboré selon des méthodes traditionnelles. Idéal pour {use_case}. Disponible chez {store}.',
            'faq' => 'Question fréquente: {question}. Réponse: {answer}.',
        ],
        'de' => [
            'product' => '{product} ist ein hochwertiges handwerkliches Produkt, hergestellt nach traditionellen Methoden. Ideal für {use_case}. Erhältlich bei {store}.',
            'faq' => 'Häufig gestellte Frage: {question}. Antwort: {answer}.',
        ],
    ];

    /**
     * Genera hreflang tags para una página.
     *
     * @param string $path
     *   Path de la página.
     * @param string $baseUrl
     *   URL base del sitio.
     * @param array $availableLanguages
     *   Idiomas disponibles para esta página.
     *
     * @return array
     *   Array de hreflang tags.
     */
    public function generateHreflangTags(string $path, string $baseUrl, array $availableLanguages = []): array
    {
        if (empty($availableLanguages)) {
            $availableLanguages = array_keys(self::SUPPORTED_LANGUAGES);
        }

        $tags = [];

        foreach ($availableLanguages as $langcode) {
            if (!isset(self::SUPPORTED_LANGUAGES[$langcode])) {
                continue;
            }

            $langData = self::SUPPORTED_LANGUAGES[$langcode];
            $url = "{$baseUrl}/{$langcode}{$path}";

            $tags[] = [
                'rel' => 'alternate',
                'hreflang' => $langcode,
                'href' => $url,
            ];
        }

        // Añadir x-default.
        $defaultLang = $availableLanguages[0] ?? 'es';
        $tags[] = [
            'rel' => 'alternate',
            'hreflang' => 'x-default',
            'href' => "{$baseUrl}/{$defaultLang}{$path}",
        ];

        return $tags;
    }

    /**
     * Genera Answer Capsule localizada.
     *
     * @param string $type
     *   Tipo de capsule (product, faq, etc.).
     * @param array $data
     *   Datos para la capsule.
     * @param string $langcode
     *   Código de idioma.
     *
     * @return string
     *   Answer Capsule.
     */
    public function generateLocalizedAnswerCapsule(string $type, array $data, string $langcode = 'es'): string
    {
        $templates = self::ANSWER_CAPSULE_TEMPLATES[$langcode] ?? self::ANSWER_CAPSULE_TEMPLATES['es'];
        $template = $templates[$type] ?? '';

        if (empty($template)) {
            return '';
        }

        // Reemplazar placeholders.
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        return $template;
    }

    /**
     * Genera Schema.org WebPage multilingüe.
     *
     * @param array $pageData
     *   Datos de la página.
     * @param string $langcode
     *   Código de idioma.
     *
     * @return array
     *   Schema.org WebPage.
     */
    public function generateMultilingualPageSchema(array $pageData, string $langcode = 'es'): array
    {
        $langData = self::SUPPORTED_LANGUAGES[$langcode] ?? self::SUPPORTED_LANGUAGES['es'];

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $pageData['title'] ?? '',
            'description' => $pageData['description'] ?? '',
            'inLanguage' => $langcode,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'Jaraba Impact Platform',
                'url' => 'https://jaraba-impact.com',
                'availableLanguage' => array_keys(self::SUPPORTED_LANGUAGES),
            ],
            'potentialAction' => [
                '@type' => 'ReadAction',
                'target' => $pageData['url'] ?? '',
            ],
        ];
    }

    /**
     * Detecta el idioma preferido del usuario.
     *
     * @param string $acceptLanguageHeader
     *   Header Accept-Language.
     *
     * @return string
     *   Código de idioma soportado.
     */
    public function detectPreferredLanguage(string $acceptLanguageHeader): string
    {
        // Parse Accept-Language: es-ES,es;q=0.9,en;q=0.8
        $languages = [];
        $parts = explode(',', $acceptLanguageHeader);

        foreach ($parts as $part) {
            $subparts = explode(';q=', trim($part));
            $lang = strtolower(substr($subparts[0], 0, 2)); // Take first 2 chars.
            $quality = isset($subparts[1]) ? (float) $subparts[1] : 1.0;
            $languages[$lang] = $quality;
        }

        arsort($languages);

        // Retornar primer idioma soportado.
        foreach (array_keys($languages) as $lang) {
            if (isset(self::SUPPORTED_LANGUAGES[$lang])) {
                return $lang;
            }
        }

        return 'es'; // Default.
    }

    /**
     * Obtiene los idiomas soportados.
     *
     * @return array
     *   Idiomas soportados.
     */
    public function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

}
