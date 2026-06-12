<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    /**
     * Sobrescribe el bypass de local para que el gate
     * aplique en todos los entornos incluyendo local
     */
    protected function authorization(): void
    {
        $this->gate();

        Horizon::auth(function ($request) {
            // Si no hay usuario autenticado, redirigir al login
            if (!auth()->check()) {
                return redirect()->route('login');
            }

            return Gate::check('viewHorizon', [$request->user()]);
        });
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return $user && $user->hasRole('Administrador');
        });
    }
}
