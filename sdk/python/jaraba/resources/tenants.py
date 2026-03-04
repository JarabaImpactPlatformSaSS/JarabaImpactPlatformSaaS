"""Tenant management resource."""

from __future__ import annotations

from typing import Any, TYPE_CHECKING

if TYPE_CHECKING:
    from jaraba.client import JarabaClient


class TenantsResource:
    """Manage tenants (organizations).

    Usage::

        tenants = client.tenants.list()
        tenant = client.tenants.get(1)
        usage = client.tenants.usage(1)
    """

    def __init__(self, client: JarabaClient) -> None:
        self._client = client

    def list(self, **params: Any) -> dict[str, Any]:
        """List tenants the authenticated user has access to."""
        return self._client.get("/tenants", **params)

    def get(self, tenant_id: int) -> dict[str, Any]:
        """Get a specific tenant by ID."""
        return self._client.get(f"/tenants/{tenant_id}")

    def usage(self, tenant_id: int) -> dict[str, Any]:
        """Get usage metrics for a tenant."""
        return self._client.get(f"/tenants/{tenant_id}/usage")
