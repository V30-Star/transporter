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
        if (config('app.url')) {
            \Illuminate\Support\Facades\URL::forceRootUrl(config('app.url'));
        }

        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $setini = \Illuminate\Support\Facades\DB::table('setini')->first(['fproject', 'fcity', 'falamat1', 'falamat2']);
            $addr1 = preg_replace('/^alamat\s*1?\s*:\s*/i', '', trim($setini->falamat1 ?? ''));
            $addr2 = preg_replace('/^alamat\s*2?\s*:\s*/i', '', trim($setini->falamat2 ?? ''));
            $view->with([
                'company_name' => $setini->fproject ?? 'PT. DEMO VERSION',
                'company_city' => $setini->fcity ?? '',
                'company_address1' => $addr1,
                'company_address2' => $addr2,
            ]);
        });
    }
}
