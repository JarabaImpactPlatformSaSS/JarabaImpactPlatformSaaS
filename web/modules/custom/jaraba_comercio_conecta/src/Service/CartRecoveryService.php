<?php

declare(strict_types=1);

namespace Drupal\jaraba_comercio_conecta\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

class CartRecoveryService {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected LoggerInterface $logger,
  ) {}

  public function detectAbandonedCarts(int $hours_threshold = 24): array {
    $cart_storage = $this->entityTypeManager->getStorage('comercio_cart');
    $threshold = \Drupal::time()->getRequestTime() - ($hours_threshold * 3600);

    $ids = $cart_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 'active')
      ->condition('changed', $threshold, '<')
      ->execute();

    $carts = $ids ? $cart_storage->loadMultiple($ids) : [];
    $abandoned = [];

    foreach ($carts as $cart) {
      $subtotal = (float) $cart->get('subtotal')->value;
      if ($subtotal <= 0) {
        continue;
      }

      $cart->set('status', 'abandoned');
      $cart->save();

      $record = $this->createAbandonedCartRecord($cart);
      if ($record) {
        $abandoned[] = $record;
      }
    }

    if (!empty($abandoned)) {
      $this->logger->info('@count carritos abandonados detectados.', ['@count' => count($abandoned)]);
    }

    return $abandoned;
  }

  protected function createAbandonedCartRecord(object $cart): ?object {
    $abandoned_storage = $this->entityTypeManager->getStorage('abandoned_cart');

    $existing = $abandoned_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('cart_id', $cart->id())
      ->range(0, 1)
      ->execute();

    if ($existing) {
      return $abandoned_storage->load(reset($existing));
    }

    $uid = (int) $cart->getOwnerId();
    $email = '';
    if ($uid > 0) {
      $user = $this->entityTypeManager->getStorage('user')->load($uid);
      if ($user) {
        $email = $user->getEmail();
      }
    }

    $record = $abandoned_storage->create([
      'tenant_id' => $cart->get('tenant_id')->target_id,
      'cart_id' => $cart->id(),
      'uid' => $uid > 0 ? $uid : NULL,
      'email' => $email,
      'recovery_token' => bin2hex(random_bytes(32)),
      'cart_value' => $cart->get('subtotal')->value,
    ]);
    $record->save();

    return $record;
  }

  public function sendRecoveryEmail(object $abandoned_cart): bool {
    if ($abandoned_cart->get('recovery_sent')->value) {
      return FALSE;
    }

    $email = $abandoned_cart->get('email')->value;
    if (!$email) {
      return FALSE;
    }

    $token = $abandoned_cart->get('recovery_token')->value;
    $recovery_url = '/comercio-local/carrito/recuperar/' . $token;

    try {
      if (\Drupal::hasService('jaraba_email.mailer')) {
        $mailer = \Drupal::service('jaraba_email.mailer');
        $mailer->send('comercio_conecta', 'cart_recovery', $email, [
          'recovery_url' => $recovery_url,
          'cart_value' => (float) $abandoned_cart->get('cart_value')->value,
        ]);
      }

      $abandoned_cart->set('recovery_sent', TRUE);
      $abandoned_cart->set('recovery_sent_at', date('Y-m-d\TH:i:s'));
      $abandoned_cart->save();

      $this->logger->info('Email de recuperacion enviado a @email para carrito @id', [
        '@email' => $email,
        '@id' => $abandoned_cart->get('cart_id')->target_id,
      ]);

      return TRUE;
    }
    catch (\Exception $e) {
      $this->logger->error('Error enviando email de recuperacion: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  public function recoverCart(string $token): ?object {
    $abandoned_storage = $this->entityTypeManager->getStorage('abandoned_cart');
    $ids = $abandoned_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('recovery_token', $token)
      ->condition('recovered', FALSE)
      ->range(0, 1)
      ->execute();

    if (!$ids) {
      return NULL;
    }

    $abandoned = $abandoned_storage->load(reset($ids));
    $cart_id = $abandoned->get('cart_id')->target_id;
    $cart = $this->entityTypeManager->getStorage('comercio_cart')->load($cart_id);

    if (!$cart) {
      return NULL;
    }

    $cart->set('status', 'active');
    $cart->save();

    $abandoned->set('recovered', TRUE);
    $abandoned->set('recovered_at', date('Y-m-d\TH:i:s'));
    $abandoned->save();

    $this->logger->info('Carrito @id recuperado via token.', ['@id' => $cart_id]);

    return $cart;
  }

  public function getRecoveryStats(int $tenant_id): array {
    $storage = $this->entityTypeManager->getStorage('abandoned_cart');

    try {
      $total = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->count()
        ->execute();

      $recovered = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('tenant_id', $tenant_id)
        ->condition('recovered', TRUE)
        ->count()
        ->execute();

      $result = $storage->getAggregateQuery()
        ->accessCheck(FALSE)
        ->aggregate('cart_value', 'SUM')
        ->condition('tenant_id', $tenant_id)
        ->condition('recovered', TRUE)
        ->execute();
      $recovered_value = (float) ($result[0]['cart_value_sum'] ?? 0);

      return [
        'total_abandoned' => $total,
        'total_recovered' => $recovered,
        'recovery_rate' => $total > 0 ? round(($recovered / $total) * 100, 1) : 0,
        'recovered_value' => $recovered_value,
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('Error obteniendo estadisticas de recuperacion: @e', ['@e' => $e->getMessage()]);
      return ['total_abandoned' => 0, 'total_recovered' => 0, 'recovery_rate' => 0, 'recovered_value' => 0];
    }
  }

}
