<?php

namespace App\Support\Domain\Enums;

enum TicketActivityType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Assigned = 'assigned';
    case Unassigned = 'unassigned';
    case StatusChanged = 'status_changed';
    case Comment = 'comment';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Ticket creado',
            self::Updated => 'Datos actualizados',
            self::Assigned => 'Técnico asignado',
            self::Unassigned => 'Técnico retirado',
            self::StatusChanged => 'Cambio de estado',
            self::Comment => 'Comentario',
        };
    }
}
