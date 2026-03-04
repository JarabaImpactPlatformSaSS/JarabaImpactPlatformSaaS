"""Content Hub resource."""

from __future__ import annotations

from typing import Any, TYPE_CHECKING

if TYPE_CHECKING:
    from jaraba.client import JarabaClient


class ContentResource:
    """Manage articles and content.

    Usage::

        articles = client.content.list_articles(limit=10)
        article = client.content.create_article(title="My Post", body="...")
        ai_article = client.content.generate_article(topic="AI in agriculture")
    """

    def __init__(self, client: JarabaClient) -> None:
        self._client = client

    def list_articles(self, **params: Any) -> dict[str, Any]:
        """List published articles."""
        return self._client.get("/content/articles", **params)

    def create_article(
        self,
        title: str,
        body: str,
        *,
        category_id: int | None = None,
        status: str = "draft",
    ) -> dict[str, Any]:
        """Create a new article."""
        payload: dict[str, Any] = {
            "title": title,
            "body": body,
            "status": status,
        }
        if category_id is not None:
            payload["category_id"] = category_id
        return self._client.post("/content/articles", payload)

    def generate_article(
        self,
        topic: str,
        *,
        tone: str = "professional",
        length: str = "medium",
    ) -> dict[str, Any]:
        """Generate a full article using AI."""
        return self._client.post("/content/ai/full-article", {
            "topic": topic,
            "tone": tone,
            "length": length,
        })
