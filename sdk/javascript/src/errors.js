/**
 * @file Error classes for Jaraba SDK.
 */

export class JarabaError extends Error {
  /**
   * @param {string} message
   * @param {number} [statusCode]
   * @param {Object} [body]
   */
  constructor(message, statusCode, body) {
    super(message);
    this.name = 'JarabaError';
    this.statusCode = statusCode ?? null;
    this.body = body ?? {};
  }
}

export class AuthenticationError extends JarabaError {
  constructor(message, body) {
    super(message, 401, body);
    this.name = 'AuthenticationError';
  }
}

export class ForbiddenError extends JarabaError {
  constructor(message, body) {
    super(message, 403, body);
    this.name = 'ForbiddenError';
  }
}

export class NotFoundError extends JarabaError {
  constructor(message, body) {
    super(message, 404, body);
    this.name = 'NotFoundError';
  }
}

export class RateLimitError extends JarabaError {
  /**
   * @param {string} message
   * @param {number|null} retryAfter
   * @param {Object} [body]
   */
  constructor(message, retryAfter, body) {
    super(message, 429, body);
    this.name = 'RateLimitError';
    this.retryAfter = retryAfter ?? null;
  }
}

export class ValidationError extends JarabaError {
  constructor(message, body) {
    super(message, 400, body);
    this.name = 'ValidationError';
  }
}
