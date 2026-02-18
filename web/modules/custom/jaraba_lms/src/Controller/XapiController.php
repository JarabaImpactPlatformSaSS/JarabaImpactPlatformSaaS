<?php

declare(strict_types=1);

namespace Drupal\jaraba_lms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for xAPI statement recording.
 *
 * Receives xAPI statements from video tracking JS and stores them.
 */
class XapiController extends ControllerBase
{

    /**
     * Records an xAPI statement.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   The request object.
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     *   JSON response.
     */
    public function recordStatement(Request $request): JsonResponse
    {
        $user = $this->currentUser();

        if ($user->isAnonymous()) {
            return new JsonResponse(['success' => FALSE, 'error' => 'Authentication required'], 401);
        }

        try {
            $data = json_decode($request->getContent(), TRUE);

            if (empty($data['lesson_id']) || empty($data['verb'])) {
                return new JsonResponse([
                    'success' => FALSE,
                    'error' => 'lesson_id and verb are required',
                ], 400);
            }

            // Store the xAPI statement
            $database = \Drupal::database();

            $database->insert('lms_xapi_statements')
                ->fields([
                    'uid' => $user->id(),
                    'lesson_id' => $data['lesson_id'],
                    'verb' => $data['verb'],
                    'progress' => $data['progress'] ?? 0,
                    'timestamp' => $data['timestamp'] ?? date('c'),
                    'created' => time(),
                ])
                ->execute();

            return new JsonResponse([
                'success' => TRUE,
                'stored' => TRUE,
            ]);
        } catch (\Exception $e) {
            \Drupal::logger('jaraba_lms')->error('Operation failed: @msg', ['@msg' => $e->getMessage()]);
            return new JsonResponse([
                'success' => FALSE,
                'error' => 'Se produjo un error interno. Inténtelo de nuevo más tarde.',
            ], 500);
        }
    }

}
