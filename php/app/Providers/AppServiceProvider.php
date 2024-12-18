<?php

namespace App\Providers;

use App\Helpers\SourceHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //Load the helpers needed
        $this->registerHelperFunctions();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if(DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON;');
        }
        SourceHelper::checkRemoteForNewVersion();
    }

    private function registerHelperFunctions() {
        $directory = app_path('Helpers');
        if(File::exists($directory)) {
            foreach(File::allFiles($directory) as $file) {
                require_once $file->getPathname();
            }
        }
    }

}
