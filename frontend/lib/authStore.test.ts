import { beforeEach, describe, expect, it } from "vitest";
import { useAuthStore } from "./authStore";

describe("useAuthStore", () => {
  beforeEach(() => {
    useAuthStore.getState().clearSession();
    window.localStorage.clear();
  });

  it("holds the token in memory only", () => {
    useAuthStore.getState().setSession("1|secret", {
      id: 1,
      name: "Omar Hassan",
      email: "omar@example.com",
    });

    expect(useAuthStore.getState().token).toBe("1|secret");
    expect(window.localStorage.getItem("fleet-token")).toBeNull();
    expect(window.sessionStorage.length).toBe(0);
  });

  it("drops the session on clear", () => {
    useAuthStore.getState().setSession("1|secret", {
      id: 1,
      name: "Omar Hassan",
      email: "omar@example.com",
    });
    useAuthStore.getState().clearSession();

    expect(useAuthStore.getState().token).toBeNull();
    expect(useAuthStore.getState().user).toBeNull();
  });
});
