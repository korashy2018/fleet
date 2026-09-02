# Fleet — working plan

Golyv senior full-stack: Egypt bus booking. Laravel 13 API + Next.js 16.3.3. Postgres for this run; schema and lock ports must stay MySQL-switchable.

Architecture diagrams: [ARCHITECTURE.md](ARCHITECTURE.md). Decisions: [adr/](adr/README.md).

---

## Locked decisions

- Trip = named ordered stations on **one** bus. No datetimes. No Route aggregate.
- 12 seats per bus. Seat identity is **`(busId, number)`**, numbers **1–12**. No global seat id. No typed `*Id` value objects — **`int` everywhere**.
- Booking occupies a **half-open** segment `[startPosition, endPosition)`. Overlap: `a < d && c < b`. Adjacent handover (Cairo→Minya then Minya→Asyut) does **not** overlap.
- Bookings are immediate and immutable. Multiple bookings per passenger are allowed. Snapshot name + email. Optional `userId`.
- Persist **station ids and positions** on the booking row so a later route edit cannot rewrite history.
- **No Eloquent** for fleet tables. Query builder + explicit columns + mappers to domain. `User` may stay Eloquent when Sanctum lands.
- **No** `int4range`, `btree_gist`, `EXCLUDE`, `pg_advisory_xact_lock`, or other vendor SQL.
- Redis is the **lock** for booking, not an availability cache, not sessions. `SESSION_DRIVER=file`. Fail closed if Redis is down (`503`).
- Lock key: `(tripId, seatNumber)`. Hold across the DB transaction. TTL ~15s. Token release.
- Domain in `backend/src/Domain`, PHPUnit + `#[DataProvider]`, domain tests extend `PHPUnit\Framework\TestCase`. No Pest. No CQRS. No domain events.
- HTTP `/api/v1`. Envelope `{ data }` / `{ error: { code, message, details? } }`. Extra `GET /trips` even though the spec listed two APIs.
- Thin optional Sanctum. Guest booking is the primary path. Bearer on `POST /bookings` stamps `user_id`.
- Swagger UI at `/docs`, spec at `/docs/openapi.yaml` (committed `backend/docs/openapi.yaml`).
- Compose: Postgres, Redis, PHP-FPM (`api`), Nginx, Next.js. Topology in `compose.env`; Laravel in `backend/.env`; Next in `frontend/.env`. Bind the whole `backend/` tree onto `api` (source + `vendor` + `.env`). Rebuild the image only for Dockerfile / PHP extension changes. Opcache revalidates timestamps.

---

## Phases

| # | Status | What |
|---|---|---|
| 1 | Done | Monorepo: Laravel 13, Next 16.3.3, Compose, CI stub |
| 2 | Done | Pure domain: Segment, Trip, Station, Seat, Bus, Booking, SeatAvailability + PHPUnit |
| 3 | Done | Portable migrations, repository ports, query-builder adapters, seeders |
| 4 | Done | Redis `SeatLock` + `BookSeat` / `GetAvailableSeats` |
| 5 | Done | REST `/api/v1`, error envelope, OpenAPI, feature tests |
| 6 | Done | Thin Sanctum register/login/me; optional `user_id` on booking |
| 7 | Done | Next.js booking UI + 409 conflict UX + TanStack Query |
| 8 | Done | Vitest / Testing Library |
| 9 | Done | README, ADRs, architecture write-up |

---

## Domain (done)

```
backend/src/Domain/
  Station/Station.php
  Bus/Seat.php, Bus.php          # Bus::withTwelveSeats, seat(n)
  Trip/Segment.php, Trip.php     # define(...stationIds), segmentBetween, positionOf
  Trip/InvalidSegment.php, StationNotOnTrip.php
  Booking/Passenger.php, Booking.php, SeatAvailability.php
```

Seat 5 Cairo→Minya blocks Fayyum legs on that seat and leaves Minya→Asyut free.

---

## Phase 3 — persistence (next)

**Schema (integer PKs, Laravel schema builder only):**

