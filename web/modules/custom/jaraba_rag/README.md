# Jaraba RAG

Módulo de Knowledge Base AI-Nativa con Retrieval-Augmented Generation (RAG) usando Qdrant como base de datos vectorial.

## Descripción

Este módulo implementa la indexación y búsqueda semántica de contenido del CMS para potenciar respuestas de IA contextuales.

## Características

- **Indexación automática**: Productos, páginas y contenido se indexan automáticamente
- **Chunking inteligente**: Texto dividido en fragmentos de 500 tokens con overlap
- **Embeddings OpenAI**: Vectores de 1536 dimensiones
- **Arquitectura dual**: Lando (desarrollo) + Cloud (producción)
- **Multi-tenant**: Filtros por tenant en búsquedas

## Arquitectura

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Drupal    │───▶│ KbIndexer   │───▶│   Qdrant    │
│   Entity    │    │  Service    │    │ jaraba_kb   │
└─────────────┘    └─────────────┘    └─────────────┘
```

## Dependencias

- `ecosistema_jaraba_core`
- Qdrant (v1.16+) - via Lando o Cloud
- OpenAI API Key (para embeddings)

## Instalación

```bash
drush en jaraba_rag -y
drush cr
```

## Configuración

1. Configurar API keys en `/admin/config/jaraba/rag`
2. Qdrant host se configura via `settings.php`:

```php
// En settings.jaraba_rag.php
$config['jaraba_rag.settings']['vector_db']['host'] = 'http://qdrant:6333';
```

## Servicios

| Servicio | Descripción |
|----------|-------------|
| `jaraba_rag.kb_indexer` | Indexación de contenido |
| `jaraba_rag.qdrant_client` | Cliente HTTP para Qdrant |
| `jaraba_rag.embedding_service` | Generación de embeddings |
| `jaraba_rag.tenant_context` | Filtros multi-tenant |

## Hooks

- `hook_entity_insert`: Indexa nuevas entidades
- `hook_entity_update`: Re-indexa entidades modificadas
- `hook_entity_delete`: Elimina del índice

## Troubleshooting

### chunk_size = 0
Si los logs muestran `chunk_size: 0`, verificar que `settings.jaraba_rag.php` está incluido en `settings.php`.

### Qdrant connection error
Verificar host con: `curl http://qdrant:6333/collections`

## Mantenimiento

- **Autor**: Jaraba Development Team
- **Versión**: 5.1.0
- **Compatibilidad**: Drupal 11.x, PHP 8.4+, Qdrant 1.16+
