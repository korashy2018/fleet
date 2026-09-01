# Fleet

Golyv senior full-stack assessment: bus booking for Egypt city trips.

This is a monorepo. Booking rules are not in this commit yet.

| Path | Role |
|---|---|
| `backend/` | Laravel 13 API (PHP 8.4) |
| `frontend/` | Next.js 16.3.3 App Router |
| `docker-compose.yml` | Postgres 16, Redis 7, PHP-FPM, Nginx, web |
| `compose.env.example` | Service names and published ports |
| `backend/.env.example` | Laravel / PHP-FPM |
| `frontend/.env.example` | Next.js |

## Run

Copy each template, then start. Composer and npm install inside the image builds.

```bash
cp compose.env.example compose.env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose --env-file compose.env up --build
```

| File | Owns |
|---|---|
| `compose.env` | DNS names (`postgres`, `api`, …) and host ports |
| `backend/.env` | App key, database, Redis, session, cache |
| `frontend/.env` | `NEXT_PUBLIC_API_URL` |

If `APP_KEY` is empty, the API entrypoint generates a key, **writes it into** `backend/.env` (bind-mounted), and exports it for PHP-FPM. Later restarts read that file and keep the same key.

If you change `NGINX_PORT`, update `APP_URL` and `NEXT_PUBLIC_API_URL` to match. Postgres user/password in Compose must match `DB_*` in `backend/.env`.

- API: http://localhost:8000 (Nginx → PHP-FPM)
- Web: http://localhost:3000
- Postgres: port from `compose.env`
- Redis: port from `compose.env`

Postgres is the assessment database. Redis is in Compose because seat locking will use it later — not for sessions or as a cache of availability. Laravel sessions stay on the `file` driver so `GET /` does not require Redis.
