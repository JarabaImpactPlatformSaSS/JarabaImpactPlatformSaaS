<?php

declare(strict_types=1);

namespace Drupal\ecosistema_jaraba_core\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trait for controllers that render premium entity forms in a slide-panel.
 *
 * When the request is an XHR (slide-panel fetch), the trait renders only the
 * form HTML and returns a Response. When the request is a normal page load,
 * it returns NULL so the controller can build a full render array.
 *
 * Usage in a controller:
 * @code
 * use PremiumFormAjaxTrait;
 *
 * public function edit(MyEntity $entity, Request $request): array|Response {
 *   $form = $this->entityFormBuilder()->getForm($entity, 'edit');
 *   if ($ajax = $this->renderFormForAjax($form, $request)) {
 *     return $ajax;
 *   }
 *   return ['#theme' => 'premium_form_wrapper', '#form' => $form, ...];
 * }
 * @endcode
 */
trait PremiumFormAjaxTrait {

  /**
   * Renders a form for AJAX/slide-panel consumption.
   *
   * @param array $form
   *   The built render array of the form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   *
   * @return \Symfony\Component\HttpFoundation\Response|null
   *   A Response with the rendered HTML if XHR, or NULL for normal requests.
   */
  protected function renderFormForAjax(array $form, Request $request): ?Response {
    if (!$request->isXmlHttpRequest()) {
      return NULL;
    }

    try {
      $html = (string) \Drupal::service('renderer')->render($form);
      return new Response($html, 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('premium_forms')->error('AJAX form render error: @message', [
        '@message' => $e->getMessage(),
      ]);
      $error_msg = t('Error loading form. Please try again.');
      return new Response(
        '<div class="slide-panel__error"><p>' . $error_msg . '</p></div>',
        500,
        ['Content-Type' => 'text/html; charset=UTF-8'],
      );
    }
  }

}
