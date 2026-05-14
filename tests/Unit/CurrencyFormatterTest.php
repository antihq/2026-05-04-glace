<?php

use App\Support\CurrencyFormatter;

test('cents formats positive amounts', function () {
    expect(CurrencyFormatter::cents(150000))->toBe('$1,500.00');
});

test('cents formats zero', function () {
    expect(CurrencyFormatter::cents(0))->toBe('$0.00');
});

test('cents formats negative amounts with minus sign', function () {
    expect(CurrencyFormatter::cents(-25075))->toBe('-$250.75');
});

test('cents formats large amounts with commas', function () {
    expect(CurrencyFormatter::cents(123456789))->toBe('$1,234,567.89');
});

test('cents formats single cent', function () {
    expect(CurrencyFormatter::cents(1))->toBe('$0.01');
});

test('percent formats positive with plus sign', function () {
    expect(CurrencyFormatter::percent(25.50))->toBe('+25.50%');
});

test('percent formats negative without plus sign', function () {
    expect(CurrencyFormatter::percent(-10.25))->toBe('-10.25%');
});

test('percent formats zero without plus sign', function () {
    expect(CurrencyFormatter::percent(0.0))->toBe('0.00%');
});

test('delta cents formats positive with plus prefix', function () {
    expect(CurrencyFormatter::deltaCents(50000))->toBe('+$500.00');
});

test('delta cents formats negative without double minus', function () {
    expect(CurrencyFormatter::deltaCents(-30000))->toBe('-$300.00');
});

test('delta cents formats zero without plus', function () {
    expect(CurrencyFormatter::deltaCents(0))->toBe('$0.00');
});
