"""Embeddings utility module for the Legal Intelligence Hub.

Provides helper classes for generating text embeddings via an external
HTTP API.  In the main Drupal-integrated pipeline, embeddings are
generated PHP-side through Drupal's ``@ai.provider`` service.  This
module is available for Python-side batch operations, maintenance
scripts, and offline re-indexing jobs.

Usage::

    import asyncio
    from embeddings import EmbeddingsHelper

    helper = EmbeddingsHelper(
        api_url="http://localhost:11434/api/embeddings",
        model="nomic-embed-text",
    )
    vector = asyncio.run(helper.generate("Artículo 24 de la Constitución"))
"""

from __future__ import annotations

import asyncio
from typing import Any

import httpx
import structlog

log = structlog.get_logger()

# Default timeout for individual embedding requests (seconds).
_DEFAULT_TIMEOUT = 30.0


class EmbeddingsHelper:
    """Utility for batch embedding generation via an external API.

    NOTE: In the main pipeline, embeddings are generated PHP-side via
    Drupal's ``@ai.provider``.  This module is available for batch
    operations or maintenance scripts.

    Parameters
    ----------
    api_url:
        Full URL of the embeddings API endpoint (e.g.
        ``http://localhost:11434/api/embeddings``).
    model:
        Model identifier to pass in the request body.
    timeout:
        Per-request timeout in seconds.
    """

    def __init__(
        self,
        api_url: str,
        model: str,
        timeout: float = _DEFAULT_TIMEOUT,
    ) -> None:
        self._api_url = api_url.rstrip("/")
        self._model = model
        self._timeout = timeout

        log.info(
            "embeddings.init",
            api_url=self._api_url,
            model=self._model,
        )

    # ------------------------------------------------------------------
    # Public API
    # ------------------------------------------------------------------

    async def generate(self, text: str) -> list[float]:
        """Generate an embedding vector for a single text.

        Parameters
        ----------
        text:
            The input text to embed.

        Returns
        -------
        list[float]
            The embedding vector.

        Raises
        ------
        httpx.HTTPStatusError
            If the API returns a non-2xx response.
        ValueError
            If the response payload is missing the embedding field.
        """
        payload: dict[str, Any] = {
            "model": self._model,
            "prompt": text,
        }

        async with httpx.AsyncClient(timeout=self._timeout) as client:
            response = await client.post(self._api_url, json=payload)
            response.raise_for_status()

        data = response.json()
        embedding = data.get("embedding")

        if embedding is None:
            log.error(
                "embeddings.missing_field",
                response_keys=list(data.keys()),
            )
            raise ValueError(
                "API response does not contain an 'embedding' field. "
                f"Available keys: {list(data.keys())}"
            )

        log.debug("embeddings.generated", dimensions=len(embedding))
        return embedding

    async def generate_batch(
        self,
        texts: list[str],
        batch_size: int = 32,
    ) -> list[list[float]]:
        """Generate embeddings for multiple texts in batches.

        Texts are processed in groups of *batch_size* using concurrent
        requests within each batch.  This avoids overwhelming the API
        while still being significantly faster than sequential calls.

        Parameters
        ----------
        texts:
            List of input texts.
        batch_size:
            Maximum number of concurrent requests per batch.

        Returns
        -------
        list[list[float]]
            Embedding vectors in the same order as the input texts.
        """
        if not texts:
            return []

        all_embeddings: list[list[float]] = []

        total_batches = (len(texts) + batch_size - 1) // batch_size

        for batch_idx in range(total_batches):
            start = batch_idx * batch_size
            end = min(start + batch_size, len(texts))
            batch = texts[start:end]

            log.info(
                "embeddings.batch_start",
                batch=batch_idx + 1,
                total_batches=total_batches,
                batch_size=len(batch),
            )

            tasks = [self.generate(text) for text in batch]
            results = await asyncio.gather(*tasks, return_exceptions=True)

            for i, result in enumerate(results):
                if isinstance(result, BaseException):
                    log.error(
                        "embeddings.batch_item_error",
                        batch=batch_idx + 1,
                        item=i,
                        error=str(result),
                    )
                    raise result
                all_embeddings.append(result)

            log.info(
                "embeddings.batch_done",
                batch=batch_idx + 1,
                total_batches=total_batches,
                embeddings_so_far=len(all_embeddings),
            )

        return all_embeddings
