<?php

namespace App\Assistant;

use App\Assets\Infrastructure\Persistence\Eloquent\Models\AssetModel;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Maintenance\Infrastructure\Persistence\Eloquent\Models\MaintenancePlanModel;
use App\Shared\Application\Contracts\TenantContext;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Asistente de soporte TI con Gemini.
 *
 * Solo consulta tickets, activos y mantenimientos de la empresa activa.
 * NUNCA consulta la bóveda de credenciales ni cuentas de servicio.
 */
class GeminiAssistantService
{
    public const SYSTEM_PROMPT = <<<'PROMPT'
Eres el asistente de soporte TI de la empresa activa en el sistema Soporte TI.
Responde siempre en español, de forma breve, clara y práctica.

Puedes ayudar a:
- Diagnosticar fallas de equipos (impresoras, computadoras, red, etc.) con preguntas guiadas.
- Consultar el estado de tickets y mantenimientos usando el contexto que se te da.
- Proponer crear un ticket cuando el problema no se resuelve con los pasos guiados.

Reglas estrictas:
- Nunca reveles, busques ni pidas contraseñas, credenciales ni secretos. Si te las piden, di que no tienes acceso a la bóveda de credenciales y que deben escalarlo a un humano.
- No inventes datos: si el contexto no trae un ticket, activo o mantenimiento, di que no lo ves registrado.
- Si la base está vacía, dilo y ofrece ayuda general o crear un ticket.
- Si no sabes la respuesta, dilo claramente y propone crear un ticket para que un técnico lo revise.
PROMPT;

