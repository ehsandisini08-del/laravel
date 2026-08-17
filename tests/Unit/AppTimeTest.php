<?php

use App\Support\AppTime;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config()->set('app.timezone', 'Asia/Jakarta');
});

it('shifts legacy naive UTC values to Asia/Jakarta', function () {
    expect(AppTime::display('2026-08-17 12:54:54', 'Y-m-d H:i:s'))
        ->toBe('2026-08-17 19:54:54');
});

it('keeps values that already carry a timezone offset unchanged', function () {
    expect(AppTime::display('2026-08-17T19:54:54+07:00', 'Y-m-d H:i:s'))
        ->toBe('2026-08-17 19:54:54');
});

it('re-formats offset values into the app timezone', function () {
    expect(AppTime::display('2026-08-17T19:54:54+05:30', 'Y-m-d H:i:s'))
        ->toBe('2026-08-17 21:24:54');
});

it('handles UTC values ending with Z', function () {
    expect(AppTime::display('2026-08-17T12:54:54Z', 'Y-m-d H:i:s'))
        ->toBe('2026-08-17 19:54:54');
});

it('returns null for empty values', function () {
    expect(AppTime::display(null))->toBeNull();
    expect(AppTime::display(''))->toBeNull();
});

it('stores an unambiguous ISO 8601 value', function () {
    expect(AppTime::store('2026-08-17 19:54:54'))
        ->toBe('2026-08-17T19:54:54+07:00');
});
