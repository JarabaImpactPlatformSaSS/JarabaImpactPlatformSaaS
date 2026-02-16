# ---------------------------------------------------------------------------
# Dockerfile -- Legal NLP FastAPI microservice
# Jaraba Impact Platform SaaS / Legal Intelligence Hub
#
# Builds a production image with spaCy + es_core_news_lg preloaded.
# Single-stage because the spaCy model download requires the full runtime.
# ---------------------------------------------------------------------------

FROM python:3.12-slim

# -- OCI / opencontainers labels ------------------------------------------
LABEL maintainer="Jaraba Impact Platform <dev@jaraba.io>" \
      org.opencontainers.image.title="legal-nlp-service" \
      org.opencontainers.image.description="Legal NLP pipeline (spaCy + FastAPI) for the Legal Intelligence Hub" \
      org.opencontainers.image.vendor="Jaraba Impact Platform SaaS" \
      org.opencontainers.image.source="https://github.com/JarabaImpactPlatformSaaS" \
      org.opencontainers.image.licenses="Proprietary"

# -- Environment -----------------------------------------------------------
ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PIP_NO_CACHE_DIR=1 \
    PIP_DISABLE_PIP_VERSION_CHECK=1 \
    APP_ENV=production \
    APP_PORT=8001 \
    SPACY_MODEL=es_core_news_lg

WORKDIR /app

# -- System dependencies ---------------------------------------------------
# gcc and build-essential are required to compile spaCy C extensions.
# curl is kept for the HEALTHCHECK probe.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       gcc \
       build-essential \
       curl \
    && rm -rf /var/lib/apt/lists/*

# -- Python dependencies ---------------------------------------------------
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# -- spaCy model (Spanish large) ------------------------------------------
RUN python -m spacy download ${SPACY_MODEL}

# -- Non-root user ---------------------------------------------------------
RUN groupadd --system appgroup \
    && useradd --system --gid appgroup --home-dir /app --no-create-home appuser

# -- Application code ------------------------------------------------------
COPY . .

# Ensure the non-root user owns the application directory.
RUN chown -R appuser:appgroup /app

USER appuser

# -- Networking ------------------------------------------------------------
EXPOSE ${APP_PORT}

# -- Health check ----------------------------------------------------------
# Hits the FastAPI /health endpoint every 30 s; considers the container
# unhealthy after 3 consecutive failures (90 s window).
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl --fail --silent http://localhost:${APP_PORT}/health || exit 1

# -- Entrypoint ------------------------------------------------------------
CMD ["uvicorn", "pipeline:app", \
     "--host", "0.0.0.0", \
     "--port", "8001", \
     "--workers", "2", \
     "--log-level", "info", \
     "--access-log"]
