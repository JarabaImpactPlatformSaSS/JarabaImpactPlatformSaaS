<?php

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\jaraba_page_builder\PageTemplateInterface;

/**
 * Controlador para páginas del Page Builder.
 */
class PageContentController extends ControllerBase
{

    /**
     * El entity type manager.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected $entityTypeManager;

    /**
     * El usuario actual.
     *
     * @var \Drupal\Core\Session\AccountInterface
     */
    protected $currentUser;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        AccountInterface $current_user
    ) {
        $this->entityTypeManager = $entity_type_manager;
        $this->currentUser = $current_user;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('entity_type.manager'),
            $container->get('current_user')
        );
    }

    /**
     * Crea una nueva página desde una plantilla.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $page_template
     *   La plantilla a usar.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|array
     *   Redirect al formulario de edición o render array.
     */
    public function createFromTemplate(PageTemplateInterface $page_template)
    {
        // Crear la entidad de página con la plantilla seleccionada.
        $page = $this->entityTypeManager
            ->getStorage('page_content')
            ->create([
                'template_id' => $page_template->id(),
                'title' => $this->t('Nueva página - @template', [
                    '@template' => $page_template->label(),
                ]),
                'uid' => $this->currentUser->id(),
                'status' => FALSE,
            ]);

        // Guardar la página.
        $page->save();

        // Redirigir al Canvas Editor visual para mejor UX.
        $url = Url::fromRoute('jaraba_page_builder.canvas_editor', [
            'page_content' => $page->id(),
        ], [
            'query' => ['mode' => 'canvas'],
        ]);

        $this->messenger()->addStatus($this->t('Página creada. Edita el contenido visualmente.'));

        return new RedirectResponse($url->toString());
    }

    /**
     * Título para la página de creación.
     *
     * @param \Drupal\jaraba_page_builder\PageTemplateInterface $page_template
     *   La plantilla.
     *
     * @return string
     *   El título.
     */
    public function createTitle(PageTemplateInterface $page_template): string
    {
        return $this->t('Crear página: @name', ['@name' => $page_template->label()]);
    }

    /**
     * Muestra las páginas del usuario actual con diseño premium.
     *
     * @return array
     *   Render array con cards de páginas.
     */
    public function myPages(): array
    {
        $storage = $this->entityTypeManager->getStorage('page_content');

        // Obtener páginas del usuario actual.
        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('uid', $this->currentUser->id())
            ->sort('changed', 'DESC');

        $ids = $query->execute();
        $pages = $storage->loadMultiple($ids);
        $count = count($pages);

        // Precargar templates para thumbnails.
        $templateStorage = $this->entityTypeManager->getStorage('page_template');
        $templateIds = [];
        foreach ($pages as $page) {
            $tid = $page->getTemplateId();
            if ($tid) {
                $templateIds[$tid] = $tid;
            }
        }
        $templates = !empty($templateIds) ? $templateStorage->loadMultiple($templateIds) : [];

        // Recoger templates únicos para el filtro dropdown.
        $uniqueTemplates = [];
        foreach ($pages as $page) {
            $tid = $page->getTemplateId();
            if ($tid && isset($templates[$tid])) {
                $uniqueTemplates[$tid] = $templates[$tid]->label();
            }
        }
        asort($uniqueTemplates);

        if (empty($pages)) {
            return [
                '#markup' => Markup::create('<div class="my-pages">'
                    . '<div class="my-pages__header">'
                    . '<div><h1 class="my-pages__title">'
                    . '<span class="my-pages__title-icon">'
                    . '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'
                    . '</span>'
                    . $this->t('Mis Páginas')
                    . '</h1></div>'
                    . '</div>'
                    . '<div class="my-pages__empty">'
                    . '<div class="my-pages__empty-icon">'
                    . '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#e97a2b" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>'
                    . '</div>'
                    . '<h2 class="my-pages__empty-title">' . $this->t('Aún no tienes páginas') . '</h2>'
                    . '<p class="my-pages__empty-desc">' . $this->t('Crea tu primera página usando nuestras plantillas profesionales. ¡Es fácil y rápido!') . '</p>'
                    . '<a href="/page-builder/templates" class="my-pages__btn-create">'
                    . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
                    . $this->t('Crear primera página')
                    . '</a>'
                    . '</div>'
                    . '</div>'),
                '#attached' => [
                    'library' => ['jaraba_page_builder/my-pages'],
                ],
            ];
        }

        // Barra de búsqueda y filtros.
        $filterBar = '<div class="my-pages__filters">'
            . '<div class="my-pages__search-wrapper">'
            . '<svg class="my-pages__search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>'
            . '<input type="search" id="my-pages-search" class="my-pages__search" placeholder="' . $this->t('Buscar páginas...') . '" autocomplete="off" />'
            . '</div>'
            . '<div class="my-pages__filter-group">'
            . '<select id="my-pages-status-filter" class="my-pages__filter-select">'
            . '<option value="">' . $this->t('Todos los estados') . '</option>'
            . '<option value="published">' . $this->t('Publicado') . '</option>'
            . '<option value="draft">' . $this->t('Borrador') . '</option>'
            . '</select>';

        // Filtro por template (solo si hay más de 1 template).
        if (count($uniqueTemplates) > 1) {
            $filterBar .= '<select id="my-pages-template-filter" class="my-pages__filter-select">'
                . '<option value="">' . $this->t('Todas las plantillas') . '</option>';
            foreach ($uniqueTemplates as $tid => $tLabel) {
                $filterBar .= '<option value="' . htmlspecialchars($tid) . '">' . htmlspecialchars($tLabel) . '</option>';
            }
            $filterBar .= '</select>';
        }

        $filterBar .= '</div>'
            . '<span class="my-pages__results-count" id="my-pages-results-count">'
            . $this->t('@count páginas', ['@count' => $count])
            . '</span>'
            . '</div>';

        // Construir cards HTML.
        $cards = '';
        foreach ($pages as $page) {
            /** @var \Drupal\jaraba_page_builder\PageContentInterface $page */
            $isPublished = $page->isPublished();
            $statusClass = $isPublished ? 'published' : 'draft';
            $statusText = $isPublished ? $this->t('Publicado') : $this->t('Borrador');
            $templateId = $page->getTemplateId() ?: '';
            $templateLabel = ($templateId && isset($templates[$templateId])) ? $templates[$templateId]->label() : $templateId;
            $title = $page->label();
            $pageUrl = $page->toUrl()->toString();
            $canvasUrl = Url::fromRoute('jaraba_page_builder.canvas_editor', [
                'page_content' => $page->id(),
            ], [
                'query' => ['mode' => 'canvas'],
            ])->toString();
            $deleteUrl = $page->toUrl('delete-form')->toString();

            // Fecha de última modificación.
            $changed = $page->getChangedTime();
            $date = \Drupal::service('date.formatter')->format($changed, 'short');

            // Thumbnail del template.
            $thumbnailHtml = '';
            if ($templateId && isset($templates[$templateId])) {
                $thumbnailPath = $templates[$templateId]->getThumbnail();
                if ($thumbnailPath) {
                    $thumbnailHtml = '<img src="' . htmlspecialchars($thumbnailPath) . '" alt="' . htmlspecialchars($templateLabel) . '" class="my-pages__card-thumb" loading="lazy" />';
                }
            }

            // Fallback: placeholder SVG si no hay thumbnail.
            if (empty($thumbnailHtml)) {
                $thumbnailHtml = '<span class="my-pages__card-preview-icon">'
                    . '<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>'
                    . '</span>';
            }

            // Data attrs para filtro JS.
            $dataAttrs = ' data-title="' . htmlspecialchars(mb_strtolower($title)) . '"'
                . ' data-status="' . $statusClass . '"'
                . ' data-template="' . htmlspecialchars($templateId) . '"';

            $cards .= '<div class="my-pages__card"' . $dataAttrs . '>'
                . '<div class="my-pages__card-preview">'
                . $thumbnailHtml
                . '<span class="my-pages__card-badge my-pages__card-badge--' . $statusClass . '">' . $statusText . '</span>'
                . '</div>'
                . '<div class="my-pages__card-body">'
                . '<h3 class="my-pages__card-title"><a href="' . $pageUrl . '">' . htmlspecialchars($title) . '</a></h3>'
                . '<div class="my-pages__card-meta">'
                . '<span>' . $date . '</span>'
                . ($templateLabel ? '<span class="my-pages__card-template">' . htmlspecialchars($templateLabel) . '</span>' : '')
                . '</div>'
                . '</div>'
                . '<div class="my-pages__card-footer">'
                . '<a href="' . $pageUrl . '" class="my-pages__card-action">'
                . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>'
                . $this->t('Ver') . '</a>'
                . '<a href="' . $canvasUrl . '" class="my-pages__card-action my-pages__card-action--primary">'
                . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'
                . $this->t('Editar Canvas') . '</a>'
                . '<a href="' . $deleteUrl . '" class="my-pages__card-action my-pages__card-action--danger">'
                . '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>'
                . $this->t('Eliminar') . '</a>'
                . '</div>'
                . '</div>';
        }

        return [
            '#markup' => Markup::create('<div class="my-pages">'
                . '<div class="my-pages__header">'
                . '<div><h1 class="my-pages__title">'
                . '<span class="my-pages__title-icon">'
                . '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>'
                . '</span>'
                . $this->t('Mis Páginas')
                . '</h1></div>'
                . '<div class="my-pages__actions">'
                . '<a href="/page-builder/templates" class="my-pages__btn-create">'
                . '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>'
                . $this->t('Nueva página')
                . '</a>'
                . '</div>'
                . '</div>'
                . $filterBar
                . '<div class="my-pages__grid" id="my-pages-grid">' . $cards . '</div>'
                . '<div class="my-pages__no-results" id="my-pages-no-results" hidden>'
                . '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="8" y1="11" x2="14" y2="11"/></svg>'
                . '<p>' . $this->t('No se encontraron páginas con los filtros actuales.') . '</p>'
                . '</div>'
                . '</div>'),
            '#attached' => [
                'library' => ['jaraba_page_builder/my-pages'],
            ],
        ];
    }

}
