<?php

declare(strict_types=1);

namespace Drupal\jaraba_tenant_knowledge\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\jaraba_tenant_knowledge\Service\KnowledgeRevisionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Controlador para comparación visual de revisiones de conocimiento.
 *
 * PROPÓSITO:
 * Proporciona UI para visualizar diferencias entre revisiones de
 * TenantFaq y TenantPolicy, y permite revertir a versiones anteriores.
 *
 * LÓGICA:
 * - Genérico: un solo controlador para FAQs y políticas
 * - El tipo de entidad se resuelve por la ruta
 * - Reusa los estilos existentes de _revision-diff.scss
 *
 * DIRECTRICES:
 * - Patrón idéntico a RevisionDiffController de jaraba_page_builder
 * - Template limpio con partials del tema
 * - Traducciones con t() en todas las cadenas
 *
 * @see \Drupal\jaraba_page_builder\Controller\RevisionDiffController
 */
class KnowledgeRevisionController extends ControllerBase {

  /**
   * Servicio de revisiones.
   */
  protected KnowledgeRevisionService $revisionService;

  /**
   * Constructor.
   */
  public function __construct(
    KnowledgeRevisionService $revision_service,
    MessengerInterface $messenger,
  ) {
    $this->revisionService = $revision_service;
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_tenant_knowledge.revision_service'),
      $container->get('messenger'),
    );
  }

  // ===========================================================================
  // FAQ REVISIONS
  // ===========================================================================

  /**
   * Lista de revisiones de una FAQ: /knowledge/faqs/{id}/revisions.
   */
  public function faqRevisions(string $tenant_faq): array {
    $entityId = (int) $tenant_faq;
    $entity = $this->entityTypeManager()->getStorage('tenant_faq')->load($entityId);

    if (!$entity) {
      throw new NotFoundHttpException();
    }

    $revisions = $this->revisionService->listRevisions('tenant_faq', $entityId);

    return [
      '#theme' => 'knowledge_revision_list',
      '#entity' => $entity,
      '#entity_type' => 'tenant_faq',
      '#revisions' => $revisions,
      '#back_route' => 'jaraba_tenant_knowledge.faqs',
      '#compare_route' => 'jaraba_tenant_knowledge.faq.revisions.compare',
      '#entity_label' => $entity->get('question')->value,
      '#attached' => [
        'library' => ['jaraba_tenant_knowledge/revision-diff'],
      ],
    ];
  }

  /**
   * Título de la página de revisiones de FAQ.
   */
  public function faqRevisionsTitle(string $tenant_faq): TranslatableMarkup|string {
    $entity = $this->entityTypeManager()->getStorage('tenant_faq')->load($tenant_faq);
    return $this->t('Historial: @label', ['@label' => $entity ? $entity->get('question')->value : $tenant_faq]);
  }

  /**
   * Comparación de revisiones de FAQ.
   */
  public function faqCompare(string $tenant_faq, int $older, int $newer): array {
    return $this->doCompare('tenant_faq', (int) $tenant_faq, $older, $newer, 'jaraba_tenant_knowledge.faq.revisions', 'jaraba_tenant_knowledge.faq.revisions.revert');
  }

  /**
   * Título de comparación de FAQ.
   */
  public function faqCompareTitle(string $tenant_faq): TranslatableMarkup|string {
    $entity = $this->entityTypeManager()->getStorage('tenant_faq')->load($tenant_faq);
    return $this->t('Comparar: @label', ['@label' => $entity ? $entity->get('question')->value : $tenant_faq]);
  }

  /**
   * Revertir revisión de FAQ.
   */
  public function faqRevert(string $tenant_faq, int $revision, Request $request): RedirectResponse {
    return $this->doRevert('tenant_faq', (int) $tenant_faq, $revision, 'jaraba_tenant_knowledge.faq.revisions');
  }

  // ===========================================================================
  // POLICY REVISIONS
  // ===========================================================================

  /**
   * Lista de revisiones de una política: /knowledge/policies/{id}/revisions.
   */
  public function policyRevisions(string $tenant_policy): array {
    $entityId = (int) $tenant_policy;
    $entity = $this->entityTypeManager()->getStorage('tenant_policy')->load($entityId);

    if (!$entity) {
      throw new NotFoundHttpException();
    }

    $revisions = $this->revisionService->listRevisions('tenant_policy', $entityId);

    return [
      '#theme' => 'knowledge_revision_list',
      '#entity' => $entity,
      '#entity_type' => 'tenant_policy',
      '#revisions' => $revisions,
      '#back_route' => 'jaraba_tenant_knowledge.policies',
      '#compare_route' => 'jaraba_tenant_knowledge.policy.revisions.compare',
      '#entity_label' => $entity->get('title')->value,
      '#attached' => [
        'library' => ['jaraba_tenant_knowledge/revision-diff'],
      ],
    ];
  }

  /**
   * Título de la página de revisiones de política.
   */
  public function policyRevisionsTitle(string $tenant_policy): TranslatableMarkup|string {
    $entity = $this->entityTypeManager()->getStorage('tenant_policy')->load($tenant_policy);
    return $this->t('Historial: @label', ['@label' => $entity ? $entity->get('title')->value : $tenant_policy]);
  }

  /**
   * Comparación de revisiones de política.
   */
  public function policyCompare(string $tenant_policy, int $older, int $newer): array {
    return $this->doCompare('tenant_policy', (int) $tenant_policy, $older, $newer, 'jaraba_tenant_knowledge.policy.revisions', 'jaraba_tenant_knowledge.policy.revisions.revert');
  }

  /**
   * Título de comparación de política.
   */
  public function policyCompareTitle(string $tenant_policy): TranslatableMarkup|string {
    $entity = $this->entityTypeManager()->getStorage('tenant_policy')->load($tenant_policy);
    return $this->t('Comparar: @label', ['@label' => $entity ? $entity->get('title')->value : $tenant_policy]);
  }

  /**
   * Revertir revisión de política.
   */
  public function policyRevert(string $tenant_policy, int $revision, Request $request): RedirectResponse {
    return $this->doRevert('tenant_policy', (int) $tenant_policy, $revision, 'jaraba_tenant_knowledge.policy.revisions');
  }

  // ===========================================================================
  // MÉTODOS GENÉRICOS COMPARTIDOS
  // ===========================================================================

  /**
   * Lógica genérica de comparación de revisiones.
   */
  protected function doCompare(string $entityType, int $entityId, int $olderId, int $newerId, string $listRoute, string $revertRoute): array {
    $entity = $this->entityTypeManager()->getStorage($entityType)->load($entityId);
    if (!$entity) {
      throw new NotFoundHttpException();
    }

    $older = $this->revisionService->loadRevision($entityType, $entityId, $olderId);
    $newer = $this->revisionService->loadRevision($entityType, $entityId, $newerId);

    if (!$older || !$newer) {
      throw new NotFoundHttpException($this->t('Una o ambas revisiones no existen.'));
    }

    $diff = $this->revisionService->compareRevisions($entityType, $older, $newer);

    $paramKey = $entityType === 'tenant_faq' ? 'tenant_faq' : 'tenant_policy';

    return [
      '#theme' => 'knowledge_revision_diff',
      '#entity' => $entity,
      '#entity_type' => $entityType,
      '#older' => $older,
      '#newer' => $newer,
      '#older_id' => $olderId,
      '#newer_id' => $newerId,
      '#diff' => $diff,
      '#has_changes' => !empty($diff),
      '#list_route' => $listRoute,
      '#list_route_params' => [$paramKey => $entityId],
      '#revert_route' => $revertRoute,
      '#revert_route_params' => [$paramKey => $entityId, 'revision' => $olderId],
      '#entity_label' => $entity->label(),
      '#attached' => [
        'library' => ['jaraba_tenant_knowledge/revision-diff'],
      ],
    ];
  }

  /**
   * Lógica genérica de revertir revisión.
   */
  protected function doRevert(string $entityType, int $entityId, int $revisionId, string $listRoute): RedirectResponse {
    $target = $this->revisionService->loadRevision($entityType, $entityId, $revisionId);

    if (!$target) {
      throw new NotFoundHttpException($this->t('La revisión no existe.'));
    }

    try {
      $storage = $this->entityTypeManager()->getStorage($entityType);
      $current = $storage->load($entityId);

      if (!$current) {
        throw new NotFoundHttpException();
      }

      // Copiar campos revisionables del target al current.
      $fieldsMap = [
        'tenant_faq' => ['question', 'answer', 'category', 'priority', 'is_published'],
        'tenant_policy' => ['title', 'content', 'summary', 'policy_type', 'version_notes', 'is_published'],
      ];

      foreach ($fieldsMap[$entityType] ?? [] as $fieldName) {
        if ($target->hasField($fieldName) && $current->hasField($fieldName)) {
          $current->set($fieldName, $target->get($fieldName)->getValue());
        }
      }

      // Crear nueva revisión con mensaje de log.
      $current->setNewRevision(TRUE);
      if ($current instanceof RevisionLogInterface) {
        $revisionTimestamp = 0;
        if ($target instanceof RevisionLogInterface) {
          $revisionTimestamp = $target->getRevisionCreationTime();
        }
        $current->setRevisionLogMessage((string) $this->t('Revertido a revisión @rev (@date)', [
          '@rev' => $revisionId,
          '@date' => $revisionTimestamp ? date('d/m/Y H:i', $revisionTimestamp) : '?',
        ]));
        $current->setRevisionCreationTime(\Drupal::time()->getRequestTime());
        $current->setRevisionUserId((int) $this->currentUser()->id());
      }

      $current->save();

      $this->messenger()->addStatus($this->t('Revertido a la revisión @rev.', ['@rev' => $revisionId]));

      $this->getLogger('jaraba_tenant_knowledge')->notice('@type @id revertido a revisión @rev por usuario @uid', [
        '@type' => $entityType,
        '@id' => $entityId,
        '@rev' => $revisionId,
        '@uid' => $this->currentUser()->id(),
      ]);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error al revertir: @msg', ['@msg' => $e->getMessage()]));
    }

    $paramKey = $entityType === 'tenant_faq' ? 'tenant_faq' : 'tenant_policy';
    $url = Url::fromRoute($listRoute, [$paramKey => $entityId]);

    return new RedirectResponse($url->toString());
  }

}
