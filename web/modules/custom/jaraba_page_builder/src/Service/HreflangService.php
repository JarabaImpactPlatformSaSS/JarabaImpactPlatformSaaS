<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Servicio para generación de tags Hreflang multi-idioma.
 *
 * ESPECIFICACIÓN: Doc 166 - Platform_i18n_Multilanguage_v1
 *
 * Genera tags hreflang para:
 * - Detectar idiomas disponibles para cada página
 * - Generar URLs alternativas por idioma
 * - Soportar x-default
 *
 * @package Drupal\jaraba_page_builder\Service
 */
class HreflangService
{

    /**
     * Entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Language manager.
     *
     * @var \Drupal\Core\Language\LanguageManagerInterface
     */
    protected LanguageManagerInterface $languageManager;

    /**
     * Route match.
     *
     * @var \Drupal\Core\Routing\RouteMatchInterface
     */
    protected RouteMatchInterface $routeMatch;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        LanguageManagerInterface $language_manager,
        RouteMatchInterface $route_match
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->languageManager = $language_manager;
        $this->routeMatch = $route_match;
    }

    /**
     * Genera array de tags hreflang para la página actual.
     *
     * @return array
     *   Array de link tags para inyectar en <head>.
     */
    public function generateHreflangTags(): array
    {
        $tags = [];
        $routeName = $this->routeMatch->getRouteName();

        // Solo procesar rutas de page_content.
        if ($routeName !== 'entity.page_content.canonical') {
            return $tags;
        }

        $pageContent = $this->routeMatch->getParameter('page_content');
        if (!$pageContent) {
            return $tags;
        }

        // Verificar si es una entidad traducible.
        if (!$pageContent->isTranslatable()) {
            return $tags;
        }

        // Obtener idiomas disponibles.
        $translations = $pageContent->getTranslationLanguages(TRUE);
        if (count($translations) <= 1) {
            return $tags;
        }

        // Idioma por defecto del sitio.
        $defaultLangcode = $this->languageManager->getDefaultLanguage()->getId();

        // Generar tags hreflang.
        foreach ($translations as $langcode => $language) {
            $translatedPage = $pageContent->getTranslation($langcode);
            $url = $this->getTranslatedUrl($translatedPage, $langcode);

            $tags[] = [
                '#type' => 'html_tag',
                '#tag' => 'link',
                '#attributes' => [
                    'rel' => 'alternate',
                    'hreflang' => $langcode,
                    'href' => $url,
                ],
            ];
        }

        // Añadir x-default (apuntando al idioma principal).
        $defaultUrl = $this->getTranslatedUrl($pageContent, $defaultLangcode);
        $tags[] = [
            '#type' => 'html_tag',
            '#tag' => 'link',
            '#attributes' => [
                'rel' => 'alternate',
                'hreflang' => 'x-default',
                'href' => $defaultUrl,
            ],
        ];

        return $tags;
    }

    /**
     * Obtiene URL de una página traducida.
     *
     * @param mixed $page
     *   Entidad PageContent.
     * @param string $langcode
     *   Código de idioma.
     *
     * @return string
     *   URL absoluta de la página.
     */
    protected function getTranslatedUrl($page, string $langcode): string
    {
        // Intentar usar path_alias si existe.
        $pathAlias = $page->get('path_alias')->value ?? '';

        if (!empty($pathAlias)) {
            $baseUrl = \Drupal::request()->getSchemeAndHttpHost();
            // Incluir prefijo de idioma si no es el predeterminado.
            $defaultLangcode = $this->languageManager->getDefaultLanguage()->getId();
            if ($langcode !== $defaultLangcode) {
                return $baseUrl . '/' . $langcode . $pathAlias;
            }
            return $baseUrl . $pathAlias;
        }

        // Fallback a URL canónica de la entidad.
        try {
            return Url::fromRoute('entity.page_content.canonical', [
                'page_content' => $page->id(),
            ], [
                'absolute' => TRUE,
                'language' => $this->languageManager->getLanguage($langcode),
            ])->toString();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Genera canonical tag para la página actual.
     *
     * @return array|null
     *   Link tag canonical o null.
     */
    public function generateCanonicalTag(): ?array
    {
        $routeName = $this->routeMatch->getRouteName();

        if ($routeName !== 'entity.page_content.canonical') {
            return NULL;
        }

        $pageContent = $this->routeMatch->getParameter('page_content');
        if (!$pageContent) {
            return NULL;
        }

        // Obtener URL canónica.
        $pathAlias = $pageContent->get('path_alias')->value ?? '';
        $baseUrl = \Drupal::request()->getSchemeAndHttpHost();

        if (!empty($pathAlias)) {
            $canonicalUrl = $baseUrl . $pathAlias;
        } else {
            try {
                $canonicalUrl = Url::fromRoute('entity.page_content.canonical', [
                    'page_content' => $pageContent->id(),
                ])->setAbsolute()->toString();
            } catch (\Exception $e) {
                return NULL;
            }
        }

        return [
            '#type' => 'html_tag',
            '#tag' => 'link',
            '#attributes' => [
                'rel' => 'canonical',
                'href' => $canonicalUrl,
            ],
        ];
    }

    /**
     * Genera Open Graph locale tags.
     *
     * @return array
     *   Array de meta tags og:locale.
     */
    public function generateOgLocaleTags(): array
    {
        $tags = [];
        $routeName = $this->routeMatch->getRouteName();

        if ($routeName !== 'entity.page_content.canonical') {
            return $tags;
        }

        $pageContent = $this->routeMatch->getParameter('page_content');
        if (!$pageContent || !$pageContent->isTranslatable()) {
            return $tags;
        }

        $currentLangcode = $this->languageManager->getCurrentLanguage()->getId();
        $translations = $pageContent->getTranslationLanguages(TRUE);

        // og:locale para idioma actual.
        $tags[] = [
            '#type' => 'html_tag',
            '#tag' => 'meta',
            '#attributes' => [
                'property' => 'og:locale',
                'content' => $this->langcodeToLocale($currentLangcode),
            ],
        ];

        // og:locale:alternate para otros idiomas.
        foreach ($translations as $langcode => $language) {
            if ($langcode === $currentLangcode) {
                continue;
            }
            $tags[] = [
                '#type' => 'html_tag',
                '#tag' => 'meta',
                '#attributes' => [
                    'property' => 'og:locale:alternate',
                    'content' => $this->langcodeToLocale($langcode),
                ],
            ];
        }

        return $tags;
    }

    /**
     * Convierte langcode a formato locale de Facebook.
     *
     * @param string $langcode
     *   Código de idioma (es, en, ca, etc.).
     *
     * @return string
     *   Locale en formato Facebook (es_ES, en_US, etc.).
     */
    protected function langcodeToLocale(string $langcode): string
    {
        $map = [
            'es' => 'es_ES',
            'en' => 'en_US',
            'ca' => 'ca_ES',
            'eu' => 'eu_ES',
            'gl' => 'gl_ES',
            'pt' => 'pt_PT',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'it' => 'it_IT',
        ];

        return $map[$langcode] ?? $langcode . '_' . strtoupper($langcode);
    }

    /**
     * Obtiene idiomas configurados en el sitio.
     *
     * @return array
     *   Array de idiomas [langcode => name].
     */
    public function getAvailableLanguages(): array
    {
        $languages = [];
        foreach ($this->languageManager->getLanguages() as $langcode => $language) {
            $languages[$langcode] = $language->getName();
        }
        return $languages;
    }

}
