<?php

namespace App\BusTicketCancellations\Presentation\Livewire;

use App\Attachments\Application\DTOs\EvidenceFileInput;
use App\BusTicketCancellations\Application\UseCases\AddBusTicketCancellationAttachment;
use App\BusTicketCancellations\Domain\Repositories\BusTicketCancellationAttachmentRepository;
use App\BusTicketCancellations\Infrastructure\Persistence\Eloquent\Models\BusTicketCancellationModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class BusTicketCancellationAttachments extends Component
{
    use WithFileUploads;

    public int $cancellationId;

    public mixed $file = null;

    public function mount(int $cancellationId): void
    {
        $this->cancellationId = $cancellationId;

        Gate::authorize('view', $this->cancellation());
    }

    public function upload(): void
    {
        $cancellation = $this->cancellation();
        Gate::authorize('update', $cancellation);

        $validated = $this->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $file = $validated['file'];

        if (! $file instanceof TemporaryUploadedFile) {
            $this->addError('file', 'No se pudo procesar el archivo seleccionado.');

            return;
        }

        $temporaryPath = $file->getRealPath();
        $sizeBytes = $file->getSize();

        try {
            app(AddBusTicketCancellationAttachment::class)->execute(
                companyId: (int) $this->companyId(),
                cancellationId: $this->cancellationId,
                file: new EvidenceFileInput(
                    temporaryPath: $temporaryPath,
                    originalName: $this->safeOriginalName($file->getClientOriginalName()),
                    extension: $file->extension() ?? 'bin',
                    mimeType: $file->getMimeType(),
                    sizeBytes: $sizeBytes,
                ),
                actorId: (int) auth()->id(),
            );
        } catch (DomainException $exception) {
            $this->addError('file', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        $this->reset('file');
        Flux::toast(variant: 'success', text: 'Evidencia adjuntada correctamente.');
    }

    public function render(): View
    {
        $cancellation = $this->cancellation();

        Gate::authorize('view', $cancellation);

        return view('bus-ticket-cancellations.livewire.attachments', [
            'attachments' => app(BusTicketCancellationAttachmentRepository::class)->forCancellation(
                $this->cancellationId,
                (int) $this->companyId(),
            ),
            'canManage' => Gate::allows('update', $cancellation),
        ]);
    }

    private function cancellation(): BusTicketCancellationModel
    {
        return BusTicketCancellationModel::query()
            ->where('company_id', $this->companyId())
            ->findOrFail($this->cancellationId);
    }

    private function companyId(): int
    {
        $companyId = app(TenantContext::class)->companyId();

        abort_if($companyId === null, 404);

        return $companyId;
    }

    private function safeOriginalName(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        $name = basename($name);
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return trim($name) !== '' ? trim($name) : 'evidencia';
    }
}
