"use client";

import { FormEvent, useState } from "react";
import { login, register } from "../lib/api";
import { useAuthStore } from "../lib/authStore";
import { ApiError } from "../lib/types";

const fieldClass =
  "rounded-xl border-2 border-border bg-surface px-3 py-2.5 text-base text-foreground outline-none transition focus:border-accent";

export function AuthPanel() {
  const user = useAuthStore((state) => state.user);
  const setSession = useAuthStore((state) => state.setSession);
  const clearSession = useAuthStore((state) => state.clearSession);
  const [open, setOpen] = useState(false);
  const [mode, setMode] = useState<"login" | "register">("login");
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [pending, setPending] = useState(false);
  const [error, setError] = useState<string | null>(null);

  function resetForm() {
    setName("");
    setEmail("");
    setPassword("");
    setError(null);
    setPending(false);
  }

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setPending(true);
    setError(null);

    try {
      const payload =
        mode === "login" ? await login(email, password) : await register(name, email, password);
      setSession(payload.token, payload.user);
      resetForm();
      setOpen(false);
    } catch (caught: unknown) {
      setError(caught instanceof ApiError ? caught.body.message : "Could not sign in.");
      setPending(false);
    }
  }

  if (user !== null) {
    return (
      <div className="flex flex-wrap items-center justify-end gap-2">
        <p className="text-sm text-muted">
          Signed in as <span className="font-medium text-foreground">{user.name}</span>
        </p>
        <button
          type="button"
          onClick={clearSession}
          className="rounded-full border-2 border-border bg-surface px-3 py-2 text-sm font-medium text-foreground transition hover:border-accent hover:text-accent"
        >
          Sign out
        </button>
      </div>
    );
  }

  if (!open) {
    return (
      <button
        type="button"
        aria-expanded={false}
        onClick={() => setOpen(true)}
        className="rounded-full border-2 border-border bg-surface px-3 py-2 text-sm font-medium text-foreground shadow-sm transition hover:border-accent hover:text-accent"
      >
        Sign in
      </button>
    );
  }

  return (
    <form
      className="flex w-72 flex-col gap-3 rounded-2xl border-2 border-border bg-surface p-4 shadow-sm"
      onSubmit={submit}
    >
      <div className="flex gap-2 text-sm font-medium">
        <button
          type="button"
          aria-pressed={mode === "login"}
          onClick={() => {
            setMode("login");
            setError(null);
          }}
          className={mode === "login" ? "text-accent" : "text-muted"}
        >
          Sign in
        </button>
        <span className="text-muted">/</span>
        <button
          type="button"
          aria-pressed={mode === "register"}
          onClick={() => {
            setMode("register");
            setError(null);
          }}
          className={mode === "register" ? "text-accent" : "text-muted"}
        >
          Create account
        </button>
      </div>

      {mode === "register" && (
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
      )}

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

      <label className="flex flex-col gap-1 text-sm font-medium">
        Password
        <input
          className={fieldClass}
          type="password"
          value={password}
          onChange={(event) => setPassword(event.target.value)}
          required
          minLength={mode === "register" ? 8 : undefined}
          autoComplete={mode === "login" ? "current-password" : "new-password"}
        />
      </label>

      {error && <p className="text-sm text-danger-fg">{error}</p>}

      <div className="flex gap-2">
        <button
          type="submit"
          disabled={pending}
          className="flex-1 rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-primary-fg disabled:opacity-40"
        >
          {pending ? "Please wait…" : mode === "login" ? "Sign in" : "Create account"}
        </button>
        <button
          type="button"
          onClick={() => {
            resetForm();
            setOpen(false);
          }}
          className="rounded-xl border-2 border-border px-3 py-2 text-sm font-medium"
        >
          Cancel
        </button>
      </div>
    </form>
  );
}
