"""AI Agents resource."""

from __future__ import annotations

from typing import Any, TYPE_CHECKING

if TYPE_CHECKING:
    from jaraba.client import JarabaClient


class AgentsResource:
    """Manage and execute AI agents.

    Usage::

        agents = client.agents.list()
        result = client.agents.execute("smart_marketing", input="Analyze my funnel")
    """

    def __init__(self, client: JarabaClient) -> None:
        self._client = client

    def list(self) -> dict[str, Any]:
        """List available AI agents."""
        return self._client.get("/agents")

    def get(self, agent_id: str) -> dict[str, Any]:
        """Get details for a specific agent."""
        return self._client.get(f"/agents/{agent_id}")

    def execute(
        self,
        agent_id: str,
        *,
        input: str = "",
        parameters: dict[str, Any] | None = None,
    ) -> dict[str, Any]:
        """Execute an AI agent action."""
        payload: dict[str, Any] = {"input": input}
        if parameters:
            payload["parameters"] = parameters
        return self._client.post(f"/agents/{agent_id}/execute", payload)
