<?php

declare(strict_types=1);

namespace Drupal\jaraba_ai_agents\Tool;

use Drupal\Core\Mail\MailManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Herramienta para enviar emails.
 *
 * REQUIERE APROBACIÓN: Sí, porque enviar emails es irreversible.
 */
class SendEmailTool extends BaseTool
{

    /**
     * Constructor.
     */
    public function __construct(
        protected LoggerInterface $logger,
        protected MailManagerInterface $mailManager,
    ) {
        parent::__construct($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return 'send_email';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel(): string
    {
        return 'Enviar Email';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Envía un email a un destinatario específico.';
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return [
            'to' => [
                'type' => 'string',
                'required' => TRUE,
                'description' => 'Dirección de email del destinatario.',
            ],
            'subject' => [
                'type' => 'string',
                'required' => TRUE,
                'description' => 'Asunto del email.',
            ],
            'body' => [
                'type' => 'string',
                'required' => TRUE,
                'description' => 'Contenido del email (HTML permitido).',
            ],
            'from' => [
                'type' => 'string',
                'required' => FALSE,
                'description' => 'Dirección de origen (opcional).',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function requiresApproval(): bool
    {
        return TRUE;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $params, array $context = []): array
    {
        $to = $params['to'];
        $subject = $params['subject'];
        $body = $params['body'];

        $this->log('Sending email to @to', ['@to' => $to]);

        try {
            $result = $this->mailManager->mail(
                'jaraba_ai_agents',
                'workflow_email',
                $to,
                'es',
                [
                    'subject' => $subject,
                    'body' => $body,
                    'context' => $context,
                ]
            );

            if ($result['result']) {
                return $this->success([
                    'sent_to' => $to,
                    'subject' => $subject,
                ]);
            }

            return $this->error('Email sending failed.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

}
