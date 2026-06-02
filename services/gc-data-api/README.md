## GC Data API (v1)

Servicio API (Laravel) que se conecta a PostgreSQL remoto y expone endpoints `v1` para el dashboard / n8n.

### Auth

Recomendado: `Authorization: Bearer <GC_API_TOKEN>`.
Compatible: `X-API-Key: <GC_API_TOKEN>`.

Para login del dashboard:
- `POST /api/v1/auth/login` se llama con `X-API-Key` (service key) y devuelve un token de usuario `u1.*`.
- Luego el dashboard usa `Authorization: Bearer u1.*` en el resto de endpoints.

### Variables `.env`

- `DB_CONNECTION=pgsql`
- `DB_HOST`, `DB_PORT=5432`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `GC_API_TOKEN=...`
- `TENANT_MODE=subdomain`
- `TENANT_BASE_DOMAIN=midominio.com`

### Ejecutar local (solo API)

`composer install`
`php artisan serve --port=8001`

### Probar

`curl -H "Authorization: Bearer $GC_API_TOKEN" http://127.0.0.1:8001/api/v1/health`
