<?php

/**
 * @file
 * Validator: Andalucía +ei phase access integrity.
 *
 * 10 checks for phase access constants, feature methods,
 * expiry service, entity classes, and grounding provider.
 *
 * Usage: php scripts/validation/validate-andalucia-ei-phase-access.php
 */

declare(strict_types=1);

$errors = [];
$passes = [];

$moduleRoot = __DIR__ . '/../../web/modules/custom/jaraba_andalucia_ei';
$candidateRoot = __DIR__ . '/../../web/modules/custom/jaraba_candidate';

$accessServiceFile = $moduleRoot . '/src/Service/ProgramaVerticalAccessService.php';
$accessContent = file_exists($accessServiceFile) ? file_get_contents($accessServiceFile) : '';

$expiryServiceFile = $moduleRoot . '/src/Service/BonoProgramaExpiryService.php';
$expiryContent = file_exists($expiryServiceFile) ? file_get_contents($expiryServiceFile) : '';

// CHECK 1: ProgramaVerticalAccessService has FASES_FORMACION constant.
if (strpos($accessContent, 'FASES_FORMACION') !== false) {
  $passes[] = "CHECK 1 PASS: ProgramaVerticalAccessService has FASES_FORMACION constant";
} else {
  $errors[] = "CHECK 1 FAIL: ProgramaVerticalAccessService missing FASES_FORMACION constant";
}

// CHECK 2: ProgramaVerticalAccessService has FASES_INSERCION constant.
if (strpos($accessContent, 'FASES_INSERCION') !== false) {
  $passes[] = "CHECK 2 PASS: ProgramaVerticalAccessService has FASES_INSERCION constant";
} else {
  $errors[] = "CHECK 2 FAIL: ProgramaVerticalAccessService missing FASES_INSERCION constant";
}

// CHECK 3: FASES_FORMACION + FASES_INSERCION have no overlap.
$formacionFases = [];
$insercionFases = [];
if (preg_match("/FASES_FORMACION\s*=\s*\[(.*?)\]/s", $accessContent, $m)) {
  preg_match_all("/'([^']+)'/", $m[1], $vals);
  $formacionFases = $vals[1] ?? [];
}
if (preg_match("/FASES_INSERCION\s*=\s*\[(.*?)\]/s", $accessContent, $m)) {
  preg_match_all("/'([^']+)'/", $m[1], $vals);
  $insercionFases = $vals[1] ?? [];
}
$overlap = array_intersect($formacionFases, $insercionFases);
if (count($formacionFases) > 0 && count($insercionFases) > 0 && count($overlap) === 0) {
  $passes[] = "CHECK 3 PASS: FASES_FORMACION and FASES_INSERCION have no overlap (" . count($formacionFases) . " + " . count($insercionFases) . " fases)";
} else {
  if (count($formacionFases) === 0 || count($insercionFases) === 0) {
    $errors[] = "CHECK 3 FAIL: Could not parse FASES_FORMACION or FASES_INSERCION constants";
  } else {
    $errors[] = "CHECK 3 FAIL: FASES_FORMACION and FASES_INSERCION overlap on: " . implode(', ', $overlap);
  }
}

// CHECK 4: FEATURES_INSERCION constant exists and is not empty.
$featuresInsercion = [];
if (preg_match("/FEATURES_INSERCION\s*=\s*\[(.*?)\]/s", $accessContent, $m)) {
  preg_match_all("/'([^']+)'/", $m[1], $vals);
  $featuresInsercion = $vals[1] ?? [];
}
if (count($featuresInsercion) > 0) {
  $passes[] = "CHECK 4 PASS: FEATURES_INSERCION constant exists with " . count($featuresInsercion) . " features";
} else {
  $errors[] = "CHECK 4 FAIL: FEATURES_INSERCION constant missing or empty in ProgramaVerticalAccessService";
}

// CHECK 5: hasFeatureAccess method exists.
if (preg_match('/function\s+hasFeatureAccess\s*\(/', $accessContent)) {
  $passes[] = "CHECK 5 PASS: hasFeatureAccess() method exists in ProgramaVerticalAccessService";
} else {
  $errors[] = "CHECK 5 FAIL: hasFeatureAccess() method not found in ProgramaVerticalAccessService";
}

