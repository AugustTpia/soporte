<?php

namespace App\Reports\Application\DTOs;

use Closure;

final readonly class ReportFile
{
    /**
     * @param  Closure(): void  $writer
     */
    public function __construct(
        public string $filename,
        public string $mimeType,
        public Closure $writer,
    ) {}
}
