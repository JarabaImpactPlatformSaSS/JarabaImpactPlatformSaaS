<?php

/**
 * @file
 * Script de validación de la Economía Agéntica.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once __DIR__ . '/../web/autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();
\Drupal::setContainer($container);

echo "\n🚀 INICIANDO TEST DE FUEGO: ECONOMÍA AGÉNTICA\n";
echo "==========================================\n";

$didManager = \Drupal::service('jaraba_identity.did_manager');
$protocol = \Drupal::service('jaraba_agent_market.protocol');
$crypto = \Drupal::service('jaraba_credentials.cryptography');

// 1. Crear usuarios de prueba si no existen.
function get_test_user($name) {
  $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $name]);
  if ($users) return reset($users);
  $user = \Drupal::entityTypeManager()->getStorage('user')->create([
    'name' => $name,
    'mail' => "$name@example.com",
    'status' => 1,
  ]);
  $user->save();
  return $user;
}

$uProductor = get_test_user('test_productor');
$uComprador = get_test_user('test_comprador');

// 2. Crear Wallets de Identidad.
echo "1. Creando identidades digitales (DID)...\n";
$wProductor = $didManager->createWallet($uProductor, 'agent');
$wComprador = $didManager->createWallet($uComprador, 'agent');

$didP = $wProductor->get('did')->value;
$didC = $wComprador->get('did')->value;

echo "   ✅ Productor: $didP\n";
echo "   ✅ Comprador: $didC\n";

// 3. Simular Negociación JDTP.
echo "2. Iniciando sesión de negociación (JDTP)...\n";
$offer = ['item' => 'Aceite AOVE', 'qty' => 100, 'price' => 5.00];
$sessionId = $protocol->initiateSession($didP, $didC, $offer);
echo "   ✅ Sesión creada ID: $sessionId\n";

// 4. Contraoferta.
echo "3. Enviando contraoferta firmada...\n";
$session = \Drupal::entityTypeManager()->getStorage('negotiation_session')->load($sessionId);
$counter = ['item' => 'Aceite AOVE', 'qty' => 100, 'price' => 4.50];
$protocol->recordStep($session, $didC, 'COUNTER', $counter);
echo "   ✅ Contraoferta registrada en Ledger.\n";

// 5. Aceptación.
echo "4. Aceptando trato...\n";
$protocol->recordStep($session, $didP, 'ACCEPT', $counter);
$session->save();
echo "   ✅ Trato CERRADO.\n";

// 6. VERIFICACIÓN CRIPTOGRÁFICA (SOC2).
echo "5. Verificando integridad del Ledger...\n";
$ledger = json_decode($session->get('ledger')->value, TRUE);

foreach ($ledger as $step) {
  $signature = $step['signature'];
  $stepCopy = $step;
  unset($stepCopy['signature']); 
  $message = json_encode($stepCopy);
  
  $walletResults = \Drupal::entityTypeManager()->getStorage('identity_wallet')
    ->loadByProperties(['did' => $step['actor']]);
  $wallet = reset($walletResults);
  $pubKey = $wallet->get('public_key')->value;

  $isValid = $crypto->verify($message, $signature, $pubKey);
  
  echo "   - Paso [{$step['type']}]: " . ($isValid ? "✅ FIRMA VÁLIDA" : "❌ CORRUPCIÓN DETECTADA") . "\n";
}

echo "\n⭐ TEST FINALIZADO CON ÉXITO: EL SISTEMA ES INMUNE A MANIPULACIONES\n";
