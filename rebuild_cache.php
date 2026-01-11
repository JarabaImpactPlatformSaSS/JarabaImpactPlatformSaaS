<?php
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

chdir('web');
$autoloader = require_once 'autoload.php';

$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();

require_once $kernel->getAppRoot() . '/core/includes/utility.inc';

echo "Starting cache rebuild...\n";
drupal_rebuild($kernel->getContainer()->get('class_loader'), $request);
echo "Cache rebuild complete!\n";
