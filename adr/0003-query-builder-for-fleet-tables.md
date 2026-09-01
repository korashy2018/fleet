# 0003. Query builder for fleet tables

## Status

Accepted.

## Context

The fleet schema is small and explicit: stations, buses, trips, trip_stations, bookings. Eloquent would invite implicit columns, global scopes, and a `seats` table we do not want. Seat identity is `(busId, number)` 1–12, stored as `seat_number` on the booking.

`User` still needs Eloquent because Sanctum’s token guard expects it.

## Decision

Fleet tables go through the query builder, explicit `select` lists, and mappers into `Fleet\Domain`. Domain tests do not boot Laravel. Integer ids stay `int` — no UUID, no typed `*Id` value objects.

`User` remains Eloquent for register / login / me. Guest `POST /bookings` stays public; an optional Bearer stamps `user_id`.

## Consequences

Switching the runtime from Postgres to MySQL does not require rewriting domain or HTTP. Persistence adapters must not use vendor SQL.

The cost is more mapping code than a `Booking extends Model` line. That is the point: the occupancy rules live in `SeatAvailability`, not in an ORM callback.
