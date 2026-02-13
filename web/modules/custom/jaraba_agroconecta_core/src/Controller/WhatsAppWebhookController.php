<?php

declare(strict_types=1);

namespace Drupal\jaraba_agroconecta_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\jaraba_agroconecta_core\Service\WhatsAppApiService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controlador del webhook de WhatsApp Business API.
 *
 * Maneja la verificación del webhook (GET) y el procesamiento de
 * mensajes entrantes (POST) desde la plataforma Meta.
 *
 * Referencia: Doc 68 — Sales Agent v1, Fase 5.
 */
class WhatsAppWebhookController extends ControllerBase implements ContainerInjectionInterface
{

    public function __construct(
        protected WhatsAppApiService $whatsappService,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_agroconecta_core.whatsapp'),
            $container->get('logger.channel.default'),
        );
    }

    /**
     * Webhook endpoint para WhatsApp Business API.
     *
     * Maneja peticiones GET (verificación del challenge de Meta) y
     * POST (mensajes entrantes desde WhatsApp).
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP entrante.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Respuesta HTTP.
     */
    public function webhook(Request $request): Response
    {
        // GET = Verificación del webhook (challenge de Meta).
        if ($request->isMethod('GET')) {
            return $this->verifyWebhook($request);
        }

        // POST = Mensaje entrante.
        // AUDIT-SEC-001: Validar firma HMAC antes de procesar.
        $rawPayload = $request->getContent();
        $signatureHeader = $request->headers->get('X-Hub-Signature-256', '');
        if (!$this->verifyHmacSignature($rawPayload, $signatureHeader)) {
            $this->logger->warning('WhatsApp webhook: firma HMAC inválida.');
            return new JsonResponse(['error' => 'Invalid signature'], 403);
        }

        $payload = json_decode($rawPayload, TRUE);
        if (empty($payload)) {
            return new JsonResponse(['error' => 'Invalid payload'], 400);
        }

        try {
            $result = $this->whatsappService->handleIncomingMessage($payload);
            return new JsonResponse($result);
        }
        catch (\Exception $e) {
            $this->logger->error('WhatsApp webhook error: @error', ['@error' => $e->getMessage()]);
            return new JsonResponse(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Verifica el webhook con el challenge de Meta.
     *
     * Meta envía una petición GET con hub.mode, hub.verify_token y
     * hub.challenge. Se valida el token y se responde con el challenge.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La petición HTTP de verificación.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     *   Challenge en texto plano si la verificación es exitosa,
     *   o error 403 si falla.
     */
    protected function verifyWebhook(Request $request): Response
    {
        $mode = $request->query->get('hub_mode', '');
        $token = $request->query->get('hub_verify_token', '');
        $challenge = $request->query->get('hub_challenge', '');

        $configToken = $this->config('jaraba_agroconecta_core.settings')->get('whatsapp_verify_token') ?? '';

        if ($mode === 'subscribe' && $token === $configToken) {
            $this->logger->info('WhatsApp webhook verificado correctamente.');
            return new Response($challenge, 200, ['Content-Type' => 'text/plain']);
        }

        $this->logger->warning('WhatsApp webhook verificación fallida.');
        return new JsonResponse(['error' => 'Verification failed'], 403);
    }

    /**
     * Verifica la firma HMAC-SHA256 del payload de Meta.
     *
     * AUDIT-SEC-001: Toda petición POST debe validar X-Hub-Signature-256
     * contra el app_secret configurado. Usa hash_equals() para prevenir
     * timing attacks.
     *
     * @param string $payload
     *   Cuerpo raw de la petición.
     * @param string $signatureHeader
     *   Valor del header X-Hub-Signature-256 (formato: "sha256=XXXX").
     *
     * @return bool
     *   TRUE si la firma es válida.
     */
    protected function verifyHmacSignature(string $payload, string $signatureHeader): bool {
        $appSecret = $this->config('jaraba_agroconecta_core.settings')->get('whatsapp_app_secret');
        if (empty($appSecret) || empty($signatureHeader)) {
            return FALSE;
        }

        $parts = explode('=', $signatureHeader, 2);
        if (count($parts) !== 2 || $parts[0] !== 'sha256') {
            return FALSE;
        }

        $expected = hash_hmac('sha256', $payload, $appSecret);
        return hash_equals($expected, $parts[1]);
    }

}
