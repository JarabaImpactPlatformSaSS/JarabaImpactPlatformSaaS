"""CRM resource."""

from __future__ import annotations

from typing import Any, TYPE_CHECKING

if TYPE_CHECKING:
    from jaraba.client import JarabaClient


class CrmResource:
    """Manage CRM contacts, companies, and opportunities.

    Usage::

        contacts = client.crm.list_contacts()
        opportunity = client.crm.create_opportunity(
            name="New Deal",
            value=5000,
            stage="qualified",
        )
    """

    def __init__(self, client: JarabaClient) -> None:
        self._client = client

    def list_contacts(self, **params: Any) -> dict[str, Any]:
        """List CRM contacts."""
        return self._client.get("/crm/contacts", **params)

    def create_contact(self, data: dict[str, Any]) -> dict[str, Any]:
        """Create a CRM contact."""
        return self._client.post("/crm/contacts", data)

    def list_companies(self, **params: Any) -> dict[str, Any]:
        """List CRM companies."""
        return self._client.get("/crm/companies", **params)

    def create_company(self, data: dict[str, Any]) -> dict[str, Any]:
        """Create a CRM company."""
        return self._client.post("/crm/companies", data)

    def list_opportunities(self, **params: Any) -> dict[str, Any]:
        """List CRM opportunities."""
        return self._client.get("/crm/opportunities", **params)

    def create_opportunity(
        self,
        name: str,
        value: float,
        stage: str = "lead",
        **extra: Any,
    ) -> dict[str, Any]:
        """Create a CRM opportunity."""
        payload: dict[str, Any] = {
            "name": name,
            "value": value,
            "stage": stage,
            **extra,
        }
        return self._client.post("/crm/opportunities", payload)

    def pipeline_stages(self) -> dict[str, Any]:
        """List pipeline stages."""
        return self._client.get("/crm/pipeline-stages")
