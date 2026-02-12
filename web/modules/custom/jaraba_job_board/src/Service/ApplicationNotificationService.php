<?php

declare(strict_types=1);

namespace Drupal\jaraba_job_board\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio de notificaciones para el Job Board.
 *
 * Centraliza el envío de notificaciones de cambio de estado
 * de candidaturas a candidatos y empleadores.
 */
class ApplicationNotificationService
{

    /**
     * Entity type manager.
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * Mail manager.
     */
    protected MailManagerInterface $mailManager;

    /**
     * HTTP client.
     */
    protected ClientInterface $httpClient;

    /**
     * Config factory.
     */
    protected ConfigFactoryInterface $configFactory;

    /**
     * Queue factory.
     */
    protected QueueFactory $queueFactory;

    /**
     * Logger.
     */
    protected LoggerInterface $logger;

    /**
     * Templates de email por estado.
     */
    const EMAIL_TEMPLATES = [
        'applied' => [
            'candidate' => [
                'subject' => 'Tu candidatura ha sido enviada - [job_title]',
                'key' => 'application_submitted',
            ],
            'employer' => [
                'subject' => 'Nueva candidatura recibida - [job_title]',
                'key' => 'new_application',
            ],
        ],
        'screening' => [
            'candidate' => [
                'subject' => 'Tu candidatura está siendo revisada - [job_title]',
                'key' => 'application_screening',
            ],
        ],
        'shortlisted' => [
            'candidate' => [
                'subject' => '¡Enhorabuena! Has sido preseleccionado/a - [job_title]',
                'key' => 'application_shortlisted',
            ],
        ],
        'interviewed' => [
            'candidate' => [
                'subject' => 'Entrevista programada - [job_title]',
                'key' => 'interview_scheduled',
            ],
        ],
        'offered' => [
            'candidate' => [
                'subject' => '¡Has recibido una oferta! - [job_title]',
                'key' => 'offer_received',
            ],
        ],
        'hired' => [
            'candidate' => [
                'subject' => '¡Felicidades por tu nuevo empleo! - [job_title]',
                'key' => 'application_hired',
            ],
            'employer' => [
                'subject' => 'Contratación confirmada - [job_title]',
                'key' => 'hire_confirmed',
            ],
        ],
        'rejected' => [
            'candidate' => [
                'subject' => 'Actualización sobre tu candidatura - [job_title]',
                'key' => 'application_rejected',
            ],
        ],
        'withdrawn' => [
            'employer' => [
                'subject' => 'Candidatura retirada - [job_title]',
                'key' => 'application_withdrawn',
            ],
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        MailManagerInterface $mailManager,
        ClientInterface $httpClient,
        ConfigFactoryInterface $configFactory,
        QueueFactory $queueFactory,
        LoggerInterface $logger
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->mailManager = $mailManager;
        $this->httpClient = $httpClient;
        $this->configFactory = $configFactory;
        $this->queueFactory = $queueFactory;
        $this->logger = $logger;
    }

    /**
     * Notifica cambio de estado de una candidatura.
     *
     * @param object $application
     *   Entidad job_application.
     * @param string $oldStatus
     *   Estado anterior.
     * @param string $newStatus
     *   Nuevo estado.
     */
    public function notifyStatusChange(object $application, string $oldStatus, string $newStatus): void
    {
        // Obtener datos de la aplicación
        $jobId = $application->get('job_id')->target_id ?? NULL;
        $candidateId = $application->get('candidate_id')->target_id ?? NULL;

        if (!$jobId || !$candidateId) {
            return;
        }

        try {
            $job = $this->entityTypeManager->getStorage('job_posting')->load($jobId);
            $candidate = $this->entityTypeManager->getStorage('user')->load($candidateId);

            if (!$job || !$candidate) {
                return;
            }

            $jobTitle = $job->label();
            $employerId = $job->get('employer_id')->target_id ?? NULL;
            $employer = $employerId ? $this->entityTypeManager->getStorage('user')->load($employerId) : NULL;

            // Preparar contexto para templates
            $context = [
                'application' => $application,
                'job' => $job,
                'job_title' => $jobTitle,
                'candidate' => $candidate,
                'candidate_name' => $candidate->getDisplayName(),
                'employer' => $employer,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
            ];

            // Notificar al candidato
            if (isset(self::EMAIL_TEMPLATES[$newStatus]['candidate'])) {
                $this->sendNotification($candidate, 'candidate', $newStatus, $context);
            }

            // Notificar al empleador
            if (isset(self::EMAIL_TEMPLATES[$newStatus]['employer']) && $employer) {
                $this->sendNotification($employer, 'employer', $newStatus, $context);
            }

            // Webhook ActiveCampaign si está configurado
            $this->sendActiveCampaignWebhook($candidate, $newStatus, $context);

            $this->logger->info('Notifications sent for application @id status change: @old -> @new', [
                '@id' => $application->id(),
                '@old' => $oldStatus,
                '@new' => $newStatus,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error sending notifications: @error', ['@error' => $e->getMessage()]);
        }
    }

    /**
     * Envía notificación a un usuario.
     */
    protected function sendNotification(object $user, string $role, string $status, array $context): void
    {
        $template = self::EMAIL_TEMPLATES[$status][$role] ?? NULL;
        if (!$template) {
            return;
        }

        $email = $user->getEmail();
        if (!$email) {
            return;
        }

        // Preparar subject
        $subject = str_replace('[job_title]', $context['job_title'], $template['subject']);

        // Encolar email para envío asíncrono
        $queue = $this->queueFactory->get('application_notification_mail');
        $queue->createItem([
            'to' => $email,
            'key' => $template['key'],
            'subject' => $subject,
            'context' => $context,
            'langcode' => $user->getPreferredLangcode(),
        ]);

        // También enviar notificación in-app
        $this->createInAppNotification($user->id(), $status, $context);
    }

    /**
     * Crea notificación in-app.
     */
    protected function createInAppNotification(int $userId, string $status, array $context): void
    {
        // Queue para procesamiento asíncrono
        $queue = $this->queueFactory->get('application_notification_inapp');
        $queue->createItem([
            'user_id' => $userId,
            'type' => 'application_' . $status,
            'title' => $this->getInAppTitle($status, $context),
            'message' => $this->getInAppMessage($status, $context),
            'link' => '/my-applications/' . $context['application']->id(),
            'created' => time(),
        ]);
    }

    /**
     * Obtiene título para notificación in-app.
     */
    protected function getInAppTitle(string $status, array $context): string
    {
        $titles = [
            'applied' => 'Candidatura enviada',
            'screening' => 'Candidatura en revisión',
            'shortlisted' => '¡Preseleccionado!',
            'interviewed' => 'Entrevista programada',
            'offered' => '¡Oferta recibida!',
            'hired' => '¡Contratado!',
            'rejected' => 'Candidatura actualizada',
        ];

        return $titles[$status] ?? 'Actualización de candidatura';
    }

    /**
     * Obtiene mensaje para notificación in-app.
     */
    protected function getInAppMessage(string $status, array $context): string
    {
        $jobTitle = $context['job_title'] ?? 'la oferta';

        $messages = [
            'applied' => "Tu candidatura para $jobTitle ha sido enviada.",
            'screening' => "Tu candidatura para $jobTitle está siendo revisada.",
            'shortlisted' => "¡Has sido preseleccionado para $jobTitle!",
            'interviewed' => "Tienes una entrevista programada para $jobTitle.",
            'offered' => "¡Has recibido una oferta para $jobTitle!",
            'hired' => "¡Felicidades! Has sido contratado para $jobTitle.",
            'rejected' => "Tu candidatura para $jobTitle ha sido actualizada.",
        ];

        return $messages[$status] ?? "Tu candidatura ha sido actualizada.";
    }

    /**
     * Envía webhook a ActiveCampaign.
     */
    protected function sendActiveCampaignWebhook(object $user, string $status, array $context): void
    {
        $config = $this->configFactory->get('jaraba_job_board.settings');
        $webhookUrl = $config->get('activecampaign_webhook_url');

        if (!$webhookUrl) {
            return;
        }

        try {
            $this->httpClient->post($webhookUrl, [
                'json' => [
                    'email' => $user->getEmail(),
                    'tag' => 'application_' . $status,
                    'job_id' => $context['job']->id() ?? NULL,
                    'job_title' => $context['job_title'] ?? '',
                    'timestamp' => time(),
                ],
                'timeout' => 5,
            ]);
        } catch (\Exception $e) {
            // Log pero no bloquear
            $this->logger->warning('ActiveCampaign webhook failed: @error', ['@error' => $e->getMessage()]);
        }
    }

    /**
     * Notifica alta coincidencia (match > 80%).
     */
    public function notifyHighMatch(object $job, object $candidate, float $matchScore): void
    {
        $employerId = $job->get('employer_id')->target_id ?? NULL;
        if (!$employerId) {
            return;
        }

        $employer = $this->entityTypeManager->getStorage('user')->load($employerId);
        if (!$employer) {
            return;
        }

        $queue = $this->queueFactory->get('application_notification_mail');
        $queue->createItem([
            'to' => $employer->getEmail(),
            'key' => 'high_match_alert',
            'subject' => 'Candidato con alta coincidencia - ' . $job->label(),
            'context' => [
                'job' => $job,
                'job_title' => $job->label(),
                'candidate' => $candidate,
                'candidate_name' => $candidate->getDisplayName(),
                'match_score' => round($matchScore),
            ],
            'langcode' => $employer->getPreferredLangcode(),
        ]);
    }

}
