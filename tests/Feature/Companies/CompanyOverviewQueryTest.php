<?php

use App\Companies\Application\Contracts\CompanyOverviewQuery;
use App\Contacts\Infrastructure\Persistence\Eloquent\Models\ClientContactModel;
use App\Support\Infrastructure\Persistence\Eloquent\Models\SupportTicketModel;

test('the overview query isolates every module by company', function () {
    $company = createTestCompany();
    $otherCompany = createTestCompany();

    ClientContactModel::factory()->count(2)->forCompany($company)->create();
    ClientContactModel::factory()->count(4)->forCompany($otherCompany)->create();

    SupportTicketModel::factory()->count(3)->forCompany($company)->create();
    SupportTicketModel::factory()->count(6)->forCompany($otherCompany)->create();

    $overview = app(CompanyOverviewQuery::class)->execute($company->id, ['contacts', 'tickets']);

    expect($overview->counts['contacts'])->toBe(2)
        ->and($overview->counts['tickets'])->toBe(3)
        ->and($overview->recentTickets)->toHaveCount(3);
});

test('the overview query only loads enabled modules', function () {
    $company = createTestCompany();
    ClientContactModel::factory()->forCompany($company)->create();

    $overview = app(CompanyOverviewQuery::class)->execute($company->id, ['tickets']);

    expect($overview->counts)->toHaveKey('tickets')
        ->and($overview->counts)->not->toHaveKey('contacts');
});
