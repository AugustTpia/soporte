{{--
    Panel del chat del asistente (mensajes, sugerencias y formulario).
    Sin Livewire: el envío lo hace JavaScript con fetch a assistant.chat.send.
    Recibe $messages (array de ['role', 'text']) y $embedded (bool).
--}}
<div class="flex items-center justify-between gap-2 border-b border-zinc-200 bg-royal-700 px-4 py-3 dark:border-zinc-700">
    <div>
        <p class="text-sm font-semibold text-white">Asistente de soporte</p>
        <p class="text-[11px] text-royal-200">Responde con los datos de tu empresa activa</p>
    </div>
    @if(! $embedded)
        <button type="button" data-assistant-toggle class="rounded-lg p-1 text-white/80 hover:bg-white/10 hover:text-white" aria-label="Cerrar chat">
            <flux:icon.x-mark class="size-5" />
        </button>
    @endif
</div>

<div class="flex-1 space-y-3 overflow-y-auto px-4 py-3" data-assistant-messages>
    @foreach($messages as $msg)
        <div class="flex {{ $msg['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[85%] whitespace-pre-line rounded-2xl px-3 py-2 text-sm leading-6 {{ $msg['role'] === 'user' ? 'bg-royal-600 text-white' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100' }}">
                {{ $msg['text'] }}
            </div>
        </div>
    @endforeach
    <div class="flex justify-start" data-assistant-typing style="display:none">
        <div class="rounded-2xl bg-zinc-100 px-3 py-2 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
            Escribiendo…
        </div>
    </div>
</div>

<div class="flex flex-wrap gap-1.5 border-t border-zinc-200 px-3 pt-2 dark:border-zinc-700">
    <button type="button" data-assistant-suggestion="¿Cómo va mi ticket?" class="rounded-full border border-zinc-300 px-2.5 py-1 text-[11px] text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
        ¿Cómo va mi ticket?
    </button>
    <button type="button" data-assistant-suggestion="Quiero reportar una falla" class="rounded-full border border-zinc-300 px-2.5 py-1 text-[11px] text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
        Reportar una falla
    </button>
    <button type="button" data-assistant-suggestion="¿Qué mantenimientos vencen?" class="rounded-full border border-zinc-300 px-2.5 py-1 text-[11px] text-zinc-600 hover:bg-zinc-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">
        ¿Qué mantenimientos vencen?
    </button>
</div>

<form data-assistant-form class="flex items-center gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
    @csrf
    <input
        type="text"
        data-assistant-input
        placeholder="Escribe tu duda…"
        maxlength="1000"
        autocomplete="off"
        class="min-w-0 flex-1 rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 outline-none focus:border-royal-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
    />
    <button
        type="submit"
        data-assistant-send
        class="shrink-0 rounded-xl bg-royal-600 px-3 py-2 text-sm font-medium text-white hover:bg-royal-700 disabled:opacity-50"
    >
        Enviar
    </button>
</form>
