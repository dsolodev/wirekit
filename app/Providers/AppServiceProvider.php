<?php

declare(strict_types = 1);

namespace App\Providers;

use Filament\Livewire\Notifications;
use Filament\Schemas\Components\Form;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict();
        Model::unguard();

        Notifications::verticalAlignment(VerticalAlignment::End);
        Form::configureUsing(fn(Form $component): Form => $component->columns(1));
    }
}
