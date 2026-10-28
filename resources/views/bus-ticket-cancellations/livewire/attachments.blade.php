<section class="surface-card p-5 sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-navy-900 dark:text-zinc-100">Evidencias adjuntas</h2>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                Archivos privados: PDF, JPG, PNG o WEBP. Máximo 10 MB por archivo.
            </p>
        </div>
        <flux:badge color="zinc" size="sm">{{ count($attachments) }}</flux:badge>
    </div>

    @if($canManage)
        <form wire:submit="upload" class="mt-4 rounded-xl border border-dashed border-zinc-300 p-4 dark:border-zinc-700">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="min-w-0 flex-1">
                    <label for="evidence-file" class="mb-1.5 block text-xs font-medium text-zinc-600 dark:text-zinc-300">
                        Adjuntar evidencia
                    </label>
                    <input
                        id="evidence-file"
                        type="file"
                        wire:model="file"
                        accept=".pdf,.jpg,.jpeg,.png,.webp"
                        class="block w-full cursor-pointer rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 file:me-3 file:rounded-md file:border-0 file:bg-royal-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-royal-700 hover:file:bg-royal-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:file:bg-royal-600/20 dark:file:text-royal-200"
                    >
                    @error('file')
                        <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <flux:button type="submit" variant="primary" icon="arrow-up-tray" wire:loading.attr="disabled" wire:target="file,upload">
                    Adjuntar
                </flux:button>
            </div>
            <div wire:loading wire:target="file" class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                Preparando archivo…
            </div>
            <div wire:loading wire:target="upload" class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                Guardando evidencia…
            </div>
        </form>
    @endif

    <div class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
        @forelse($attachments as $attachment)
            <div wire:key="cancellation-attachment-{{ $attachment->id }}" class="flex flex-col gap-3 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-lg bg-royal-50 text-royal-700 dark:bg-royal-600/20 dark:text-royal-300">
                        <flux:icon.document-text class="size-4" />
                    </span>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium text-navy-900 dark:text-zinc-100">{{ $attachment->originalName }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ number_format($attachment->sizeBytes / 1024, 0) }} KB
                            · {{ $attachment->uploadedByName ?: 'Sistema' }}
                            · {{ $attachment->createdAt?->format('d/m/Y H:i') ?? '—' }}
                        </p>
                    </div>
                </div>

                <flux:button
                    size="sm"
                    variant="subtle"
                    icon="arrow-down-tray"
                    :href="route('bus-ticket-cancellations.attachments.download', ['cancellation' => $cancellationId, 'attachment' => $attachment->id])"
                >
                    Descargar
                </flux:button>
            </div>
        @empty
            <p class="py-4 text-sm text-zinc-500 dark:text-zinc-400">
                No hay evidencias adjuntas.
            </p>
        @endforelse
    </div>
</section>
