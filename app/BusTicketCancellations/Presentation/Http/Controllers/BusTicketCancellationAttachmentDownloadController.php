<?php

namespace App\BusTicketCancellations\Presentation\Http\Controllers;

use App\BusTicketCancellations\Application\UseCases\DownloadBusTicketCancellationAttachment;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Shared\Application\Contracts\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BusTicketCancellationAttachmentDownloadController
{
    public function __invoke(BusTicketCancellationModel $cancellation, int $attachment): StreamedResponse
    {
        $companyId = app(TenantContext::class)->companyId();

        if ($companyId === null || (int) $cancellation->company_id !== $companyId) {
            abort(404);
        }

        Gate::authorize('view', $cancellation);

        $file = app(DownloadBusTicketCancellationAttachment::class)->execute(
            companyId: $companyId,
            cancellationId: (int) $cancellation->id,
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
