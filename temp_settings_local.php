<?php
// Settings.local.php - IONOS Production
// DO NOT COMMIT TO GIT

$databases['default']['default'] = [
    'database' => 'dbs14934629',
    'username' => 'dbu360732',
    'password' => 'Pe@06Ja#11Mu$2025_ped_[v%Tf9!zK$4#pL&j*]',
    'host' => 'db5018953276.hosting-data.io',
    'port' => '3306',
    'driver' => 'mysql',
    'prefix' => '',
    'collation' => 'utf8mb4_general_ci',
];

$settings['hash_salt'] = 'jaraba_ionos_production_2026_saas_platform_secure_salt_key';

$settings['trusted_host_patterns'] = [
    '^plataformadeecosistemas\.com$',
    '^.+\.plataformadeecosistemas\.com$',
    '^access834313033\.webspace-data\.io$',
];

$settings['file_private_path'] = '../private';
$settings['file_temp_path'] = '/tmp';
$settings['config_sync_directory'] = '../config/sync';

$config['system.performance']['css']['preprocess'] = TRUE;
$config['system.performance']['js']['preprocess'] = TRUE;
$config['system.logging']['error_level'] = 'hide';
