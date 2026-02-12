<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ecosistema_jaraba_core\Entity\PushSubscription;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Servicio de notificaciones push de la plataforma.
 *
 * PROPÓSITO:
 * Gestiona suscripciones y envío de notificaciones push a los navegadores
 * de los usuarios mediante el protocolo Web Push (RFC 8030) con
 * autenticación VAPID (RFC 8292).
 *
 * FUNCIONALIDAD:
 * - Registrar/desregistrar suscripciones push de navegadores.
 * - Enviar notificaciones a usuarios individuales o tenants completos.
 * - Cifrado de payload según RFC 8291 (Content-Encoding: aes128gcm).
 * - Limpieza automática de suscripciones obsoletas (endpoints caídos).
 *
 * DEPENDENCIAS:
 * - entity_type.manager: Gestión de entidades PushSubscription.
 * - http_client: Cliente HTTP Guzzle para envío de notificaciones.
 * - config.factory: Acceso a configuración VAPID.
 *
 * PHASE 5 - G109-3: Push Notifications
 */
class PlatformPushService {

  /**
   * Número máximo de fallos antes de eliminar una suscripción.
   */
  protected const MAX_FAILURES = 3;

  /**
   * TTL por defecto para notificaciones push (en segundos).
   */
  protected const DEFAULT_TTL = 86400;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Gestor de tipos de entidad.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Cliente HTTP para envío de notificaciones.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger para registro de eventos.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Factoría de configuración para claves VAPID.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ClientInterface $httpClient,
    protected LoggerInterface $logger,
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Crea una suscripción push para un usuario.
   *
   * Registra el endpoint del navegador y las claves criptográficas
   * necesarias para el envío de notificaciones push cifradas.
   *
   * @param int $userId
   *   ID del usuario que se suscribe.
   * @param array $subscriptionData
   *   Datos de la suscripción del navegador (Push API):
   *   - endpoint: URL del push service del navegador.
   *   - keys.auth: Clave de autenticación (base64url).
   *   - keys.p256dh: Clave pública P-256 DH (base64url).
   *   - user_agent: (opcional) User Agent del navegador.
   *   - tenant_id: (opcional) ID del tenant asociado.
   *
   * @return \Drupal\ecosistema_jaraba_core\Entity\PushSubscription
   *   La entidad de suscripción creada.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Si falla la creación de la entidad.
   */
  public function subscribe(int $userId, array $subscriptionData): PushSubscription {
    $storage = $this->entityTypeManager->getStorage('push_subscription');

    // Verificar si ya existe una suscripción para este endpoint.
    $existing = $storage->loadByProperties([
      'user_id' => $userId,
      'endpoint' => $subscriptionData['endpoint'],
    ]);

    if (!empty($existing)) {
      $subscription = reset($existing);
      // Reactivar si estaba desactivada y actualizar claves.
      $subscription->set('active', TRUE);
      $subscription->set('auth_key', $subscriptionData['keys']['auth'] ?? '');
      $subscription->set('p256dh_key', $subscriptionData['keys']['p256dh'] ?? '');
      if (!empty($subscriptionData['user_agent'])) {
        $subscription->set('user_agent', $subscriptionData['user_agent']);
      }
      $subscription->save();

      $this->logger->info('Suscripción push reactivada para usuario @uid', [
        '@uid' => $userId,
      ]);

      return $subscription;
    }

    // Crear nueva suscripción.
    $values = [
      'user_id' => $userId,
      'endpoint' => $subscriptionData['endpoint'],
      'auth_key' => $subscriptionData['keys']['auth'] ?? '',
      'p256dh_key' => $subscriptionData['keys']['p256dh'] ?? '',
      'user_agent' => $subscriptionData['user_agent'] ?? '',
      'active' => TRUE,
    ];

    if (!empty($subscriptionData['tenant_id'])) {
      $values['tenant_id'] = $subscriptionData['tenant_id'];
    }

    /** @var \Drupal\ecosistema_jaraba_core\Entity\PushSubscription $subscription */
    $subscription = $storage->create($values);
    $subscription->save();

    $this->logger->info('Nueva suscripción push creada para usuario @uid (endpoint: @endpoint)', [
      '@uid' => $userId,
      '@endpoint' => substr($subscriptionData['endpoint'], 0, 80) . '...',
    ]);

    return $subscription;
  }

