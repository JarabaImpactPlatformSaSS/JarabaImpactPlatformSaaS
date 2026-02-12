<?php

declare(strict_types=1);

namespace Drupal\jaraba_email\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Servicio para gestionar y enviar campañas de email.
 *
 * PROPÓSITO:
 * Orquesta el envío de campañas de email a los suscriptores de las
 * listas asignadas. Gestiona todo el flujo: validación, envío,
 * personalización y actualización de estadísticas.
 *
 * CARACTERÍSTICAS:
 * - Envío batch a todos los suscriptores de las listas
 * - Personalización de contenido con merge tags
 * - Programación de campañas para envío futuro
 * - Envío de tests antes del envío masivo
 * - Tracking de estadísticas de entrega
 *
 * MERGE TAGS SOPORTADOS:
 * - {{first_name}}: Nombre del suscriptor
 * - {{last_name}}: Apellido del suscriptor
 * - {{email}}: Email del suscriptor
 * - {{full_name}}: Nombre completo
 * - {{unsubscribe_url}}: URL de baja
 *
 * ESPECIFICACIÓN: Doc 139 - Marketing_AI_Stack_Native
 */
class CampaignService
{

    /**
     * El gestor de tipos de entidad.
     *
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface
     */
    protected EntityTypeManagerInterface $entityTypeManager;

    /**
     * El gestor de email de Drupal.
     *
     * @var \Drupal\Core\Mail\MailManagerInterface
     */
    protected MailManagerInterface $mailManager;

