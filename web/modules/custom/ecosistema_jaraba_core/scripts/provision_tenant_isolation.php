<?php
/**
 * @file
 * Script para aprovisionar Group/Domain a Tenants existentes.
 *
 * Uso: lando drush php:script provision_tenant_isolation.php
 */

use Drupal\group\Entity\Group;
use Drupal\domain\Entity\Domain;

$tenantStorage = \Drupal::entityTypeManager()->getStorage('tenant');
$groupStorage = \Drupal::entityTypeManager()->getStorage('group');
$domainStorage = \Drupal::entityTypeManager()->getStorage('domain');

// Cargar tenant 2 (Academia Talento Digital)
$tenant = $tenantStorage->load(2);

if (!$tenant) {
    echo "Tenant 2 no encontrado\n";
    return;
}

echo "Tenant: " . $tenant->getName() . "\n";
echo "Domain field: " . $tenant->getDomain() . "\n";
echo "Group ID: " . ($tenant->get('group_id')->target_id ?? 'NULL') . "\n";
echo "Domain ID: " . ($tenant->get('domain_id')->target_id ?? 'NULL') . "\n";

// Crear Group si no existe
if (!$tenant->get('group_id')->target_id) {
    echo "\nCreando Group...\n";

    $group = $groupStorage->create([
        'type' => 'tenant',
        'label' => $tenant->getName(),
    ]);
    $group->save();

    // Add admin user as member
    $adminUser = $tenant->getAdminUser();
    if ($adminUser) {
        $group->addMember($adminUser);
        echo "Admin user añadido como miembro\n";
    }

    // Update tenant
    $tenant->set('group_id', $group->id());
    echo "Group creado: ID " . $group->id() . "\n";
}

// Crear Domain si no existe
if (!$tenant->get('domain_id')->target_id) {
    $hostname = $tenant->getDomain();

    if ($hostname) {
        echo "\nCreando Domain...\n";

        // Normalizar hostname
        if (strpos($hostname, '.') === FALSE) {
            $hostname = $hostname . '.jaraba-saas.lndo.site';
        }

        $domainId = preg_replace('/[^a-z0-9_]/', '_', strtolower($hostname));

        // Check if domain already exists
        $existingDomain = $domainStorage->load($domainId);
        if (!$existingDomain) {
            $domain = $domainStorage->create([
                'id' => $domainId,
                'hostname' => $hostname,
                'name' => $tenant->getName(),
                'scheme' => 'https',
                'status' => TRUE,
                'weight' => 0,
                'is_default' => FALSE,
            ]);
            $domain->save();
            echo "Domain creado: " . $hostname . " (ID: " . $domainId . ")\n";
        } else {
            $domain = $existingDomain;
            echo "Domain ya existía: " . $hostname . "\n";
        }

        $tenant->set('domain_id', $domain->id());
    }
}

// Guardar cambios
$tenant->save();
echo "\nTenant actualizado!\n";
echo "Group ID: " . $tenant->get('group_id')->target_id . "\n";
echo "Domain ID: " . $tenant->get('domain_id')->target_id . "\n";
