<?php

namespace App\Support\Presentation\Livewire\Tickets;

use App\Attachments\Application\DTOs\EvidenceFileInput;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use App\Support\Application\UseCases\AddTicketAttachment;
use App\Support\Domain\Repositories\TicketAttachmentRepository;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class TicketAttachments extends Component
{
    use WithFileUploads;

    public int $ticketId;

    public mixed $file = null;

    public function mount(int $ticketId): void
    {
        $this->ticketId = $ticketId;

        Gate::authorize('view', $this->ticket());
    }

    public function upload(): void
    {
        $ticket = $this->ticket();
        Gate::authorize('update', $ticket);

        $validated = $this->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $file = $validated['file'];

        if (! $file instanceof TemporaryUploadedFile) {
            $this->addError('file', 'No se pudo procesar el archivo seleccionado.');

            return;
        }

        try {
            app(AddTicketAttachment::class)->execute(
                companyId: $this->companyId(),
                ticketId: $this->ticketId,
                file: new EvidenceFileInput(
                    temporaryPath: $file->getRealPath(),
                    originalName: $this->safeOriginalName($file->getClientOriginalName()),
                    extension: $file->extension() ?? 'bin',
                    mimeType: $file->getMimeType(),
                    sizeBytes: $file->getSize(),
                ),
                actorId: (int) auth()->id(),
            );
        } catch (DomainException $exception) {
            $this->addError('file', $exception->getMessage());
            Flux::toast(variant: 'danger', text: $exception->getMessage());

            return;
        }

        $this->reset('file');
        Flux::toast(variant: 'success', text: 'Adjunto agregado correctamente.');
    }

    public function render(): View
    {
        $ticket = $this->ticket();

        Gate::authorize('view', $ticket);

        return view('tickets.livewire.attachments', [
            'attachments' => app(TicketAttachmentRepository::class)->forTicket(
                $this->ticketId,
                $this->companyId(),
            ),
            'canManage' => Gate::allows('update', $ticket),
        ]);
    }

    private function ticket(): SupportTicketModel
    {
        return SupportTicketModel::query()
            ->where('company_id', $this->companyId())
            ->findOrFail($this->ticketId);
    }

    private function companyId(): int
    {
        $companyId = app(TenantContext::class)->companyId();

        if ($companyId === null) {
            abort(404);
        }

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
