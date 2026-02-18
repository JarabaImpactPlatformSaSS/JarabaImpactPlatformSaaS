<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_credentials\Service\CredentialVerifier;
use Drupal\jaraba_credentials\Service\QrCodeGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controlador para verificación pública de credenciales.
 *
 * La ruta /verify/{uuid} es pública (sin autenticación).
 */
class VerifyController extends ControllerBase
{

    /**
     * Servicio de verificación.
     */
    protected CredentialVerifier $verifier;

    /**
     * Generador de QR.
     */
    protected QrCodeGenerator $qrGenerator;

    /**
     * Constructor del controlador.
     */
    public function __construct(CredentialVerifier $verifier, QrCodeGenerator $qrGenerator)
    {
        $this->verifier = $verifier;
        $this->qrGenerator = $qrGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_credentials.verifier'),
            $container->get('jaraba_credentials.qr_generator')
        );
    }

    /**
     * Página pública de verificación de credencial.
     *
     * @param string $uuid
     *   UUID de la credencial.
     *
     * @return array
     *   Render array.
     */
    public function verify(string $uuid): array
    {
        $result = $this->verifier->verify($uuid);

        $qrCode = '';
        if ($result['credential']) {
            $qrCode = $this->qrGenerator->generateForCredential($uuid);
        }

        return [
            '#theme' => 'credential_verify',
            '#credential' => $result['credential'],
            '#issuer' => $result['issuer'],
            '#template' => $result['template'],
            '#is_valid' => $result['is_valid'],
            '#verification_message' => $result['message'],
            '#qr_code' => $qrCode,
            '#cache' => [
                'max-age' => 0,
            ],
            '#attached' => [
                'library' => [
                    'jaraba_credentials/verify',
                ],
            ],
        ];
    }

    /**
     * Endpoint JSON para verificación programática.
     *
     * @param string $uuid
     *   UUID de la credencial.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Response JSON con datos OB3.
     */
    public function verifyJson(string $uuid): JsonResponse
    {
        $result = $this->verifier->verify($uuid);
        $ob3Json = $this->verifier->getOb3Json($uuid);

        $response = [
            'verification' => [
                'is_valid' => $result['is_valid'],
                'message' => (string) $result['message'],
                'verified_at' => gmdate('Y-m-d\TH:i:s\Z'),
            ],
        ];

        if ($ob3Json) {
            $response['credential'] = $ob3Json;
        }

        if ($result['credential']) {
            $credential = $result['credential'];
            $response['status'] = $credential->get('status')->value;
            $response['issued_on'] = $credential->get('issued_on')->value;
            $response['expires_on'] = $credential->get('expires_on')->value;
        }

        return // AUDIT-CONS-N08: Standardized JSON envelope.
        new JsonResponse(['success' => TRUE, 'data' => $response, 'meta' => ['timestamp' => time()]]);
    }

    /**
     * Título dinámico para la página de verificación.
     *
     * @param string $uuid
     *   UUID de la credencial.
     *
     * @return string
     *   Título de la página.
     */
    public function verifyTitle(string $uuid): string
    {
        $result = $this->verifier->verify($uuid);

        if ($result['template']) {
            return (string) $this->t('Verificar: @name', [
                '@name' => $result['template']->get('name')->value,
            ]);
        }

        return (string) $this->t('Verificar Credencial');
    }

}
