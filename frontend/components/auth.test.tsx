import { render, screen } from "@testing-library/react";
import userEvent from "@testing-library/user-event";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { ApiError } from "../lib/types";
import { useAuthStore } from "../lib/authStore";
import { AuthPanel } from "./auth";

vi.mock("../lib/api", () => ({
  login: vi.fn(),
  register: vi.fn(),
}));

import { login, register } from "../lib/api";

const user = { id: 1, name: "Omar Hassan", email: "omar@example.com" };

describe("AuthPanel", () => {
  beforeEach(() => {
    vi.mocked(login).mockReset();
    vi.mocked(register).mockReset();
    useAuthStore.getState().clearSession();
    window.localStorage.clear();
  });

  it("signs in and keeps the token out of localStorage", async () => {
    vi.mocked(login).mockResolvedValue({ token: "1|secret", user });
    const events = userEvent.setup();
    render(<AuthPanel />);

    await events.click(screen.getByRole("button", { name: "Sign in" }));
    await events.type(screen.getByLabelText("Email"), "omar@example.com");
    await events.type(screen.getByLabelText("Password"), "password");
    await events.keyboard("{Enter}");

    expect(await screen.findByText("Omar Hassan")).toBeInTheDocument();
    expect(useAuthStore.getState().token).toBe("1|secret");
    expect(window.localStorage.getItem("fleet-token")).toBeNull();
  });

  it("shows the API error when credentials are rejected", async () => {
    vi.mocked(login).mockRejectedValue(
      new ApiError(401, { code: "invalid_credentials", message: "The provided credentials are incorrect." }),
    );
    const events = userEvent.setup();
    render(<AuthPanel />);

    await events.click(screen.getByRole("button", { name: "Sign in" }));
    await events.type(screen.getByLabelText("Email"), "omar@example.com");
    await events.type(screen.getByLabelText("Password"), "nope");
    await events.keyboard("{Enter}");

    expect(await screen.findByText("The provided credentials are incorrect.")).toBeInTheDocument();
    expect(useAuthStore.getState().token).toBeNull();
  });

  it("signs out of the in-memory session", async () => {
    useAuthStore.getState().setSession("1|secret", user);
    const events = userEvent.setup();
    render(<AuthPanel />);

    await events.click(screen.getByRole("button", { name: "Sign out" }));

    expect(screen.getByRole("button", { name: "Sign in" })).toBeInTheDocument();
    expect(useAuthStore.getState().token).toBeNull();
  });
});
