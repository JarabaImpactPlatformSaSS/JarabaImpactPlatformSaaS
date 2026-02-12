<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\jaraba_lms\Entity\EnrollmentInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio puente entre jaraba_lms y jaraba_credentials.
 *
 * PROPÓSITO:
 * Conecta el sistema de matrículas (LMS) con el sistema de credenciales
 * verificables, emitiendo automáticamente certificados firmados Ed25519
 * cuando un usuario completa un curso.
 *
 * FLUJO:
 * 1. hook_lms_enrollment_update() detecta status='completed'
 * 2. Este servicio busca CredentialTemplate asociado al curso
 * 3. Usa CredentialIssuer para emitir IssuedCredential firmada
 * 4. Encola email de notificación
 *
 * @see ECA-TRAIN-003
 */
class LmsCredentialsIntegration
{

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Credential issuer service.
     */
    protected CredentialIssuer $credentialIssuer;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Queue factory.
     */
    protected QueueFactory $queueFactory;

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        CredentialIssuer $credentialIssuer,
        LoggerChannelFactoryInterface $loggerFactory,
        QueueFactory $queueFactory
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->credentialIssuer = $credentialIssuer;
        $this->logger = $loggerFactory->get('jaraba_credentials');
        $this->queueFactory = $queueFactory;
    }

    /**
     * Emite credencial cuando se completa un curso.
     *
     * Llamado desde hook_lms_enrollment_update() cuando detecta
     * que un enrollment pasa a status='completed'.
     *
     * @param \Drupal\jaraba_lms\Entity\EnrollmentInterface $enrollment
     *   El enrollment completado.
     *
     * @return \Drupal\jaraba_credentials\Entity\IssuedCredential|null
     *   La credencial emitida o NULL si no hay template asociado.
     */
    public function issueForCourseCompletion(EnrollmentInterface $enrollment): ?object
    {
        $courseId = $enrollment->getCourseId();
        $userId = $enrollment->getUserId();

        $this->logger->info('Processing course completion for credential issuance: course=@course, user=@user', [
            '@course' => $courseId,
            '@user' => $userId,
        ]);

        // Buscar CredentialTemplate asociado al curso
        $template = $this->findTemplateForCourse($courseId);

        if (!$template) {
            $this->logger->notice('No CredentialTemplate found for course @course', [
                '@course' => $courseId,
            ]);
            return NULL;
        }

        // Verificar que no existe ya una credencial emitida para este usuario/template
        if ($this->credentialAlreadyIssued($template->id(), $userId)) {
            $this->logger->notice('Credential already issued for user @user on template @template', [
                '@user' => $userId,
                '@template' => $template->id(),
            ]);
            return NULL;
        }

        // Preparar metadatos del enrollment
        $metadata = [
            'trigger' => 'lms',
            'enrollment_id' => $enrollment->id(),
            'course_id' => $courseId,
            'completed_at' => $enrollment->get('completed_at')->value ?? date('c'),
            'progress_percent' => $enrollment->get('progress_percent')->value ?? 100,
            'source' => 'lms_course_completion',
        ];

        // Emitir credencial
        try {
            $credential = $this->credentialIssuer->issueCredential(
                $template,
                $userId,
                $metadata
            );

            if ($credential) {
                $this->logger->info('Credential issued: @uuid for user @user (course @course)', [
                    '@uuid' => $credential->uuid(),
                    '@user' => $userId,
                    '@course' => $courseId,
                ]);

                // Encolar email de notificación
                $this->queueCredentialNotification($credential, $userId);
            }

            return $credential;
        } catch (\Exception $e) {
            $this->logger->error('Error issuing credential for course completion: @error', [
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Busca un CredentialTemplate asociado a un curso.
     *
     * @param int $courseId
     *   ID del curso LMS.
     *
     * @return \Drupal\jaraba_credentials\Entity\CredentialTemplate|null
     *   El template encontrado o NULL.
     */
    public function findTemplateForCourse(int $courseId): ?object
    {
        $templates = $this->entityTypeManager->getStorage('credential_template')
            ->loadByProperties([
                'lms_course_id' => $courseId,
                'status' => TRUE,
            ]);

        if (empty($templates)) {
            return NULL;
        }

        // Retornar el primero (debería haber solo uno por curso)
        return reset($templates);
    }

    /**
     * Verifica si ya existe una credencial emitida.
     *
     * @param int $templateId
     *   ID del template.
     * @param int $userId
     *   ID del usuario.
     *
     * @return bool
     *   TRUE si ya existe.
     */
    protected function credentialAlreadyIssued(int $templateId, int $userId): bool
    {
        $credentials = $this->entityTypeManager->getStorage('issued_credential')
            ->loadByProperties([
                'template_id' => $templateId,
                'recipient_id' => $userId,
            ]);

        return !empty($credentials);
    }

    /**
     * Encola notificación de credencial emitida.
     *
     * @param object $credential
     *   La credencial emitida.
     * @param int $userId
     *   ID del usuario.
     */
    protected function queueCredentialNotification(object $credential, int $userId): void
    {
        $queue = $this->queueFactory->get('jaraba_credentials_notifications');
        $queue->createItem([
            'type' => 'credential_issued',
            'credential_uuid' => $credential->uuid(),
            'user_id' => $userId,
            'created' => time(),
        ]);

        $this->logger->debug('Queued notification for credential @uuid', [
            '@uuid' => $credential->uuid(),
        ]);
    }

    /**
     * Procesa resultado de contenido interactivo y emite credencial si aprueba.
     *
     * Llamado desde hook_jaraba_interactive_result_insert() cuando
     * se completa un contenido evaluativo.
     *
     * @param \Drupal\jaraba_interactive\Entity\InteractiveResultInterface $result
     *   El resultado del contenido interactivo.
     *
     * @return \Drupal\jaraba_credentials\Entity\IssuedCredential|null
     *   La credencial emitida o NULL si no aplica.
     */
    public function issueForInteractiveResult(object $result): ?object
    {
        $contentId = $result->get('content_id')->target_id ?? NULL;
        $userId = $result->get('uid')->target_id ?? NULL;

        if (!$contentId || !$userId) {
            $this->logger->warning('InteractiveResult missing content_id or uid');
            return NULL;
        }

        $score = (float) ($result->get('score')->value ?? 0);
        $passed = (bool) ($result->get('passed')->value ?? FALSE);

        $this->logger->info('Processing interactive result for credential: content=@content, user=@user, score=@score, passed=@passed', [
            '@content' => $contentId,
            '@user' => $userId,
            '@score' => $score,
            '@passed' => $passed ? 'yes' : 'no',
        ]);

        // Solo emitir si aprobó.
        if (!$passed) {
            return NULL;
        }

        // Buscar CredentialTemplate vinculado a este contenido interactivo.
        $template = $this->findTemplateForInteractiveContent($contentId);
        if (!$template) {
            $this->logger->debug('No CredentialTemplate linked to interactive_content @id', [
                '@id' => $contentId,
            ]);
            return NULL;
        }

        // Verificar nota mínima del template si está configurada.
        $templatePassingScore = $template->get('passing_score')->value ?? NULL;
        if ($templatePassingScore !== NULL && $score < $templatePassingScore) {
            $this->logger->notice('Score @score below template threshold @threshold for content @id', [
                '@score' => $score,
                '@threshold' => $templatePassingScore,
                '@id' => $contentId,
            ]);
            return NULL;
        }

        // Verificar no-duplicado.
        if ($this->credentialAlreadyIssued((int) $template->id(), $userId)) {
            $this->logger->notice('Credential already issued for user @user on template @template', [
                '@user' => $userId,
                '@template' => $template->id(),
            ]);
            return NULL;
        }

        // Preparar metadatos.
        $metadata = [
            'trigger' => 'exam',
            'interactive_result_id' => $result->id(),
            'interactive_content_id' => $contentId,
            'score' => $score,
            'completed_at' => $result->get('created')->value ?? date('c'),
            'source' => 'interactive_exam_passed',
        ];

        // Emitir credencial.
        try {
            $credential = $this->credentialIssuer->issueCredential(
                $template,
                $userId,
                $metadata
            );

            if ($credential) {
                $this->logger->info('Credential issued from exam: @uuid for user @user (content @content)', [
                    '@uuid' => $credential->uuid(),
                    '@user' => $userId,
                    '@content' => $contentId,
                ]);

                // Encolar notificación.
                $this->queueCredentialNotification($credential, $userId);
            }

            return $credential;
        } catch (\Exception $e) {
            $this->logger->error('Error issuing credential for interactive result: @error', [
                '@error' => $e->getMessage(),
            ]);
            return NULL;
        }
    }

    /**
     * Busca un CredentialTemplate vinculado a contenido interactivo.
     *
     * @param int $contentId
     *   ID del contenido interactivo.
     *
     * @return object|null
     *   El template encontrado o NULL.
     */
    public function findTemplateForInteractiveContent(int $contentId): ?object
    {
        $templates = $this->entityTypeManager->getStorage('credential_template')
            ->loadByProperties([
                'interactive_activity_id' => $contentId,
                'is_active' => TRUE,
            ]);

        if (empty($templates)) {
            return NULL;
        }

        return reset($templates);
    }

}
