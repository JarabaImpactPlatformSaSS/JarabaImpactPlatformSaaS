<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_credentials\Service\QrCodeGenerator;
use Drupal\jaraba_credentials\Service\PdfGenerator;
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
     * Generador de PDF.
     */
    protected PdfGenerator $pdfGenerator;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->qrGenerator = $container->get('jaraba_credentials.qr_generator');
        $instance->pdfGenerator = $container->get('jaraba_credentials.pdf_generator');
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
            $issuer = $template ? $template->getIssuer() : NULL;

            $credentialsData[] = [
                'credential' => $credential,
                'template' => $template,
                'qr_code' => $this->qrGenerator->generateForCredential($credential->uuid(), 100),
                'verification_url' => $this->qrGenerator->getVerificationUrl($credential->uuid()),
                'linkedin_url' => $this->generateLinkedInUrl($credential, $template, $issuer),
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

    /**
     * Genera la URL de "Add to Profile" para LinkedIn.
     */
    protected function generateLinkedInUrl($credential, $template, $issuer): string
    {
        $baseUrl = 'https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME';
        $params = [
            'name' => $template ? $template->get('name')->value : 'Certificación',
            'organizationName' => $issuer ? $issuer->get('name')->value : 'Jaraba Impact',
            'issueYear' => date('Y', strtotime($credential->get('issued_on')->value)),
            'issueMonth' => date('m', strtotime($credential->get('issued_on')->value)),
            'certUrl' => $this->qrGenerator->getVerificationUrl($credential->uuid()),
            'certId' => $credential->uuid(),
        ];

        return $baseUrl . '&' . http_build_query($params);
    }

    /**
     * Endpoint para descarga PDF de la credencial.
     *
     * @param string $uuid
     *   UUID de la credencial.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   La respuesta con el PDF.
     */
    public function downloadPdf(string $uuid): Response
    {
        $storage = $this->entityTypeManager()->getStorage('issued_credential');
        $entities = $storage->loadByProperties(['uuid' => $uuid]);

        if (empty($entities)) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
        $credential = reset($entities);
        if ($credential->get('recipient_id')->target_id != $this->currentUser()->id()) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
        }

        $pdfData = $this->pdfGenerator->generate($credential);

        $response = new Response($pdfData);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="credential-' . $uuid . '.pdf"');

        return $response;
    }

}
