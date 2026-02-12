<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\jaraba_page_builder\Entity\PageContent;

/**
 * Servicio de auditoría SEO para páginas del Site Builder.
 *
 * Realiza 6 comprobaciones ponderadas sobre una PageContent entity
 * y devuelve un score 0-100 con issues detallados.
 *
 * CHECKS Y PESOS:
 * - Meta Title:      20% (presente, 10-60 chars)
 * - Meta Description: 20% (presente, 50-160 chars)
 * - H1 Único:        20% (exactamente 1 h1 en rendered_html)
 * - Images Alt:      15% (todas las img con alt no vacío)
 * - Content Length:   15% (≥300 palabras en rendered_html)
 * - URL Slug:        10% (existe, sin mayúsculas/especiales, ≤80 chars)
 *
 * Sprint B2: SEO Assistant Integrado.
 *
 * COMPILACIÓN:
 * docker exec jarabasaas_appserver_1 drush cr
 */
class SeoAuditorService
{

    use StringTranslationTrait;

    /**
     * Pesos de cada check SEO (deben sumar 1.0).
     */
    protected const WEIGHTS = [
        'meta_title' => 0.20,
        'meta_description' => 0.20,
        'h1_unique' => 0.20,
        'images_alt' => 0.15,
        'content_length' => 0.15,
        'url_slug' => 0.10,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
    ) {
    }

    /**
     * Audita una página y devuelve score + issues.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageContent $page
     *   La página a auditar.
     *
     * @return array
     *   Array con:
     *   - score: int 0-100
     *   - issues: array de issues con type, message, check, score
     *   - checks: array detallado de cada check
     */
    public function audit(PageContent $page): array
    {
        $checks = [];

        $checks['meta_title'] = $this->checkMetaTitle($page);
        $checks['meta_description'] = $this->checkMetaDescription($page);
        $checks['h1_unique'] = $this->checkH1Unique($page);
        $checks['images_alt'] = $this->checkImagesAlt($page);
        $checks['content_length'] = $this->checkContentLength($page);
        $checks['url_slug'] = $this->checkUrlSlug($page);

        // Calcular score ponderado.
        $score = 0;
        foreach ($checks as $key => $check) {
            $score += $check['score'] * self::WEIGHTS[$key];
        }

        // Recopilar issues.
        $issues = [];
        foreach ($checks as $check) {
            foreach ($check['issues'] as $issue) {
                $issues[] = $issue;
            }
        }

        return [
            'score' => (int) round($score),
            'issues' => $issues,
            'checks' => $checks,
        ];
    }

    /**
     * Audita múltiples páginas y devuelve score promedio.
     *
     * @param array $pageIds
     *   Array de IDs de PageContent.
     *
     * @return int|null
     *   Score promedio 0-100, o NULL si no hay páginas.
     */
    public function getAverageScore(array $pageIds): ?int
    {
        if (empty($pageIds)) {
            return NULL;
        }

        $storage = $this->entityTypeManager->getStorage('page_content');
        $pages = $storage->loadMultiple($pageIds);

        if (empty($pages)) {
            return NULL;
        }

        $totalScore = 0;
        $count = 0;

        foreach ($pages as $page) {
            if ($page instanceof PageContent) {
                $result = $this->audit($page);
                $totalScore += $result['score'];
                $count++;
            }
        }

        return $count > 0 ? (int) round($totalScore / $count) : NULL;
    }

    /**
     * Obtiene el HTML renderizado de la página para análisis.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageContent $page
     *   La página.
     *
     * @return string
     *   HTML renderizado.
     */
    protected function getRenderedHtml(PageContent $page): string
    {
        // Priorizar rendered_html (Canvas Editor output).
        if ($page->hasField('rendered_html')) {
            $html = $page->get('rendered_html')->value;
            if (!empty($html)) {
                return $html;
            }
        }

        // Fallback: extraer HTML de canvas_data.
        if ($page->hasField('canvas_data')) {
            $canvasRaw = $page->get('canvas_data')->value;
            if (!empty($canvasRaw)) {
                $canvasData = json_decode($canvasRaw, TRUE);
                if (isset($canvasData['html'])) {
                    return $canvasData['html'];
                }
            }
        }

        // Fallback: content_data puede contener HTML embebido.
        if ($page->hasField('content_data')) {
            $contentRaw = $page->get('content_data')->value;
            if (!empty($contentRaw)) {
                $contentData = json_decode($contentRaw, TRUE);
                // Buscar campos de tipo HTML en content_data.
                return $this->extractHtmlFromContentData($contentData);
            }
        }

        return '';
    }

    /**
     * Extrae texto HTML de content_data recursivamente.
     *
     * @param mixed $data
     *   Datos del contenido.
     *
     * @return string
     *   HTML concatenado.
     */
    protected function extractHtmlFromContentData($data): string
    {
        if (!is_array($data)) {
            return is_string($data) && strip_tags($data) !== $data ? $data : '';
        }

        $html = '';
        foreach ($data as $value) {
            $html .= $this->extractHtmlFromContentData($value);
        }
        return $html;
    }

