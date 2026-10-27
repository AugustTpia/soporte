<?php

use App\Shared\Domain\Pagination\Page;

test('a page calculates its pagination bounds', function () {
    $page = new Page(items: range(1, 10), total: 25, perPage: 10, currentPage: 2);

    expect($page->lastPage())->toBe(3);
    expect($page->hasPages())->toBeTrue();
    expect($page->from())->toBe(11);
    expect($page->to())->toBe(20);
});

test('an empty page reports no pages', function () {
    $page = new Page(items: [], total: 0, perPage: 15, currentPage: 1);

    expect($page->lastPage())->toBe(1);
    expect($page->hasPages())->toBeFalse();
    expect($page->from())->toBe(0);
    expect($page->to())->toBe(0);
});
