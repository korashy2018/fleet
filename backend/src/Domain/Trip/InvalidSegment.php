<?php

declare(strict_types=1);

namespace Fleet\Domain\Trip;

final class InvalidSegment extends \RuntimeException
{
    public static function becauseStartIsNotBeforeEnd(int $start, int $end): self
    {
        return new self(sprintf(
            'Segment start position %d must be before end position %d.',
            $start,
            $end,
        ));
    }
}
