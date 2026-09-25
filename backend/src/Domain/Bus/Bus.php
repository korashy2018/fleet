<?php

declare(strict_types=1);

namespace Fleet\Domain\Bus;

use Fleet\Domain\Bus\ValueObject\Seat;

final readonly class Bus
{
    public const SEAT_COUNT = 12;

    /**
     * @param list<Seat> $seats
     */
    private function __construct(
        public int $id,
        public array $seats,
    ) {
    }

    public static function withTwelveSeats(int $id): self
    {
        $seats = [];

        for ($number = 1; $number <= self::SEAT_COUNT; $number++) {
            $seats[] = new Seat($id, $number);
        }

        return new self($id, $seats);
    }

    public function seat(int $number): Seat
    {
        foreach ($this->seats as $seat) {
            if ($seat->number === $number) {
                return $seat;
            }
        }

        throw new \InvalidArgumentException(sprintf('Bus %d has no seat %d.', $this->id, $number));
    }
}
