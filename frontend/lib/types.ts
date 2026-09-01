export type Station = {
  id: number;
  name: string;
  position: number;
};

export type Trip = {
  id: number;
  name: string;
  bus_id: number;
  seat_count: number;
  stations: Station[];
};

export type Seat = {
  number: number;
  available: boolean;
};

export type Booking = {
  id: number;
  trip_id: number;
  seat_number: number;
  start_station_id: number;
  end_station_id: number;
  start_position: number;
  end_position: number;
  user_id: number | null;
  passenger: {
    name: string;
    email: string;
  };
};

export type ApiErrorBody = {
  code: string;
  message: string;
  details?: Record<string, string[]>;
};

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly body: ApiErrorBody,
  ) {
    super(body.message);
    this.name = "ApiError";
  }
}
