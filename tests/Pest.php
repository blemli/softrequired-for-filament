<?php

use Blemli\SoftRequired\Tests\Fixtures\User;
use Blemli\SoftRequired\Tests\TestCase;
use Filament\Facades\Filament;
use Filament\Panel;

uses(TestCase::class)->in(__DIR__);

function bootPanel(string $id = 'admin'): Panel
{
    $panel = Filament::getPanel($id);

    Filament::setCurrentPanel($panel);
    Filament::bootCurrentPanel();

    return $panel;
}

function loginUser(): User
{
    $user = User::create([
        'name' => 'Test User',
        'email' => uniqid() . '@example.com',
        'password' => bcrypt('secret'),
    ]);

    test()->actingAs($user);

    return $user;
}
