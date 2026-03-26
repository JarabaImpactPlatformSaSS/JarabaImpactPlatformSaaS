<?php
$s = \Drupal::entityTypeManager()->getStorage('success_case');
$ids = $s->getQuery()->accessCheck(FALSE)->condition('status', 1)->sort('weight')->execute();
$total = count($ids);
$stats = ['foto' => 0, 'video' => 0, 'quote' => 0, 'metrics' => 0];
foreach ($s->loadMultiple($ids) as $c) {
  $foto = !$c->get('hero_image')->isEmpty(); if ($foto) $stats['foto']++;
  $video = !empty($c->get('video_url')->value); if ($video) $stats['video']++;
  $quote = !empty(trim((string) $c->get('quote_short')->value)); if ($quote) $stats['quote']++;
  $m = json_decode((string) $c->get('metrics_json')->value, TRUE); $met = !empty($m); if ($met) $stats['metrics']++;
  $p = $c->get('program_name')->value ?? '';
  $type = !empty($p) ? 'REAL' : 'pre';
  printf("%2d %-15s %-35s %s %s %s %s %s\n", $c->id(), $c->get('vertical')->value, substr($c->get('name')->value, 0, 33), $foto ? 'Y' : '-', $video ? 'Y' : '.', $quote ? 'Y' : '.', $met ? 'Y' : '.', $type);
}
echo str_repeat('-', 80) . "\n";
printf("Foto: %d/%d | Video: %d/%d | Quote: %d/%d | Metricas: %d/%d\n", $stats['foto'], $total, $stats['video'], $total, $stats['quote'], $total, $stats['metrics'], $total);
