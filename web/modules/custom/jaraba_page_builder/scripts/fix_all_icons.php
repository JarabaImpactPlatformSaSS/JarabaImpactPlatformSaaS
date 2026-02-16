<?php
/**
 * Script para auditar y corregir iconos inválidos en templates.
 * 
 * Lista de iconos válidos en ui/:
 * alert-triangle, arrow-*, badge-check, bell, bolt, book, book-open, brain,
 * building, calendar, calendar-plus, check, check-circle, chevron-*, clipboard,
 * clock, code, cog, database, document, download, edit, eye, file, file-signature,
 * filter, fire, folder, gamepad, globe, graduation, heart, heartbeat, help-circle,
 * hospital, inbox, info, layout-grid, link, list, location, lock, mail, map,
 * map-pin, medal, menu, message, minus, package, party, pause, pin, play,
 * play-circle, plug, pointer, qr-code, question, quote, search, send, settings,
 * shield, shopping-cart, sitemap, star, storefront, tools, trophy, user, users,
 * video, warning, webhook, wrench, x
 */

// Mapeado de iconos inválidos a válidos
$icon_map = [
  // Iconos que no existen -> reemplazo
  'rocket' => 'bolt',
  'layers' => 'layout-grid',
  'target' => 'star',
  'progress' => 'fire',
  'zap' => 'bolt',
  'chart-line' => 'fire',
  'briefcase' => 'cog',
  'team' => 'users',
  'slack' => 'link',
  'github' => 'code',
  'google' => 'globe',
  'stripe' => 'shopping-cart',
  'cloud' => 'globe',
  'money' => 'trophy',
  'achievement' => 'trophy',
  'award' => 'medal',
  'graduation-cap' => 'graduation',
  'handshake' => 'users',
  'analytics' => 'fire',
  'lightning' => 'bolt',
  'cpu' => 'cog',
  'cursor' => 'pointer',
  'home' => 'building',
  'office' => 'building',
  'store' => 'storefront',
  'phone' => 'message',
  'email' => 'mail',
  'chat' => 'message',
  'comment' => 'message',
  'dollar' => 'shopping-cart',
  'euro' => 'shopping-cart',
  'card' => 'shopping-cart',
  'credit-card' => 'shopping-cart',
  'clipboard-list' => 'clipboard',
  'clipboard-check' => 'clipboard',
  'check-square' => 'check-circle',
  'x-circle' => 'x',
  'alert' => 'alert-triangle',
  'exclamation' => 'warning',
  'trending-up' => 'fire',
  'trending-down' => 'fire',
  'bar-chart' => 'fire',
  'pie-chart' => 'fire',
  'activity' => 'fire',
  'grid' => 'layout-grid',
  'list-ul' => 'list',
  'list-ol' => 'list',
];

$config_dir = '/app/web/modules/custom/jaraba_page_builder/config/install';
$files = glob("$config_dir/jaraba_page_builder.template.*.yml");

$changes = [];
$total_replacements = 0;

foreach ($files as $file) {
  $content = file_get_contents($file);
  $original = $content;
  $file_changes = [];
  
  foreach ($icon_map as $invalid => $valid) {
    // Buscar patrones como icon: "rocket" o icon: 'rocket' o icon: rocket
    $patterns = [
      "/icon:\s*[\"']{$invalid}[\"']/",
      "/icon:\s*{$invalid}\s*$/m"
    ];
    
    foreach ($patterns as $pattern) {
      if (preg_match($pattern, $content)) {
        $content = preg_replace(
          "/icon:\s*[\"']?{$invalid}[\"']?/",
          "icon: \"{$valid}\"",
          $content,
          -1,
          $count
        );
        if ($count > 0) {
          $file_changes[] = "$invalid → $valid ($count)";
          $total_replacements += $count;
        }
      }
    }
  }
  
  if ($content !== $original) {
    file_put_contents($file, $content);
    $changes[basename($file)] = $file_changes;
  }
}

echo "=== ICON FIX AUDIT ===\n\n";
echo "Total files scanned: " . count($files) . "\n";
echo "Total replacements: $total_replacements\n\n";

if (!empty($changes)) {
  echo "Files modified:\n";
  foreach ($changes as $file => $file_changes) {
    echo "  - $file:\n";
    foreach ($file_changes as $change) {
      echo "      $change\n";
    }
  }
} else {
  echo "No invalid icons found.\n";
}

echo "\nDone!\n";
