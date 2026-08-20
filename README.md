# PHPAML Movies API

A small, read-only REST API built with classic PHPAML. It exposes a seeded movie catalog as JSON with search, genre filtering, pagination, consistent errors, CORS headers, and an OpenAPI document.

## Run locally

```bash
aml install
cp .env.example .env
aml serve
```

AML starts at `http://127.0.0.1:8910` or the next available port.

## Endpoints

```text
GET /                         API information
GET /api/v1/movies            Paginated catalog
GET /api/v1/movies/{id}       One movie
GET /api/v1/genres            Available genres
GET /openapi.json             OpenAPI 3.1 specification
```

Search and filter the catalog:

```bash
curl "http://127.0.0.1:8910/api/v1/movies?q=Villeneuve"
curl "http://127.0.0.1:8910/api/v1/movies?genre=Science%20Fiction&page=1&per_page=5"
```

## Test

```bash
aml test
```

The demo intentionally contains no HTML interface. SQLite is created inside `runtime/storage/` on first access and populated with a small catalog.
