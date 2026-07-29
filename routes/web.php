<?php

declare(strict_types = 1);

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn(): RedirectResponse => redirect()->to(Filament::getDefaultPanel()->getPath()))
    ->name('home');
