<?php

namespace App\Presentation\Http\Middleware;

use App\Companies\Domain\Enums\CompanyFeature;
use App\Companies\Domain\Repositories\CompanyRepository;
use App\Shared\Application\Contracts\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureCompanyFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $companyId = app(TenantContext::class)->companyId();
        $companyFeature = CompanyFeature::tryFrom($feature);

        if ($companyId === null || $companyFeature === null) {
            abort(403);
        }

        $company = app(CompanyRepository::class)->find($companyId);

        if ($company === null || ! $company->hasFeature($companyFeature)) {
            abort(403);
        }

        return $next($request);
    }
}
