<?php

declare(strict_types=1);

namespace Drupal\jaraba_page_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\jaraba_page_builder\Entity\PageContent;
use Drupal\jaraba_page_builder\Service\RevisionDiffService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador para comparación visual de revisiones.
 *
 * PROPÓSITO:
 * Proporciona UI para visualizar diferencias entre revisiones de PageContent
 * y permite revertir a versiones anteriores con un clic.
 *
 * RUTAS:
 * - /page/{page_content}/revisions: Lista de revisiones
 * - /page/{page_content}/revisions/compare/{older}/{newer}: Comparación visual
 * - /page/{page_content}/revisions/{revision}/revert: Revertir revisión
 *
 * @see docs/planificacion/20260202-Auditoria_Plan_Elevacion_Clase_Mundial_v1.md (Gap G)
 */
class RevisionDiffController extends ControllerBase
{

    /**
     * El servicio de diff de revisiones.
     *
     * @var \Drupal\jaraba_page_builder\Service\RevisionDiffService
     */
    protected RevisionDiffService $diffService;

    /**
     * Constructor.
     *
     * @param \Drupal\jaraba_page_builder\Service\RevisionDiffService $diffService
     *   Servicio de diff de revisiones.
     * @param \Drupal\Core\Messenger\MessengerInterface $messenger
     *   Servicio de mensajes.
     */
    public function __construct(
        RevisionDiffService $diffService,
        MessengerInterface $messenger,
    ) {
        $this->diffService = $diffService;
        $this->setMessenger($messenger);
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_page_builder.revision_diff'),
            $container->get('messenger'),
        );
    }

    /**
     * Lista todas las revisiones de una página.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageContent $page_content
     *   La entidad de página.
     *
     * @return array
     *   Render array con la lista de revisiones.
     */
    public function listRevisions(PageContent $page_content): array
    {
        $revisions = $this->diffService->listRevisions((int) $page_content->id());

        return [
            '#theme' => 'revision_list',
            '#page' => $page_content,
            '#revisions' => $revisions,
            '#attached' => [
                'library' => ['jaraba_page_builder/revision-diff'],
            ],
        ];
    }

    /**
     * Título de la página de lista de revisiones.
     */
    public function listRevisionsTitle(PageContent $page_content): TranslatableMarkup|string
    {
        return $this->t('Historial de revisiones: @title', [
            '@title' => $page_content->label(),
        ]);
    }

    /**
     * Muestra comparación visual entre dos revisiones.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageContent $page_content
     *   La entidad de página.
     * @param int $older
     *   ID de la revisión más antigua.
     * @param int $newer
     *   ID de la revisión más reciente.
     *
     * @return array
     *   Render array con la comparación.
     */
    public function compare(PageContent $page_content, int $older, int $newer): array
    {
        $entityId = (int) $page_content->id();

        // Cargar revisiones.
        $olderRevision = $this->diffService->loadRevision($entityId, $older);
        $newerRevision = $this->diffService->loadRevision($entityId, $newer);

        if (!$olderRevision || !$newerRevision) {
            throw new NotFoundHttpException('Una o ambas revisiones no existen.');
        }

        // Obtener diferencias.
        $diff = $this->diffService->compareRevisions($olderRevision, $newerRevision);

        return [
            '#theme' => 'revision_diff',
            '#page' => $page_content,
            '#older' => $olderRevision,
            '#newer' => $newerRevision,
            '#older_id' => $older,
            '#newer_id' => $newer,
            '#diff' => $diff,
            '#has_changes' => !empty($diff),
            '#attached' => [
                'library' => ['jaraba_page_builder/revision-diff'],
            ],
        ];
    }

    /**
     * Título de la página de comparación.
     */
    public function compareTitle(PageContent $page_content, int $older, int $newer): TranslatableMarkup|string
    {
        return $this->t('Comparar revisiones: @title', [
            '@title' => $page_content->label(),
        ]);
    }

    /**
     * Revierte una página a una revisión anterior.
     *
     * @param \Drupal\jaraba_page_builder\Entity\PageContent $page_content
     *   La entidad de página.
     * @param int $revision
     *   ID de la revisión a la que revertir.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La solicitud HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *   Redirección a la lista de revisiones.
     */
    public function revert(PageContent $page_content, int $revision, Request $request): RedirectResponse
    {
        $entityId = (int) $page_content->id();
        $targetRevision = $this->diffService->loadRevision($entityId, $revision);

        if (!$targetRevision) {
            throw new NotFoundHttpException('La revisión no existe.');
        }

        try {
            $storage = $this->entityTypeManager()->getStorage('page_content');

            // Cargar la entidad actual.
            /** @var \Drupal\jaraba_page_builder\Entity\PageContent $current */
            $current = $storage->load($entityId);

            // Copiar valores de la revisión objetivo.
            $fieldsToRevert = [
                'title',
                'slug',
                'sections',
                'meta_title',
                'meta_description',
                'status',
            ];

            foreach ($fieldsToRevert as $fieldName) {
                if ($targetRevision->hasField($fieldName) && $current->hasField($fieldName)) {
                    $current->set($fieldName, $targetRevision->get($fieldName)->getValue());
                }
            }

            // Crear nueva revisión con mensaje de log.
            $current->setNewRevision(TRUE);
            $current->setRevisionLogMessage($this->t('Revertido a revisión @rev (@date)', [
                '@rev' => $revision,
                '@date' => date('d/m/Y H:i', $targetRevision->getRevisionCreationTime()),
            ]));
            $current->setRevisionCreationTime(\Drupal::time()->getRequestTime());
            $current->setRevisionUserId($this->currentUser()->id());

            $current->save();

            $this->messenger->addStatus($this->t('La página ha sido revertida a la revisión @rev.', [
                '@rev' => $revision,
            ]));

            $this->getLogger('jaraba_page_builder')->notice('Página @id revertida a revisión @rev por usuario @uid', [
                '@id' => $entityId,
                '@rev' => $revision,
                '@uid' => $this->currentUser()->id(),
            ]);
        } catch (\Exception $e) {
            $this->messenger->addError($this->t('Error al revertir: @message', [
                '@message' => $e->getMessage(),
            ]));

            $this->getLogger('jaraba_page_builder')->error('Error revirtiendo página @id a revisión @rev: @message', [
                '@id' => $entityId,
                '@rev' => $revision,
                '@message' => $e->getMessage(),
            ]);
        }

        // Redirigir a la lista de revisiones.
        $url = Url::fromRoute('jaraba_page_builder.revision_list', [
            'page_content' => $entityId,
        ]);

        return new RedirectResponse($url->toString());
    }

    /**
     * Título de la página de revertir.
     */
    public function revertTitle(PageContent $page_content, int $revision): TranslatableMarkup|string
    {
        return $this->t('Revertir a revisión @rev', ['@rev' => $revision]);
    }

}
