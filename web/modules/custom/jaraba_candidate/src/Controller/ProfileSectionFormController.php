<?php

declare(strict_types=1);

namespace Drupal\jaraba_candidate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\CsrfTokenGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Generic CRUD controller for profile section entities (slide-panel).
 *
 * Renders entity forms for candidate_experience, candidate_education,
 * and candidate_language inside the slide-panel via AJAX.
 */
class ProfileSectionFormController extends ControllerBase {

  /**
   * Allowed entity types for profile section CRUD.
   */
  private const ALLOWED_TYPES = [
    'candidate_experience',
    'candidate_education',
    'candidate_language',
  ];

  /**
   * The CSRF token generator.
   */
  protected CsrfTokenGenerator $csrfToken;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->csrfToken = $container->get('csrf_token');
    return $instance;
  }

  /**
   * Renders an add form for a section entity.
   */
  public function add(string $entity_type_id, Request $request): array|Response {
    $this->validateEntityType($entity_type_id);

    $entity = $this->entityTypeManager()
      ->getStorage($entity_type_id)
      ->create(['user_id' => $this->currentUser()->id()]);

    $form = $this->entityFormBuilder()->getForm($entity, 'default');

    if ($request->isXmlHttpRequest()) {
      $html = (string) \Drupal::service('renderer')->render($form);
      return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    return $form;
  }

  /**
   * Renders an edit form for a section entity.
   */
  public function edit(string $entity_type_id, string $entity_id, Request $request): array|Response {
    $this->validateEntityType($entity_type_id);

    $entity = $this->entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($entity_id);

    if (!$entity) {
      throw new NotFoundHttpException();
    }

    $this->validateOwnership($entity);

    $form = $this->entityFormBuilder()->getForm($entity, 'default');

    if ($request->isXmlHttpRequest()) {
      $html = (string) \Drupal::service('renderer')->render($form);
      return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    return $form;
  }

  /**
   * Deletes a section entity (POST only, CSRF-protected).
   */
  public function delete(string $entity_type_id, string $entity_id, Request $request): JsonResponse {
    $this->validateEntityType($entity_type_id);

    // Validate CSRF token.
    $token = $request->headers->get('X-CSRF-Token', '');
    if (!$this->csrfToken->validate($token, 'session')) {
      throw new AccessDeniedHttpException('Invalid CSRF token.');
    }

    $entity = $this->entityTypeManager()
      ->getStorage($entity_type_id)
      ->load($entity_id);

    if (!$entity) {
      throw new NotFoundHttpException();
    }

    $this->validateOwnership($entity);

    $entity->delete();

    return new JsonResponse(['success' => TRUE]);
  }

  /**
   * Validates that the entity type is in the whitelist.
   */
  protected function validateEntityType(string $entity_type_id): void {
    if (!in_array($entity_type_id, self::ALLOWED_TYPES, TRUE)) {
      throw new NotFoundHttpException('Invalid entity type.');
    }
  }

  /**
   * Validates that the current user owns the entity.
   */
  protected function validateOwnership(object $entity): void {
    $entity_user_id = $entity->get('user_id')->target_id ?? $entity->get('user_id')->value ?? NULL;
    if ((int) $entity_user_id !== (int) $this->currentUser()->id()) {
      throw new AccessDeniedHttpException('You do not own this entity.');
    }
  }

}
