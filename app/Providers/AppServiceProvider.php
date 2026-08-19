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
            $setting = company_setting();

            $view->with([
                'company_name' => $setting->fproject,
                'company_city' => $setting->fcity,
                'company_address1' => $setting->falamat1,
                'company_address2' => $setting->falamat2,
                'company_alamat1' => $setting->falamat1,
                'company_alamat2' => $setting->falamat2,
                'company_telp' => $setting->ftelp,
                'company_fax' => $setting->ffax,
                'company_npwp' => $setting->fnpwp,
                'company_alamat1npwp' => $setting->falamat1npwp,
                'company_alamat2npwp' => $setting->falamat2npwp,
                'namattdpo' => $setting->fnamattdpo,
                'namattdpo2' => $setting->fnamattdpo2,
                'namattdfakturpenjualan' => $setting->fnamattdfakturpenjualan,
                'namattdfakturpenjualan2' => $setting->fnamattdfakturpenjualan2,
                'company_setting' => $setting,
            ]);
        });
    }
}
