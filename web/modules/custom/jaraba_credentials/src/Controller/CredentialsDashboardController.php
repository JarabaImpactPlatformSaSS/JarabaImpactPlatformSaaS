<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_credentials\Service\QrCodeGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador para el dashboard de credenciales del usuario.
 *
 * Frontend limpio en /my-certifications.
 */
class CredentialsDashboardController extends ControllerBase
{

    /**
     * Generador de QR.
     */
    protected QrCodeGenerator $qrGenerator;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->qrGenerator = $container->get('jaraba_credentials.qr_generator');
        return $instance;
    }

    /**
     * Dashboard principal de credenciales del usuario.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La request actual.
     *
     * @return array|Response
     *   Render array o response para AJAX.
     */
    public function dashboard(Request $request): array|Response
    {
        $user = $this->currentUser();

        // Cargar credenciales del usuario usando entityTypeManager() del padre
        $credentials = $this->entityTypeManager()->getStorage('issued_credential')
            ->loadByProperties([
                'recipient_id' => $user->id(),
            ]);

        // Ordenar por fecha de emisión (más recientes primero)
        $credentialsArray = array_values($credentials);
        usort($credentialsArray, function ($a, $b) {
            $dateA = $a->get('issued_on')->value ?? '';
            $dateB = $b->get('issued_on')->value ?? '';
            return strcmp($dateB, $dateA);
        });

        // Preparar datos para el template
        $credentialsData = [];
        foreach ($credentialsArray as $credential) {
            /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
            $template = $credential->getTemplate();
            $credentialsData[] = [
                'credential' => $credential,
                'template' => $template,
                'qr_code' => $this->qrGenerator->generateForCredential($credential->uuid(), 100),
                'verification_url' => $this->qrGenerator->getVerificationUrl($credential->uuid()),
            ];
        }

        $build = [
            '#theme' => 'credentials_dashboard',
            '#credentials' => $credentialsData,
            '#user' => $user,
            '#total_count' => count($credentialsArray),
            '#attached' => [
                'library' => [
                    'jaraba_credentials/dashboard',
                ],
            ],
            '#cache' => [
                'contexts' => ['user'],
                'tags' => ['user:' . $user->id()],
            ],
        ];

        // Si es AJAX, retornar solo el contenido
        if ($request->isXmlHttpRequest()) {
            $html = (string) \Drupal::service('renderer')->render($build);
            return new Response($html, 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        }

        return $build;
    }

}
