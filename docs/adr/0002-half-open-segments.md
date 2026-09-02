# 0002. Bookings occupy a half-open segment

## Status

Accepted.

## Context

A trip is an ordered list of stations, not a timetable. The same physical seat can be sold Cairo→Minya and Minya→Asyut: the handover station is the end of one booking and the start of the next.

A closed interval `[start, end]` would treat that handover as overlap. A seat-wide unique `(trip, seat)` would ban the second sale entirely.

## Decision

A booking occupies half-open `[startPosition, endPosition)`. Two segments overlap when `a < d && c < b`. Adjacent bookings that only share an endpoint do not overlap.

Station ids **and** positions are stored on the booking row so a later route edit cannot rewrite history.

## Consequences

The seeded catalog can book seat 5 Cairo→Minya and Minya→Asyut on trip 1. Fayyum legs on that seat stay blocked because they sit inside `[Cairo, Minya)`.

Invalid segments (`start >= end`) are rejected in the domain, not with a database check constraint. Laravel’s schema builder has no portable `CHECK`.
