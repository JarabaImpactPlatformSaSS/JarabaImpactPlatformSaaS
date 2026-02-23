<?php

declare(strict_types=1);

namespace Drupal\jaraba_messaging\Exception;

/**
 * Thrown when message decryption fails (wrong key, corrupted data).
 */
class DecryptionException extends \RuntimeException {

}
