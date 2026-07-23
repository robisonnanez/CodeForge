<?php

namespace App\Providers;

use App\Models\Issue;
use App\Models\Repository;
use App\Policies\IssuePolicy;
use App\Policies\RepositoryPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\GateEvaluated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
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
        Gate::before(static function ($user, string $ability): ?bool {
            return method_exists($user, 'hasRole') && $user->hasRole('super-admin') ? true : null;
        });

        Gate::policy(Repository::class, RepositoryPolicy::class);
        Gate::policy(Issue::class, IssuePolicy::class);

        $this->configureSecurityAudit();
        $this->configureDefaults();
    }

    private function configureSecurityAudit(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            activity('security')
                ->causedBy($event->user)
                ->withProperties($this->requestContext(['result' => 'allowed']))
                ->log('authentication.login');
        });

        Event::listen(Logout::class, function (Logout $event): void {
            $activity = activity('security')
                ->withProperties($this->requestContext(['result' => 'allowed']));

            if ($event->user !== null) {
                $activity->causedBy($event->user);
            }

            $activity->log('authentication.logout');
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $activity = activity('security')
                ->withProperties($this->requestContext(['result' => 'denied']));

            if ($event->user !== null) {
                $activity->causedBy($event->user);
            }

            $activity->log('authentication.failed');
        });

        Event::listen(GateEvaluated::class, function (GateEvaluated $event): void {
            if (
                $event->result === true
                && $event->user?->hasRole('super-admin')
                && preg_match('/^(create|update|delete|restore|forceDelete|manage)/', $event->ability)
            ) {
                activity('security')
                    ->causedBy($event->user)
                    ->withProperties($this->requestContext([
                        'ability' => $event->ability,
                        'result' => 'allowed',
                    ]))
                    ->log('authorization.super-admin');
            }
        });
    }

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    private function requestContext(array $properties): array
    {
        if (! app()->runningInConsole()) {
            $properties['ip'] = request()->ip();
            $properties['user_agent'] = request()->userAgent();
        }

        return $properties;
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
