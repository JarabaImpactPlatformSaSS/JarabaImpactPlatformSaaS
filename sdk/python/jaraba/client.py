"""Main client for Jaraba Impact Platform API."""

from __future__ import annotations

from typing import Any

import httpx

from jaraba.exceptions import (
    AuthenticationError,
    ForbiddenError,
    JarabaError,
    NotFoundError,
    RateLimitError,
    ValidationError,
)
from jaraba.resources.tenants import TenantsResource
from jaraba.resources.analytics import AnalyticsResource
from jaraba.resources.content import ContentResource
from jaraba.resources.copilot import CopilotResource
from jaraba.resources.agents import AgentsResource
from jaraba.resources.billing import BillingResource
from jaraba.resources.webhooks import WebhooksResource
from jaraba.resources.crm import CrmResource

_DEFAULT_BASE_URL = "https://plataformadeecosistemas.es/api/v1"
_DEFAULT_TIMEOUT = 30.0


class JarabaClient:
    """Synchronous client for Jaraba Impact Platform API.

    Usage::

        from jaraba import JarabaClient

        client = JarabaClient(api_key="jrb_...")

        # List tenants
        tenants = client.tenants.list()

        # Track analytics event
        client.analytics.track("page_view", properties={"path": "/home"})

        # Chat with AI copilot
        response = client.copilot.chat("How can I improve my business?")
    """

    def __init__(
        self,
        api_key: str,
        base_url: str = _DEFAULT_BASE_URL,
        timeout: float = _DEFAULT_TIMEOUT,
    ) -> None:
        if not api_key:
            raise AuthenticationError("API key is required")

        self._http = httpx.Client(
            base_url=base_url,
            headers={
                "X-API-Key": api_key,
                "Content-Type": "application/json",
                "Accept": "application/json",
                "User-Agent": "jaraba-sdk-python/1.0.0",
            },
            timeout=timeout,
        )

        # Resource namespaces.
        self.tenants = TenantsResource(self)
        self.analytics = AnalyticsResource(self)
        self.content = ContentResource(self)
        self.copilot = CopilotResource(self)
        self.agents = AgentsResource(self)
        self.billing = BillingResource(self)
        self.webhooks = WebhooksResource(self)
        self.crm = CrmResource(self)

    # -- HTTP helpers --------------------------------------------------------

    def _request(
        self,
        method: str,
        path: str,
        *,
        json: dict[str, Any] | None = None,
        params: dict[str, Any] | None = None,
    ) -> dict[str, Any]:
        """Execute an HTTP request and handle errors."""
        response = self._http.request(method, path, json=json, params=params)
        return self._handle_response(response)

    def get(self, path: str, **params: Any) -> dict[str, Any]:
        """GET request."""
        return self._request("GET", path, params=params or None)

    def post(self, path: str, data: dict[str, Any] | None = None) -> dict[str, Any]:
        """POST request."""
        return self._request("POST", path, json=data)

    def patch(self, path: str, data: dict[str, Any] | None = None) -> dict[str, Any]:
        """PATCH request."""
        return self._request("PATCH", path, json=data)

    def delete(self, path: str) -> dict[str, Any]:
        """DELETE request."""
        return self._request("DELETE", path)

    def _handle_response(self, response: httpx.Response) -> dict[str, Any]:
        """Parse response and raise appropriate exceptions."""
        if response.status_code == 204:
            return {}

        try:
            body = response.json()
        except Exception:
            body = {}

        if 200 <= response.status_code < 300:
            return body

        error_msg = ""
        if isinstance(body, dict) and "error" in body:
            err = body["error"]
            error_msg = err.get("message", "") if isinstance(err, dict) else str(err)

        error_msg = error_msg or f"HTTP {response.status_code}"

        error_map: dict[int, type[JarabaError]] = {
            400: ValidationError,
            401: AuthenticationError,
            403: ForbiddenError,
            404: NotFoundError,
            429: RateLimitError,
        }

        exc_class = error_map.get(response.status_code, JarabaError)

        if exc_class is RateLimitError:
            retry_after = response.headers.get("Retry-After")
            raise RateLimitError(
                error_msg,
                retry_after=int(retry_after) if retry_after else None,
                status_code=429,
                body=body,
            )

        raise exc_class(error_msg, status_code=response.status_code, body=body)

    def close(self) -> None:
        """Close the underlying HTTP client."""
        self._http.close()

    def __enter__(self) -> JarabaClient:
        return self

    def __exit__(self, *args: Any) -> None:
        self.close()
