<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

class CacheController extends Controller
{
    public function clearCache()
    {
        try {
            // Clear application cache
            Artisan::call('cache:clear');

            // Clear route cache
            Artisan::call('route:clear');

            // Clear config cache
            Artisan::call('config:clear');

            // Clear view cache
            Artisan::call('view:clear');

            // Clear artisan cache
            Artisan::call('clear-compiled');

            // Clear application cache
            Artisan::call('optimize:clear');

            // Additional cache clearing commands can be added if needed

            return 'Cache cleared successfully.';
        } catch (\Exception $e) {
            return 'Error while clearing cache: '.$e->getMessage();
        }
    }
}
