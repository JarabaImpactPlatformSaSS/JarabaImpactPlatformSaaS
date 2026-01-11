<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';

try {
    $request = Request::createFromGlobals();
    $kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
    $kernel->boot();

    // Incluir bootstrap.inc para tener acceso a drupal_flush_all_caches()
    require_once \Drupal::root() . '/core/includes/bootstrap.inc';
    require_once \Drupal::root() . '/core/includes/common.inc';
    require_once \Drupal::root() . '/core/includes/install.inc';

    drupal_flush_all_caches();
    echo 'SUCCESS: All caches flushed and registry rebuilt.';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
