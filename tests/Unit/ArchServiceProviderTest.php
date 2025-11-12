<?php

declare(strict_types = 1);

use Pekral\Arch\ArchServiceProvider;

test('register configures arch settings', function (): void {
    $provider = new ArchServiceProvider(app());
    
    $provider->register();
    
    expect(config()->has('arch'))->toBeTrue()
        ->and(config('arch.default_items_per_page'))->toBe(15);
});

test('boot works in console', function (): void {
    $provider = new ArchServiceProvider(app());
    
    $provider->boot();
    
    expect(config()->has('arch'))->toBeTrue();
});
