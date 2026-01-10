<?php

namespace Drupal\ai_provider_google_vertex;

use Drupal\ai\OperationType\Chat\StreamedChatMessage;
use Drupal\ai\OperationType\Chat\StreamedChatMessageIterator;
use Google\ApiCore\ServerStream;

/**
 * Google Vertex Chat message iterator.
 */
class GoogleVertexChatIterator extends StreamedChatMessageIterator {

  /**
   * The protobuf server stream.
   *
   * @var \Google\ApiCore\ServerStream
   */
  protected $stream;

  /**
   * Set the streamed response.
   *
   * @param \Google\ApiCore\ServerStream $stream
   *   The streamed response.
   */
  public function setStreamedResponse(ServerStream $stream) {
    $this->stream = $stream;
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator(): \Generator {
    foreach ($this->stream->readAll() as $data) {
      foreach ($data->getCandidates() as $candidate) {
        yield new StreamedChatMessage(
          $candidate->getContent()->getRole() ?? '',
          $candidate->getContent()->getParts()[0]->getText() ?? '',
          (array) $data->getUsageMetadata() ?? []
        );
      }
    }
  }

}
