<?php

declare(strict_types=1);

namespace Drupal\jaraba_site_builder\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\ecosistema_jaraba_core\Service\TenantContextService;
use Drupal\jaraba_site_builder\Entity\SiteConfig;

/**
 * Servicio para renderizar variantes de footer según configuración del tenant.
 *
 * Canvas v2 Full Page Editor:
 * Proporciona pre-renderizado de variantes de footer para su uso en el
 * editor visual GrapesJS. Permite cambio de variante sin llamadas API.
 */
class FooterVariantService
{

    /**
     * Variantes de footer disponibles.
     * Alineadas con theme settings: /admin/appearance/settings.
     */
    protected const FOOTER_VARIANTS = [
        'minimal',
        'standard',
        'mega',
        'split',
    ];

    /**
     * Constructor.
     */
    public function __construct(
        protected EntityTypeManagerInterface $entityTypeManager,
        protected TenantContextService $tenantContext,
        protected RendererInterface $renderer,
        protected SiteStructureService $siteStructure,
    ) {
    }

    /**
     * Obtiene la configuración del site para el tenant actual.
     *
     * @return \Drupal\jaraba_site_builder\Entity\SiteConfig|null
     *   La configuración o NULL si no existe.
     */
    public function getSiteConfig(): ?SiteConfig
    {
        $tenant = $this->tenantContext->getCurrentTenant();
        if (!$tenant) {
            return NULL;
        }
        $tenantId = (int) $tenant->id();

        $configs = $this->entityTypeManager
            ->getStorage('site_config')
            ->loadByProperties(['tenant_id' => $tenantId]);

        return $configs ? reset($configs) : NULL;
    }

    /**
     * Obtiene el tipo de footer configurado para el tenant actual.
     *
     * @return string
     *   Tipo de footer: simple, columns, mega, minimal.
     */
    public function getConfiguredFooterType(): string
    {
        $config = $this->getSiteConfig();
        return $config ? $config->getFooterType() : 'standard';
    }

    /**
     * Renderiza el footer según la configuración del tenant.
     *
     * @param string|null $variant
     *   Variante específica a renderizar, o NULL para usar la configurada.
     *
     * @return string
     *   HTML renderizado del footer.
     */
    public function renderFooter(?string $variant = NULL): string
    {
        $config = $this->getSiteConfig();
        $type = $variant ?? ($config ? $config->getFooterType() : 'standard');

        $tenant = $this->tenantContext->getCurrentTenant();
        $navigation = $this->siteStructure->getNavigation(
            $tenant ? (int) $tenant->id() : NULL,
            'footer'
        );

        // Extraer valores escalares para evitar errores de FieldItemList en Twig.
        $configData = [
            'tagline' => $config?->get('site_tagline')->value ?? 'Transformando el futuro digital',
            'contact_email' => $config?->get('contact_email')->value ?? '',
            'contact_phone' => $config?->get('contact_phone')->value ?? '',
        ];

        $build = [
            '#theme' => 'jaraba_footer_' . $type,
            '#config' => $configData,
            '#navigation' => $navigation,
            '#columns' => $config?->getFooterColumns() ?? 4,
            '#show_social' => $config?->showFooterSocial() ?? TRUE,
            '#show_newsletter' => $config?->showFooterNewsletter() ?? TRUE,
            '#copyright' => $config?->getFooterCopyright() ?? '© ' . date('Y'),
            '#social_links' => $config?->getSocialLinks() ?? [],
            '#site_name' => $config?->getSiteName() ?? 'Jaraba',
            '#logo' => $this->getLogo($config),
        ];

        return (string) $this->renderer->renderRoot($build);
    }

    /**
     * Pre-renderiza todas las variantes de footer.
     *
     * Útil para el Canvas Editor: permite cambio de variante sin API call.
     *
     * @return array
     *   Array [variant => html].
     */
    public function preRenderAllVariants(): array
    {
        $variants = [];
        foreach (self::FOOTER_VARIANTS as $variant) {
            $variants[$variant] = $this->renderFooter($variant);
        }
        return $variants;
    }

    /**
     * Obtiene los datos del footer para el Canvas Editor.
     *
     * @return array
     *   Datos estructurados para inicializar el Canvas Editor.
     */
    public function getFooterDataForCanvas(): array
    {
        $siteConfig = $this->getSiteConfig();
        $currentType = $this->getConfiguredFooterType();

        return [
            // Estructura que espera el template Twig.
            'config' => [
                'current' => $currentType,
                'columns' => $siteConfig?->getFooterColumns() ?? 4,
                'showSocial' => $siteConfig?->showFooterSocial() ?? TRUE,
                'showNewsletter' => $siteConfig?->showFooterNewsletter() ?? TRUE,
                'copyright' => $siteConfig?->getFooterCopyright() ?? '© ' . date('Y'),
            ],
            'availableTypes' => self::FOOTER_VARIANTS,
            'html' => $this->renderFooter(),
            'variants' => $this->preRenderAllVariants(),
        ];
    }

    /**
     * Obtiene la URL del logo del tenant.
     *
     * @param \Drupal\jaraba_site_builder\Entity\SiteConfig|null $config
     *   Configuración del sitio.
     *
     * @return string|null
     *   URL del logo o NULL.
     */
    protected function getLogo(?SiteConfig $config): ?string
    {
        if (!$config || $config->get('site_logo')->isEmpty()) {
            return NULL;
        }

        /** @var \Drupal\file\FileInterface $file */
        $file = $config->get('site_logo')->entity;
        return $file ? $file->createFileUrl() : NULL;
    }

}
