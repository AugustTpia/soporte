<?php

namespace App\Reports\Application\Support;

use DateTimeImmutable;

final class ReportValue
{
    public static function dateTime(?DateTimeImmutable $value): string
    {
        return $value?->format('d/m/Y H:i') ?? '—';
    }

    public static function date(?DateTimeImmutable $value): string
    {
        return $value?->format('d/m/Y') ?? '—';
    }

    public static function money(?int $cents): string
    {
        if ($cents === null) {
            return '—';
        }

        return '$'.number_format($cents / 100, 2, '.', ',');
    }

    public static function hours(?float $hours): string
    {
        return $hours === null ? '—' : number_format($hours, 2).' h';
    }

    public static function minutes(?int $minutes): string
    {
        if ($minutes === null) {
            return '—';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours === 0) {
            return $rest.' min';
        }

        return $rest === 0 ? $hours.' h' : $hours.' h '.$rest.' min';
    }

    /**
     * @param  array<int, string>  $values
     */
    public static function list(array $values): string
    {
        return $values === [] ? '—' : implode(', ', $values);
    }

    public static function text(?string $value): string
    {
        return filled($value) ? (string) $value : '—';
    }
}
