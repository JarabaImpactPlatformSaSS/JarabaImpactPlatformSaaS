<?php

/**
 * @file
 * Regression tests for validate-phantom-args.php parser.
 *
 * Validates that the countConstructorParams() and parseServicesYaml()
 * functions handle edge cases correctly. Run with:
 *   php scripts/validation/tests/test-phantom-args-parser.php
 *
 * Exit: 0 = all pass, 1 = failures found.
 */

declare(strict_types=1);

// Include the functions from the validator.
require_once dirname(__DIR__) . '/validate-phantom-args.php';

$pass = 0;
$fail = 0;

// ============================================================================
// Test: countConstructorParams() edge cases.
// ============================================================================

/**
 * Helper: write temp PHP file, count params, cleanup.
 */
function testConstructor(string $label, string $phpCode, ?array $expected): void {
  global $pass, $fail;

  $tmp = tempnam(sys_get_temp_dir(), 'phantom_test_');
  file_put_contents($tmp, "<?php\n" . $phpCode);
  $result = countConstructorParams($tmp);
  unlink($tmp);

  if ($result === $expected) {
    $pass++;
  }
  else {
    $fail++;
    fprintf(STDERR, "FAIL: %s\n  Expected: %s\n  Got:      %s\n",
      $label,
      $expected === NULL ? 'NULL' : json_encode($expected),
      $result === NULL ? 'NULL' : json_encode($result)
    );
  }
}

// 1. Trailing comma + inline comment (the 2026-03-17 regression).
testConstructor(
  'Trailing comma + inline comment should not create phantom param',
  <<<'PHP'
class Foo {
  public function __construct(
    protected readonly FooService $foo,
    protected readonly BarService $bar,
    protected readonly BazService $baz, // AUDIT-CONS-N10: Proper DI.
  ) {}
}
PHP,
  ['total' => 3, 'required' => 3]
);

// 2. Optional param with default NULL.
testConstructor(
  'Optional param with = NULL',
  <<<'PHP'
class Foo {
  public function __construct(
    protected FooService $foo,
    protected ?BarService $bar = NULL,
  ) {}
}
PHP,
  ['total' => 2, 'required' => 1]
);

// 3. No constructor.
testConstructor(
  'Class without constructor returns NULL',
  <<<'PHP'
class Foo {
  public function doStuff(): void {}
}
PHP,
  NULL
);

// 4. Empty constructor.
testConstructor(
  'Empty constructor',
  <<<'PHP'
class Foo {
  public function __construct() {}
}
PHP,
  ['total' => 0, 'required' => 0]
);

// 5. Mixed required + optional params.
testConstructor(
  'Mixed required and optional params',
  <<<'PHP'
class Foo {
  public function __construct(
    protected readonly A $a,
    protected readonly B $b,
    protected ?C $c = NULL,
    protected string $d = 'default',
    protected array $e = [],
  ) {}
}
PHP,
  ['total' => 5, 'required' => 2]
);

// 6. Default value with nested parens (e.g., new instance).
testConstructor(
  'Default value with nested parens',
  <<<'PHP'
class Foo {
  public function __construct(
    protected A $a,
    protected B $b = new B('x', 'y'),
  ) {}
}
PHP,
  ['total' => 2, 'required' => 1]
);

// 7. Block comment inside constructor.
testConstructor(
  'Block comment inside constructor',
  <<<'PHP'
class Foo {
  public function __construct(
    /* The main service. */
    protected A $a,
    protected B $b,
  ) {}
}
PHP,
  ['total' => 2, 'required' => 2]
);

// 8. Single-line constructor.
testConstructor(
  'Single-line constructor',
  'class Foo { public function __construct(A $a, B $b, C $c) {} }',
  ['total' => 3, 'required' => 3]
);

// 9. Multiple inline comments on separate params.
testConstructor(
  'Multiple inline comments on separate params',
  <<<'PHP'
class Foo {
  public function __construct(
    protected readonly A $a, // First service.
    protected readonly B $b, // Second service.
    protected readonly C $c, // Third service.
  ) {}
}
PHP,
  ['total' => 3, 'required' => 3]
);

// ============================================================================
// Test: parseServicesYaml() edge cases.
// ============================================================================

function testYaml(string $label, string $yaml, array $expected): void {
  global $pass, $fail;

  $result = parseServicesYaml($yaml);

  // Compare only the services we care about.
  $matches = TRUE;
  foreach ($expected as $serviceId => $expectedInfo) {
    if (!isset($result[$serviceId])) {
      $matches = FALSE;
      break;
    }
    if ($result[$serviceId]['arg_count'] !== $expectedInfo['arg_count']) {
      $matches = FALSE;
      break;
    }
  }

  if ($matches) {
    $pass++;
  }
  else {
    $fail++;
    fprintf(STDERR, "FAIL: %s\n  Expected: %s\n  Got:      %s\n",
      $label,
      json_encode($expected),
      json_encode($result)
    );
  }
}

// 10. Standard service with 3 args.
testYaml(
  'Standard service with 3 args',
  <<<'YAML'
services:
  my_module.my_service:
    class: Drupal\my_module\Service\MyService
    arguments:
      - '@entity_type.manager'
      - '@current_user'
      - '@logger.channel.my_module'
YAML,
  ['my_module.my_service' => ['arg_count' => 3]]
);

// 11. Service with 0 args.
testYaml(
  'Service with 0 args (no arguments key)',
  <<<'YAML'
services:
  my_module.simple:
    class: Drupal\my_module\Service\Simple
YAML,
  ['my_module.simple' => ['arg_count' => 0]]
);

// 12. Multiple services.
testYaml(
  'Multiple services parsed correctly',
  <<<'YAML'
services:
  mod.a:
    class: Drupal\mod\A
    arguments:
      - '@foo'
      - '@bar'
  mod.b:
    class: Drupal\mod\B
    arguments:
      - '@baz'
YAML,
  [
    'mod.a' => ['arg_count' => 2],
    'mod.b' => ['arg_count' => 1],
  ]
);

// ============================================================================
// Results.
// ============================================================================

$total = $pass + $fail;
if ($fail > 0) {
  echo sprintf("\nPHANTOM-ARG PARSER TESTS: %d/%d passed, %d FAILED.\n", $pass, $total, $fail);
  exit(1);
}

echo sprintf("PHANTOM-ARG PARSER TESTS: OK — %d/%d passed.\n", $pass, $total);
exit(0);
