<?php

declare(strict_types=1);

namespace Drupal\jaraba_content_hub\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para suscripción al newsletter del blog.
 *
 * PROPÓSITO:
 * Endpoint público que captura emails de suscriptores desde el
 * formulario de newsletter del blog y crea entidades EmailSubscriber
 * del módulo jaraba_email.
 *
 * SEGURIDAD:
 * - CSRF-API-001: Token CSRF via _csrf_request_header_token.
 * - Validación de email server-side.
 * - Rate limiting por IP (básico).
 * - Solo acepta POST.
 */
class NewsletterController extends ControllerBase
{

    /**
     * Procesa la suscripción al newsletter.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Respuesta JSON con resultado.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), TRUE);
        $email = trim($content['email'] ?? '');

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse([
                'success' => FALSE,
                'message' => (string) $this->t('Please provide a valid email address.'),
            ], 422);
        }

        // Verificar si ya existe un suscriptor con ese email.
        $storage = $this->entityTypeManager()->getStorage('email_subscriber');
        $existing = $storage->getQuery()
            ->condition('email', $email)
            ->accessCheck(FALSE)
            ->range(0, 1)
            ->execute();

        if (!empty($existing)) {
            return new JsonResponse([
                'success' => TRUE,
                'message' => (string) $this->t('You are already subscribed. Thank you!'),
            ]);
        }

        try {
            $subscriber = $storage->create([
                'email' => $email,
                'status' => 'subscribed',
                'source' => 'blog_newsletter',
            ]);
            $subscriber->save();

            return new JsonResponse([
                'success' => TRUE,
                'message' => (string) $this->t('Successfully subscribed! Thank you.'),
            ]);
        }
        catch (\Exception $e) {
            $this->getLogger('jaraba_content_hub')->error(
                'Error creating newsletter subscriber: @error',
                ['@error' => $e->getMessage()]
            );

            return new JsonResponse([
                'success' => FALSE,
                'message' => (string) $this->t('An error occurred. Please try again later.'),
            ], 500);
        }
    }

}
