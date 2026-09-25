# Booking state

End-to-end machine for one guest. The UI is a driving adapter. `GET .../available-seats` does not take the Redis lock, so **Seat map** can be slightly stale. The lock plus a second occupancy read on `POST /bookings` is what prevents a double book.

Bookings are immediate and **immutable**. There is no cancel, no hold-that-expires, no paid/unpaid. A successful insert is the terminal sold state.

## One guest

Happy path is top to bottom. Failures return to an earlier choice; they do not leave a booking row.

```mermaid
stateDiagram-v2
    direction TB

    [*] --> Browsing

    Browsing --> TripOpen: GET /trips or /trips/id
    TripOpen --> ChoosingStations: pick From and To
    ChoosingStations --> FetchingSeats: GET available-seats

    FetchingSeats --> ChoosingStations: 422 invalid segment
    FetchingSeats --> Browsing: 404 trip not found
    FetchingSeats --> SeatMap: seat numbers 1-12

    SeatMap --> FillingForm: pick a free seat
    FillingForm --> SeatMap: change seat
    FillingForm --> Posting: POST /bookings

    Posting --> WaitingForLock: SET booking trip seat

    WaitingForLock --> FillingForm: 503 lock_unavailable
    WaitingForLock --> CheckingOccupancy: lock held

    CheckingOccupancy --> ChoosingStations: 422 station seat or order
    CheckingOccupancy --> Browsing: 404 trip not found
    CheckingOccupancy --> SeatMap: 409 seat_unavailable
    CheckingOccupancy --> Booked: insert row

    Booked --> [*]
```

| State | What is true |
|---|---|
| Browsing | Catalog only. No lock. |
| TripOpen | Trip + ordered stations loaded. |
| ChoosingStations | From/To must be on that trip, From before To. |
| FetchingSeats | Domain filter over existing bookings. May be stale. |
| SeatMap | Twelve numbers minus overlapping occupancy. |
| FillingForm | Name + email. Optional Bearer stamps `user_id`. |
| Posting | Request accepted by HTTP. Outcome not decided. |
| WaitingForLock | Redis `booking:{trip}:{seat}`, TTL 15s, wait 5s. |
| CheckingOccupancy | DB transaction. Re-read that seat. `SeatAvailability`. |
| Booked | Row inserted. Lock released. No further transitions. |

**409** clears the selected seat and refetches the map. **503** keeps the form: Redis was down or another writer held the key for 5s. Booking without the lock is a race, so the API fails closed.

Optional sign-in (`/login`, `/register`, `/me`) sits beside this machine. It does not change occupancy. A Bearer on `POST /bookings` only stamps `user_id`.

## Two guests, same seat

This is the README race. Both can sit on **Seat map** at once. Redis allows only one writer into **CheckingOccupancy**.

```mermaid
stateDiagram-v2
    direction LR

    [*] --> BothSeeSeatFree

    BothSeeSeatFree --> AHoldsLock: Guest A SET NX
    BothSeeSeatFree --> BWaits: Guest B block 5s

    AHoldsLock --> ABooked: insert and release
    BWaits --> BRechecks: A released

    ABooked --> OneRow
    BRechecks --> OneRow: 409 overlap

    BWaits --> BGaveUp: still locked after 5s
    BGaveUp --> OneRow: 503 no insert

    BothSeeSeatFree: Both see seat free
    AHoldsLock: Guest A holds lock
    BWaits: Guest B waits
    ABooked: Guest A booked
    BRechecks: Guest B re-reads
    BGaveUp: Guest B timed out
    OneRow: One booking row
```

The loser does not get a second row. Adjacent handover (Cairo→Minya then Minya→Asyut on the same seat) is **not** this machine: after A books, B’s re-read sees no overlap and B also reaches **Booked**.
