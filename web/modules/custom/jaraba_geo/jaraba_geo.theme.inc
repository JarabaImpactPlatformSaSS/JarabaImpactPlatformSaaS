/**
 * @file
 * Register hooks for jaraba_geo module.
 */

/**
 * Implements hook_theme().
 */
function jaraba_geo_theme(): array {
  return [
    'geo_answer_capsule' => [
      'variables' => [
        'capsule' => [],
        'entity' => NULL,
      ],
      'template' => 'geo-answer-capsule',
    ],
    'geo_faq_page' => [
      'variables' => [
        'faqs' => [],
      ],
      'template' => 'geo-faq-page',
    ],
  ];
}
