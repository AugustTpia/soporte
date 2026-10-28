<?php

namespace App\Companies\Domain\Enums;

enum CompanyFeature: string
{
    case BusTicketCancellations = 'bus_ticket_cancellations';

    public function label(): string
    {
        return match ($this) {
            self::BusTicketCancellations => 'Cancelación de boletos de autobús',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BusTicketCancellations => 'Registra y reporta cancelaciones de boletos realizadas manualmente.',
        };
    }
}
