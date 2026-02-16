"""Custom legal Named Entity Recognition for Spanish legal texts.

Provides regex-based extraction of legislation references, court decisions,
EU directives/regulations, ECLI codes, CELEX numbers and other identifiers
commonly found in Spanish and EU legal documents.

Usage::

    import spacy
    from legal_ner import LegalNER

    nlp = spacy.load("es_core_news_lg")
    ner = LegalNER(nlp)
    entities = ner.extract("El artículo 24.2 de la Ley Orgánica 6/1985 ...")
"""

from __future__ import annotations

import re
from typing import Any

import spacy
import structlog

log = structlog.get_logger()

# ---------------------------------------------------------------------------
# Pattern definitions
# ---------------------------------------------------------------------------

# Raw pattern strings.  Compiled in ``LegalNER.__init__``.
PATTERNS: dict[str, str] = {
    "ley": (
        r"Ley\s+(?:Orgánica\s+)?(?:\d+/\d{4})"
        r"(?:,?\s+de\s+\d+\s+de\s+\w+)?"
    ),
    "rd": (
        r"Real\s+Decreto(?:-[Ll]ey)?\s+\d+/\d{4}"
        r"(?:,?\s+de\s+\d+\s+de\s+\w+)?"
    ),
    "articulo": (
        r"[Aa]rt(?:ículo|\.?)\s+\d+(?:\.\d+)?"
        r"(?:\s+(?:bis|ter|quater|quinquies|sexies))?"
    ),
    "sentencia": (
        r"S(?:T|entencia)\s*(?:del?\s+)?"
        r"(?:TS|TSJ|TC|AN|AP|TJUE|TEDH)\s+\d+/\d{4}"
    ),
    "consulta_dgt": r"V\d{4}-\d{2}",
    "directiva_ue": (
        r"Directiva\s+(?:\(UE\)\s+)?"
        r"(?:\d{2,4}/\d+/(?:CEE|CE|UE)|\(UE\)\s+\d{4}/\d+)"
    ),
    "reglamento_ue": (
        r"Reglamento\s+(?:\(UE\)\s+)?(?:n[ºo]\s*)?\d+/\d{4}"
    ),
    "ecli": r"ECLI:[A-Z]{2}:[A-Z0-9]+:\d{4}:\d+",
    "celex": r"\d{5}[A-Z]{1,2}\d{4}",
    "sts": (
        r"STS\s+\d+/\d{4}"
        r"(?:,?\s+de\s+\d+\s+de\s+\w+)?"
    ),
    "stc": r"STC\s+\d+/\d{4}",
    "lo": r"(?:L\.?O\.?\s+\d+/\d{4})",
}

# Map pattern keys to a human-readable subtype label.
_SUBTYPE_LABELS: dict[str, str] = {
    "ley": "ley",
    "rd": "real_decreto",
    "articulo": "articulo",
    "sentencia": "sentencia",
    "consulta_dgt": "consulta_dgt",
    "directiva_ue": "directiva_ue",
    "reglamento_ue": "reglamento_ue",
    "ecli": "ecli",
    "celex": "celex",
    "sts": "sentencia_ts",
    "stc": "sentencia_tc",
    "lo": "ley_organica",
}

# Context window: number of characters to include before and after the match.
_CONTEXT_CHARS = 100


# ---------------------------------------------------------------------------
# LegalNER class
# ---------------------------------------------------------------------------


class LegalNER:
    """Regex-based legal Named Entity Recognition for Spanish texts.

    Parameters
    ----------
    nlp:
        A loaded spaCy ``Language`` object.  Currently kept for potential
        future integration with spaCy pipelines (e.g. dependency-based
        disambiguation).
    """

    def __init__(self, nlp: spacy.language.Language) -> None:
        self._nlp = nlp
        self._compiled: dict[str, re.Pattern[str]] = {}

        for key, raw in PATTERNS.items():
            try:
                self._compiled[key] = re.compile(raw, re.UNICODE)
            except re.error as exc:
                log.error(
                    "legal_ner.pattern_compile_error",
                    pattern_key=key,
                    error=str(exc),
                )
                raise

        log.info("legal_ner.init", patterns_loaded=len(self._compiled))

    # ------------------------------------------------------------------
    # Public API
    # ------------------------------------------------------------------

    def extract(self, text: str) -> list[dict[str, Any]]:
        """Extract all legal entities from *text*.

        Returns a list of dicts, each containing:

        - ``type`` — always ``"legislation_ref"``
        - ``subtype`` — e.g. ``"ley"``, ``"real_decreto"``, ``"ecli"``
        - ``reference`` — the matched text
        - ``start`` — character offset (inclusive)
        - ``end`` — character offset (exclusive)
        - ``context`` — surrounding text snippet

        Overlapping matches are deduplicated: when two matches overlap the
        longer (or earlier, if same length) match wins.
        """
        raw_matches: list[dict[str, Any]] = []

        for key, pattern in self._compiled.items():
            for m in pattern.finditer(text):
                ctx_start = max(0, m.start() - _CONTEXT_CHARS)
                ctx_end = min(len(text), m.end() + _CONTEXT_CHARS)
                context = text[ctx_start:ctx_end].strip()

                raw_matches.append({
                    "type": "legislation_ref",
                    "subtype": _SUBTYPE_LABELS.get(key, key),
                    "reference": m.group(),
                    "start": m.start(),
                    "end": m.end(),
                    "context": context,
                })

        # Deduplicate overlapping spans.
        deduplicated = self._deduplicate(raw_matches)

        log.debug(
            "legal_ner.extract",
            raw_count=len(raw_matches),
            dedup_count=len(deduplicated),
        )
        return deduplicated

    # ------------------------------------------------------------------
    # Internal helpers
    # ------------------------------------------------------------------

    @staticmethod
    def _deduplicate(matches: list[dict[str, Any]]) -> list[dict[str, Any]]:
        """Remove overlapping matches, keeping the longest span.

        When two matches share character offsets the longer match is
        retained.  Ties are broken by earlier start position.
        """
        if not matches:
            return []

        # Sort by start ascending, then by span length descending so that
        # longer matches come first when starting at the same position.
        sorted_matches = sorted(
            matches,
            key=lambda m: (m["start"], -(m["end"] - m["start"])),
        )

        kept: list[dict[str, Any]] = [sorted_matches[0]]

        for candidate in sorted_matches[1:]:
            last = kept[-1]
            # Overlap check: candidate starts before the last kept match
            # ends.
            if candidate["start"] < last["end"]:
                # Keep the longer span.
                if (candidate["end"] - candidate["start"]) > (
                    last["end"] - last["start"]
                ):
                    kept[-1] = candidate
                # Otherwise discard the candidate (shorter or equal).
            else:
                kept.append(candidate)

        return kept