// CHECK 6: getAvailableFeatures method exists.
if (preg_match('/function\s+getAvailableFeatures\s*\(/', $accessContent)) {
  $passes[] = "CHECK 6 PASS: getAvailableFeatures() method exists in ProgramaVerticalAccessService";
} else {
  $errors[] = "CHECK 6 FAIL: getAvailableFeatures() method not found in ProgramaVerticalAccessService";
}

// CHECK 7: BonoProgramaExpiryService has MESES_PROGRAMA = 12.
if (preg_match('/MESES_PROGRAMA\s*=\s*12\s*;/', $expiryContent)) {
  $passes[] = "CHECK 7 PASS: BonoProgramaExpiryService has MESES_PROGRAMA = 12";
} else {
  if (strpos($expiryContent, 'MESES_PROGRAMA') !== false) {
    $errors[] = "CHECK 7 FAIL: BonoProgramaExpiryService has MESES_PROGRAMA but value is not 12";
  } else {
    $errors[] = "CHECK 7 FAIL: BonoProgramaExpiryService missing MESES_PROGRAMA constant";
  }
}

// CHECK 8: BonoProgramaExpiryService has AVISOS_DIAS with 6 elements.
$avisosDias = [];
if (preg_match("/AVISOS_DIAS\s*=\s*\[(.*?)\]/s", $expiryContent, $m)) {
  $avisosDias = array_filter(array_map('trim', explode(',', $m[1])), fn($v) => $v !== '');
}
if (count($avisosDias) === 6) {
  $passes[] = "CHECK 8 PASS: BonoProgramaExpiryService AVISOS_DIAS has 6 elements [" . implode(', ', $avisosDias) . "]";
} else {
  $errors[] = "CHECK 8 FAIL: BonoProgramaExpiryService AVISOS_DIAS has " . count($avisosDias) . " elements (expected 6)";
}

// CHECK 9: ClienteParticipanteEi entity class exists.
$clienteEntityFile = $moduleRoot . '/src/Entity/ClienteParticipanteEi.php';
if (file_exists($clienteEntityFile)) {
  $clienteContent = file_get_contents($clienteEntityFile);
  if (strpos($clienteContent, 'class ClienteParticipanteEi') !== false) {
    $passes[] = "CHECK 9 PASS: ClienteParticipanteEi entity class exists";
  } else {
    $errors[] = "CHECK 9 FAIL: ClienteParticipanteEi.php exists but class declaration not found";
  }
} else {
  $errors[] = "CHECK 9 FAIL: ClienteParticipanteEi entity class not found at src/Entity/ClienteParticipanteEi.php";
}

// CHECK 10: CandidateGroundingProvider exists in jaraba_candidate.
$groundingFiles = glob($candidateRoot . '/src/{Grounding,Service}/*GroundingProvider*', GLOB_BRACE);
$groundingFound = false;
if (is_array($groundingFiles)) {
  foreach ($groundingFiles as $file) {
    $content = file_get_contents($file);
    if (strpos($content, 'CandidateGroundingProvider') !== false) {
      $groundingFound = true;
      break;
    }
  }
}
if ($groundingFound) {
  $passes[] = "CHECK 10 PASS: CandidateGroundingProvider exists in jaraba_candidate";
} else {
  $errors[] = "CHECK 10 FAIL: CandidateGroundingProvider not found in jaraba_candidate/src/{Grounding,Service}/";
}

// RESULTS
$total = count($errors) + count($passes);
echo "\n=== ANDALUCIA +EI — PHASE ACCESS INTEGRITY ===\n\n";
foreach ($passes as $msg) {
  echo "  [PASS] $msg\n";
}
foreach ($errors as $msg) {
  echo "  [FAIL] $msg\n";
}
echo "\n--- Score: " . count($passes) . "/$total checks passed ---\n\n";
exit(count($errors) === 0 ? 0 : 1);
