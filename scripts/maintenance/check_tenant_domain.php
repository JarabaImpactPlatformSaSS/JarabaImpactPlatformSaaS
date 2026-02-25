<?php
$etm = \Drupal::entityTypeManager();

echo "=== TENANT ENTITIES ===\n";
$tenants = $etm->getStorage('tenant')->loadMultiple();
foreach ($tenants as $t) {
  $id = $t->id();
  $label = $t->label();
  $domain = $t->get('domain')->value ?? 'NULL';
  $domain_id = '';
  if ($t->hasField('domain_id')) {
    $domain_id = $t->get('domain_id')->target_id ?? 'NULL';
  }
  $admin_user = '';
  if ($t->hasField('admin_user')) {
    $admin_user = $t->get('admin_user')->target_id ?? 'NULL';
  }
  $group_id = '';
  if ($t->hasField('group_id')) {
    $group_id = $t->get('group_id')->target_id ?? 'NULL';
  }
  echo "Tenant {$id}: {$label}\n";
  echo "  domain: {$domain}\n";
  echo "  domain_id: {$domain_id}\n";
  echo "  admin_user: {$admin_user}\n";
  echo "  group_id: {$group_id}\n\n";
}

echo "=== DOMAIN ACCESS ENTITIES ===\n";
if ($etm->hasDefinition('domain')) {
  $domains = $etm->getStorage('domain')->loadMultiple();
  foreach ($domains as $d) {
    $id = $d->id();
    $hostname = $d->getHostname();
    $name = $d->label();
    $is_default = $d->isDefault() ? 'YES' : 'NO';
    $status = $d->status() ? 'active' : 'inactive';
    echo "Domain {$id}: {$name}\n";
    echo "  hostname: {$hostname}\n";
    echo "  default: {$is_default} | status: {$status}\n\n";
  }
} else {
  echo "Domain entity type NOT installed\n";
}

echo "=== DRUPAL SETTINGS (base domain) ===\n";
$settings = \Drupal\Core\Site\Settings::getAll();
$base_domain = $settings['jaraba_base_domain'] ?? 'NOT SET';
echo "jaraba_base_domain: {$base_domain}\n";

echo "\n=== SITE BUILDER ROUTES CHECK ===\n";
$route_provider = \Drupal::service('router.route_provider');
$routes_to_check = [
  'jaraba_site_builder.frontend',
  'jaraba_site_builder.frontend.page',
  'jaraba_site_builder.page.view',
  'jaraba_site_builder.page_content.canonical',
  'entity.page_content.canonical',
];
foreach ($routes_to_check as $r) {
  try {
    $route = $route_provider->getRouteByName($r);
    echo "Route {$r}: " . $route->getPath() . "\n";
  } catch (\Exception $e) {
    echo "Route {$r}: NOT FOUND\n";
  }
}

echo "\n=== EVENT SUBSCRIBERS (domain-related) ===\n";
$container = \Drupal::getContainer();
$dispatcher = $container->get('event_dispatcher');
$listeners = $dispatcher->getListeners('kernel.request');
foreach ($listeners as $listener) {
  if (is_array($listener) && is_object($listener[0])) {
    $class = get_class($listener[0]);
    if (stripos($class, 'domain') !== FALSE || stripos($class, 'tenant') !== FALSE || stripos($class, 'site_builder') !== FALSE) {
      echo "  " . $class . "::" . $listener[1] . "\n";
    }
  }
}
