<?php

namespace App\Reports\Domain\Enums;

use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Companies\Domain\Enums\CompanyFeature;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

enum ReportType: string
{
    case Tickets = 'tickets';
    case Maintenance = 'maintenance';
    case Cancellations = 'cancellations';

    public function label(): string
    {
        return match ($this) {
            self::Tickets => 'Tickets',
            self::Maintenance => 'Mantenimientos',
            self::Cancellations => 'Cancelaciones de boletos',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Tickets => 'Solicitudes de soporte, estados, prioridades y tiempos de resolución.',
            self::Maintenance => 'Planes ejecutados, servicios programados y tiempo invertido.',
            self::Cancellations => 'Cancelaciones registradas, resultados e importes.',
        };
    }

    public function permission(): string
    {
        return match ($this) {
            self::Tickets => 'tickets.view',
            self::Maintenance => 'maintenance.view',
            self::Cancellations => 'bus_ticket_cancellations.view',
        };
    }

    public function requiresCancellationFeature(): bool
    {
        return $this === self::Cancellations;
    }

    public function requiredFeature(): ?CompanyFeature
    {
        return $this->requiresCancellationFeature() ? CompanyFeature::BusTicketCancellations : null;
    }

    public function authorizationModel(): string
    {
        return match ($this) {
            self::Tickets => SupportTicketModel::class,
            self::Maintenance => MaintenanceExecutionModel::class,
            self::Cancellations => BusTicketCancellationModel::class,
        };
    }
}
