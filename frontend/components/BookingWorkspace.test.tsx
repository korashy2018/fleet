import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { render, screen, waitFor } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { ApiError, type Booking, type Seat, type Trip } from "../lib/types";
import { ThemeProvider } from "./theme";
import { BookingWorkspace } from "./BookingWorkspace";

vi.mock("../lib/api", () => ({
  listTrips: vi.fn(),
  listAvailableSeats: vi.fn(),
  bookSeat: vi.fn(),
  login: vi.fn(),
  register: vi.fn(),
}));

import { bookSeat, listAvailableSeats, listTrips } from "../lib/api";
import { useAuthStore } from "../lib/authStore";

const trip: Trip = {
  id: 1,
  name: "Cairo to Asyut",
  bus_id: 1,
  seat_count: 12,
  stations: [
    { id: 1, name: "Cairo", position: 0 },
    { id: 3, name: "Al Fayyum", position: 1 },
    { id: 4, name: "Al Minya", position: 2 },
    { id: 5, name: "Asyut", position: 3 },
  ],
};

function seats(unavailable: number[] = [5]): { seats: Seat[] } {
  return {
    seats: Array.from({ length: 12 }, (_, index) => {
      const number = index + 1;
      return { number, available: !unavailable.includes(number) };
    }),
  };
}

const booking: Booking = {
  id: 9,
  trip_id: 1,
  seat_number: 1,
  start_station_id: 1,
  end_station_id: 4,
  start_position: 0,
  end_position: 2,
  user_id: null,
  passenger: { name: "Mona Hassan", email: "mona@example.com" },
};

function renderWorkspace() {
  const client = new QueryClient({
    defaultOptions: {
      queries: { retry: false },
      mutations: { retry: false },
    },
  });

  return render(
    <ThemeProvider>
      <QueryClientProvider client={client}>
        <BookingWorkspace />
      </QueryClientProvider>
    </ThemeProvider>,
  );
}

async function chooseCairoToMinya(user: ReturnType<typeof userEvent.setup>) {
  await screen.findByRole("option", { name: "Cairo to Asyut" });
  await user.selectOptions(screen.getByLabelText("Trip"), "1");
  const from = screen.getByLabelText("From");
  await waitFor(() => expect(from).toBeEnabled());
  await user.selectOptions(from, "1");
  const to = screen.getByLabelText("To");
  await waitFor(() => expect(to).toBeEnabled());
  await user.selectOptions(to, "4");
}

