<?php

declare(strict_types=1);

namespace Drupal\jaraba_credentials\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_credentials\Service\RevocationService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controlador API para credenciales.
 */
class CredentialsApiController extends ControllerBase
{

    /**
     * Servicio de revocación.
     */
    protected RevocationService $revocationService;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        $instance = new static();
        $instance->revocationService = $container->get('jaraba_credentials.revocation');
        return $instance;
    }

    /**
     * Lista las credenciales del usuario actual.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Lista de credenciales en JSON.
     */
    public function list(): JsonResponse
    {
        $user = $this->currentUser();

        $credentials = $this->entityTypeManager()->getStorage('issued_credential')
            ->loadByProperties([
                'recipient_id' => $user->id(),
            ]);

        $data = [];
        foreach ($credentials as $credential) {
            /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
            $data[] = [
                'uuid' => $credential->uuid(),
                'status' => $credential->get('status')->value,
                'issued_on' => $credential->get('issued_on')->value,
                'expires_on' => $credential->get('expires_on')->value,
                'verification_url' => $credential->get('verification_url')->value,
            ];
        }

        return new JsonResponse(['credentials' => $data]);
    }

    /**
     * Obtiene una credencial específica.
     *
     * @param string $uuid
     *   UUID de la credencial.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Datos de la credencial.
     */
    public function get(string $uuid): JsonResponse
    {
        $credentials = $this->entityTypeManager()->getStorage('issued_credential')
            ->loadByProperties(['uuid' => $uuid]);

        if (empty($credentials)) {
            return new JsonResponse(['error' => 'Credential not found'], 404);
        }

        /** @var \Drupal\jaraba_credentials\Entity\IssuedCredential $credential */
        $credential = reset($credentials);

        $ob3Json = $credential->get('ob3_json')->value ?? '';
        $ob3Data = $ob3Json ? json_decode($ob3Json, TRUE) : NULL;

        return new JsonResponse([
            'uuid' => $credential->uuid(),
            'status' => $credential->get('status')->value,
            'issued_on' => $credential->get('issued_on')->value,
            'expires_on' => $credential->get('expires_on')->value,
            'verification_url' => $credential->get('verification_url')->value,
            'ob3' => $ob3Data,
        ]);
    }

    /**
     * Revoca una credencial vía API.
     *
     * @param int $credential_id
     *   ID de la credencial.
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La solicitud HTTP.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   Resultado de la revocación.
     */
    public function revoke(int $credential_id, Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), TRUE) ?? [];
        $reason = $content['reason'] ?? '';
        $notes = $content['notes'] ?? NULL;

        $validReasons = ['fraud', 'error', 'request', 'policy'];
        if (!in_array($reason, $validReasons, TRUE)) {
            return new JsonResponse([
                'error' => 'Invalid reason. Must be one of: ' . implode(', ', $validReasons),
            ], 400);
        }

        try {
            $entry = $this->revocationService->revoke(
                $credential_id,
                (int) $this->currentUser()->id(),
                $reason,
                $notes
            );

            return new JsonResponse([
                'success' => TRUE,
                'revocation_entry_id' => $entry->id(),
                'credential_id' => $credential_id,
                'reason' => $reason,
                'revoked_at' => date('c'),
            ]);
        }
        catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }
        catch (\LogicException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 409);
        }
        catch (\Exception $e) {
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

}
