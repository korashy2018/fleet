<?php

declare(strict_types=1);

namespace Tests\Domain\Trip;

use Fleet\Domain\Trip\Exception\InvalidSegment;
use Fleet\Domain\Trip\ValueObject\Segment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SegmentTest extends TestCase
{
    #[Test]
    #[DataProvider('overlapCases')]
    public function it_detects_overlap(Segment $left, Segment $right, bool $overlaps): void
    {
        $this->assertSame($overlaps, $left->overlaps($right));
        $this->assertSame($overlaps, $right->overlaps($left));
    }

    /**
     * @return iterable<string, array{Segment, Segment, bool}>
     */
    public static function overlapCases(): iterable
    {
        yield 'identical' => [new Segment(0, 2), new Segment(0, 2), true];
        yield 'contained' => [new Segment(0, 3), new Segment(1, 2), true];
        yield 'partial_overlap' => [new Segment(0, 2), new Segment(1, 3), true];
        yield 'adjacent_handover' => [new Segment(0, 2), new Segment(2, 3), false];
        yield 'disjoint' => [new Segment(0, 1), new Segment(2, 3), false];
    }

    #[Test]
    public function it_rejects_a_start_that_is_not_before_the_end(): void
    {
        $this->expectException(InvalidSegment::class);

        new Segment(2, 2);
    }
}
