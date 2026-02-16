"""FastAPI microservice for the Legal Intelligence Hub NLP pipeline.

Main entry point for the NLP service. Provides endpoints for:
- Health checking
- Spanish legal text segmentation (sections extraction)
- Legal named entity recognition (NER)

Startup: uvicorn pipeline:app --host 0.0.0.0 --port 8001
"""

from __future__ import annotations

import re
from collections.abc import AsyncGenerator
from contextlib import asynccontextmanager
from typing import Any

import spacy
import structlog
from fastapi import FastAPI, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field

from legal_ner import LegalNER

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------

structlog.configure(
    processors=[
        structlog.contextvars.merge_contextvars,
        structlog.processors.add_log_level,
        structlog.processors.StackInfoRenderer(),
        structlog.dev.set_exc_info,
        structlog.processors.TimeStamper(fmt="iso"),
        structlog.processors.JSONRenderer(),
    ],
    wrapper_class=structlog.make_filtering_bound_logger(0),
    context_class=dict,
    logger_factory=structlog.PrintLoggerFactory(),
    cache_logger_on_first_use=True,
)

log = structlog.get_logger()

# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------

VERSION = "1.0.0"
SPACY_MODEL = "es_core_news_lg"
TEXT_LIMIT = 50_000

# Section markers used to split Spanish legal texts.
# Order matters: more specific patterns must come before shorter ones.
SECTION_MARKERS: list[tuple[str, re.Pattern[str]]] = [
    ("fundamentos", re.compile(
        r"^\s*(?:FUNDAMENTOS?\s+DE\s+DERECHO|FUNDAMENTOS?)\s*$",
        re.IGNORECASE | re.MULTILINE,
    )),
    ("antecedentes", re.compile(
        r"^\s*ANTECEDENTES?\s*(?:DE\s+HECHO)?\s*$",
        re.IGNORECASE | re.MULTILINE,
    )),
    ("hechos", re.compile(
        r"^\s*HECHOS?\s*(?:PROBADOS?)?\s*$",
        re.IGNORECASE | re.MULTILINE,
    )),
    ("fallo", re.compile(
        r"^\s*(?:FALLO|RESUELVE|RESOLVEMOS|DECIDIMOS|PARTE\s+DISPOSITIVA)\s*$",
        re.IGNORECASE | re.MULTILINE,
    )),
    ("voto_particular", re.compile(
        r"^\s*VOTO\s+PARTICULAR\s*$",
        re.IGNORECASE | re.MULTILINE,
    )),
    ("disposicion", re.compile(
        r"^\s*DISPOSICI[OÓ]N\s*$",
        re.IGNORECASE | re.MULTILINE,
    )),
    ("vistos", re.compile(
        r"^\s*VISTOS?\s*$",
        re.IGNORECASE | re.MULTILINE,
    )),
    ("resultando", re.compile(
        r"^\s*RESULTANDO\s*$",
        re.IGNORECASE | re.MULTILINE,
    )),
    ("considerando", re.compile(
        r"^\s*CONSIDERANDO\s*$",
        re.IGNORECASE | re.MULTILINE,
    )),
]

# ---------------------------------------------------------------------------
# Application state
# ---------------------------------------------------------------------------


class AppState:
    """Container for shared runtime resources."""

    nlp: spacy.language.Language | None = None
    legal_ner: LegalNER | None = None


_state = AppState()


# ---------------------------------------------------------------------------
# Lifespan
# ---------------------------------------------------------------------------


@asynccontextmanager
async def lifespan(app: FastAPI) -> AsyncGenerator[None, None]:
    """Load spaCy model and initialise NER on startup; clean up on shutdown."""
    log.info("pipeline.startup", spacy_model=SPACY_MODEL, version=VERSION)
    try:
        _state.nlp = spacy.load(SPACY_MODEL)
        log.info("pipeline.spacy_loaded", model=SPACY_MODEL)
    except OSError:
        log.error(
            "pipeline.spacy_model_missing",
            model=SPACY_MODEL,
            hint=f"Run: python -m spacy download {SPACY_MODEL}",
        )
        raise RuntimeError(
            f"spaCy model '{SPACY_MODEL}' not found. "
            f"Install it with: python -m spacy download {SPACY_MODEL}"
        )

    _state.legal_ner = LegalNER(_state.nlp)
    log.info("pipeline.legal_ner_ready")

    yield

    # Shutdown cleanup.
    log.info("pipeline.shutdown")
    _state.nlp = None
    _state.legal_ner = None


# ---------------------------------------------------------------------------
# FastAPI app
# ---------------------------------------------------------------------------

