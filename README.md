# Fleet

Golyv senior full-stack assessment: bus booking for Egypt city trips.

This is a monorepo. Booking rules are not in this commit yet.

| Path | Role |
|---|---|
| `backend/` | Laravel 13 API (PHP 8.4) |
| `frontend/` | Next.js 16.3.3 App Router |
| `docker-compose.yml` | Postgres 16, Redis 7, API, web |

## Run

Composer and npm install **inside the image builds**. You do not need `composer install` or `npm install` on the host.

```bash
docker compose up --build
```

- API: http://localhost:8000
- Web: http://localhost:3000
- Postgres: `fleet` / `fleet` / `secret` on port 5432
- Redis: port 6379

Postgres is the assessment database. Redis is in Compose because seat locking will use it later — not as a cache of availability.
