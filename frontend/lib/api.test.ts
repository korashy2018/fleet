import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { useAuthStore } from "./authStore";
import { bookSeat, login } from "./api";

describe("api auth header", () => {
  beforeEach(() => {
    useAuthStore.getState().clearSession();
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        ok: true,
        status: 200,
        json: async () => ({
          data: {
            id: 9,
            trip_id: 1,
            seat_number: 1,
            start_station_id: 1,
            end_station_id: 4,
            start_position: 0,
            end_position: 2,
            user_id: 1,
            passenger: { name: "Omar Hassan", email: "omar@example.com" },
            token: "1|secret",
            user: { id: 1, name: "Omar Hassan", email: "omar@example.com" },
          },
        }),
      }),
    );
  });

  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it("sends a Bearer token from the Zustand store", async () => {
    useAuthStore.getState().setSession("1|secret", {
      id: 1,
      name: "Omar Hassan",
      email: "omar@example.com",
    });

    await bookSeat({
      tripId: 1,
      startStationId: 1,
      endStationId: 4,
      seatNumber: 1,
      name: "Omar Hassan",
      email: "omar@example.com",
    });

    expect(fetch).toHaveBeenCalledWith(
      expect.stringContaining("/api/v1/bookings"),
      expect.objectContaining({
        headers: expect.objectContaining({ Authorization: "Bearer 1|secret" }),
      }),
    );
  });

  it("omits Authorization for a guest booking", async () => {
    await bookSeat({
      tripId: 1,
      startStationId: 1,
      endStationId: 4,
      seatNumber: 1,
      name: "Mona Hassan",
      email: "mona@example.com",
    });

    const [, init] = vi.mocked(fetch).mock.calls[0];
    expect(init?.headers).not.toHaveProperty("Authorization");
  });

  it("stores login tokens only through the caller, not disk", async () => {
    await login("omar@example.com", "password");
    expect(window.localStorage.length).toBe(0);
  });
});
