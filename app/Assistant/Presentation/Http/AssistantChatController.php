<?php

namespace App\Assistant\Presentation\Http;

use App\Assistant\AssistantChatState;
use App\Assistant\GeminiAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Envío del chat del asistente por POST normal + fetch.
 *
 * El texto de la pregunta viene en el cuerpo de la petición, no en el
 * snapshot de un componente Livewire: así un segundo envío no puede
 * perderse por una sincronización desactualizada del input.
 */
final class AssistantChatController
{
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $text = trim($validated['message']);

        if ($text === '') {
            return response()->json(['error' => 'Escribe una pregunta.'], 422);
        }

        // Guardar la pregunta ANTES de llamar a Gemini.
        $messages = AssistantChatState::add('user', $text);

        try {
            $result = app(GeminiAssistantService::class)->ask($text, $messages);
            $reply = $result['reply'];
        } catch (\Throwable $exception) {
            report($exception);
            $reply = 'Ocurrió un error al contactar al asistente. Inténtalo de nuevo en unos minutos, o crea un ticket para que un técnico lo revise.';
        }

        $messages = AssistantChatState::add('assistant', $reply);

        return response()->json([
            'reply' => $reply,
            'messages' => $messages,
            // El flotante nunca se auto-abre al navegar: el panel queda
            // cerrado en la siguiente página aunque haya historial.
            'open' => false,
        ]);
    }
}
