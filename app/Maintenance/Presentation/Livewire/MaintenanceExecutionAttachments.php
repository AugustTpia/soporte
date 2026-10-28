<?php

namespace App\Maintenance\Presentation\Livewire;

use App\Attachments\Application\DTOs\EvidenceFileInput;
use App\Maintenance\Application\UseCases\AddMaintenanceExecutionAttachment;
use App\Maintenance\Domain\Enums\MaintenanceExecutionStatus;
use App\Maintenance\Domain\Repositories\MaintenanceExecutionAttachmentRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenanceExecutionModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Shared\Domain\Exceptions\DomainException;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class MaintenanceExecutionAttachments extends Component
{
    use WithFileUploads;

    public int $executionId;

    public mixed $file = null;

    public function mount(int $executionId): void
    {
        $this->executionId = $executionId;

        Gate::authorize('view', $this->execution());
    }

    public function upload(): void
    {
        $execution = $this->execution();
        Gate::authorize('update', $execution);

        if (! $this->isEditable($execution)) {
            abort(403);
        }

        $validated = $this->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp'],
        ]);

        $file = $validated['file'];

        if (! $file instanceof TemporaryUploadedFile) {
            $this->addError('file', 'No se pudo procesar el archivo seleccionado.');

            return;
        }

        try {
            app(AddMaintenanceExecutionAttachment::class)->execute(
                companyId: $this->companyId(),
                executionId: $this->executionId,
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
        $execution = $this->execution();

        Gate::authorize('view', $execution);

        return view('maintenance.livewire.executions-attachments', [
            'attachments' => app(MaintenanceExecutionAttachmentRepository::class)->forExecution(
                $this->executionId,
                $this->companyId(),
            ),
            'canManage' => Gate::allows('update', $execution) && $this->isEditable($execution),
        ]);
    }

    private function execution(): MaintenanceExecutionModel
    {
        return MaintenanceExecutionModel::query()
            ->where('company_id', $this->companyId())
            ->findOrFail($this->executionId);
    }

    private function isEditable(MaintenanceExecutionModel $execution): bool
    {
        return MaintenanceExecutionStatus::tryFrom((string) $execution->status)?->isEditable() ?? false;
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