  /**
   * Desactiva una suscripción push por endpoint.
   *
   * @param int $userId
   *   ID del usuario propietario de la suscripción.
   * @param string $endpoint
   *   URL del endpoint push a desactivar.
   *
   * @return bool
   *   TRUE si se desactivó correctamente, FALSE si no se encontró.
   */
  public function unsubscribe(int $userId, string $endpoint): bool {
    $storage = $this->entityTypeManager->getStorage('push_subscription');
    $subscriptions = $storage->loadByProperties([
      'user_id' => $userId,
      'endpoint' => $endpoint,
    ]);

    if (empty($subscriptions)) {
      $this->logger->warning('No se encontró suscripción push para usuario @uid con endpoint dado.', [
        '@uid' => $userId,
      ]);
      return FALSE;
    }

    foreach ($subscriptions as $subscription) {
      $subscription->deactivate();
      $subscription->save();
    }

    $this->logger->info('Suscripción push desactivada para usuario @uid.', [
      '@uid' => $userId,
    ]);

    return TRUE;
  }

  /**
   * Envía una notificación push a todas las suscripciones activas de un usuario.
   *
   * @param int $userId
   *   ID del usuario destinatario.
   * @param string $title
   *   Título de la notificación.
   * @param string $body
   *   Cuerpo del mensaje.
   * @param array $data
   *   Datos adicionales para la notificación (e.g. url, icon, badge).
   *
   * @return int
   *   Número de notificaciones enviadas con éxito.
   */
  public function sendToUser(int $userId, string $title, string $body, array $data = []): int {
    $storage = $this->entityTypeManager->getStorage('push_subscription');
    $subscriptions = $storage->loadByProperties([
      'user_id' => $userId,
      'active' => TRUE,
    ]);

    if (empty($subscriptions)) {
      return 0;
    }

    $payload = [
      'title' => $title,
      'body' => $body,
      'data' => $data,
      'timestamp' => time(),
    ];

    $sent = 0;
    foreach ($subscriptions as $subscription) {
      if ($this->sendNotification($subscription, $payload)) {
        $sent++;
      }
    }

    $this->logger->info('Notificación push enviada a usuario @uid: @sent/@total exitosas.', [
      '@uid' => $userId,
      '@sent' => $sent,
      '@total' => count($subscriptions),
    ]);

    return $sent;
  }

  /**
   * Envía una notificación push a todos los usuarios de un tenant.
   *
   * @param int $tenantId
   *   ID del tenant (grupo).
   * @param string $title
   *   Título de la notificación.
   * @param string $body
   *   Cuerpo del mensaje.
   * @param array $data
   *   Datos adicionales para la notificación.
   *
   * @return int
   *   Número total de notificaciones enviadas con éxito.
   */
  public function sendToTenant(int $tenantId, string $title, string $body, array $data = []): int {
    $storage = $this->entityTypeManager->getStorage('push_subscription');
    $subscriptions = $storage->loadByProperties([
      'tenant_id' => $tenantId,
      'active' => TRUE,
    ]);

    if (empty($subscriptions)) {
      return 0;
    }

    $payload = [
      'title' => $title,
      'body' => $body,
      'data' => $data,
      'timestamp' => time(),
    ];

    $sent = 0;
    foreach ($subscriptions as $subscription) {
      if ($this->sendNotification($subscription, $payload)) {
        $sent++;
      }
    }

    $this->logger->info('Notificación push enviada a tenant @tid: @sent/@total exitosas.', [
      '@tid' => $tenantId,
      '@sent' => $sent,
      '@total' => count($subscriptions),
    ]);

    return $sent;
  }