describe("BookingWorkspace", () => {
  beforeEach(() => {
    vi.mocked(listTrips).mockReset();
    vi.mocked(listAvailableSeats).mockReset();
    vi.mocked(bookSeat).mockReset();
    vi.mocked(listTrips).mockResolvedValue([trip]);
    vi.mocked(listAvailableSeats).mockResolvedValue(seats());
    useAuthStore.getState().clearSession();
  });

  it("shows a loading state while seats are fetched", async () => {
    let resolveSeats!: (value: { seats: Seat[] }) => void;
    vi.mocked(listAvailableSeats).mockImplementation(
      () =>
        new Promise((resolve) => {
          resolveSeats = resolve;
        }),
    );
    const user = userEvent.setup();
    renderWorkspace();

    await chooseCairoToMinya(user);

    expect(await screen.findByText("Loading seats…")).toBeInTheDocument();
    resolveSeats(seats());
    expect(await screen.findByRole("button", { name: "1" })).toBeEnabled();
  });

  it("renders twelve seats and disables a taken one", async () => {
    const user = userEvent.setup();
    renderWorkspace();

    await chooseCairoToMinya(user);

    for (let number = 1; number <= 12; number += 1) {
      expect(await screen.findByRole("button", { name: String(number) })).toBeInTheDocument();
    }
    expect(screen.getByRole("button", { name: "5" })).toBeDisabled();
    expect(screen.getByRole("button", { name: "1" })).toBeEnabled();
  });

  it("shows an error when trips cannot be loaded", async () => {
    vi.mocked(listTrips).mockRejectedValue(
      new ApiError(500, { code: "internal_error", message: "Catalog is down." }),
    );
    renderWorkspace();

    expect(await screen.findByText("Catalog is down.")).toBeInTheDocument();
  });

  it("confirms a booking and clears the selected seat", async () => {
    vi.mocked(bookSeat).mockResolvedValue(booking);
    const user = userEvent.setup();
    renderWorkspace();

    await chooseCairoToMinya(user);
    await user.click(await screen.findByRole("button", { name: "1" }));
    await user.type(screen.getByLabelText("Name"), "Mona Hassan");
    await user.type(screen.getByLabelText("Email"), "mona@example.com");
    await user.click(screen.getByRole("button", { name: "Book seat" }));

    expect(
      await screen.findByText("Booked seat 1 on trip 1 (booking #9)."),
    ).toBeInTheDocument();
    expect(screen.queryByText(/Pick another seat/)).not.toBeInTheDocument();
    expect(vi.mocked(listAvailableSeats)).toHaveBeenCalledTimes(2);
  });

  it("on 409 shows the conflict, clears the seat, and refreshes availability", async () => {
    vi.mocked(listAvailableSeats)
      .mockResolvedValueOnce(seats())
      .mockResolvedValueOnce(seats([1, 5]));
    vi.mocked(bookSeat).mockRejectedValue(
      new ApiError(409, {
        code: "seat_unavailable",
        message: "Seat 1 is not available on trip 1 for that segment.",
      }),
    );
    const user = userEvent.setup();
    renderWorkspace();

    await chooseCairoToMinya(user);
    const seat = await screen.findByRole("button", { name: "1" });
    await user.click(seat);
    expect(seat).toHaveClass("bg-seat-selected");

    await user.type(screen.getByLabelText("Name"), "Mona Hassan");
    await user.type(screen.getByLabelText("Email"), "mona@example.com");
    await user.click(screen.getByRole("button", { name: "Book seat" }));

    expect(
      await screen.findByText(
        /Seat 1 is not available on trip 1 for that segment\. Pick another seat/,
      ),
    ).toBeInTheDocument();
    expect(screen.queryByText(/Booked seat/)).not.toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByRole("button", { name: "1" })).toBeDisabled();
    });
    expect(screen.getByRole("button", { name: "1" })).not.toHaveClass("bg-seat-selected");
    expect(vi.mocked(listAvailableSeats)).toHaveBeenCalledTimes(2);
  });

  it("prefills passenger fields from the signed-in user", async () => {
    useAuthStore.getState().setSession("1|secret", {
      id: 1,
      name: "Omar Hassan",
      email: "omar@example.com",
    });
    renderWorkspace();

    expect(await screen.findByLabelText("Name")).toHaveValue("Omar Hassan");
    expect(screen.getByLabelText("Email")).toHaveValue("omar@example.com");
    expect(screen.getByText(/linked to your account/i)).toBeInTheDocument();
  });

  it("clears the booking selection on sign out", async () => {
    useAuthStore.getState().setSession("1|secret", {
      id: 1,
      name: "Omar Hassan",
      email: "omar@example.com",
    });
    const user = userEvent.setup();
    renderWorkspace();

    await chooseCairoToMinya(user);
    await user.click(await screen.findByRole("button", { name: "1" }));
    expect(screen.getByRole("button", { name: "1" })).toHaveClass("bg-seat-selected");

    await user.click(screen.getByRole("button", { name: "Sign out" }));

    expect(screen.getByLabelText("Trip")).toHaveValue("");
    expect(screen.getByLabelText("Name")).toHaveValue("");
    expect(screen.getByLabelText("Email")).toHaveValue("");
    expect(screen.queryByRole("button", { name: "1" })).not.toBeInTheDocument();
    expect(screen.queryByText(/linked to your account/i)).not.toBeInTheDocument();
  });
});
