<?php

declare(strict_types=1);

namespace Fleet\Domain\Trip\ValueObject;

use Fleet\Domain\Trip\Exception\InvalidSegment;

final readonly class Segment
{
    public function __construct(
        public int $startPosition,
        public int $endPosition,
    ) {
        if ($startPosition >= $endPosition) {
            throw InvalidSegment::becauseStartIsNotBeforeEnd($startPosition, $endPosition);
        }
    }

    public function overlaps(self $other): bool
    {
        return $this->startPosition < $other->endPosition
            && $other->startPosition < $this->endPosition;
    }
}
