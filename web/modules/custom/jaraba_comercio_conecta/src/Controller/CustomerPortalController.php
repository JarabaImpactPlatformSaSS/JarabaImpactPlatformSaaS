<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\jaraba_comercio_conecta\Service\CustomerProfileService;
use Drupal\jaraba_comercio_conecta\Service\OrderRetailService;
use Drupal\jaraba_comercio_conecta\Service\WishlistService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller del portal privado del cliente (/mi-cuenta).
 *
 * Estructura: Gestiona las rutas /mi-cuenta, /mi-cuenta/wishlist,
 *   /mi-cuenta/perfil, y endpoints API de wishlist.
 *   Devuelve render arrays con #theme apuntando a los templates del modulo.
 *
 * Logica: Todas las rutas requieren autenticacion. El cliente solo ve
 *   datos de su propio perfil. Los endpoints API devuelven JsonResponse
 *   para operaciones AJAX sobre la wishlist.
 */
class CustomerPortalController extends ControllerBase {

  /**
   * Constructor del controller.
   *
   * @param \Drupal\jaraba_comercio_conecta\Service\CustomerProfileService $customerProfileService
   *   Servicio de perfil del cliente.
   * @param \Drupal\jaraba_comercio_conecta\Service\WishlistService $wishlistService
   *   Servicio de lista de deseos.
   * @param \Drupal\jaraba_comercio_conecta\Service\OrderRetailService $orderService
   *   Servicio de pedidos retail.
   */
  public function __construct(
    protected CustomerProfileService $customerProfileService,
    protected WishlistService $wishlistService,
    protected OrderRetailService $orderService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('jaraba_comercio_conecta.customer_profile'),
      $container->get('jaraba_comercio_conecta.wishlist'),
      $container->get('jaraba_comercio_conecta.order_retail'),
    );
  }

  /**
   * Dashboard principal del cliente con resumen de actividad.
   *
   * Estructura: Muestra perfil, pedidos recientes, estadisticas
   *   y contador de wishlist en una vista consolidada.
   *
   * Logica: Carga el perfil del cliente actual, obtiene los ultimos
   *   5 pedidos, calcula estadisticas (total gastado, puntos de
   *   fidelidad) y cuenta items en la wishlist.
   *
   * @return array
   *   Render array con #theme 'comercio_customer_dashboard'.
   */
  public function dashboard(): array {
    $uid = (int) $this->currentUser()->id();

    if ($uid <= 0) {
      throw new AccessDeniedHttpException($this->t('Debes iniciar sesion para acceder a tu cuenta.'));
    }

    $profile = $this->customerProfileService->getOrCreateProfile($uid);
    $recent_orders_result = $this->orderService->getUserOrders($uid, 0, 5);
    $wishlist_count = $this->wishlistService->getItemCount($uid);

    $orders = [];
    foreach ($recent_orders_result['orders'] as $order) {
      $orders[] = [
        'id' => (int) $order->id(),
        'order_number' => $order->get('order_number')->value,
        'status' => $order->get('status')->value,
        'payment_status' => $order->get('payment_status')->value,
        'total' => (float) $order->get('total')->value,
        'created' => $order->get('created')->value,
      ];
    }

    $stats = $this->customerProfileService->getCustomerStats($uid);

    return [
      '#theme' => 'comercio_customer_dashboard',
      '#profile' => $profile,
      '#recent_orders' => $orders,
      '#stats' => $stats,
      '#wishlist_count' => $wishlist_count,
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/customer-portal',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['customer_profile:' . $uid],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Listado de productos en la wishlist del cliente.
   *
   * Estructura: Muestra una cuadricula de productos favoritos
   *   con imagen, titulo, precio y boton de eliminar.
   *
   * Logica: Obtiene la wishlist por defecto del usuario actual
   *   y carga los items asociados con sus datos de producto.
   *
   * @return array
   *   Render array con #theme 'comercio_customer_wishlist'.
   */
  public function wishlist(): array {
    $uid = (int) $this->currentUser()->id();

    if ($uid <= 0) {
      throw new AccessDeniedHttpException($this->t('Debes iniciar sesion para ver tu lista de deseos.'));
    }

    $wishlist = $this->wishlistService->getDefaultWishlist($uid);
    $items = $wishlist ? $this->wishlistService->getWishlistItems($wishlist) : [];

    return [
      '#theme' => 'comercio_customer_wishlist',
      '#wishlist' => $wishlist,
      '#items' => $items,
      '#empty' => empty($items),
      '#attached' => [
        'library' => [
          'jaraba_comercio_conecta/customer-portal',
        ],
      ],
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['wishlist:' . $uid],
        'max-age' => 60,
      ],
    ];
  }

  /**
   * Formulario de edicion del perfil del cliente.
   *
   * Estructura: Renderiza el formulario de la entidad CustomerProfile
   *   del usuario actual.
   *
   * Logica: Carga o crea el perfil del cliente y devuelve el
   *   formulario de edicion de la entidad. Si no existe perfil,
   *   se crea uno nuevo con los datos minimos.
   *
   * @return array
   *   Render array con el formulario de la entidad.
   */
  public function profile(): array {
    $uid = (int) $this->currentUser()->id();

    if ($uid <= 0) {
      throw new AccessDeniedHttpException($this->t('Debes iniciar sesion para editar tu perfil.'));
    }

    $profile = $this->customerProfileService->getOrCreateProfile($uid);

    return $this->entityFormBuilder()->getForm($profile, 'edit');
  }

  /**
   * Endpoint API para anadir un producto a la wishlist.
   *
   * Estructura: Endpoint POST que recibe product_id en el cuerpo JSON
   *   y devuelve JsonResponse con resultado de la operacion.
   *
   * Logica: Decodifica el JSON del request, extrae product_id,
   *   llama a WishlistService::addItem() y devuelve respuesta JSON
   *   con indicador de exito.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto request con product_id en el cuerpo JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con ['data' => ['success' => true]].
   */
  public function apiAddToWishlist(Request $request): JsonResponse {
    $uid = (int) $this->currentUser()->id();

    if ($uid <= 0) {
      return new JsonResponse(['error' => $this->t('Acceso denegado.')], 403);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $product_id = (int) ($data['product_id'] ?? 0);

    if ($product_id <= 0) {
      return new JsonResponse(['error' => $this->t('Campo product_id requerido.')], 400);
    }

    try {
      $this->wishlistService->addItem($uid, $product_id);

      return new JsonResponse([
        'data' => ['success' => TRUE],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => $this->t('Error al anadir el producto a la lista de deseos.'),
      ], 500);
    }
  }

  /**
   * Endpoint API para eliminar un producto de la wishlist.
   *
   * Estructura: Endpoint DELETE que recibe product_id en el cuerpo JSON
   *   y devuelve JsonResponse con resultado de la operacion.
   *
   * Logica: Decodifica el JSON del request, extrae product_id,
   *   llama a WishlistService::removeItem() y devuelve respuesta JSON
   *   con indicador de exito.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Objeto request con product_id en el cuerpo JSON.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Respuesta JSON con ['data' => ['success' => true]].
   */
  public function apiRemoveFromWishlist(Request $request): JsonResponse {
    $uid = (int) $this->currentUser()->id();

    if ($uid <= 0) {
      return new JsonResponse(['error' => $this->t('Acceso denegado.')], 403);
    }

    $data = json_decode($request->getContent(), TRUE) ?? [];
    $product_id = (int) ($data['product_id'] ?? 0);

    if ($product_id <= 0) {
      return new JsonResponse(['error' => $this->t('Campo product_id requerido.')], 400);
    }

    try {
      $this->wishlistService->removeItem($uid, $product_id);

      return new JsonResponse([
        'data' => ['success' => TRUE],
      ]);
    }
    catch (\Exception $e) {
      return new JsonResponse([
        'error' => $this->t('Error al eliminar el producto de la lista de deseos.'),
      ], 500);
    }
  }

}
