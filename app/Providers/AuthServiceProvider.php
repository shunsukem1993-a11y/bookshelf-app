<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Policies\DatabaseNotificationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\DatabaseNotification;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * DatabaseNotificationはIlluminate\Notifications名前空間のモデルであり、
     * Laravelの自動解決（App\Models\*のみ対象）が及ばないため、手動で登録する。
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        DatabaseNotification::class => DatabaseNotificationPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
