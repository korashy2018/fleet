import { ApiError, type Booking, type Seat, type Trip } from "./types";

const baseUrl = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000";

type Envelope<T> = { data: T } | { error: { code: string; message: string; details?: Record<string, string[]> } };

async function request<T>(path: string, init?: RequestInit): Promise<T> {
  const response = await fetch(`${baseUrl}/api/v1${path}`, {
    ...init,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      ...init?.headers,
    },
  });

  const payload = (await response.json()) as Envelope<T>;

  if (!response.ok || "error" in payload) {
    const error =
      "error" in payload
        ? payload.error
        : { code: "internal_error", message: "An unexpected error occurred." };
    throw new ApiError(response.status, error);
  }

  return payload.data;
}

export function listTrips(): Promise<Trip[]> {
  return request<Trip[]>("/trips");
}

export function listAvailableSeats(
  tripId: number,
  startStationId: number,
  endStationId: number,
): Promise<{ seats: Seat[] }> {
  const query = new URLSearchParams({
    start_station_id: String(startStationId),
    end_station_id: String(endStationId),
  });

  return request<{ seats: Seat[] }>(`/trips/${tripId}/available-seats?${query}`);
}

export function bookSeat(input: {
  tripId: number;
  startStationId: number;
  endStationId: number;
  seatNumber: number;
  name: string;
  email: string;
}): Promise<Booking> {
  return request<Booking>("/bookings", {
    method: "POST",
    body: JSON.stringify({
      trip_id: input.tripId,
      start_station_id: input.startStationId,
      end_station_id: input.endStationId,
      seat_number: input.seatNumber,
      passenger: {
        name: input.name,
        email: input.email,
      },
    }),
  });
}
