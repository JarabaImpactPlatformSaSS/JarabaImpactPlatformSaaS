/**
 * @module @jaraba/sdk
 * Official JavaScript SDK for Jaraba Impact Platform API.
 */

export { JarabaClient } from './client.js';
export {
  JarabaError,
  AuthenticationError,
  NotFoundError,
  RateLimitError,
  ValidationError,
} from './errors.js';
