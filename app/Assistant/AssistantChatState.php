<?php

namespace App\Assistant;

/**
 * Historial del chat del asistente, guardado en sesión.
 *
 * Es la única fuente de verdad del historial: el envío por fetch y el
 * render del widget leen y escriben aquí, nunca en el snapshot de Livewire.
 * Así una recarga o un segundo envío no pueden perder mensajes.
 *
 * El estado "abierto" NO se persiste ni se restaura: el chat flotante
 * siempre arranca cerrado al cambiar de página y solo se abre con clic.
 * El historial sí sobrevive a la navegación; el panel, no.
 */
final class AssistantChatState
{
    public const SESSION_KEY = 'assistant_chat_state';

    public const GREETING = 'Hola, soy el asistente de soporte. Puedo ayudarte a diagnosticar una falla, consultar cómo va un ticket o revisar qué mantenimientos vencen. ¿En qué te ayudo?';

    private const MAX_MESSAGES = 20;

    /**
     * @return array{open: bool, messages: array<int, array{role: string, text: string}>}
     */
    public static function load(): array
    {
        $saved = session(self::SESSION_KEY, []);

        $messages = [];

        if (is_array($saved) && ! empty($saved['messages']) && is_array($saved['messages'])) {
            $messages = array_values(array_filter(
                array_map(fn ($m) => is_array($m) && isset($m['role'], $m['text'])
                    ? ['role' => (string) $m['role'], 'text' => (string) $m['text']]
                    : null, $saved['messages']
                )
            ));
        }

        if ($messages === []) {
            $messages[] = ['role' => 'assistant', 'text' => self::GREETING];
        }

        return [
            // Siempre false: el flotante nunca se auto-abre al navegar.
            // Se ignora cualquier 'open' guardado por versiones anteriores.
            'open' => false,
            'messages' => array_slice($messages, -self::MAX_MESSAGES),
        ];
    }

    /**
     * @param  array<int, array{role: string, text: string}>  $messages
     */
    public static function save(array $messages, bool $open): void
    {
        session()->put(self::SESSION_KEY, [
            'open' => $open,
            'messages' => array_slice($messages, -self::MAX_MESSAGES),
        ]);
    }

    /**
     * Agrega un mensaje y lo guarda de inmediato. Se llama con la pregunta
     * del usuario ANTES de llamar a Gemini: si la respuesta falla o tarda,
     * la pregunta ya quedó guardada y no se pierde.
     *
     * @return array<int, array{role: string, text: string}>
     */
    public static function add(string $role, string $text): array
    {
        $state = self::load();
        $state['messages'][] = ['role' => $role, 'text' => mb_substr($text, 0, 1000)];
        $state['messages'] = array_slice($state['messages'], -self::MAX_MESSAGES);
        // open=false: enviar un mensaje no debe hacer que el chat se abra
        // solo en la siguiente página que Jorge visite.
        self::save($state['messages'], false);

        return $state['messages'];
    }
}