    /**
     * @param  array<int, array{role: string, text: string}>  $history
     * @return array{reply: string, model: string|null, error: string|null}
     */
    public function ask(string $message, array $history = []): array
    {
        $apiKey = (string) config('assistant.api_key', '');

        if ($apiKey === '') {
            return [
                'reply' => 'El asistente todavía no tiene llave de Gemini configurada. Pídele a tu administrador que ponga GEMINI_API_KEY en el archivo .env del servidor y vuelve a intentarlo.',
                'model' => null,
                'error' => 'missing_api_key',
            ];
        }

        $models = array_values(array_unique(array_filter(array_merge(
            [(string) config('assistant.model', 'gemini-3.5-flash')],
            (array) config('assistant.fallbacks', []),
        ))));

        $lastError = null;
        $lastStatus = null;

        foreach ($models as $model) {
            try {
                $response = Http::timeout((int) config('assistant.timeout', 30))
                    ->withHeaders([
                        'X-Goog-Api-Key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->endpoint($model), $this->payload($message, $history));
            } catch (ConnectionException $exception) {
                Log::warning('Asistente Gemini: error de conexión', ['model' => $model, 'error' => $exception->getMessage()]);
                $lastError = 'connection';

                continue;
            }

            if ($response->status() === 401 || $response->status() === 403) {
                return [
                    'reply' => 'La llave de Gemini no es válida o no tiene permiso. Revisa GEMINI_API_KEY en el .env del servidor.',
                    'model' => $model,
                    'error' => 'invalid_api_key',
                ];
            }

            // Modelo no disponible o saturado: probar el siguiente fallback.
            if (in_array($response->status(), [404, 429, 503], true)) {
                $lastError = 'model_unavailable';
                $lastStatus = $response->status();
                Log::warning('Asistente Gemini: modelo no disponible, probando fallback', ['model' => $model, 'status' => $response->status()]);

                continue;
            }

            if (! $response->successful()) {
                $lastError = 'http_'.$response->status();
                $lastStatus = $response->status();
                Log::warning('Asistente Gemini: respuesta no exitosa', ['model' => $model, 'status' => $response->status()]);

                continue;
            }

            $text = $this->extractText($response->json());

            if ($text === null) {
                return [
                    'reply' => 'No pude generar una respuesta esta vez. Inténtalo de nuevo o crea un ticket para que un técnico lo revise.',
                    'model' => $model,
                    'error' => 'empty_response',
                ];
            }

            return ['reply' => $text, 'model' => $model, 'error' => null];
        }

        Log::warning('Asistente Gemini: todos los modelos fallaron', ['error' => $lastError, 'status' => $lastStatus]);

        return [
            'reply' => 'El servicio de IA está saturado o no responde en este momento. Inténtalo de nuevo en unos minutos, o crea un ticket para que un técnico lo revise.',
            'model' => null,
            'error' => $lastError ?? 'unavailable',
        ];
    }

    private function endpoint(string $model): string
    {
        return rtrim((string) config('assistant.base_url'), '/').'/models/'.urlencode($model).':generateContent';
    }

    /**
     * @param  array<int, array{role: string, text: string}>  $history
     */
    private function payload(string $message, array $history): array
    {
        $contents = [];

        foreach (array_slice($history, -6) as $turn) {
            $contents[] = [
                'role' => $turn['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $turn['text']]],
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        return [
            'systemInstruction' => [
                'parts' => [['text' => self::SYSTEM_PROMPT."\n\n".$this->buildContext()]],
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
            ],
        ];
    }

    private function extractText(mixed $data): ?string
    {
        if (! is_array($data)) {
            return null;
        }

        $parts = $data['candidates'][0]['content']['parts'] ?? null;

        if (! is_array($parts)) {
            return null;
        }

        $text = '';

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $text .= $part['text'];
            }
        }

        $text = trim($text);

        return $text === '' ? null : $text;
    }

    /**
     * Contexto real de la empresa activa. Solo tickets, activos y
     * mantenimientos. Jamás credenciales ni cuentas de servicio.
     */
    private function buildContext(): string
    {
        $tenant = app(TenantContext::class);
        $companyId = $tenant->companyId();

        if ($companyId === null) {
            $companies = app(CompanyRepository::class)->recent(10);
            $names = collect($companies)->map(fn ($c) => $c->name)->implode(', ');
            $totalTickets = SupportTicketModel::count();
            $totalAssets = AssetModel::count();

            return 'CONTEXTO: no hay una empresa activa seleccionada (vista de superadministrador). '
                .'Empresas registradas: '.($names !== '' ? $names : 'ninguna').'. '
                ."En total hay {$totalTickets} tickets y {$totalAssets} activos en el sistema. "
                .'Pide que seleccionen una empresa para dar datos concretos de sus tickets y activos.';
        }

        $company = app(CompanyRepository::class)->find($companyId);
        $companyName = $company?->name ?? 'empresa '.$companyId;

        $tickets = SupportTicketModel::query()
            ->where('company_id', $companyId)
            ->with('asset:id,internal_code,hostname')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $ticketLines = [];
        foreach ($tickets as $ticket) {
            $asset = $ticket->asset ? ($ticket->asset->internal_code.' / '.($ticket->asset->hostname ?? 'sin hostname')) : 'sin activo';
            $ticketLines[] = '- '.($ticket->folio ?? '#'.$ticket->id).': '.$ticket->subject
                .' | estado: '.$ticket->status
                .' | prioridad: '.$ticket->priority
                .' | activo: '.$asset
                .' | abierto: '.($ticket->opened_at?->format('d/m/Y') ?? $ticket->created_at?->format('d/m/Y'));
        }

        $assetCount = AssetModel::query()->where('company_id', $companyId)->count();
        $assetsByStatus = AssetModel::query()
            ->where('company_id', $companyId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($total, $status) => "{$status}: {$total}")
            ->implode(', ');

        $plans = MaintenancePlanModel::query()
            ->where('company_id', $companyId)
            ->where('next_due_at', '<=', now()->addDays(7))
            ->orderBy('next_due_at')
            ->limit(5)
            ->get();

        $planLines = [];
        foreach ($plans as $plan) {
            $planLines[] = '- '.$plan->title.' ('.$plan->target_label.') | vence: '.$plan->next_due_at->format('d/m/Y').' | estado: '.$plan->status;
        }

        $overdue = MaintenancePlanModel::query()
            ->where('company_id', $companyId)
            ->where('next_due_at', '<', now())
            ->count();

        return "CONTEXTO DE LA EMPRESA ACTIVA: {$companyName} (id {$companyId}).\n"
            ."Activos: {$assetCount}".($assetsByStatus !== '' ? " ({$assetsByStatus})" : '').".\n"
            ."Últimos tickets (máx. 10):\n".($ticketLines !== [] ? implode("\n", $ticketLines) : '- No hay tickets registrados todavía. La base está vacía.')."\n"
            ."Mantenimientos vencidos: {$overdue}. Próximos a vencer (7 días):\n".($planLines !== [] ? implode("\n", $planLines) : '- Ninguno próximo a vencer.');
    }
}
