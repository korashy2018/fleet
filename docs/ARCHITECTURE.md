# Architecture

This is the review brief. The working plan is [PLAN.md](PLAN.md). Laravel is the host, not the model. Occupancy lives in `backend/src` (`Fleet\Domain`, `Fleet\Application`). HTTP, Redis, and SQL sit outside that core and are bound in `AppServiceProvider`. Domain tests boot PHPUnit only — no container, no Eloquent.

If we walk the code, start at `BookSeat` and `SeatAvailability`, then the Redis lock, then the HTTP envelope. The three decisions I will defend:

- [0001](adr/0001-redis-seat-lock.md) — Redis lock `booking:{trip}:{seat}`; fail closed (`503`) if Redis is down. Not a session store (`SESSION_DRIVER=file`), not an availability cache.
- [0002](adr/0002-half-open-segments.md) — a booking occupies `[start, end)`. Adjacent handover does not overlap.
- [0003](adr/0003-query-builder-for-fleet-tables.md) — query builder + mappers for fleet tables. `User` stays Eloquent for Sanctum, outside the fleet hexagon.

## Hexagonal architecture

Driving adapters call inward. Driven adapters implement ports the domain declared. Nothing in `Fleet\Domain` imports `App\` or `Illuminate\`.

```mermaid
flowchart TB
  subgraph driving["Driving adapters"]
    Web["Next.js BookingWorkspace"]
    Http["Laravel HTTP\ncontrollers, FormRequests,\nMapsApiExceptions"]
  end

  subgraph application["Application"]
    BookSeat["BookSeat"]
    GetSeats["GetAvailableSeats"]
    TxPort["TransactionBoundary\nport"]
  end

  subgraph domain["Domain"]
    Model["Trip, Segment, Bus, Seat,\nBooking, Passenger,\nSeatAvailability"]
    Ports["Ports:\nTripRepository, BusRepository,\nBookingRepository, StationRepository,\nSeatLock"]
  end

  subgraph driven["Driven adapters"]
    Sql["Query*Repository\n+ mappers"]
    RedisLock["RedisSeatLock"]
    LaravelTx["LaravelTransactionBoundary"]
  end

  Web --> Http
  Http --> BookSeat
  Http --> GetSeats
  BookSeat --> Model
  BookSeat --> Ports
  BookSeat --> TxPort
  GetSeats --> Model
  GetSeats --> Ports
  Sql -.->|implements| Ports
  RedisLock -.->|implements| Ports
  LaravelTx -.->|implements| TxPort
```

`User` / Sanctum stay Laravel-shaped: register, login, `/me`, and an optional Bearer on `POST /bookings` that stamps `user_id`. Guest booking does not go through that model.

## Optional sign-in

Guest booking is what the assessment asked for. Sign-in is extra: the header hits `POST /login` and `POST /register`.

The Sanctum access token lives in a Zustand store in memory. It is not written to `localStorage`, `sessionStorage`, or a cookie. Refresh, a new tab, or Sign out drops it. While it is present, `POST /bookings` sends `Authorization: Bearer` and the API stamps `user_id`.

That is a deliberate assessment tradeoff, not a vault. XSS on the page can still read the token from JS. A production SPA with a separate API would use a BFF: short-lived access token in memory, httpOnly refresh cookie, silent rotate. The Laravel API still issues a long-lived PAT; this repo does not add refresh tokens or Next.js auth routes.

Seeded account (after first Compose seed): `test@example.com` / `password`.

## Dependency direction

Dependencies point inward only. The domain does not know Postgres, Redis, or HTTP. Application orchestrates domain objects through ports. Infrastructure depends on those ports — never the other way around.

```mermaid
flowchart BT
  subgraph outer["Outer — can change without rewriting occupancy"]
    Next["frontend/"]
    LaravelHttp["app/Http"]
    Infra["app/Infrastructure"]
  end

  subgraph inner["Inner — PHPUnit, no Laravel"]
    AppLayer["Fleet\\Application"]
    DomainLayer["Fleet\\Domain"]
  end

  Next --> LaravelHttp
  LaravelHttp --> AppLayer
  AppLayer --> DomainLayer
  Infra --> DomainLayer
  Infra --> AppLayer
