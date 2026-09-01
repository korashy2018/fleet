"use client";

import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { FormEvent, useMemo, useState } from "react";
import { bookSeat, listAvailableSeats, listTrips } from "../lib/api";
import { ApiError, type Station, type Trip } from "../lib/types";
import { ThemeToggle } from "./theme";

export function BookingWorkspace() {
  const queryClient = useQueryClient();
  const tripsQuery = useQuery({ queryKey: ["trips"], queryFn: listTrips });

  const [tripId, setTripId] = useState<number | "">("");
  const [startStationId, setStartStationId] = useState<number | "">("");
  const [endStationId, setEndStationId] = useState<number | "">("");
  const [seatNumber, setSeatNumber] = useState<number | null>(null);
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [conflict, setConflict] = useState<string | null>(null);
  const [confirmation, setConfirmation] = useState<string | null>(null);

  const trip = useMemo(
    () => tripsQuery.data?.find((item: Trip) => item.id === tripId),
    [tripsQuery.data, tripId],
  );

  const startStation = trip?.stations.find((station: Station) => station.id === startStationId);
  const endOptions = trip?.stations.filter(
    (station: Station) => startStation !== undefined && station.position > startStation.position,
  );

  const segmentReady =
    typeof tripId === "number" &&
    typeof startStationId === "number" &&
    typeof endStationId === "number";

  const seatsQuery = useQuery({
    queryKey: ["seats", tripId, startStationId, endStationId],
    queryFn: () => listAvailableSeats(tripId as number, startStationId as number, endStationId as number),
    enabled: segmentReady,
  });

  const bookMutation = useMutation({
    mutationFn: bookSeat,
    onSuccess: (booking) => {
      setConflict(null);
      setConfirmation(
        `Booked seat ${booking.seat_number} on trip ${booking.trip_id} (booking #${booking.id}).`,
      );
      setSeatNumber(null);
      void queryClient.invalidateQueries({ queryKey: ["seats"] });
    },
    onError: (error: Error) => {
      setConfirmation(null);
      if (error instanceof ApiError && error.status === 409) {
        setConflict(error.body.message);
        setSeatNumber(null);
        void queryClient.invalidateQueries({ queryKey: ["seats"] });
        return;
      }
      setConflict(error instanceof ApiError ? error.body.message : error.message);
    },
  });

  function selectTrip(id: number) {
    setTripId(id);
    setStartStationId("");
    setEndStationId("");
    setSeatNumber(null);
    setConflict(null);
    setConfirmation(null);
  }

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!segmentReady || seatNumber === null) {
      return;
    }
    bookMutation.mutate({
      tripId: tripId as number,
      startStationId: startStationId as number,
      endStationId: endStationId as number,
      seatNumber,
      name,
      email,
    });
  }

  const fieldClass =
    "rounded-xl border-2 border-border bg-surface px-3 py-2.5 text-base text-foreground outline-none transition focus:border-accent disabled:cursor-not-allowed disabled:opacity-60";

  return (
    <main className="mx-auto flex w-full max-w-3xl flex-col gap-8 px-6 py-12">
      <header className="flex items-start justify-between gap-4">
        <div className="space-y-2">
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-accent">Fleet</p>
          <h1 className="text-4xl font-semibold tracking-tight">Book a seat</h1>
          <p className="max-w-xl text-muted">
            Choose a trip and segment, then pick one of twelve seats. A taken seat on an overlapping
            leg comes back as a conflict — we clear the selection and refresh availability.
          </p>
        </div>
        <ThemeToggle />
      </header>

      {tripsQuery.isError && (
        <p className="rounded-xl border-2 border-danger-border bg-danger-bg px-3 py-2 text-sm text-danger-fg">
          {tripsQuery.error instanceof ApiError
            ? tripsQuery.error.body.message
            : "Could not load trips."}
        </p>
      )}

      <form
        className="flex flex-col gap-6 rounded-3xl border-2 border-border bg-surface p-6 shadow-[0_16px_40px_rgba(255,61,46,0.08)]"
        onSubmit={submit}
      >
        <label className="flex flex-col gap-1 text-sm font-medium">
          Trip
          <select
            className={fieldClass}
            value={tripId}
            onChange={(event) => selectTrip(Number(event.target.value))}
            required
          >
            <option value="">Select a trip</option>
            {tripsQuery.data?.map((item) => (
              <option key={item.id} value={item.id}>
                {item.name}
              </option>
            ))}
          </select>
        </label>

        <div className="grid gap-4 sm:grid-cols-2">
          <label className="flex flex-col gap-1 text-sm font-medium">
            From
            <select
              className={fieldClass}
              value={startStationId}
              disabled={!trip}
              onChange={(event) => {
                setStartStationId(Number(event.target.value));
                setEndStationId("");
                setSeatNumber(null);
                setConflict(null);
              }}
              required
            >
              <option value="">Start station</option>
              {trip?.stations.slice(0, -1).map((station) => (
                <option key={station.id} value={station.id}>
                  {station.name}
                </option>
              ))}
            </select>
          </label>
          <label className="flex flex-col gap-1 text-sm font-medium">
            To
            <select
              className={fieldClass}
              value={endStationId}
              disabled={!startStation}
              onChange={(event) => {
                setEndStationId(Number(event.target.value));
                setSeatNumber(null);
                setConflict(null);
              }}
              required
            >
              <option value="">End station</option>
              {endOptions?.map((station) => (
                <option key={station.id} value={station.id}>
                  {station.name}
                </option>
              ))}
            </select>
          </label>
        </div>

        {segmentReady && (
          <fieldset className="space-y-3 rounded-2xl bg-surface-2 p-4">
            <legend className="px-1 text-sm font-semibold">Seat</legend>
            {seatsQuery.isLoading && <p className="text-sm text-muted">Loading seats…</p>}
            {seatsQuery.isError && (
              <p className="text-sm text-danger-fg">
                {seatsQuery.error instanceof ApiError
                  ? seatsQuery.error.body.message
                  : "Could not load seats."}
              </p>
            )}
            <div className="grid grid-cols-4 gap-2 sm:grid-cols-6">
              {seatsQuery.data?.seats.map((seat) => {
                const selected = seatNumber === seat.number;
                return (
                  <button
                    key={seat.number}
                    type="button"
                    disabled={!seat.available}
                    onClick={() => {
                      setSeatNumber(seat.number);
                      setConflict(null);
                      setConfirmation(null);
                    }}
                    className={[
                      "rounded-xl border-2 px-2 py-3 text-sm font-semibold transition",
                      !seat.available
                        ? "cursor-not-allowed border-transparent bg-taken text-taken-fg"
                        : selected
                          ? "border-seat-selected bg-seat-selected text-primary-fg shadow-[0_8px_16px_rgba(255,61,46,0.35)]"
                          : "border-seat-border bg-seat text-foreground hover:scale-[1.03] hover:border-accent",
                    ].join(" ")}
                  >
                    {seat.number}
                  </button>
                );
              })}
            </div>
          </fieldset>
        )}

        <div className="grid gap-4 sm:grid-cols-2">
          <label className="flex flex-col gap-1 text-sm font-medium">
            Name
            <input
              className={fieldClass}
              value={name}
              onChange={(event) => setName(event.target.value)}
              required
              autoComplete="name"
            />
          </label>
          <label className="flex flex-col gap-1 text-sm font-medium">
            Email
            <input
              className={fieldClass}
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              required
              autoComplete="email"
            />
          </label>
        </div>

        {conflict && (
          <p className="rounded-xl border-2 border-warn-fg/30 bg-warn-bg px-3 py-2 text-sm text-warn-fg">
            {conflict} Pick another seat — availability was refreshed.
          </p>
        )}

        {confirmation && (
          <p className="rounded-xl border-2 border-ok-fg/30 bg-ok-bg px-3 py-2 text-sm text-ok-fg">
            {confirmation}
          </p>
        )}

        <button
          type="submit"
          disabled={!segmentReady || seatNumber === null || bookMutation.isPending}
          className="rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-primary-fg shadow-[0_10px_20px_rgba(255,61,46,0.28)] transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
        >
          {bookMutation.isPending ? "Booking…" : "Book seat"}
        </button>
      </form>
    </main>
  );
}
