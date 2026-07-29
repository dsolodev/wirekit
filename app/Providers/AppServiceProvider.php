<?php

declare(strict_types = 1);

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Filament\Livewire\Notifications;
use Filament\Support\Enums\VerticalAlignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureModels();
        $this->configureDates();
        $this->configureCommands();
        $this->configureUrls();
        $this->configurePasswords();
        $this->configureAuthorization();
        $this->configureFilament();
    }

    /**
     * Configure Eloquent's global behaviour.
     *
     * Strict mode is only enabled outside production. It turns lazy loading, accessing a missing attribute,
     * and discarding an unfillable attribute into exceptions, which is what you want while developing but
     * not what you want in front of real users. In production these degrade to the framework defaults, so a
     * missing attribute reads as null and authorization checks fail closed rather than throwing.
     *
     * Mass assignment protection is disabled globally. Every write in this application goes through a
     * Filament schema, and Filament only ever saves the fields declared in that schema, so `$fillable` would
     * be redundant bookkeeping. This trade-off stops holding the moment you accept request data anywhere
     * else: if you add an API controller, a public form, or any `Model::create($request->all())`, either
     * re-enable guarding or validate the payload into an explicit array first.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Model::unguard();
    }

    /**
     * Use immutable dates everywhere, matching the `date_time_immutable` rule enforced by Pint.
     */
    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    /**
     * Refuse to run commands that drop data when running against production.
     */
    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }

    /**
     * Always generate HTTPS URLs in production, regardless of what the proxy reports.
     */
    private function configureUrls(): void
    {
        URL::forceHttps($this->app->isProduction());
    }

    /**
     * Require reasonably strong passwords, and check production passwords against known breaches.
     */
    private function configurePasswords(): void
    {
        Password::defaults(fn(): Password => Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->when($this->app->isProduction(), fn(Password $rule): Password => $rule->uncompromised()));
    }

    /**
     * Gate access to the log viewer on the same signal that gates the panel itself.
     */
    private function configureAuthorization(): void
    {
        Gate::define('viewLogViewer', fn(?User $user): bool => $user?->is_active === true);
    }

    /**
     * Configure shared Filament presentation defaults.
     */
    private function configureFilament(): void
    {
        Notifications::verticalAlignment(VerticalAlignment::End);
    }
}
