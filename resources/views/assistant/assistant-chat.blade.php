{{--
    Chat del asistente, sin Livewire.
    El envío es un POST normal con fetch: la pregunta viaja en el cuerpo de
    la petición y el historial vive en sesión en el servidor, así que un
    segundo envío nunca se pierde por un snapshot desactualizado.
    Recibe $embedded (bool, opcional): en la página dedicada el chat va
    embebido y siempre visible; en el resto va flotante.

    Dos decisiones deliberadas, por molestias reportadas por Jorge:
    1. El flotante NUNCA se abre solo al navegar. El historial sí persiste
       en sesión, pero el estado "abierto" no se restaura: cada página
       nueva arranca con el panel cerrado y solo se abre con clic.
    2. El botón vive abajo a la IZQUIERDA del área de contenido, no abajo
       a la derecha: en esta app todos los botones de acción principales
       (Nueva empresa, Nuevo ticket, Crear/Guardar) van alineados a la
       derecha, arriba en el encabezado y abajo en los formularios. Abajo
       a la derecha es la esquina de acción; abajo a la izquierda del
       contenido está libre. En escritorio el sidebar mide 16rem (w-64),
       así que a partir de lg el botón se desplaza a 18rem para no tapar
       el menú ni el usuario de la parte inferior del sidebar. En móvil
       el sidebar está oculto, así que queda a 24px del borde.
--}}
@php
    $assistantState = \App\Assistant\AssistantChatState::load();
    $assistantMessages = $assistantState['messages'];
    $assistantEmbedded = (bool) ($embedded ?? false);
    // Solo la página dedicada arranca abierta. El flotante siempre
    // arranca cerrado, aunque haya historial en sesión.
    $assistantOpen = $assistantEmbedded;
@endphp
<style>
    @media (min-width: 1024px) {
        .assistant-chat-floating {
            left: 18rem !important;
        }
    }
</style>
@if($assistantEmbedded)
<div class="assistant-chat mx-auto w-full max-w-3xl" data-assistant-chat data-embedded="1" data-send-url="{{ route('assistant.chat.send') }}" data-csrf-token="{{ csrf_token() }}">
    <div class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900" style="height:32rem">
        @include('assistant.chat-panel', ['messages' => $assistantMessages, 'embedded' => true])
    </div>
</div>
@else
<div class="assistant-chat assistant-chat-floating fixed bottom-6 left-6 z-50 flex flex-col items-start gap-3" data-assistant-chat data-embedded="0" data-send-url="{{ route('assistant.chat.send') }}" data-csrf-token="{{ csrf_token() }}" style="position:fixed;bottom:24px;left:24px;z-index:9999;display:flex;flex-direction:column;align-items:flex-start;gap:12px">
    <div class="flex h-[28rem] w-[22rem] max-w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900" data-assistant-panel style="width:22rem;height:28rem;max-width:calc(100vw - 2rem);display:{{ $assistantOpen ? 'flex' : 'none' }}">
        @include('assistant.chat-panel', ['messages' => $assistantMessages, 'embedded' => false])
    </div>

    <button
        type="button"
        data-assistant-toggle
        class="flex size-14 items-center justify-center rounded-full shadow-2xl"
        style="background:#facc15;color:#0a1f44;border:2px solid #0a1f44;box-shadow:0 10px 30px rgba(0,0,0,.5)"
        aria-label="Abrir asistente de soporte"
        title="Asistente"
    >
        <flux:icon.chat-bubble-left-right class="size-6" />
    </button>
</div>
@endif
<script>
(function () {
    function initAssistantChat(root) {
        if (!root || root.dataset.assistantInit) return;
        root.dataset.assistantInit = '1';
        var embedded = root.dataset.embedded === '1';
        var panel = root.querySelector('[data-assistant-panel]');
        var messagesEl = root.querySelector('[data-assistant-messages]');
        var form = root.querySelector('[data-assistant-form]');
        var input = root.querySelector('[data-assistant-input]');
        var sendBtn = root.querySelector('[data-assistant-send]');
        var typingEl = root.querySelector('[data-assistant-typing]');
        var busy = false;

        function scrollDown() {
            if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
        }

        function bubble(role, text) {
            var wrap = document.createElement('div');
            wrap.className = 'flex ' + (role === 'user' ? 'justify-end' : 'justify-start');
            var b = document.createElement('div');
            b.className = 'max-w-[85%] whitespace-pre-line rounded-2xl px-3 py-2 text-sm leading-6 ' + (role === 'user' ? 'bg-royal-600 text-white' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-100');
            b.textContent = text;
            wrap.appendChild(b);
            messagesEl.appendChild(wrap);
            scrollDown();
        }

        function setBusy(value) {
            busy = value;
            if (input) input.disabled = value;
            if (sendBtn) sendBtn.disabled = value;
            if (typingEl) typingEl.style.display = value ? 'flex' : 'none';
        }

        function togglePanel(show) {
            if (embedded || !panel) return;
            var open = typeof show === 'boolean' ? show : panel.style.display === 'none';
            panel.style.display = open ? 'flex' : 'none';
            var label = root.querySelector('[data-assistant-toggle-label]');
            if (label) label.textContent = open ? 'Cerrar asistente' : 'Asistente';
            if (open) scrollDown();
        }

        root.querySelectorAll('[data-assistant-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () { togglePanel(); });
        });

        root.querySelectorAll('[data-assistant-suggestion]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (!input || !form) return;
                if (!embedded) togglePanel(true);
                input.value = btn.getAttribute('data-assistant-suggestion') || '';
                form.requestSubmit();
            });
        });

        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var text = (input.value || '').trim();
                if (!text || busy) return;
                input.value = '';
                if (!embedded) togglePanel(true);
                bubble('user', text);
                setBusy(true);
                fetch(root.dataset.sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': root.dataset.csrfToken
                    },
                    body: JSON.stringify({ message: text })
                })
                .then(function (res) {
                    if (res.status === 429) throw new Error('rate');
                    if (!res.ok) throw new Error('http');
                    return res.json();
                })
                .then(function (data) {
                    bubble('assistant', data.reply || 'No pude generar una respuesta esta vez. Inténtalo de nuevo.');
                })
                .catch(function (err) {
                    bubble('assistant', err && err.message === 'rate'
                        ? 'Estás enviando mensajes muy rápido, espera unos segundos e inténtalo de nuevo.'
                        : 'Ocurrió un error al contactar al asistente. Inténtalo de nuevo en unos minutos, o crea un ticket para que un técnico lo revise.');
                })
                .finally(function () {
                    setBusy(false);
                    if (input) input.focus();
                });
            });
        }

        scrollDown();
    }

    function initAllAssistantChats() {
        document.querySelectorAll('[data-assistant-chat]').forEach(initAssistantChat);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllAssistantChats);
    } else {
        initAllAssistantChats();
    }
    document.addEventListener('livewire:navigated', initAllAssistantChats);
})();
</script>
