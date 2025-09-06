<?php

namespace BotMirzaPanel\Domain\ValueObjects\Common;

/**
 * DateTimeRange Value Object
 * 
 * Represents a range between two DateTime objects with validation and utility methods.
 */
class DateTimeRange
{
    private \DateTimeImmutable $start;
    private \DateTimeImmutable $end;

    public function __construct()
    {
        $this->validate($start, $end);
        $this->start = $start;
        $this->end = $end;
    }

    public static function create(\DateTimeImmutable $start, \DateTimeImmutable $end): self
    {
        return new self($start, $end);
    }

    public static function fromStrings(string $start, string $end, ?\DateTimeZone $timezone = null): self
    {
        $startDate = new \DateTimeImmutable($start, $timezone);
        $endDate = new \DateTimeImmutable($end, $timezone);
        return new self($startDate, $endDate);
    }

    public static function fromTimestamps(int $startTimestamp, int $endTimestamp, ?\DateTimeZone $timezone = null): self
    {
        $startDate = (new \DateTimeImmutable('@' . $startTimestamp))->setTimezone($timezone ?? new \DateTimeZone('UTC'));
        $endDate = (new \DateTimeImmutable('@' . $endTimestamp))->setTimezone($timezone ?? new \DateTimeZone('UTC'));
        return new self($startDate, $endDate);
    }

