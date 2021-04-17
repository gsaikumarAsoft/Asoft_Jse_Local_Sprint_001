<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\Sftp\SftpAdapter;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        if (env('APP_DEBUG')) {
           DB::listen(function ($query) {
           File::append(
               storage_path('/logs/query.log'),
               $query->sql . ' [' . implode(', ', $query->bindings) . ']' . PHP_EOL
                );

            });
        }

        //DB::listen(function($query) {
        //    Log::info(
        //        $query->sql,
        //        $query->bindings,
        //        $query->time
        //    );
        //});

        Storage::extend('sftp', function ($app, $config) {
            return new Filesystem(new SftpAdapter($config));
        });
    }
}
