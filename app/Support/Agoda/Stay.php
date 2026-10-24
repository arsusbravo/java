<?php

namespace App\Support\Agoda;

use Carbon\CarbonImmutable;

/**
 * Dates and guests a visitor searched for, carried in URLs as
 * ?checkin=2026-11-08&checkout=2026-11-10&adults=2&children=0.
 */
final readonly class Stay
{
    public const MAX_NIGHTS = 30;

    public const MAX_ADULTS = 10;

    public const MAX_CHILDREN = 6;

    public function __construct(
        public CarbonImmutable $checkIn,
        public CarbonImmutable $checkOut,
        public int $adults = 2,
        public int $children = 0,
    ) {}

    /**
     * A valid stay from query values, or null. Past dates, wrong order and
     * impossible guest counts give null, so callers can simply ignore them.
     *
     * @param  array<string, mixed>  $query
     */
    public static function fromQuery(array $query): ?self
    {
        $checkIn = self::date($query['checkin'] ?? null);
        $checkOut = self::date($query['checkout'] ?? null);
        $adults = filter_var($query['adults'] ?? 2, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => self::MAX_ADULTS]]);
        $children = filter_var($query['children'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => self::MAX_CHILDREN]]);

        if (! $checkIn || ! $checkOut || $adults === false || $children === false) {
            return null;
        }

        $nights = (int) $checkIn->diffInDays($checkOut);
        if ($checkIn->lt(today()) || $checkIn->gt(today()->addYear()) || $nights < 1 || $nights > self::MAX_NIGHTS) {
            return null;
        }

        return new self($checkIn, $checkOut, $adults, $children);
    }

    public function nights(): int
    {
        return (int) $this->checkIn->diffInDays($this->checkOut);
    }

    /**
     * @return array{checkin: string, checkout: string, adults: int, children: int}
     */
    public function query(): array
    {
        return [
            'checkin' => $this->checkIn->toDateString(),
            'checkout' => $this->checkOut->toDateString(),
            'adults' => $this->adults,
            'children' => $this->children,
        ];
    }

    /**
     * "8–10 Nov · 2 nights · 2 adults"
     */
    public function label(): string
    {
        $dates = $this->checkIn->isSameMonth($this->checkOut)
            ? $this->checkIn->format('j').'–'.$this->checkOut->format('j M')
            : $this->checkIn->format('j M').' – '.$this->checkOut->format('j M');
        $guests = trans_choice('{1} 1 adult|[2,*] :count adults', $this->adults).($this->children ? ', '.trans_choice('{1} 1 child|[2,*] :count children', $this->children) : '');

        return $dates.' · '.trans_choice('{1} 1 night|[2,*] :count nights', $this->nights()).' · '.$guests;
    }

    protected static function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->toDateString() === $value ? $date : null;
    }
}
