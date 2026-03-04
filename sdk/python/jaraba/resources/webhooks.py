"""Webhooks resource."""

from __future__ import annotations

from typing import Any, TYPE_CHECKING

if TYPE_CHECKING:
    from jaraba.client import JarabaClient


class WebhooksResource:
    """Manage webhook subscriptions.

    Usage::

        hooks = client.webhooks.list()
        new_hook = client.webhooks.create(
            url="https://my-app.com/webhook",
            events=["order.created", "subscription.changed"],
        )
        client.webhooks.delete(hook_id=42)
    """

    def __init__(self, client: JarabaClient) -> None:
        self._client = client

    def list(self) -> dict[str, Any]:
        """List configured webhooks."""
        return self._client.get("/webhooks")

    def create(
        self,
        url: str,
        events: list[str],
        *,
        secret: str | None = None,
    ) -> dict[str, Any]:
        """Register a new webhook."""
        payload: dict[str, Any] = {"url": url, "events": events}
        if secret:
            payload["secret"] = secret
        return self._client.post("/webhooks", payload)

    def delete(self, hook_id: int) -> dict[str, Any]:
        """Delete a webhook by ID."""
        return self._client.delete(f"/webhooks/{hook_id}")
