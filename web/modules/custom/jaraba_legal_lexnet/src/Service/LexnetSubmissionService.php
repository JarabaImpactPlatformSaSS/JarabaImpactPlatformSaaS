<?php

declare(strict_types=1);

namespace Drupal\jaraba_legal_lexnet\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de presentaciones electronicas a LexNET.
 *
 * Estructura: Gestiona el envio de escritos judiciales via LexNET.
 * Logica: submit() envia un escrito al juzgado via API CGPJ.
 *   checkStatus() verifica el estado de una presentacion.
 *   attachDocuments() adjunta documentos a una presentacion.
 */
class LexnetSubmissionService {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LexnetApiClient $apiClient,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly LoggerInterface $logger,
  ) {}

  /**
   * Envia una presentacion a LexNET.
   */
  public function submit(int $submissionId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('lexnet_submission');
      $submission = $storage->load($submissionId);
      if (!$submission) {
        return ['error' => 'Submission not found.'];
      }

      if ($submission->get('status')->value !== 'draft') {
        return ['error' => 'Only draft submissions can be submitted.'];
      }

      $submission->set('status', 'submitting');
      $submission->save();

      $payload = [
        'json' => [
          'type' => $submission->get('submission_type')->value,
          'court' => $submission->get('court')->value,
          'procedure_number' => $submission->get('procedure_number')->value,
          'subject' => $submission->get('subject')->value,
          'documents' => $submission->get('document_ids')->getValue()[0] ?? [],
        ],
      ];

      $response = $this->apiClient->request('POST', 'submissions', $payload);

      if (isset($response['error'])) {
        $submission->set('status', 'error');
        $submission->set('error_message', $response['error']);
        $submission->save();
        return $response;
      }

      $submission->set('status', 'submitted');
      $submission->set('submitted_at', date('Y-m-d\TH:i:s'));
      $submission->set('confirmation_id', $response['confirmation_id'] ?? $response['id'] ?? '');
      $submission->set('raw_response', json_encode($response));
      $submission->save();

      // Log activity to case if linked.
      $this->logCaseActivity($submission, 'lexnet_submission');

      return [
        'id' => $submissionId,
        'status' => 'submitted',
        'confirmation_id' => $submission->get('confirmation_id')->value,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Submit error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Verifica el estado de una presentacion en LexNET.
   */
  public function checkStatus(int $submissionId): array {
    try {
      $storage = $this->entityTypeManager->getStorage('lexnet_submission');
      $submission = $storage->load($submissionId);
      if (!$submission) {
        return ['error' => 'Submission not found.'];
      }

      $confirmationId = $submission->get('confirmation_id')->value;
      if (empty($confirmationId)) {
        return ['id' => $submissionId, 'status' => $submission->get('status')->value];
      }

      $response = $this->apiClient->request('GET', "submissions/{$confirmationId}/status");

      if (isset($response['error'])) {
        return $response;
      }

      $remoteStatus = $response['status'] ?? '';
      $statusMap = [
        'confirmed' => 'confirmed',
        'rejected' => 'rejected',
        'processing' => 'submitted',
      ];

      $newStatus = $statusMap[$remoteStatus] ?? $submission->get('status')->value;
      if ($newStatus !== $submission->get('status')->value) {
        $submission->set('status', $newStatus);
        $submission->set('raw_response', json_encode($response));
        if ($newStatus === 'rejected') {
          $submission->set('error_message', $response['reason'] ?? '');
        }
        $submission->save();
      }

      return $this->serializeSubmission($submission);
    }
    catch (\Exception $e) {
      $this->logger->error('Check status error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Adjunta documentos a una presentacion.
   */
  public function attachDocuments(int $submissionId, array $documents): array {
    try {
      $storage = $this->entityTypeManager->getStorage('lexnet_submission');
      $submission = $storage->load($submissionId);
      if (!$submission) {
        return ['error' => 'Submission not found.'];
      }

      $existing = $submission->get('document_ids')->getValue()[0] ?? [];
      $merged = array_merge($existing, $documents);
      $submission->set('document_ids', [$merged]);
      $submission->save();

      return [
        'id' => $submissionId,
        'document_count' => count($merged),
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Attach documents error: @msg', ['@msg' => $e->getMessage()]);
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Lista presentaciones con filtros.
   */
  public function listSubmissions(array $filters = [], int $limit = 25, int $offset = 0): array {
    try {
      $storage = $this->entityTypeManager->getStorage('lexnet_submission');
      $query = $storage->getQuery()->accessCheck(TRUE);

      if (!empty($filters['status'])) {
        $query->condition('status', $filters['status']);
      }
      if (!empty($filters['case_id'])) {
        $query->condition('case_id', $filters['case_id']);
      }

      $total = (clone $query)->count()->execute();
      $ids = $query->sort('created', 'DESC')->range($offset, $limit)->execute();

      return [
        'items' => array_map(fn($s) => $this->serializeSubmission($s), $storage->loadMultiple($ids)),
        'total' => (int) $total,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('List submissions error: @msg', ['@msg' => $e->getMessage()]);
      return ['items' => [], 'total' => 0];
    }
  }

  /**
   * Serializa una presentacion.
   */
  public function serializeSubmission($submission): array {
    return [
      'id' => (int) $submission->id(),
      'uuid' => $submission->uuid(),
      'submission_type' => $submission->get('submission_type')->value ?? '',
      'court' => $submission->get('court')->value ?? '',
      'procedure_number' => $submission->get('procedure_number')->value ?? '',
      'subject' => $submission->get('subject')->value ?? '',
      'status' => $submission->get('status')->value ?? 'draft',
      'submitted_at' => $submission->get('submitted_at')->value ?? NULL,
      'confirmation_id' => $submission->get('confirmation_id')->value ?? NULL,
      'case_id' => $submission->get('case_id')->target_id ? (int) $submission->get('case_id')->target_id : NULL,
      'created' => $submission->get('created')->value ?? '',
    ];
  }

  /**
   * Registra actividad en el timeline del expediente.
   */
  protected function logCaseActivity($submission, string $activityType): void {
    $caseId = $submission->get('case_id')->target_id ?? NULL;
    if (!$caseId) {
      return;
    }

    try {
      if (\Drupal::hasService('jaraba_legal_cases.activity_logger')) {
        $logger = \Drupal::service('jaraba_legal_cases.activity_logger');
        if (method_exists($logger, 'log')) {
          $logger->log($caseId, $activityType, [
            'submission_id' => $submission->id(),
            'subject' => $submission->get('subject')->value,
            'court' => $submission->get('court')->value,
          ]);
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error('Case activity log error: @msg', ['@msg' => $e->getMessage()]);
    }
  }

}