    /**
     * El logger para registrar eventos.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Construye un CampaignService.
     *
     * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
     *   El gestor de tipos de entidad.
     * @param \Drupal\Core\Mail\MailManagerInterface $mailManager
     *   El gestor de envío de emails.
     * @param \Psr\Log\LoggerInterface $logger
     *   El servicio de logging.
     */
    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        MailManagerInterface $mailManager,
        LoggerInterface $logger,
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->mailManager = $mailManager;
        $this->logger = $logger;
    }

    /**
     * Envía una campaña a todos los suscriptores de sus listas.
     *
     * Flujo de ejecución:
     * 1. Valida que la campaña exista y pueda enviarse
     * 2. Cambia estado a 'sending'
     * 3. Obtiene suscriptores activos de todas las listas
     * 4. Personaliza y envía email a cada suscriptor
     * 5. Actualiza estadísticas de la campaña
     *
     * @param int $campaignId
     *   El ID de la campaña.
     *
     * @return array
     *   Resultado con:
     *   - 'success': bool
     *   - 'total_recipients': Número total de destinatarios
     *   - 'total_sent': Emails enviados exitosamente
     *   - 'errors': Array de emails con error
     */
    public function sendCampaign(int $campaignId): array
    {
        $storage = $this->entityTypeManager->getStorage('email_campaign');
        $campaign = $storage->load($campaignId);

        if (!$campaign) {
            return ['success' => FALSE, 'error' => 'Campaña no encontrada.'];
        }

        if (!$campaign->canSend()) {
            return ['success' => FALSE, 'error' => 'La campaña no puede enviarse en su estado actual.'];
        }

        // Actualizar estado a enviando.
        $campaign->set('status', 'sending');
        $campaign->set('sent_at', date('Y-m-d\TH:i:s'));
        $campaign->save();

        // Obtener listas destino.
        $listIds = [];
        foreach ($campaign->get('list_ids') as $item) {
            if ($item->target_id) {
                $listIds[] = $item->target_id;
            }
        }

        if (empty($listIds)) {
            $campaign->set('status', 'cancelled');
            $campaign->save();
            return ['success' => FALSE, 'error' => 'No hay listas destino configuradas.'];
        }

        // Obtener suscriptores de todas las listas.
        $subscriberStorage = $this->entityTypeManager->getStorage('email_subscriber');
        $query = $subscriberStorage->getQuery()
            ->condition('status', 'subscribed')
            ->accessCheck(FALSE);

        // Construir condición OR para las listas.
        $orGroup = $query->orConditionGroup();
        foreach ($listIds as $listId) {
            $orGroup->condition('lists', $listId);
        }
        $query->condition($orGroup);

        $subscriberIds = $query->execute();
        $subscribers = $subscriberStorage->loadMultiple($subscriberIds);

        $totalRecipients = count($subscribers);
        $totalSent = 0;
        $errors = [];

        // Obtener contenido de la campaña.
        $subject = $campaign->get('subject_line')->value;
        $body = $campaign->get('body_html')->value ?? '';
        $fromEmail = $campaign->get('from_email')->value;
        $fromName = $campaign->get('from_name')->value;

        // Enviar a cada suscriptor.
        foreach ($subscribers as $subscriber) {
            $email = $subscriber->getEmail();

            // Personalizar contenido con datos del suscriptor.
            $personalizedBody = $this->personalizeContent($body, $subscriber);
            $personalizedSubject = $this->personalizeContent($subject, $subscriber);

            try {
                $result = $this->mailManager->mail(
                    'jaraba_email',
                    'campaign',
                    $email,
                    'es',
                    [
                        'subject' => $personalizedSubject,
                        'body' => $personalizedBody,
                        'from' => "{$fromName} <{$fromEmail}>",
                    ]
                );

                if ($result['result']) {
                    $totalSent++;

                    // Actualizar estadísticas del suscriptor.
                    $subscriber->set('last_email_at', date('Y-m-d\TH:i:s'));
                    $subscriber->set('total_emails_sent', ((int) $subscriber->get('total_emails_sent')->value) + 1);
                    $subscriber->save();
                } else {
                    $errors[] = $email;
                }
            } catch (\Exception $e) {
                $errors[] = $email;
                $this->logger->error('Error al enviar a @email: @error', [
                    '@email' => $email,
                    '@error' => $e->getMessage(),
                ]);
            }
        }

        // Actualizar estadísticas de la campaña.
        $campaign->set('status', 'sent');
        $campaign->set('completed_at', date('Y-m-d\TH:i:s'));
        $campaign->set('total_recipients', $totalRecipients);
        $campaign->set('total_sent', $totalSent);
        $campaign->save();

        $this->logger->info('Campaña @name enviada: @sent/@total', [
            '@name' => $campaign->getName(),
            '@sent' => $totalSent,
            '@total' => $totalRecipients,
        ]);

        return [
            'success' => TRUE,
            'total_recipients' => $totalRecipients,
            'total_sent' => $totalSent,
            'errors' => $errors,
        ];
    }

    /**
     * Personaliza contenido con datos del suscriptor.
     *
     * Reemplaza merge tags ({{nombre}}) con los valores reales
     * del suscriptor para cada email.
     *
     * @param string $content
     *   El contenido con merge tags.
     * @param mixed $subscriber
     *   La entidad suscriptor.
     *
     * @return string
     *   El contenido personalizado.
     */
    protected function personalizeContent(string $content, $subscriber): string
    {
        $replacements = [
            '{{first_name}}' => $subscriber->get('first_name')->value ?? '',
            '{{last_name}}' => $subscriber->get('last_name')->value ?? '',
            '{{email}}' => $subscriber->getEmail(),
            '{{full_name}}' => $subscriber->getFullName(),
            '{{unsubscribe_url}}' => '/email/unsubscribe/' . $subscriber->uuid(),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Programa una campaña para envío futuro.
     *
     * Cambia el estado de la campaña a 'scheduled' y establece
     * la fecha/hora de envío. El cron procesará las campañas
     * programadas cuando llegue el momento.
     *
     * @param int $campaignId
     *   El ID de la campaña.
     * @param string $datetime
     *   Fecha/hora de envío en formato ISO 8601.
     *
     * @return bool
     *   TRUE si se programó correctamente.
     */
    public function scheduleCampaign(int $campaignId, string $datetime): bool
    {
        $storage = $this->entityTypeManager->getStorage('email_campaign');
        $campaign = $storage->load($campaignId);

        if (!$campaign || !$campaign->canSend()) {
            return FALSE;
        }

        $campaign->set('status', 'scheduled');
        $campaign->set('scheduled_at', $datetime);
        $campaign->save();

        $this->logger->info('Campaña @name programada para @date', [
            '@name' => $campaign->getName(),
            '@date' => $datetime,
        ]);

        return TRUE;
    }

    /**
     * Envía un email de prueba de una campaña.
     *
     * Permite verificar el contenido y formato antes del
     * envío masivo. El asunto se prefija con [TEST].
     *
     * @param int $campaignId
     *   El ID de la campaña.
     * @param string $testEmail
     *   La dirección de email para el test.
     *
     * @return bool
     *   TRUE si el test se envió correctamente.
     */
    public function sendTest(int $campaignId, string $testEmail): bool
    {
        $storage = $this->entityTypeManager->getStorage('email_campaign');
        $campaign = $storage->load($campaignId);

        if (!$campaign) {
            return FALSE;
        }

        $subject = '[TEST] ' . $campaign->get('subject_line')->value;
        $body = $campaign->get('body_html')->value ?? '';
        $fromEmail = $campaign->get('from_email')->value;
        $fromName = $campaign->get('from_name')->value;

        $result = $this->mailManager->mail(
            'jaraba_email',
            'campaign',
            $testEmail,
            'es',
            [
                'subject' => $subject,
                'body' => $body,
                'from' => "{$fromName} <{$fromEmail}>",
            ]
        );

        return (bool) $result['result'];
    }

}
