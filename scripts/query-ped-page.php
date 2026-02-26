<?php
/**
 * Query the PageContent entity for PED meta-site.
 * Body class indicates tenant-7, so let's find it.
 * Run: lando ssh -c 'php scripts/query-ped-page.php'
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once '/app/web/autoload.php';
$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod');
$kernel->boot();
$container = $kernel->getContainer();
$container->get('request_stack')->push($request);

echo "=== PED Meta-Site PageContent Query ===\n\n";

$storage = \Drupal::entityTypeManager()->getStorage('page_content');

// List all page_content entities
$ids = $storage->getQuery()->accessCheck(FALSE)->execute();
echo "Total page_content entities: " . count($ids) . "\n\n";

foreach ($storage->loadMultiple($ids) as $entity) {
  $id = $entity->id();
  $label = $entity->label();
  $alias = '';
  try {
    $alias = \Drupal::service('path_alias.manager')
      ->getAliasByPath('/page/' . $id, 'es');
  } catch (\Exception $e) {
    $alias = 'N/A';
  }

  // Check tenant_id field
  $tenantId = $entity->hasField('tenant_id') ? $entity->get('tenant_id')->value : 'N/A';
  $status = $entity->hasField('status') ? ($entity->get('status')->value ? 'Published' : 'Draft') : 'N/A';
  $isMeta = $entity->hasField('is_meta_site') ? ($entity->get('is_meta_site')->value ? 'YES' : 'NO') : 'N/A';

  // Get HTML length
  $htmlField = '';
  foreach (['html_content', 'compiled_html', 'body', 'content_html'] as $f) {
    if ($entity->hasField($f) && !$entity->get($f)->isEmpty()) {
      $htmlField = $f;
      break;
    }
  }
  $htmlLen = $htmlField ? strlen($entity->get($htmlField)->value ?? '') : 0;

  echo "ID: $id | Label: $label | Tenant: $tenantId | Meta: $isMeta | Status: $status | Alias: $alias | HTML: {$htmlLen}b\n";

  // For the PED meta-site, show more details
  if (stripos($label, 'PED') !== false || stripos($label, 'plataforma') !== false || stripos($label, 'ecosistema') !== false || $tenantId == 7) {
    echo "  >>> PED META-SITE FOUND! Dumping fields:\n";
    foreach ($entity->getFields() as $fieldName => $field) {
      $val = $field->value ?? '';
      if (is_string($val) && strlen($val) > 200) {
        $val = substr($val, 0, 200) . '... (' . strlen($field->value) . ' total)';
      }
      if ($val !== '' && $val !== NULL && $val !== 0) {
        echo "    $fieldName: $val\n";
      }
    }
    echo "\n";
  }
}
