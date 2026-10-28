<?php

namespace App\Reports\Application\DTOs;

use App\Shared\Domain\Exceptions\DomainException;
use DateTimeImmutable;

final readonly class ReportPeriod
{
    public DateTimeImmutable $from;

    public DateTimeImmutable $to;

    public function __construct(DateTimeImmutable $from, DateTimeImmutable $to)
    {
        $this->from = $from->setTime(0, 0, 0);
        $this->to = $to->setTime(23, 59, 59);

        if ($this->from > $this->to) {
            throw DomainException::because('La fecha inicial no puede ser posterior a la fecha final.');
        }
    }

    public static function default(): self
    {
        $now = new DateTimeImmutable;

        return new self($now->modify('first day of this month'), $now->modify('last day of this month'));
    }

    public static function fromStrings(?string $from, ?string $to): self
    {
        $from = trim((string) $from);
        $to = trim((string) $to);

        if ($from === '' && $to === '') {
            return self::default();
        }

        $default = self::default();

        return new self(
            from: $from === '' ? $default->from : self::parse($from),
            to: $to === '' ? $default->to : self::parse($to),
        );
    }

    public function label(): string
    {
        return $this->from->format('d/m/Y').' - '.$this->to->format('d/m/Y');
    }

    public function shortLabel(): string
    {
        return $this->from->format('d/m/Y').' – '.$this->to->format('d/m/Y');
    }

    /**
     * @return array{from: string, to: string}
     */
    public function toQuery(): array
    {
        return [
            'from' => $this->from->format('Y-m-d'),
            'to' => $this->to->format('Y-m-d'),
        ];
    }

    private static function parse(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw DomainException::because('El rango de fechas no es válido.');
        }

        return $date;
    }
}
