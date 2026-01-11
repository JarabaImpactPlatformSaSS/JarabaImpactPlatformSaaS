<?php
// Script para habilitar settings.local.php en producción IONOS
// Uso: php8.4-cli fix_settings.php

$settings_file = __DIR__ . '/web/sites/default/settings.php';
$content = file_get_contents($settings_file);

// Descomentar el bloque de settings.local.php
$content = str_replace(
    "# if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {\n#   include \$app_root . '/' . \$site_path . '/settings.local.php';\n# }",
    "if (file_exists(\$app_root . '/' . \$site_path . '/settings.local.php')) {\n  include \$app_root . '/' . \$site_path . '/settings.local.php';\n}",
    $content
);

// Comentar el bloque de base de datos Lando (host = 'database')
$content = preg_replace(
    '/\$databases\[\'default\'\]\[\'default\'\] = array \([\s\S]*?\'host\' => \'database\',[\s\S]*?\);/m',
    "// Lando database config commented out for production\n// \$databases block removed",
    $content
);

file_put_contents($settings_file, $content);
echo "settings.php updated successfully!\n";
