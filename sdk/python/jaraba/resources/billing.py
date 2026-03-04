"""Billing and payments resource."""

from __future__ import annotations

from typing import Any, TYPE_CHECKING

if TYPE_CHECKING:
    from jaraba.client import JarabaClient


class BillingResource:
    """Manage subscriptions, invoices, and payment methods.

    Usage::

        subscription = client.billing.get_subscription()
        invoices = client.billing.list_invoices()
        plans = client.billing.list_plans()
    """

    def __init__(self, client: JarabaClient) -> None:
        self._client = client

    def list_plans(self, *, vertical: str | None = None) -> dict[str, Any]:
        """List available subscription plans."""
        params: dict[str, Any] = {}
        if vertical:
            params["vertical"] = vertical
        return self._client.get("/plans", **params)

    def get_subscription(self) -> dict[str, Any]:
        """Get current subscription details."""
        return self._client.get("/billing/subscription")

    def list_invoices(self) -> dict[str, Any]:
        """List billing invoices."""
        return self._client.get("/billing/invoices")

    def list_payment_methods(self) -> dict[str, Any]:
        """List saved payment methods."""
        return self._client.get("/billing/payment-methods")

    def proration_preview(self, new_price_id: str) -> dict[str, Any]:
        """Preview proration for a plan change."""
        return self._client.get("/billing/proration-preview", new_price_id=new_price_id)
