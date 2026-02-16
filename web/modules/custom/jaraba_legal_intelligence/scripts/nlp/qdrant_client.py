"""Qdrant client wrapper for the Legal Intelligence Hub.

Provides a high-level interface on top of ``qdrant_client`` for
indexing, searching and managing vectors in the Legal Intelligence Hub
collections (``legal_intelligence`` and ``legal_intelligence_eu``).

Usage::

    from qdrant_client import LegalQdrantClient

    client = LegalQdrantClient(url="http://localhost:6333")
    results = client.search(
        collection="legal_intelligence",
        vector=[0.1, 0.2, ...],
        limit=5,
    )
"""

from __future__ import annotations

from typing import Any

import structlog
from qdrant_client import QdrantClient
from qdrant_client.http import models as qdrant_models
from qdrant_client.http.exceptions import UnexpectedResponse

log = structlog.get_logger()


class LegalQdrantClient:
    """Wrapper for Qdrant operations in the Legal Intelligence Hub.

    Provides high-level methods for indexing, searching and managing
    vectors in the ``legal_intelligence`` and ``legal_intelligence_eu``
    collections.

    Parameters
    ----------
    url:
        Qdrant server URL (e.g. ``http://localhost:6333``).
    api_key:
        Optional API key for authenticated Qdrant instances.
    """

    def __init__(self, url: str, api_key: str | None = None) -> None:
        self._url = url
        self._client = QdrantClient(url=url, api_key=api_key)
        log.info("qdrant.init", url=url)

    # ------------------------------------------------------------------
    # Public API
    # ------------------------------------------------------------------

    def upsert_points(
        self,
        collection: str,
        points: list[dict[str, Any]],
    ) -> None:
        """Batch upsert points into a Qdrant collection.

        Each dict in *points* must contain:

        - ``id`` — unique point identifier (int or UUID string)
        - ``vector`` — embedding vector (``list[float]``)
        - ``payload`` — metadata dict

        Parameters
        ----------
        collection:
            Target collection name.
        points:
            List of point dicts to upsert.

        Raises
        ------
        ValueError
            If any point dict is missing required keys.
        UnexpectedResponse
            On Qdrant communication errors.
        """
        if not points:
            log.debug("qdrant.upsert_points.empty", collection=collection)
            return

        qdrant_points: list[qdrant_models.PointStruct] = []

        for idx, pt in enumerate(points):
            for required_key in ("id", "vector", "payload"):
                if required_key not in pt:
                    raise ValueError(
                        f"Point at index {idx} is missing required key "
                        f"'{required_key}'."
                    )

            qdrant_points.append(
                qdrant_models.PointStruct(
                    id=pt["id"],
                    vector=pt["vector"],
                    payload=pt["payload"],
                )
            )

        log.info(
            "qdrant.upsert_points",
            collection=collection,
            count=len(qdrant_points),
        )

        self._client.upsert(
            collection_name=collection,
            points=qdrant_points,
        )

        log.info(
            "qdrant.upsert_points.done",
            collection=collection,
            count=len(qdrant_points),
        )

    def search(
        self,
        collection: str,
        vector: list[float],
        limit: int = 10,
        filter_conditions: dict[str, Any] | None = None,
    ) -> list[dict[str, Any]]:
        """Perform a vector similarity search.

        Parameters
        ----------
        collection:
            Collection to search in.
        vector:
            Query embedding vector.
        limit:
            Maximum number of results to return.
        filter_conditions:
            Optional Qdrant filter expressed as a dict.  When provided it
            is converted to a ``qdrant_models.Filter`` via the ``must``
            clause.  Each key/value pair becomes a ``FieldCondition``
            with a ``MatchValue``.

        Returns
        -------
        list[dict]
            Search results, each dict containing ``id``, ``score`` and
            ``payload``.
        """
        query_filter: qdrant_models.Filter | None = None

        if filter_conditions:
            must_conditions: list[qdrant_models.FieldCondition] = []
            for field, value in filter_conditions.items():
                must_conditions.append(
                    qdrant_models.FieldCondition(
                        key=field,
                        match=qdrant_models.MatchValue(value=value),
                    )
                )
            query_filter = qdrant_models.Filter(must=must_conditions)

        log.info(
            "qdrant.search",
            collection=collection,
            limit=limit,
            has_filter=filter_conditions is not None,
        )

        results = self._client.search(
            collection_name=collection,
            query_vector=vector,
            limit=limit,
            query_filter=query_filter,
        )

        hits: list[dict[str, Any]] = []
        for point in results:
            hits.append({
                "id": point.id,
                "score": point.score,
                "payload": point.payload,
            })

        log.info(
            "qdrant.search.done",
            collection=collection,
            hits=len(hits),
        )
        return hits

    def delete_by_resolution_id(
        self,
        collection: str,
        resolution_id: int,
    ) -> None:
        """Delete all points matching a specific resolution ID.

        Uses a payload filter on the ``resolution_id`` field to identify
        and remove the relevant vectors.

        Parameters
        ----------
        collection:
            Target collection name.
        resolution_id:
            The Drupal node / resolution ID whose vectors should be
            removed.
        """
        log.info(
            "qdrant.delete_by_resolution_id",
            collection=collection,
            resolution_id=resolution_id,
        )

        self._client.delete(
            collection_name=collection,
            points_selector=qdrant_models.FilterSelector(
                filter=qdrant_models.Filter(
                    must=[
                        qdrant_models.FieldCondition(
                            key="resolution_id",
                            match=qdrant_models.MatchValue(
                                value=resolution_id,
                            ),
                        ),
                    ],
                ),
            ),
        )

        log.info(
            "qdrant.delete_by_resolution_id.done",
            collection=collection,
            resolution_id=resolution_id,
        )

    def get_collection_info(self, collection: str) -> dict[str, Any]:
        """Retrieve collection statistics and configuration.

        Parameters
        ----------
        collection:
            Collection name.

        Returns
        -------
        dict
            Dictionary with ``status``, ``vectors_count``,
            ``points_count``, and ``config`` keys.
        """
        log.debug("qdrant.get_collection_info", collection=collection)

        info = self._client.get_collection(collection_name=collection)

        result: dict[str, Any] = {
            "status": str(info.status),
            "vectors_count": info.vectors_count,
            "points_count": info.points_count,
            "config": {
                "params": (
                    info.config.params.model_dump()
                    if info.config and info.config.params
                    else None
                ),
            },
        }

        log.debug(
            "qdrant.get_collection_info.done",
            collection=collection,
            vectors_count=result["vectors_count"],
        )
        return result

    def health_check(self) -> bool:
        """Check connectivity to the Qdrant instance.

        Returns
        -------
        bool
            ``True`` if the Qdrant server is reachable and responsive,
            ``False`` otherwise.
        """
        try:
            # qdrant_client exposes a simple health/readiness probe via
            # the collections list endpoint.  If it responds, the server
            # is up.
            self._client.get_collections()
            log.debug("qdrant.health_check.ok", url=self._url)
            return True
        except Exception as exc:
            log.warning(
                "qdrant.health_check.failed",
                url=self._url,
                error=str(exc),
            )
            return False
