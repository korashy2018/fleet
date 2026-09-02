# Architecture decisions

Diagrams and the hexagon live in [`ARCHITECTURE.md`](../ARCHITECTURE.md). The working plan is [`PLAN.md`](../PLAN.md). These records are the choices that are easy to defend in review. Status is **accepted** unless noted.

| # | Decision |
|---|---|
| [0001](0001-redis-seat-lock.md) | Redis serializes booking writes; Postgres stays portable |
| [0002](0002-half-open-segments.md) | A booking occupies `[start, end)` |
| [0003](0003-query-builder-for-fleet-tables.md) | Query builder + mappers for fleet tables, not Eloquent |
