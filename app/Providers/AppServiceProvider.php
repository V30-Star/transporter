<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Validator;

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
    public function boot()
    {
        \Illuminate\Support\Facades\Validator::extend('fsysuserid', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[a-zA-Z0-9_]+$/', $value);
        });

        // NYALAKAN KEMBALI INI
        if (!empty(env('APP_URL'))) {
            \Illuminate\Support\Facades\URL::forceRootUrl(env('APP_URL'));
        }
    }
}