```

Composer encodes the split: `Fleet\Domain\` → `src/Domain/`, `Fleet\Application\` → `src/Application/`, `App\` → `app/`. Bindings in `AppServiceProvider` are the only place adapters are chosen:

| Port | Adapter |
|---|---|
| `TripRepository` | `QueryTripRepository` |
| `BusRepository` | `QueryBusRepository` |
| `BookingRepository` | `QueryBookingRepository` |
| `StationRepository` | `QueryStationRepository` |
| `SeatLock` | `RedisSeatLock` |
| `TransactionBoundary` | `LaravelTransactionBoundary` |

Switching the runtime to MySQL changes the connection, not `SeatAvailability` or `BookSeat`.

## Domain

One bounded context: a trip is an ordered list of stations on **one** bus. There is no Route aggregate, no timetable, no `seats` table. Seat identity is `(busId, number)` 1–12. Occupancy is a domain service over already-loaded bookings, not an ORM callback.

```mermaid
classDiagram
  class Station {
    int id
    string name
  }

  class Bus {
    int id
    Seat[] seats
    withTwelveSeats(id)
    seat(number)
  }

  class Seat {
    int busId
    int number
  }

  class Trip {
    int id
    string name
    int busId
    int[] stationIds
    segmentBetween(startId, endId)
    positionOf(stationId)
  }

  class Segment {
    int startPosition
    int endPosition
    overlaps(other)
  }

  class Booking {
    int id
    int tripId
    int seatNumber
    int startStationId
    int endStationId
    Segment segment
    Passenger passenger
    int userId
    occupies(segment)
    occupiesSeat(number)
  }

  class Passenger {
    string name
    string email
  }

  class SeatAvailability {
    availableSeats(segment, seats, bookings)
    isOccupied(seat, segment, bookings)
  }

  Bus "1" --> "12" Seat
  Trip --> Bus : busId
  Trip --> Segment : segmentBetween
  Booking --> Segment
  Booking --> Passenger
  SeatAvailability ..> Seat
  SeatAvailability ..> Segment
  SeatAvailability ..> Booking
```

`Segment` is half-open `[start, end)`. Overlap is `a < d && c < b`, so Cairo→Minya and Minya→Asyut can share seat 5. Station ids **and** positions are stored on the booking row so a later route edit cannot rewrite history.

## Booking flow

This is the path in the README gif. The UI is a driving adapter. Availability may be slightly stale; the lock + occupancy re-read is what prevents a double book. `GetAvailableSeats` does not take the lock.

```mermaid
sequenceDiagram
  actor Guest
  participant UI as BookingWorkspace
  participant API as Laravel HTTP
  participant UseCase as BookSeat
  participant Lock as RedisSeatLock
  participant Redis
  participant Tx as TransactionBoundary
  participant Domain as SeatAvailability
  participant DB as QueryBookingRepository

  Guest->>UI: pick trip, From, To
  UI->>API: GET /trips/{id}/available-seats
  API->>Domain: no lock, filter 12 seats
  Domain-->>UI: available numbers
  Guest->>UI: pick seat, name, email, Book
  UI->>API: POST /bookings seat_number
  API->>UseCase: handle(...)
  UseCase->>Lock: acquire(trip, seat)
  Lock->>Redis: SET booking:{trip}:{seat} TTL 15s wait 5s
  alt Redis down or wait timeout
    Lock-->>API: LockUnavailable
    API-->>UI: 503 lock_unavailable
  else lock held
    UseCase->>Tx: run
    Tx->>DB: load trip, bus, bookings for that seat
    Tx->>Domain: isOccupied?
    alt overlapping booking
      Domain-->>API: SeatUnavailable
      API-->>UI: 409 seat_unavailable
      UI->>UI: clear seat, invalidate seats query
    else free
      Tx->>DB: insert booking
      DB-->>Guest: 201
    end
    Lock->>Redis: release token
  end
```

Two guests, same seat, same instant: Redis serializes the writers. The first commits. The second acquires the lock, re-reads occupancy, and gets `409` — not a second row. The UI shows the API message, clears the selected seat, and refetches availability.