    // =========================================================================
    // CHECKS INDIVIDUALES
    // =========================================================================

    /**
     * Check: Meta Title (20%).
     *
     * Verifica presencia y longitud del meta título.
     */
    protected function checkMetaTitle(PageContent $page): array
    {
        $metaTitle = '';
        if ($page->hasField('meta_title')) {
            $metaTitle = trim((string) $page->get('meta_title')->value);
        }

        $issues = [];
        $score = 100;

        if (empty($metaTitle)) {
            $score = 0;
            $issues[] = [
                'type' => 'error',
                'check' => 'meta_title',
                'message' => $this->t('Falta el meta título. Añade un título SEO de 10-60 caracteres.'),
            ];
        } else {
            $len = mb_strlen($metaTitle);
            if ($len < 10) {
                $score = 30;
                $issues[] = [
                    'type' => 'warning',
                    'check' => 'meta_title',
                    'message' => $this->t('El meta título es demasiado corto (@len caracteres). Recomendado: 10-60.', ['@len' => $len]),
                ];
            } elseif ($len > 60) {
                $score = 60;
                $issues[] = [
                    'type' => 'warning',
                    'check' => 'meta_title',
                    'message' => $this->t('El meta título es demasiado largo (@len caracteres). Recomendado: máximo 60.', ['@len' => $len]),
                ];
            } else {
                $issues[] = [
                    'type' => 'success',
                    'check' => 'meta_title',
                    'message' => $this->t('Meta título correcto (@len caracteres).', ['@len' => $len]),
                ];
            }
        }

        return ['score' => $score, 'issues' => $issues];
    }

    /**
     * Check: Meta Description (20%).
     *
     * Verifica presencia y longitud de la meta descripción.
     */
    protected function checkMetaDescription(PageContent $page): array
    {
        $metaDesc = '';
        if ($page->hasField('meta_description')) {
            $metaDesc = trim((string) $page->get('meta_description')->value);
        }

        $issues = [];
        $score = 100;

        if (empty($metaDesc)) {
            $score = 0;
            $issues[] = [
                'type' => 'error',
                'check' => 'meta_description',
                'message' => $this->t('Falta la meta descripción. Añade una descripción de 50-160 caracteres.'),
            ];
        } else {
            $len = mb_strlen($metaDesc);
            if ($len < 50) {
                $score = 40;
                $issues[] = [
                    'type' => 'warning',
                    'check' => 'meta_description',
                    'message' => $this->t('La meta descripción es demasiado corta (@len caracteres). Recomendado: 50-160.', ['@len' => $len]),
                ];
            } elseif ($len > 160) {
                $score = 60;
                $issues[] = [
                    'type' => 'warning',
                    'check' => 'meta_description',
                    'message' => $this->t('La meta descripción es demasiado larga (@len caracteres). Recomendado: máximo 160.', ['@len' => $len]),
                ];
            } else {
                $issues[] = [
                    'type' => 'success',
                    'check' => 'meta_description',
                    'message' => $this->t('Meta descripción correcta (@len caracteres).', ['@len' => $len]),
                ];
            }
        }

        return ['score' => $score, 'issues' => $issues];
    }

    /**
     * Check: H1 Único (20%).
     *
     * Verifica que exista exactamente un H1 en el HTML renderizado.
     */
    protected function checkH1Unique(PageContent $page): array
    {
        $html = $this->getRenderedHtml($page);
        $issues = [];
        $score = 100;

        if (empty($html)) {
            $score = 0;
            $issues[] = [
                'type' => 'warning',
                'check' => 'h1_unique',
                'message' => $this->t('No hay contenido HTML para analizar.'),
            ];
            return ['score' => $score, 'issues' => $issues];
        }

        // Contar H1s usando regex (más rápido que DOM para este caso).
        preg_match_all('/<h1[\s>]/i', $html, $matches);
        $h1Count = count($matches[0]);

        if ($h1Count === 0) {
            $score = 20;
            $issues[] = [
                'type' => 'error',
                'check' => 'h1_unique',
                'message' => $this->t('No se encontró ningún H1. Cada página debe tener exactamente un H1.'),
            ];
        } elseif ($h1Count > 1) {
            $score = 40;
            $issues[] = [
                'type' => 'warning',
                'check' => 'h1_unique',
                'message' => $this->t('Se encontraron @count H1. Cada página debe tener exactamente uno.', ['@count' => $h1Count]),
            ];
        } else {
            $issues[] = [
                'type' => 'success',
                'check' => 'h1_unique',
                'message' => $this->t('H1 único encontrado correctamente.'),
            ];
        }

        return ['score' => $score, 'issues' => $issues];
    }

