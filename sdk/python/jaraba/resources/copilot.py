"""AI Copilot resource."""

from __future__ import annotations

from typing import Any, TYPE_CHECKING

if TYPE_CHECKING:
    from jaraba.client import JarabaClient


class CopilotResource:
    """Interact with the AI copilot.

    Usage::

        response = client.copilot.chat("How can I improve my sales?")
        print(response["data"]["message"])
    """

    def __init__(self, client: JarabaClient) -> None:
        self._client = client

    def chat(
        self,
        message: str,
        *,
        conversation_id: str | None = None,
        mode: str | None = None,
    ) -> dict[str, Any]:
        """Send a message to the AI copilot."""
        payload: dict[str, Any] = {"message": message}
        if conversation_id:
            payload["conversation_id"] = conversation_id
        if mode:
            payload["mode"] = mode
        return self._client.post("/copilot/chat", payload)

    def modes(self) -> dict[str, Any]:
        """List available copilot modes."""
        return self._client.get("/copilot/modes")
