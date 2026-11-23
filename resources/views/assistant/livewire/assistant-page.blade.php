<div>
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Asistente de soporte</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Pregunta con tus palabras. El asistente responde con los datos de tu empresa activa: tickets, activos y mantenimientos.</p>
    </div>

    @if($assistantEnabled)
        @include('assistant.assistant-chat', ['embedded' => true])
    @else
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <p class="font-medium text-navy-900 dark:text-zinc-100">El asistente IA no está activado para esta empresa.</p>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Pide al superadministrador que lo active en la ficha de la empresa (Empresas → editar → Asistente IA).</p>
        </div>
    @endif
</div>