    public static function today(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable('today', $timezone));
        $end = $start->modify('+1 day -1 second');
        return new self($start, $end);
    }

    public static function yesterday(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable('yesterday', $timezone));
        $end = $start->modify('+1 day -1 second');
        return new self($start, $end);
    }

    public static function thisWeek(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable('monday this week', $timezone));
        $end = $start->modify('+7 days -1 second');
        return new self($start, $end);
    }

    public static function lastWeek(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable('monday last week', $timezone));
        $end = $start->modify('+7 days -1 second');
        return new self($start, $end);
    }

    public static function thisMonth(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable('first day of this month', $timezone));
        $end = $start->modify('last day of this month +1 day -1 second');
        return new self($start, $end);
    }

    public static function lastMonth(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable('first day of last month', $timezone));
        $end = $start->modify('last day of this month +1 day -1 second');
        return new self($start, $end);
    }

    public static function thisYear(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable('first day of January this year', $timezone));
        $end = (new \DateTimeImmutable('first day of January next year', $timezone))->modify('-1 second');
        return new self($start, $end);
    }

    public static function lastYear(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable('first day of January last year', $timezone));
        $end = (new \DateTimeImmutable('first day of January this year', $timezone))->modify('-1 second');
        return new self($start, $end);
    }

    public static function last30Days(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $end = new \DateTimeImmutable('now', $timezone);
        $start = $end->modify('-30 days');
        return new self($start, $end);
    }

    public static function last7Days(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $end = new \DateTimeImmutable('now', $timezone);
        $start = $end->modify('-7 days');
        return new self($start, $end);
    }

    public static function last24Hours(\DateTimeZone $timezone = null): self
    {
        $timezone = $timezone ?? new \DateTimeZone('UTC');
        $end = new \DateTimeImmutable('now', $timezone);
        $start = $end->modify('-24 hours');
        return new self($start, $end);
    }

    public function getStart(): \DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): \DateTimeImmutable
    {
        return $this->end;
    }

    public function getDuration(): \DateInterval
    {
        return $this->start->diff($this->end);
    }

    public function getDurationInSeconds(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    public function getDurationInMinutes(): float
    {
        return $this->getDurationInSeconds() / 60;
    }

    public function getDurationInHours(): float
    {
        return $this->getDurationInSeconds() / 3600;
    }

    public function getDurationInDays(): float
    {
        return $this->getDurationInSeconds() / 86400;
    }

    public function contains(\DateTimeImmutable $dateTime): bool
    {
        return $dateTime >= $this->start && $dateTime <= $this->end;
    }

    public function containsRange(DateTimeRange $other): bool
    {
        return $this->contains($other->start) && $this->contains($other->end);
    }

    public function overlaps(DateTimeRange $other): bool
    {
        return $this->start <= $other->end && $this->end >= $other->start;
    }

    public function touches(DateTimeRange $other): bool
    {
        return $this->start == $other->end || $this->end == $other->start;
    }

    public function isAdjacent(DateTimeRange $other): bool
    {
        return $this->touches($other) && !$this->overlaps($other);
    }

    public function equals(DateTimeRange $other): bool
    {
        return $this->start == $other->start && $this->end == $other->end;
    }

    public function isBefore(DateTimeRange $other): bool
    {
        return $this->end < $other->start;
    }

    public function isAfter(DateTimeRange $other): bool
    {
        return $this->start > $other->end;
    }

    public function intersect(DateTimeRange $other): ?self
    {
        if (!$this->overlaps($other)) {
            return null;
        }
        
        $start = $this->start > $other->start ? $this->start : $other->start;
        $end = $this->end < $other->end ? $this->end : $other->end;
        
        return new self($start, $end);
    }

    public function union(DateTimeRange $other): ?self
    {
        if (!$this->overlaps($other) && !$this->touches($other)) {
            return null;
        }
        
        $start = $this->start < $other->start ? $this->start : $other->start;
        $end = $this->end > $other->end ? $this->end : $other->end;
        
        return new self($start, $end);
    }

    public function extend(\DateInterval $interval): self
    {
        return new self(
            $this->start->sub($interval),
            $this->end->add($interval)
        );
    }

    public function extendStart(\DateInterval $interval): self
    {
        return new self(
            $this->start->sub($interval),
            $this->end
        );
    }

    public function extendEnd(\DateInterval $interval): self
    {
        return new self(
            $this->start,
            $this->end->add($interval)
        );
    }

    public function shrink(\DateInterval $interval): self
    {
        $newStart = $this->start->add($interval);
        $newEnd = $this->end->sub($interval);
        
        if ($newStart >= $newEnd) {
            throw new \InvalidArgumentException('Cannot shrink range: would result in invalid range');
        }
        
        return new self($newStart, $newEnd);
    }

    public function shift(\DateInterval $interval): self
    {
        return new self(
            $this->start->add($interval),
            $this->end->add($interval)
        );
    }

    public function shiftToTimezone(\DateTimeZone $timezone): self
    {
        return new self(
            $this->start->setTimezone($timezone),
            $this->end->setTimezone($timezone)
        );
    }

    public function split(int $parts): array
    {
        if ($parts <= 0) {
            throw new \InvalidArgumentException('Parts must be greater than 0');
        }
        
        $duration = $this->getDurationInSeconds();
        $partDuration = (int) ($duration / $parts);
        
        $ranges = [];
        $currentStart = $this->start;
        
        for ($i = 0; $i < $parts; $i++) {
            $currentEnd = $i === $parts - 1 
                ? $this->end 
                : $currentStart->modify("+{$partDuration} seconds");
            
            $ranges[] = new self($currentStart, $currentEnd);
            $currentStart = $currentEnd;
        }
        
        return $ranges;
    }

    public function splitByInterval(\DateInterval $interval): array
    {
        $ranges = [];
        $currentStart = $this->start;
        
        while ($currentStart < $this->end) {
            $currentEnd = $currentStart->add($interval);
            if ($currentEnd > $this->end) {
                $currentEnd = $this->end;
            }
            
            $ranges[] = new self($currentStart, $currentEnd);
            $currentStart = $currentEnd;
        }
        
        return $ranges;
    }

    public function isInPast(\DateTimeImmutable $reference = null): bool
    {
        $reference = $reference ?? new \DateTimeImmutable();
        return $this->end < $reference;
    }

    public function isInFuture(\DateTimeImmutable $reference = null): bool
    {
        $reference = $reference ?? new \DateTimeImmutable();
        return $this->start > $reference;
    }

    public function isCurrently(\DateTimeImmutable $reference = null): bool
    {
        $reference = $reference ?? new \DateTimeImmutable();
        return $this->contains($reference);
    }

    public function format(string $format = 'Y-m-d H:i:s'): array
    {
        return [
            'start' => $this->start->format($format),
            'end' => $this->end->format($format),
        ];
    }

    public function formatRange(string $format = 'Y-m-d H:i:s', string $separator = ' - '): string
    {
        return $this->start->format($format) . $separator . $this->end->format($format);
    }

    public function formatDuration(): string
    {
        $interval = $this->getDuration();
        
        $parts = [];
        
        if ($interval->y > 0) {
            $parts[] = $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
        }
        
        if ($interval->m > 0) {
            $parts[] = $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
        }
        
        if ($interval->d > 0) {
            $parts[] = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
        }
        
        if ($interval->h > 0) {
            $parts[] = $interval->h . ' hour' . ($interval->h > 1 ? 's' : '');
        }
        
        if ($interval->i > 0) {
            $parts[] = $interval->i . ' minute' . ($interval->i > 1 ? 's' : '');
        }
        
        if ($interval->s > 0 || empty($parts)) {
            $parts[] = $interval->s . ' second' . ($interval->s > 1 ? 's' : '');
        }
        
        return implode(', ', $parts);
    }

    public function __toString(): string
    {
        return $this->formatRange();
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start->format('c'),
            'end' => $this->end->format('c'),
            'start_timestamp' => $this->start->getTimestamp(),
            'end_timestamp' => $this->end->getTimestamp(),
            'duration_seconds' => $this->getDurationInSeconds(),
            'duration_minutes' => $this->getDurationInMinutes(),
            'duration_hours' => $this->getDurationInHours(),
            'duration_days' => $this->getDurationInDays(),
            'duration_formatted' => $this->formatDuration(),
            'is_in_past' => $this->isInPast(),
            'is_in_future' => $this->isInFuture(),
            'is_currently' => $this->isCurrently(),
        ];
    }

    private function validate(\DateTimeImmutable $start, \DateTimeImmutable $end): void
    {
        if ($start > $end) {
            throw new \InvalidArgumentException('Start date must be before or equal to end date.');
        }
    }
}