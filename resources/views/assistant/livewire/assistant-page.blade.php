<div>
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Asistente de soporte</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Pregunta con tus palabras. El asistente responde con los datos de tu empresa activa: tickets, activos y mantenimientos.</p>
    </div>

    @include('assistant.assistant-chat', ['embedded' => true])
</div>
