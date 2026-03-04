"""Analytics resource."""

from __future__ import annotations

from typing import Any, TYPE_CHECKING

if TYPE_CHECKING:
    from jaraba.client import JarabaClient


class AnalyticsResource:
    """Track events and retrieve analytics data.

    Usage::

        client.analytics.track("page_view", properties={"path": "/home"})
        dashboard = client.analytics.dashboard()
    """

    def __init__(self, client: JarabaClient) -> None:
        self._client = client

    def track(
        self,
        event_name: str,
        *,
        properties: dict[str, Any] | None = None,
        user_id: int | None = None,
    ) -> dict[str, Any]:
        """Track a single analytics event."""
        payload: dict[str, Any] = {"event": event_name}
        if properties:
            payload["properties"] = properties
        if user_id is not None:
            payload["user_id"] = user_id
        return self._client.post("/analytics/event", payload)

    def dashboard(self, **params: Any) -> dict[str, Any]:
        """Get dashboard KPIs."""
        return self._client.get("/analytics/dashboard", **params)

    def funnel(
        self,
        steps: list[str],
        *,
        start_date: str | None = None,
        end_date: str | None = None,
    ) -> dict[str, Any]:
        """Calculate funnel conversion."""
        payload: dict[str, Any] = {"steps": steps}
        if start_date:
            payload["start_date"] = start_date
        if end_date:
            payload["end_date"] = end_date
        return self._client.post("/analytics/funnel", payload)
