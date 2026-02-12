<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Service;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Servicio generador de códigos QR para credenciales.
 *
 * Genera QR codes en formato SVG inline para verificación.
 */
class QrCodeGenerator
{

    /**
     * Request stack.
     */
    protected RequestStack $requestStack;

    /**
     * Constructor del servicio.
     *
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     *   Stack de requests.
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Genera un código QR como SVG.
     *
     * @param string $content
     *   El contenido a codificar (generalmente una URL).
     * @param int $size
     *   Tamaño del QR en píxeles.
     *
     * @return string
     *   SVG del código QR.
     */
    public function generate(string $content, int $size = 200): string
    {
        // Usar QuickChart.io - API gratuita y funcional para QR
        $encodedContent = urlencode($content);
        $qrUrl = "https://quickchart.io/qr?text={$encodedContent}&size={$size}&margin=1";

        // Retornar como imagen embebida
        return sprintf(
            '<img src="%s" alt="%s" width="%d" height="%d" class="credential-qr" loading="lazy" />',
            htmlspecialchars($qrUrl),
            t('Código QR de verificación'),
            $size,
            $size
        );
    }

    /**
     * Genera la URL de verificación para una credencial.
     *
     * @param string $uuid
     *   UUID de la credencial.
     *
     * @return string
     *   URL completa de verificación.
     */
    public function getVerificationUrl(string $uuid): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $baseUrl = $request ? $request->getSchemeAndHttpHost() : 'https://jaraba.es';

        return "{$baseUrl}/verify/{$uuid}";
    }

    /**
     * Genera QR con URL de verificación de credencial.
     *
     * @param string $uuid
     *   UUID de la credencial.
     * @param int $size
     *   Tamaño del QR.
     *
     * @return string
     *   SVG del QR.
     */
    public function generateForCredential(string $uuid, int $size = 200): string
    {
        $url = $this->getVerificationUrl($uuid);
        return $this->generate($url, $size);
    }

}
