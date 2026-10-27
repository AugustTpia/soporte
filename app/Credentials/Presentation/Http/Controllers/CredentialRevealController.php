<?php

namespace App\Credentials\Presentation\Http\Controllers;

use App\Credentials\Application\Exceptions\InvalidCurrentPasswordException;
use App\Credentials\Application\UseCases\RevealCredential;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Shared\Domain\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class CredentialRevealController
{
    public function store(Request $request, CredentialModel $credential): JsonResponse
    {
        Gate::authorize('reveal', $credential);

        $validator = Validator::make(
            $request->only(['current_password']),
            ['current_password' => ['required', 'string']],
        );

        if ($validator->fails()) {
            return response()->json(
                [
                    'message' => 'La contraseña actual es obligatoria.',
                    'errors' => $validator->errors(),
                ],
                422,
                $this->noStoreHeaders(),
            );
        }

        $validated = $validator->validated();

        try {
            $secret = app(RevealCredential::class)->execute(
                companyId: (int) $credential->company_id,
                credentialId: (int) $credential->id,
                currentPassword: $validated['current_password'],
                actorId: (int) $request->user()->id,
            );
        } catch (InvalidCurrentPasswordException $exception) {
            return $this->secretError($exception->getMessage(), 'current_password', 422);
        } catch (DomainException $exception) {
            return $this->secretError($exception->getMessage(), null, 422);
        }

        return response()->json(
            ['secret' => $secret],
            200,
            $this->noStoreHeaders(),
        );
    }

    private function secretError(string $message, ?string $field, int $status): JsonResponse
    {
        $payload = ['message' => $message];

        if ($field !== null) {
            $payload['errors'] = [$field => [$message]];
        }

        return response()->json($payload, $status, $this->noStoreHeaders());
    }

    /**
     * @return array<string, string>
     */
    private function noStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, private, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