    /**
     * Check: Images Alt (15%).
     *
     * Verifica que todas las imágenes tengan atributo alt no vacío.
     */
    protected function checkImagesAlt(PageContent $page): array
    {
        $html = $this->getRenderedHtml($page);
        $issues = [];
        $score = 100;

        if (empty($html)) {
            // Sin HTML = sin imágenes que verificar → aprobado.
            $issues[] = [
                'type' => 'info',
                'check' => 'images_alt',
                'message' => $this->t('No hay contenido HTML para verificar imágenes.'),
            ];
            return ['score' => $score, 'issues' => $issues];
        }

        // Buscar todas las etiquetas <img>.
        preg_match_all('/<img\b[^>]*>/i', $html, $imgMatches);
        $totalImages = count($imgMatches[0]);

        if ($totalImages === 0) {
            $issues[] = [
                'type' => 'info',
                'check' => 'images_alt',
                'message' => $this->t('No se encontraron imágenes en la página.'),
            ];
            return ['score' => $score, 'issues' => $issues];
        }

        // Verificar alt en cada imagen.
        $missingAlt = 0;
        foreach ($imgMatches[0] as $imgTag) {
            // Verificar si tiene alt="" o no tiene alt.
            if (!preg_match('/\balt\s*=\s*["\']([^"\']+)["\']/i', $imgTag)) {
                $missingAlt++;
            }
        }

        if ($missingAlt > 0) {
            $pct = ($totalImages - $missingAlt) / $totalImages;
            $score = (int) round($pct * 100);
            $issues[] = [
                'type' => $missingAlt === $totalImages ? 'error' : 'warning',
                'check' => 'images_alt',
                'message' => $this->t('@count de @total imágenes sin texto alternativo (alt).', [
                    '@count' => $missingAlt,
                    '@total' => $totalImages,
                ]),
            ];
        } else {
            $issues[] = [
                'type' => 'success',
                'check' => 'images_alt',
                'message' => $this->t('Todas las imágenes (@total) tienen texto alternativo.', ['@total' => $totalImages]),
            ];
        }

        return ['score' => $score, 'issues' => $issues];
    }

    /**
     * Check: Content Length (15%).
     *
     * Verifica que el contenido tenga al menos 300 palabras.
     */
    protected function checkContentLength(PageContent $page): array
    {
        $html = $this->getRenderedHtml($page);
        $issues = [];
        $score = 100;

        // Extraer texto plano del HTML.
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', trim($text));
        $wordCount = str_word_count($text);

        if ($wordCount < 100) {
            $score = 20;
            $issues[] = [
                'type' => 'error',
                'check' => 'content_length',
                'message' => $this->t('Contenido muy corto (@count palabras). Recomendado: mínimo 300 palabras.', ['@count' => $wordCount]),
            ];
        } elseif ($wordCount < 300) {
            $score = 50;
            $issues[] = [
                'type' => 'warning',
                'check' => 'content_length',
                'message' => $this->t('Contenido algo corto (@count palabras). Recomendado: mínimo 300 palabras.', ['@count' => $wordCount]),
            ];
        } else {
            $issues[] = [
                'type' => 'success',
                'check' => 'content_length',
                'message' => $this->t('Longitud de contenido adecuada (@count palabras).', ['@count' => $wordCount]),
            ];
        }

        return ['score' => $score, 'issues' => $issues];
    }

    /**
     * Check: URL Slug (10%).
     *
     * Verifica que exista un path alias limpio.
     */
    protected function checkUrlSlug(PageContent $page): array
    {
        $slug = '';
        if ($page->hasField('path_alias')) {
            $slug = trim((string) $page->get('path_alias')->value);
        }

        $issues = [];
        $score = 100;

        if (empty($slug)) {
            $score = 20;
            $issues[] = [
                'type' => 'error',
                'check' => 'url_slug',
                'message' => $this->t('No se ha definido una URL amigable (slug).'),
            ];
            return ['score' => $score, 'issues' => $issues];
        }

        // Verificar mayúsculas.
        if ($slug !== mb_strtolower($slug)) {
            $score -= 30;
            $issues[] = [
                'type' => 'warning',
                'check' => 'url_slug',
                'message' => $this->t('La URL contiene mayúsculas. Se recomienda usar solo minúsculas.'),
            ];
        }

        // Verificar caracteres especiales (solo letras, números, guiones, barras).
        if (preg_match('/[^a-z0-9\-\/]/', mb_strtolower($slug))) {
            $score -= 20;
            $issues[] = [
                'type' => 'warning',
                'check' => 'url_slug',
                'message' => $this->t('La URL contiene caracteres especiales. Usa solo letras, números y guiones.'),
            ];
        }

        // Verificar longitud.
        if (mb_strlen($slug) > 80) {
            $score -= 20;
            $issues[] = [
                'type' => 'warning',
                'check' => 'url_slug',
                'message' => $this->t('La URL es demasiado larga (@len caracteres). Recomendado: máximo 80.', ['@len' => mb_strlen($slug)]),
            ];
        }

        $score = max(0, $score);

        if (empty($issues)) {
            $issues[] = [
                'type' => 'success',
                'check' => 'url_slug',
                'message' => $this->t('URL amigable correcta.'),
            ];
        }

        return ['score' => $score, 'issues' => $issues];
    }

}
