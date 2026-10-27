<?php

namespace App\Credentials\Presentation\Http\Controllers;

use App\Credentials\Application\Exceptions\InvalidCurrentPasswordException;
use App\Credentials\Application\UseCases\SetCredentialSecret;
use App\Credentials\Domain\Entities\Credential;
use App\Credentials\Infrastructure\Persistence\Eloquent\Models\CredentialModel;
use App\Shared\Domain\Exceptions\DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class CredentialSecretController
{
    public function store(Request $request, CredentialModel $credential): RedirectResponse
    {
        Gate::authorize('setSecret', $credential);

        $maxLength = Credential::MAX_SECRET_LENGTH;

        $validator = Validator::make(
            $request->only(['current_password', 'secret', 'secret_confirmation']),
            [
                'current_password' => ['required', 'string', 'max:255'],
                'secret' => ['required', 'string', 'max:'.$maxLength, 'confirmed'],
            ],
        );

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $validated = $validator->validated();

        try {
            app(SetCredentialSecret::class)->execute(
                companyId: (int) $credential->company_id,
                credentialId: (int) $credential->id,
                plaintext: $validated['secret'],
                currentPassword: $validated['current_password'],
                actorId: (int) $request->user()->id,
            );
        } catch (InvalidCurrentPasswordException $exception) {
            return back()->withErrors(['current_password' => $exception->getMessage()]);
        } catch (DomainException $exception) {
            return back()->withErrors(['secret' => $exception->getMessage()]);
        }

        return back()->with('status', 'Secreto almacenado correctamente.');
    }
}