- `stations` — `id`, `name`
- `buses` — `id` (seats are not a table; 1–12 belong to the bus in domain)
- `trips` — `id`, `name`, `bus_id` FK
- `trip_stations` — `trip_id`, `station_id`, `position`; unique `(trip_id, station_id)`, unique `(trip_id, position)`
- `bookings` — `id`, `trip_id`, **`seat_number`**, `start_station_id`, `end_station_id`, `start_position`, `end_position`, `user_id` nullable, `passenger_name`, `passenger_email`, timestamps
- Index `(trip_id, seat_number)`
- `unsignedTinyInteger` / `unsignedInteger` for seat and positions. Laravel 13’s schema builder has no portable `check()`. Domain still rejects seat 0/13 and `start >= end`.
- No unique on `(trip_id, seat_number, start, end)` — that does not prevent overlap

**Seed:** 5 stations (Cairo, Giza, Al Fayyum, Al Minya, Asyut), 2 buses, 2 trips (Cairo→Asyut spec stops; a second trip that includes Giza), a few non-overlapping sample bookings.

**First file batch (Keep / Reject in the editor):**

| File | Role |
|---|---|
| `backend/database/migrations/xxxx_create_fleet_tables.php` | Portable catalog + bookings |
| `backend/src/Domain/Trip/TripRepository.php` | Port: find / all |
| `backend/src/Domain/Booking/BookingRepository.php` | Port: forTrip, forTripAndSeat, save |
| `backend/src/Domain/Station/StationRepository.php` | Port: find / all |
| `backend/src/Domain/Bus/BusRepository.php` | Port: find → `Bus::withTwelveSeats` |
| `backend/app/Infrastructure/Persistence/Mappers/*.php` | Row ↔ domain |
| `backend/app/Infrastructure/Persistence/Query*Repository.php` | `DB::table` + explicit `select` |
| `backend/app/Providers/AppServiceProvider.php` | Bind ports |
| `backend/database/seeders/DatabaseSeeder.php` | Catalog + sample bookings via query builder |
| `backend/tests/Feature/Persistence/*Test.php` | Round-trip against sqlite memory |

Do **not** add Eloquent models for fleet tables. Do **not** add `BookSeat` or Redis lock yet (Phase 4).

---

## Phase 4 — booking use cases

- Port `SeatLock::acquire(tripId, seatNumber)` → Redis adapter (`Cache::lock`). Fail closed.
- `BookSeat`: lock → DB transaction → load trip + bookings for that seat → domain overlap → insert → commit → release.
- `GetAvailableSeats`: no lock; may be stale.
- Redis lock is tested through the same PHPUnit case as the adapter: `Cache::store('redis')->lock()` is exclusive on `(trip, seat)`. Sequential `BookSeat` tests still use `PassThroughSeatLock`. Two OS workers are unnecessary for that lock proof.

---

## Phase 5 — HTTP

| Method | Path | Auth |
|---|---|---|
| GET | `/api/v1/trips` | public |
| GET | `/api/v1/trips/{id}` | public |
| GET | `/api/v1/trips/{id}/available-seats?start_station_id&end_station_id` | public |
| POST | `/api/v1/bookings` | optional Bearer — `user_id` when present |
| POST | `/api/v1/register` | public |
| POST | `/api/v1/login` | public |
| GET | `/api/v1/me` | Bearer |

`POST /bookings` uses **`seat_number`**, not `seat_id`.

Errors: 422 validation / invalid station / order / seat; 404 trip; 409 `seat_unavailable`; 503 `lock_unavailable`.

OpenAPI lives at `backend/docs/openapi.yaml` (HTTP `/docs/openapi.yaml`).

---

## Phase 6–8 — auth and frontend

- Sanctum token auth, thin. Guest still books.
- Next.js booking workspace, TanStack Query, 409 → message + clear seat + refetch availability. No success toast on conflict.
- Vitest: seats, success, loading, error, 409 refresh/reset.

---

## Phase 9 — what *does* go in git

README, locking explanation, production notes. ADRs live in [`adr/`](adr/README.md). Justifications live in [ARCHITECTURE.md](ARCHITECTURE.md).

---

## Out of scope

Payments, cancel/expire, waitlists, admin CRUD, schedules, multi-bus trips, microservices, availability cache, Redlock / Redis Cluster.
