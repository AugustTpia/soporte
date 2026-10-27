<?php

namespace App\Assets\Domain\Enums;

enum AssetCategory: string
{
    case Desktop = 'desktop';
    case Laptop = 'laptop';
    case Server = 'server';
    case Nuc = 'nuc';
    case Printer = 'printer';
    case Router = 'router';
    case Modem = 'modem';
    case Switch = 'switch';
    case AccessPoint = 'access_point';
    case Ups = 'ups';
    case Camera = 'camera';
    case DvrNvr = 'dvr_nvr';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Desktop => 'Computadora de escritorio',
            self::Laptop => 'Laptop',
            self::Server => 'Servidor',
            self::Nuc => 'NUC',
            self::Printer => 'Impresora',
            self::Router => 'Router',
            self::Modem => 'Módem',
            self::Switch => 'Switch',
            self::AccessPoint => 'Punto de acceso',
            self::Ups => 'UPS',
            self::Camera => 'Cámara',
            self::DvrNvr => 'DVR / NVR',
            self::Other => 'Otro',
        };
    }
}