app = FastAPI(
    title="Legal Intelligence Hub — NLP Pipeline",
    description=(
        "Microservicio NLP para segmentación de textos jurídicos españoles "
        "y extracción de entidades legales."
    ),
    version=VERSION,
    lifespan=lifespan,
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ---------------------------------------------------------------------------
# Pydantic models — request / response
# ---------------------------------------------------------------------------


class SegmentRequest(BaseModel):
    """Request body for ``POST /api/segment``."""

    text: str = Field(..., max_length=TEXT_LIMIT, description="Spanish legal text to segment.")
    source_id: str = Field(..., min_length=1, description="Unique identifier of the source document.")


class SegmentItem(BaseModel):
    """A single section extracted from the legal text."""

    section: str
    text: str


class NERRequest(BaseModel):
    """Request body for ``POST /api/ner``."""

    text: str = Field(..., max_length=TEXT_LIMIT, description="Text to extract legal entities from.")


class EntityItem(BaseModel):
    """A single extracted legal entity."""

    type: str
    subtype: str
    reference: str
    start: int
    end: int
    context: str


class NERResponse(BaseModel):
    """Response body for ``POST /api/ner``."""

    entities: list[EntityItem]


class HealthResponse(BaseModel):
    """Response body for ``GET /health``."""

    status: str
    spacy_model: str
    version: str


# ---------------------------------------------------------------------------
# Endpoints
# ---------------------------------------------------------------------------


@app.get("/health", response_model=HealthResponse, tags=["system"])
async def health() -> HealthResponse:
    """Return service health information."""
    return HealthResponse(
        status="ok",
        spacy_model=SPACY_MODEL,
        version=VERSION,
    )


@app.post("/api/segment", response_model=list[SegmentItem], tags=["nlp"])
async def segment(request: SegmentRequest) -> list[SegmentItem]:
    """Segment a Spanish legal text into its structural sections.

    Uses spaCy sentence segmentation combined with regex-based section
    marker detection to split resolutions, sentencias, and other legal
    documents into labelled sections.
    """
    if _state.nlp is None:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="spaCy model is not loaded.",
        )

    text = request.text.strip()
    if not text:
        return []

    log.info(
        "segment.start",
        source_id=request.source_id,
        text_length=len(text),
    )

    sections = _segment_text(text)

    log.info(
        "segment.done",
        source_id=request.source_id,
        sections_found=len(sections),
    )
    return sections


@app.post("/api/ner", response_model=NERResponse, tags=["nlp"])
async def ner(request: NERRequest) -> NERResponse:
    """Extract legal named entities from the given text.

    Identifies legislation references, court decisions, EU directives /
    regulations, ECLI codes, CELEX numbers and other Spanish legal
    identifiers.
    """
    if _state.legal_ner is None:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="Legal NER engine is not loaded.",
        )

    text = request.text.strip()
    if not text:
        return NERResponse(entities=[])

    log.info("ner.start", text_length=len(text))

    raw_entities = _state.legal_ner.extract(text)
    entities = [EntityItem(**ent) for ent in raw_entities]

    log.info("ner.done", entities_found=len(entities))
    return NERResponse(entities=entities)


# ---------------------------------------------------------------------------
# Segmentation helpers
# ---------------------------------------------------------------------------


def _segment_text(text: str) -> list[SegmentItem]:
    """Split *text* into labelled legal sections.

    The algorithm:
    1. Scan the text for all known section markers and record their
       positions.
    2. Sort the markers by position.
    3. Extract the text between consecutive markers (and before the first
       marker as ``encabezamiento`` if present).
    """
    # Collect all marker hits: (start, end, section_label).
    hits: list[tuple[int, int, str]] = []

    for label, pattern in SECTION_MARKERS:
        for m in pattern.finditer(text):
            hits.append((m.start(), m.end(), label))

    if not hits:
        # No recognisable structure — return full text as single block.
        return [SegmentItem(section="texto_completo", text=text.strip())]

    # Sort by position in the document.
    hits.sort(key=lambda h: h[0])

    sections: list[SegmentItem] = []

    # Text before the first marker → "encabezamiento".
    preamble = text[: hits[0][0]].strip()
    if preamble:
        sections.append(SegmentItem(section="encabezamiento", text=preamble))

    for idx, (start, end, label) in enumerate(hits):
        # Text runs from just after the marker to the start of the next
        # marker (or end of document).
        next_start = hits[idx + 1][0] if idx + 1 < len(hits) else len(text)
        body = text[end:next_start].strip()
        if body:
            sections.append(SegmentItem(section=label, text=body))

    return sections
