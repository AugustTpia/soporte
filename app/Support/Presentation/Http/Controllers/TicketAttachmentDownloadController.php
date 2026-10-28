<?php

namespace App\Support\Presentation\Http\Controllers;

use App\Shared\Application\Contracts\TenantContext;
use App\Support\Application\UseCases\DownloadTicketAttachment;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TicketAttachmentDownloadController
{
    public function __invoke(SupportTicketModel $ticket, int $attachment): StreamedResponse
    {
        $companyId = app(TenantContext::class)->companyId();

        if ($companyId === null || (int) $ticket->company_id !== $companyId) {
            abort(404);
        }

        Gate::authorize('view', $ticket);

        $file = app(DownloadTicketAttachment::class)->execute(
            companyId: $companyId,
            ticketId: (int) $ticket->id,
            attachmentId: $attachment,
        );

        if ($file === null || ! Storage::disk($file->disk)->exists($file->path)) {
            abort(404);
        }

        return Storage::disk($file->disk)->download($file->path, $file->originalName, [
            'Content-Type' => $file->mimeType,
            'Cache-Control' => 'private, no-store, no-cache, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }
}
