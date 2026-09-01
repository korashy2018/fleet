# Fleet

Golyv senior full-stack assessment: Egypt city bus booking. Laravel 13 API + Next.js 16.3.3. Postgres for this run; the schema and lock ports stay MySQL-switchable.

Two guests book the same seat at the same instant. Redis holds `booking:{trip}:{seat}` so one write commits; the other waits, sees the occupied segment, and the UI shows **409**, clears the selection, and refreshes availability.

![Two guests racing for the same seat](assets/booking-race.gif)

| Path | Role |
|---|---|
| `backend/` | Laravel 13 API (PHP 8.4), domain in `src/` |
| `frontend/` | Next.js 16.3.3 App Router |
| `docker-compose.yml` | Postgres 16, Redis 7, PHP-FPM, Nginx, web |
| `compose.env.example` | Service DNS names and host ports |
| `backend/.env.example` | Laravel / PHP-FPM |
| `frontend/.env.example` | Next.js |

## Run

Host PHP is required because `backend/` (including `vendor/`) is bind-mounted into `api`.

```bash
cp compose.env.example compose.env
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
cd backend && composer install && cd ..
cd frontend && npm install && cd ..
docker compose --env-file compose.env up --build
```

Leave `APP_KEY` empty. The API entrypoint generates one, writes it into `backend/.env`, and reuses it on later starts. It then migrates and seeds if the catalog is empty.

| File | Owns |
|---|---|
| `compose.env` | DNS names (`postgres`, `api`, …) and host ports |
| `backend/.env` | App key, database, Redis, session, cache |
| `frontend/.env` | `NEXT_PUBLIC_API_URL` |

If you change `NGINX_PORT`, update `APP_URL` and `NEXT_PUBLIC_API_URL` to match. Postgres user/password in Compose must match `DB_*` in `backend/.env`.

PHP edits on the host show up without rebuilding. Rebuild the image when the Dockerfile or PHP extensions change.

```bash
docker compose --env-file compose.env up -d --build api
docker compose --env-file compose.env down
docker compose --env-file compose.env down -v   # drop Postgres data too
```

## URLs

- API: http://localhost:8000/api/v1
- **Swagger UI:** http://localhost:8000/docs
- OpenAPI YAML: http://localhost:8000/docs/openapi.yaml
- Web: http://localhost:3000 — guest booking workspace
- Postgres / Redis: ports from `compose.env`

## API

Guest booking is the primary path. Optional Sanctum Bearer on `POST /bookings` stamps `user_id`.

| Method | Path | Auth |
|---|---|---|
| GET | `/api/v1/trips` | public |
| GET | `/api/v1/trips/{id}` | public |
| GET | `/api/v1/trips/{id}/available-seats?start_station_id=&end_station_id=` | public |
| POST | `/api/v1/bookings` | optional Bearer |
| POST | `/api/v1/register` | public |
| POST | `/api/v1/login` | public |
| GET | `/api/v1/me` | Bearer |

Envelope: `{ "data": ... }` or `{ "error": { "code", "message", "details?" } }`. Try the flows in Swagger rather than copying curl from here.

Seeded catalog: Cairo → Fayyum → Minya → Asyut (trip 1) and a second trip via Giza. Seat 5 on trip 1 is booked Cairo→Minya and Minya→Asyut (adjacent handover, no overlap).

## Tests

PHPUnit (no Pest). Domain tests do not boot Laravel.

```bash
cd backend
php artisan test
php artisan test --testsuite=Domain
php artisan test tests/Feature/Api
php artisan test tests/Feature/Booking/RedisSeatLockTest.php
```

The Redis lock test skips if Redis is not reachable on `127.0.0.1`. With Compose up, map `REDIS_HOST` for that one case or run it against the published Redis port.

## Notes

Postgres is the assessment database. Redis is the **seat lock**, not a session store and not an availability cache. `SESSION_DRIVER=file`. If Redis is down, booking fails closed (`503` / `lock_unavailable`).