  /**
   * Envía una notificación push a una suscripción específica.
   *
   * Implementa el protocolo Web Push (RFC 8030) con autenticación
   * VAPID (RFC 8292). El payload se cifra usando AES-128-GCM
   * según RFC 8291.
   *
   * @param \Drupal\ecosistema_jaraba_core\Entity\PushSubscription $subscription
   *   La suscripción destino.
   * @param array $payload
   *   Datos de la notificación (title, body, data, etc.).
   *
   * @return bool
   *   TRUE si la notificación se envió correctamente, FALSE en caso contrario.
   */
  public function sendNotification(PushSubscription $subscription, array $payload): bool {
    $endpoint = $subscription->getEndpoint();

    if (empty($endpoint)) {
      $this->logger->warning('Suscripción @id tiene endpoint vacío.', [
        '@id' => $subscription->id(),
      ]);
      return FALSE;
    }

    try {
      $vapidKeys = $this->getVapidKeys();

      if (empty($vapidKeys['public_key']) || empty($vapidKeys['private_key'])) {
        $this->logger->error('Claves VAPID no configuradas. No se puede enviar notificación push.');
        return FALSE;
      }

      // Construir headers VAPID según RFC 8292.
      $audience = $this->extractAudience($endpoint);
      $vapidHeaders = $this->buildVapidHeaders($vapidKeys, $audience);

      // Cifrar el payload según RFC 8291 (Content-Encoding: aes128gcm).
      $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
      $encryptedPayload = $this->encryptPayload(
        $payloadJson,
        $subscription->getP256dhKey(),
        $subscription->getAuthKey()
      );

      // Enviar la notificación via HTTP POST al endpoint push.
      $response = $this->httpClient->request('POST', $endpoint, [
        'headers' => array_merge($vapidHeaders, [
          'Content-Type' => 'application/octet-stream',
          'Content-Encoding' => 'aes128gcm',
          'TTL' => (string) self::DEFAULT_TTL,
        ]),
        'body' => $encryptedPayload,
        'timeout' => 10,
        'http_errors' => TRUE,
      ]);

      $statusCode = $response->getStatusCode();

      // 201 Created = notificación aceptada por el push service.
      if ($statusCode === 201 || $statusCode === 200) {
        return TRUE;
      }

      // 410 Gone = suscripción expirada, desactivar.
      if ($statusCode === 410) {
        $subscription->deactivate();
        $subscription->save();
        $this->logger->info('Suscripción @id desactivada: endpoint expirado (410 Gone).', [
          '@id' => $subscription->id(),
        ]);
        return FALSE;
      }

      $this->logger->warning('Respuesta inesperada del push service: @code para suscripción @id.', [
        '@code' => $statusCode,
        '@id' => $subscription->id(),
      ]);

      return FALSE;
    }
    catch (RequestException $e) {
      $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0;

      // 404 o 410 = endpoint ya no válido.
      if (in_array($statusCode, [404, 410], TRUE)) {
        $subscription->deactivate();
        $subscription->save();
        $this->logger->info('Suscripción @id desactivada: endpoint no válido (@code).', [
          '@id' => $subscription->id(),
          '@code' => $statusCode,
        ]);
      }
      else {
        $this->logger->error('Error enviando notificación push a suscripción @id: @error', [
          '@id' => $subscription->id(),
          '@error' => $e->getMessage(),
        ]);
      }

      return FALSE;
    }
    catch (\Exception $e) {
      $this->logger->error('Excepción inesperada al enviar push a suscripción @id: @error', [
        '@id' => $subscription->id(),
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Obtiene las claves VAPID desde la configuración.
   *
   * @return array
   *   Array con claves:
   *   - public_key: Clave pública VAPID (base64url).
   *   - private_key: Clave privada VAPID (base64url).
   *   - subject: Identificador del emisor (mailto: o URL).
   */
  public function getVapidKeys(): array {
    $config = $this->configFactory->get('ecosistema_jaraba_core.push_settings');

    return [
      'public_key' => $config->get('vapid_public_key') ?? '',
      'private_key' => $config->get('vapid_private_key') ?? '',
      'subject' => $config->get('vapid_subject') ?? 'mailto:admin@plataformadeecosistemas.es',
    ];
  }

  /**
   * Elimina suscripciones que han fallado repetidamente.
   *
   * Busca suscripciones inactivas (desactivadas por fallos en el envío)
   * y las elimina permanentemente de la base de datos.
   *
   * @return int
   *   Número de suscripciones eliminadas.
   */
  public function cleanupStaleSubscriptions(): int {
    $storage = $this->entityTypeManager->getStorage('push_subscription');

    // Cargar suscripciones inactivas (desactivadas por fallos).
    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('active', FALSE);

    $ids = $query->execute();

    if (empty($ids)) {
      return 0;
    }

    $subscriptions = $storage->loadMultiple($ids);
    $count = count($subscriptions);
    $storage->delete($subscriptions);

    $this->logger->info('Limpieza de suscripciones push: @count eliminadas.', [
      '@count' => $count,
    ]);

    return $count;
  }

  /**
   * Extrae la audiencia (origin) de una URL de endpoint.
   *
   * @param string $endpoint
   *   URL del endpoint push.
   *
   * @return string
   *   El origin (scheme + host) del endpoint.
   */
  protected function extractAudience(string $endpoint): string {
    $parsed = parse_url($endpoint);
    return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '');
  }

  /**
   * Construye los headers de autenticación VAPID (RFC 8292).
   *
   * Genera un JWT firmado con la clave privada VAPID para
   * autenticar el servidor ante el push service.
   *
   * @param array $vapidKeys
   *   Claves VAPID (public_key, private_key, subject).
   * @param string $audience
   *   Origin del push service.
   *
   * @return array
   *   Headers HTTP para la autenticación VAPID.
   */
  protected function buildVapidHeaders(array $vapidKeys, string $audience): array {
    // Construir JWT header.
    $header = $this->base64UrlEncode(json_encode([
      'typ' => 'JWT',
      'alg' => 'ES256',
    ]));

    // Construir JWT payload con claims VAPID.
    $jwtPayload = $this->base64UrlEncode(json_encode([
      'aud' => $audience,
      'exp' => time() + 43200, // 12 horas.
      'sub' => $vapidKeys['subject'],
    ]));

    // Firmar con ECDSA P-256 (ES256).
    $signingInput = $header . '.' . $jwtPayload;
    $signature = $this->signWithEcdsa($signingInput, $vapidKeys['private_key']);
    $jwt = $signingInput . '.' . $signature;

    return [
      'Authorization' => 'vapid t=' . $jwt . ', k=' . $vapidKeys['public_key'],
    ];
  }

  /**
   * Firma datos con ECDSA P-256 (ES256).
   *
   * @param string $data
   *   Datos a firmar.
   * @param string $privateKeyBase64
   *   Clave privada VAPID en base64url.
   *
   * @return string
   *   Firma en base64url.
   */
  protected function signWithEcdsa(string $data, string $privateKeyBase64): string {
    // Decodificar la clave privada desde base64url.
    $privateKeyRaw = $this->base64UrlDecode($privateKeyBase64);

    // Construir la clave PEM desde los bytes crudos de la clave privada.
    // La clave VAPID es un escalar EC de 32 bytes para la curva P-256.
    $derPrefix = hex2bin(
      '30770201010420' // SEQUENCE, INTEGER version=1, OCTET STRING 32 bytes
    );
    $derSuffix = hex2bin(
      'a00a06082a8648ce3d030107' // [0] OID prime256v1
    );
    $der = $derPrefix . $privateKeyRaw . $derSuffix;
    $pem = "-----BEGIN EC PRIVATE KEY-----\n"
      . chunk_split(base64_encode($der), 64, "\n")
      . "-----END EC PRIVATE KEY-----";

    $key = openssl_pkey_get_private($pem);
    if (!$key) {
      $this->logger->error('No se pudo cargar la clave privada VAPID para firmado ECDSA.');
      return '';
    }

    $signature = '';
    openssl_sign($data, $signature, $key, OPENSSL_ALGO_SHA256);

    // Convertir la firma DER de OpenSSL al formato raw R||S (64 bytes).
    $rawSignature = $this->derSignatureToRaw($signature);

    return $this->base64UrlEncode($rawSignature);
  }

  /**
   * Cifra el payload de la notificación según RFC 8291.
   *
   * Utiliza Content-Encoding: aes128gcm para cifrar el contenido
   * destinado al endpoint push del suscriptor.
   *
   * @param string $payload
   *   Payload en texto plano (JSON).
   * @param string $userPublicKey
   *   Clave pública P-256 del suscriptor (base64url).
   * @param string $userAuthToken
   *   Token de autenticación del suscriptor (base64url).
   *
   * @return string
   *   Payload cifrado para envío al push service.
   */
  protected function encryptPayload(string $payload, string $userPublicKey, string $userAuthToken): string {
    $userPublicKeyBytes = $this->base64UrlDecode($userPublicKey);
    $userAuthBytes = $this->base64UrlDecode($userAuthToken);

    // Generar clave ECDH efímera para el servidor.
    $serverKey = openssl_pkey_new([
      'curve_name' => 'prime256v1',
      'private_key_type' => OPENSSL_KEYTYPE_EC,
    ]);

    $serverKeyDetails = openssl_pkey_get_details($serverKey);
    $serverPublicKey = $this->extractUncompressedPublicKey($serverKeyDetails);

    // Derivar secreto compartido ECDH.
    $sharedSecret = $this->computeEcdhSharedSecret($serverKey, $userPublicKeyBytes);

    // Generar salt aleatorio de 16 bytes.
    $salt = random_bytes(16);

    // Derivar claves de cifrado usando HKDF (RFC 5869).
    // PRK = HKDF-Extract(auth_secret, shared_secret).
    $prk = hash_hmac('sha256', $sharedSecret, $userAuthBytes, TRUE);

    // IKM = HKDF-Expand(PRK, "WebPush: info\0" || ua_public || as_public, 32).
    $keyInfo = "WebPush: info\0" . $userPublicKeyBytes . $serverPublicKey;
    $ikm = $this->hkdfExpand($prk, $keyInfo, 32);

    // Derivar clave y nonce finales.
    $prkFinal = hash_hmac('sha256', $ikm, $salt, TRUE);
    $contentKey = $this->hkdfExpand($prkFinal, "Content-Encoding: aes128gcm\0", 16);
    $nonce = $this->hkdfExpand($prkFinal, "Content-Encoding: nonce\0", 12);

    // Añadir padding (1 byte de delimitador + 0 bytes de padding).
    $paddedPayload = $payload . "\x02";

    // Cifrar con AES-128-GCM.
    $tag = '';
    $encrypted = openssl_encrypt(
      $paddedPayload,
      'aes-128-gcm',
      $contentKey,
      OPENSSL_RAW_DATA,
      $nonce,
      $tag,
      '',
      16
    );

    // Construir header aes128gcm: salt(16) + rs(4) + idlen(1) + keyid(65).
    $recordSize = pack('N', 4096);
    $keyIdLen = chr(strlen($serverPublicKey));
    $header = $salt . $recordSize . $keyIdLen . $serverPublicKey;

    return $header . $encrypted . $tag;
  }

  /**
   * Extrae la clave pública sin comprimir de los detalles de clave OpenSSL.
   *
   * @param array $keyDetails
   *   Detalles de la clave devueltos por openssl_pkey_get_details().
   *
   * @return string
   *   Clave pública sin comprimir (65 bytes: 0x04 || X || Y).
   */
  protected function extractUncompressedPublicKey(array $keyDetails): string {
    $x = str_pad($keyDetails['ec']['x'], 32, "\0", STR_PAD_LEFT);
    $y = str_pad($keyDetails['ec']['y'], 32, "\0", STR_PAD_LEFT);
    return "\x04" . $x . $y;
  }

  /**
   * Calcula el secreto compartido ECDH.
   *
   * @param \OpenSSLAsymmetricKey $serverKey
   *   Clave privada del servidor.
   * @param string $userPublicKeyBytes
   *   Clave pública del suscriptor (sin comprimir, 65 bytes).
   *
   * @return string
   *   Secreto compartido ECDH.
   */
  protected function computeEcdhSharedSecret($serverKey, string $userPublicKeyBytes): string {
    // Construir clave PEM pública del usuario a partir de los bytes crudos.
    $derHeader = hex2bin(
      '3059301306072a8648ce3d020106082a8648ce3d030107034200'
    );
    $der = $derHeader . $userPublicKeyBytes;
    $pem = "-----BEGIN PUBLIC KEY-----\n"
      . chunk_split(base64_encode($der), 64, "\n")
      . "-----END PUBLIC KEY-----";

    $peerKey = openssl_pkey_get_public($pem);
    if (!$peerKey) {
      throw new \RuntimeException('No se pudo cargar la clave pública del suscriptor.');
    }

    // openssl_pkey_derive disponible en PHP 7.3+.
    $sharedSecret = openssl_pkey_derive($peerKey, $serverKey);
    if ($sharedSecret === FALSE) {
      throw new \RuntimeException('Fallo en la derivación ECDH del secreto compartido.');
    }

    return $sharedSecret;
  }

  /**
   * Implementa HKDF-Expand (RFC 5869).
   *
   * @param string $prk
   *   Pseudorandom key.
   * @param string $info
   *   Información contextual.
   * @param int $length
   *   Longitud deseada del output.
   *
   * @return string
   *   Material de clave derivado.
   */
  protected function hkdfExpand(string $prk, string $info, int $length): string {
    $output = '';
    $t = '';
    $counter = 1;

    while (strlen($output) < $length) {
      $t = hash_hmac('sha256', $t . $info . chr($counter), $prk, TRUE);
      $output .= $t;
      $counter++;
    }

    return substr($output, 0, $length);
  }

  /**
   * Convierte una firma DER de OpenSSL al formato raw R||S.
   *
   * @param string $derSignature
   *   Firma en formato DER de OpenSSL.
   *
   * @return string
   *   Firma en formato raw (R || S, 64 bytes total).
   */
  protected function derSignatureToRaw(string $derSignature): string {
    $offset = 2;

    // Leer R.
    $rLength = ord($derSignature[$offset + 1]);
    $r = substr($derSignature, $offset + 2, $rLength);
    $offset += 2 + $rLength;

    // Leer S.
    $sLength = ord($derSignature[$offset + 1]);
    $s = substr($derSignature, $offset + 2, $sLength);

    // Normalizar a 32 bytes cada uno (eliminar leading zeros o padding).
    $r = str_pad(ltrim($r, "\0"), 32, "\0", STR_PAD_LEFT);
    $s = str_pad(ltrim($s, "\0"), 32, "\0", STR_PAD_LEFT);

    return $r . $s;
  }

  /**
   * Codifica datos en base64url (sin padding).
   *
   * @param string $data
   *   Datos a codificar.
   *
   * @return string
   *   Cadena codificada en base64url.
   */
  protected function base64UrlEncode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
  }

  /**
   * Decodifica datos desde base64url.
   *
   * @param string $data
   *   Cadena codificada en base64url.
   *
   * @return string
   *   Datos decodificados.
   */
  protected function base64UrlDecode(string $data): string {
    $padded = $data . str_repeat('=', (4 - strlen($data) % 4) % 4);
    return base64_decode(strtr($padded, '-_', '+/'));
  }

}
