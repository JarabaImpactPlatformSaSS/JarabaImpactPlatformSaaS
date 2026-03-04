"""Jaraba Impact Platform — Official Python SDK."""

from jaraba.client import JarabaClient
from jaraba.exceptions import (
    JarabaError,
    AuthenticationError,
    NotFoundError,
    RateLimitError,
    ValidationError,
)

__version__ = "1.0.0"
__all__ = [
    "JarabaClient",
    "JarabaError",
    "AuthenticationError",
    "NotFoundError",
    "RateLimitError",
    "ValidationError",
]
