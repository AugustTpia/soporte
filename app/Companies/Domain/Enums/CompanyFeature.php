<?php

namespace App\Companies\Domain\Enums;

enum CompanyFeature: string
{
    case BusTicketCancellations = 'bus_ticket_cancellations';
    case Assistant = 'assistant';

    public function label(): string
    {
        return match ($this) {
            self::BusTicketCancellations => 'Cancelación de boletos de autobús',
            self::Assistant => 'Asistente IA',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::BusTicketCancellations => 'Registra y reporta cancelaciones de boletos realizadas manualmente.',
            self::Assistant => 'Asistente de soporte con IA (Gemini): responde con los datos de la empresa. Solo lectura, no crea ni modifica nada.',
        };
    }
}
