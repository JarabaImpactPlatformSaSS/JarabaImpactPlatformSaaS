<?php

declare(strict_types=1);

namespace Drupal\jaraba_mentoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jaraba_mentoring\Service\StripeConnectService;

/**
 * API Controller for package endpoints.
 *
 * PROPÓSITO:
 * Gestiona las API REST para paquetes de mentoría:
 * - Listar paquetes disponibles
 * - Obtener detalle de un paquete
 * - Iniciar proceso de compra
 *
 * SPEC: 31_Emprendimiento_Mentoring_Core_v1
 */
class PackageApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected StripeConnectService $stripeService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_mentoring.stripe_connect'),
        );
    }

    /**
     * Lists available packages.
     *
     * GET /api/v1/packages
     * Query params: ?mentor_id=123&type=session_pack&featured=1
     */
    public function list(Request $request): JsonResponse
    {
        $storage = $this->entityTypeManager()->getStorage('mentoring_package');

        $query = $storage->getQuery()
            ->accessCheck(TRUE)
            ->condition('is_published', TRUE);

        // Filtros opcionales.
        if ($mentor_id = $request->query->get('mentor_id')) {
            $query->condition('mentor_id', (int) $mentor_id);
        }
        if ($type = $request->query->get('type')) {
            $query->condition('package_type', $type);
        }
        if ($request->query->get('featured')) {
            $query->condition('is_featured', TRUE);
        }

        $query->sort('is_featured', 'DESC')
            ->sort('total_sold', 'DESC')
            ->range(0, 50);

        $ids = $query->execute();
        $packages = $storage->loadMultiple($ids);

        $data = [];
        foreach ($packages as $package) {
            $data[] = $this->serializePackage($package);
        }

        return new JsonResponse(['data' => $data, 'count' => count($data)]);
    }

    /**
     * Gets a single package.
     *
     * GET /api/v1/packages/{package_id}
     */
    public function get(int $package_id): JsonResponse
    {
        $package = $this->entityTypeManager()
            ->getStorage('mentoring_package')
            ->load($package_id);

        if (!$package) {
            return new JsonResponse(['error' => 'Package not found'], 404);
        }

        if (!$package->get('is_published')->value) {
            return new JsonResponse(['error' => 'Package not available'], 404);
        }

        return new JsonResponse([
            'data' => $this->serializePackage($package, TRUE),
        ]);
    }

    /**
     * Initiates package purchase.
     *
     * POST /api/v1/packages/{package_id}/purchase
     * Body: { diagnostic_id, goals }
     *
     * Returns Stripe PaymentIntent client_secret for frontend completion.
     */
    public function purchase(int $package_id, Request $request): JsonResponse
    {
        // Verificar que el usuario está autenticado.
        if ($this->currentUser()->isAnonymous()) {
            return new JsonResponse(['error' => 'Authentication required'], 401);
        }

        $package = $this->entityTypeManager()
            ->getStorage('mentoring_package')
            ->load($package_id);

        if (!$package) {
            return new JsonResponse(['error' => 'Package not found'], 404);
        }

        if (!$package->get('is_published')->value) {
            return new JsonResponse(['error' => 'Package not available'], 404);
        }

        $content = json_decode($request->getContent(), TRUE) ?? [];

        // Obtener el mentor.
        $mentor = $package->getMentor();
        if (!$mentor || !$mentor->get('stripe_onboarding_complete')->value) {
            return new JsonResponse([
                'error' => 'Mentor is not ready to receive payments',
            ], 400);
        }

        // Calcular precio con descuento si aplica.
        $price = $package->getPrice();
        $discount = (int) ($package->get('discount_percent')->value ?? 0);
        if ($discount > 0) {
            $price = $price * (1 - $discount / 100);
        }

        // Obtener fee de plataforma del mentor.
        $platform_fee_percent = (float) ($mentor->get('platform_fee_percent')->value ?? 15.0);

        try {
            // Crear PaymentIntent con Stripe Connect (delega en jaraba_foc).
            $payment_intent = $this->stripeService->createPaymentIntent(
                amount: (int) ($price * 100),
                currency: 'eur',
                destinationAccount: $mentor->get('stripe_account_id')->value,
                applicationFeePercent: $platform_fee_percent,
                metadata: [
                    'type' => 'mentoring_package',
                    'package_id' => $package->id(),
                    'mentor_id' => $mentor->id(),
                    'mentee_id' => $this->currentUser()->id(),
                    'diagnostic_id' => $content['diagnostic_id'] ?? NULL,
                ]
            );

            // Crear engagement en estado pendiente.
            $engagement = $this->entityTypeManager()
                ->getStorage('mentoring_engagement')
                ->create([
                    'mentor_id' => $mentor->id(),
                    'mentee_id' => $this->currentUser()->id(),
                    'package_id' => $package->id(),
                    'payment_intent_id' => $payment_intent['id'],
                    'sessions_total' => $package->getSessionsIncluded(),
                    'sessions_remaining' => $package->getSessionsIncluded(),
                    'amount_paid' => $price,
                    'status' => 'pending',
                    'goals' => $content['goals'] ?? '',
                    'business_diagnostic_id' => $content['diagnostic_id'] ?? NULL,
                    'start_date' => date('Y-m-d\TH:i:s'),
                    'expiry_date' => date('Y-m-d\TH:i:s', strtotime('+6 months')),
                ]);

            $engagement->save();

            return new JsonResponse([
                'data' => [
                    'client_secret' => $payment_intent['client_secret'],
                    'payment_intent_id' => $payment_intent['id'],
                    'engagement_id' => (int) $engagement->id(),
                    'amount' => $price,
                    'currency' => 'EUR',
                ],
                'message' => 'Payment initiated. Complete payment on frontend.',
            ]);

        } catch (\Exception $e) {
            \Drupal::logger('jaraba_mentoring')->error('Payment initiation failed: @error', [
                '@error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'error' => 'Payment initiation failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Serializes a package entity.
     */
    protected function serializePackage($package, bool $full = FALSE): array
    {
        $mentor = $package->getMentor();

        $data = [
            'id' => (int) $package->id(),
            'uuid' => $package->uuid(),
            'title' => $package->get('title')->value,
            'package_type' => $package->get('package_type')->value,
            'sessions_included' => $package->getSessionsIncluded(),
            'session_duration_minutes' => (int) ($package->get('session_duration_minutes')->value ?? 60),
            'price' => $package->getPrice(),
            'discount_percent' => (int) ($package->get('discount_percent')->value ?? 0),
            'is_featured' => (bool) $package->get('is_featured')->value,
            'total_sold' => (int) ($package->get('total_sold')->value ?? 0),
            'mentor' => $mentor ? [
                'id' => (int) $mentor->id(),
                'display_name' => $mentor->getDisplayName(),
                'average_rating' => $mentor->getAverageRating(),
            ] : NULL,
        ];

        if ($full) {
            $data['description'] = $package->get('description')->value ?? '';
            $data['includes_async_support'] = (bool) $package->get('includes_async_support')->value;
            $data['async_response_hours'] = (int) ($package->get('async_response_hours')->value ?? 48);
        }

        return $data;
    }

}
