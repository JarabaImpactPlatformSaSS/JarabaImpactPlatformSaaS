"""Exception classes for Jaraba SDK."""

from __future__ import annotations

from typing import Any


class JarabaError(Exception):
    """Base exception for all Jaraba SDK errors."""

    def __init__(
        self,
        message: str,
        status_code: int | None = None,
        body: dict[str, Any] | None = None,
    ) -> None:
        super().__init__(message)
        self.status_code = status_code
        self.body = body or {}


class AuthenticationError(JarabaError):
    """Raised when API key is invalid or missing (HTTP 401)."""


class ForbiddenError(JarabaError):
    """Raised when access is denied (HTTP 403)."""


class NotFoundError(JarabaError):
    """Raised when resource is not found (HTTP 404)."""


class RateLimitError(JarabaError):
    """Raised when rate limit is exceeded (HTTP 429)."""

    def __init__(
        self,
        message: str,
        retry_after: int | None = None,
        **kwargs: Any,
    ) -> None:
        super().__init__(message, **kwargs)
        self.retry_after = retry_after


class ValidationError(JarabaError):
    """Raised when request data is invalid (HTTP 400)."""
