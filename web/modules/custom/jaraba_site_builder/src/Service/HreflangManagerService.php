<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Psr\Log\LoggerInterface;

/**
 * Servicio de gestión de etiquetas hreflang multi-idioma.
 *
 * Gestiona la configuración hreflang por página para SEO multi-idioma,
 * incluyendo detección de idiomas disponibles y generación de link tags.
 *
 * Fase 4 Doc 179: Hreflang Manager.
 */
class HreflangManagerService
{

    use StringTranslationTrait;

    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantContextService $tenantContext,
        protected LanguageManagerInterface $languageManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Obtiene los idiomas disponibles en la plataforma.
     *
     * @return array
     *   Array de idiomas: [{code: 'es', name: 'Español'}, ...].
     */
    public function getAvailableLanguages(): array
    {
        $languages = $this->languageManager->getLanguages();
        $result = [];

        foreach ($languages as $langcode => $language) {
            $result[] = [
                'code' => $langcode,
                'name' => $language->getName(),
                'is_default' => $language->isDefault(),
            ];
        }

        return $result;
    }

    /**
     * Obtiene la configuración hreflang de una página.
     *
     * @param int $pageId
     *   ID de la página.
     *
     * @return array
     *   Array de entradas hreflang: [{lang: 'es', url: '...'}, ...].
     */
    public function getHreflangConfig(int $pageId): array
    {
        $seoConfig = $this->loadSeoConfig($pageId);
        if (!$seoConfig) {
            return [];
        }

        return $seoConfig->getHreflangConfig();
    }

    /**
     * Actualiza la configuración hreflang de una página.
     *
     * @param int $pageId
     *   ID de la página.
     * @param array $entries
     *   Array de entradas: [{lang: 'es', url: '...'}, {lang: 'en', url: '...'}].
     *
     * @return bool
     *   TRUE si se actualizó correctamente.
     */
    public function updateHreflangConfig(int $pageId, array $entries): bool
    {
        $seoConfig = $this->loadSeoConfig($pageId);
        if (!$seoConfig) {
            return FALSE;
        }

        // Validar y limpiar las entradas.
        $cleanEntries = [];
        foreach ($entries as $entry) {
            if (empty($entry['lang']) || empty($entry['url'])) {
                continue;
            }
            $cleanEntries[] = [
                'lang' => $this->sanitizeLanguageCode($entry['lang']),
                'url' => filter_var($entry['url'], FILTER_SANITIZE_URL),
            ];
        }

        $seoConfig->set('hreflang_config', json_encode($cleanEntries));
        $seoConfig->save();

        $this->logger->info('Hreflang actualizado para página @page: @count idiomas.', [
            '@page' => $pageId,
            '@count' => count($cleanEntries),
        ]);

        return TRUE;
    }

    /**
     * Genera etiquetas link hreflang para inyectar en <head>.
     *
     * @param int $pageId
     *   ID de la página.
     * @param string $canonicalUrl
     *   URL canónica de la página actual.
     *
     * @return array
     *   Array de tags para #attached html_head.
     */
    public function generateHreflangTags(int $pageId, string $canonicalUrl = ''): array
    {
        $entries = $this->getHreflangConfig($pageId);
        if (empty($entries)) {
            return [];
        }

        $tags = [];
        $hasDefault = FALSE;

        foreach ($entries as $entry) {
            $tags[] = [
                [
                    '#tag' => 'link',
                    '#attributes' => [
                        'rel' => 'alternate',
                        'hreflang' => $entry['lang'],
                        'href' => $entry['url'],
                    ],
                ],
                'hreflang_' . $entry['lang'],
            ];

            if ($entry['lang'] === 'x-default') {
                $hasDefault = TRUE;
            }
        }

        // Añadir x-default si no existe.
        if (!$hasDefault && !empty($canonicalUrl)) {
            $tags[] = [
                [
                    '#tag' => 'link',
                    '#attributes' => [
                        'rel' => 'alternate',
                        'hreflang' => 'x-default',
                        'href' => $canonicalUrl,
                    ],
                ],
                'hreflang_x_default',
            ];
        }

        // OG locale tags.
        $currentLang = $this->languageManager->getCurrentLanguage()->getId();
        $tags[] = [
            ['#tag' => 'meta', '#attributes' => ['property' => 'og:locale', 'content' => $this->toFacebookLocale($currentLang)]],
            'og_locale',
        ];

        foreach ($entries as $entry) {
            if ($entry['lang'] !== $currentLang && $entry['lang'] !== 'x-default') {
                $tags[] = [
                    ['#tag' => 'meta', '#attributes' => ['property' => 'og:locale:alternate', 'content' => $this->toFacebookLocale($entry['lang'])]],
                    'og_locale_alt_' . $entry['lang'],
                ];
            }
        }

        return $tags;
    }

    /**
     * Convierte código de idioma a formato Facebook locale.
     */
    protected function toFacebookLocale(string $langcode): string
    {
        $localeMap = [
            'es' => 'es_ES',
            'en' => 'en_US',
            'fr' => 'fr_FR',
            'de' => 'de_DE',
            'pt' => 'pt_BR',
            'it' => 'it_IT',
            'ca' => 'ca_ES',
            'eu' => 'eu_ES',
            'gl' => 'gl_ES',
        ];

        return $localeMap[$langcode] ?? $langcode . '_' . strtoupper($langcode);
    }

    /**
     * Sanitiza un código de idioma.
     */
    protected function sanitizeLanguageCode(string $code): string
    {
        return preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($code)));
    }

    /**
     * Carga la configuración SEO de una página.
     */
    protected function loadSeoConfig(int $pageId): ?\Drupal\jaraba_site_builder\Entity\SeoPageConfig
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        if (!$tenant) {
            return NULL;
        }

        $storage = $this->entityTypeManager->getStorage('seo_page_config');
        $results = $storage->loadByProperties([
            'page_id' => $pageId,
            'tenant_id' => $tenant->id(),
        ]);

        return empty($results) ? NULL : reset($results);
    }

}
