<?php

namespace App\Presentation\Http\Middleware;

use App\Companies\Domain\Enums\CompanyStatus;
use App\Companies\Infrastructure\Persistence\Eloquent\Models\CompanyModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        if (! $user->isActive()) {
            return $this->logout($request, 'Tu cuenta está inactiva. Contacta al administrador.');
        }

        if ($user->isSuperAdmin()) {
            $companyId = session('active_company_id');

            if ($companyId !== null) {
                $company = CompanyModel::query()
                    ->whereKey($companyId)
                    ->where('status', CompanyStatus::Active->value)
                    ->first();

                if ($company === null) {
                    session()->forget('active_company_id');
                }
            }

            return $next($request);
        }

        $company = $user->company;

        if ($company === null || $company->status !== CompanyStatus::Active->value) {
            return $this->logout($request, 'Tu empresa está inactiva o no está disponible. Contacta al administrador.');
        }

        return $next($request);
    }

    private function logout(Request $request, string $message): Response
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors(['email' => $message]);
    }
}
