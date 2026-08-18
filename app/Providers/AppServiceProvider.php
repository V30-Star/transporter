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
            $setini = \Illuminate\Support\Facades\DB::table('setini')->first();
            $addr1 = preg_replace('/^alamat\s*1?\s*:\s*/i', '', trim($setini->falamat1 ?? ''));
            $addr2 = preg_replace('/^alamat\s*2?\s*:\s*/i', '', trim($setini->falamat2 ?? ''));
            $view->with([
                'company_name' => $setini->fproject ?? 'PT. M-Trade',
                'company_city' => $setini->fcity ?? '',
                'company_address1' => $addr1,
                'company_address2' => $addr2,
                'company_telp' => $setini->ftelp ?? '',
                'company_fax' => $setini->ffax ?? '',
                'company_npwp' => $setini->fnpwp ?? '',
                'company_alamat1npwp' => $setini->falamat1npwp ?? '',
                'company_alamat2npwp' => $setini->falamat2npwp ?? '',
                'namattdpo' => $setini->fnamattdpo ?? '',
                'namattdpo2' => $setini->fnamattdpo2 ?? '',
                'namattdfakturpenjualan' => $setini->fnamattdfakturpenjualan ?? '',
                'namattdfakturpenjualan2' => $setini->fnamattdfakturpenjualan2 ?? '',
            ]);
        });
    }
}
