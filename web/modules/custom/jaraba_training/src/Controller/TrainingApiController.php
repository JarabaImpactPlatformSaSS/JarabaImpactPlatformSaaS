<?php

declare(strict_types=1);

namespace Drupal\jaraba_training\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_training\Service\LadderService;
use Drupal\jaraba_training\Service\PurchaseService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Controlador para endpoints API REST del módulo Training.
 */
class TrainingApiController extends ControllerBase
{

    /**
     * Constructor.
     */
    public function __construct(
        protected LadderService $ladderService,
        protected PurchaseService $purchaseService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container): static
    {
        return new static(
            $container->get('jaraba_training.ladder_service'),
            $container->get('jaraba_training.purchase_service'),
        );
    }

    /**
     * Lista todos los productos de training.
     *
     * GET /api/v1/training/products
     */
    public function listProducts(): JsonResponse
    {
        $products = $this->ladderService->getFullLadder();

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->id(),
                'title' => $product->getTitle(),
                'type' => $product->getProductType(),
                'ladder_level' => $product->getLadderLevel(),
                'price' => $product->getPrice(),
                'billing_type' => $product->getBillingType(),
                'is_free' => $product->isFree(),
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'count' => count($data),
            'products' => $data,
        ]);
    }

    /**
     * Obtiene la escalera completa con niveles.
     *
     * GET /api/v1/training/ladder
     */
    public function getLadder(): JsonResponse
    {
        $products = $this->ladderService->getFullLadder();

        // Agrupar por nivel.
        $levels = [];
        for ($i = 0; $i <= 5; $i++) {
            $levels[$i] = [
                'level' => $i,
                'level_name' => $this->getLevelName($i),
                'products' => [],
            ];
        }

        foreach ($products as $product) {
            $level = $product->getLadderLevel();
            $levels[$level]['products'][] = [
                'id' => $product->id(),
                'title' => $product->getTitle(),
                'price' => $product->getPrice(),
                'is_featured' => (bool) $product->get('is_featured')->value,
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'ladder' => array_values($levels),
        ]);
    }

    /**
     * Recomienda el siguiente producto para el usuario actual.
     *
     * GET /api/v1/training/ladder/recommend
     */
    public function recommend(): JsonResponse
    {
        $progress = $this->ladderService->getUserProgress();
        $recommended = $progress['recommended_product'];

        $data = [
            'current_level' => $progress['current_level'],
            'progress_percent' => $progress['progress_percent'],
            'recommended' => NULL,
        ];

        if ($recommended) {
            $data['recommended'] = [
                'id' => $recommended->id(),
                'title' => $recommended->getTitle(),
                'type' => $recommended->getProductType(),
                'price' => $recommended->getPrice(),
                'upsell_message' => $recommended->get('upsell_message')->value ?? '',
            ];
        }

        return new JsonResponse([
            'success' => TRUE,
            'data' => $data,
        ]);
    }

    /**
     * Procesa la compra de un producto formativo.
     *
     * POST /api/v1/training/purchase
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   La peticion con product_id y datos de pago opcionales.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON con el resultado de la compra.
     */
    public function purchase(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), TRUE);

        if (empty($data['product_id'])) {
            throw new BadRequestHttpException('El campo product_id es requerido.');
        }

        $productId = (int) $data['product_id'];
        $paymentData = $data['payment'] ?? [];

        $result = $this->purchaseService->purchase($productId, $paymentData);

        $statusCode = $result['success'] ? 200 : 400;

        return new JsonResponse($result, $statusCode);
    }

    /**
     * Obtiene nombre descriptivo del nivel.
     */
    protected function getLevelName(int $level): string
    {
        $names = [
            0 => 'Lead Magnets',
            1 => 'Microcursos',
            2 => 'Membresías',
            3 => 'Mastermind Groups',
            4 => 'Mentoring 1:1',
            5 => 'Certificaciones',
        ];
        return $names[$level] ?? 'Nivel ' . $level;
    }

}
