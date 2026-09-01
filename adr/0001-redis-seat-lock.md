# 0001. Redis serializes booking writes

## Status

Accepted.

## Context

Two guests can POST the same `(trip, seat)` at the same instant. The occupancy check is ordinary SQL plus domain overlap; it is not a Postgres `EXCLUDE` constraint. The schema must stay MySQL-switchable, so advisory locks, `int4range`, and `btree_gist` are out.

Availability reads (`GET .../available-seats`) are allowed to be slightly stale. Caching them in Redis would make the UI look fresh while the lock is the thing that actually prevents a double book.

## Decision

Hold a Redis cache lock `booking:{tripId}:{seatNumber}` across the database transaction that re-reads that seat’s bookings and inserts.

- TTL 15s so a crashed worker cannot pin a seat forever.
- Wait up to 5s for the lock. Timeout or a dead Redis becomes `503` / `lock_unavailable`.
- Release by token in `finally`; TTL is the backstop.
- After the loser acquires the lock, the occupancy check still runs. That request becomes `409` / `seat_unavailable`, not a second row.

Sessions stay on the file driver. Redis is not a session store and not an availability cache.

## Consequences

Booking fails closed if Redis is down. That is deliberate: a book without the lock is a race.

A single Redis instance is enough for this assessment. Redis Cluster / Redlock are out of scope. Production would still fail closed, and would add persistence and failover around the same port.

`GetAvailableSeats` does not take the lock. The UI refreshes after `409`.
