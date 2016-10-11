<?php
namespace F3\Providers;

use Illuminate\Support\ServiceProvider;

class CorsServiceProvider extends ServiceProvider
{
    /**
     * Class constants.
     */
    const CORS = [
        'allowed_origins' => ['http://orders.local.lbcx.ph:3000', 'http://orders.staging.lbcx.ph']
    ];

    /**
     * Register any application services.
     * @return void
     */
    public function register()
    {
        // Get the request object.
        $request = app('request');

        // Fetch the origin.
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : false;

        if (strtolower($request->method()) == 'options' && in_array($origin, self::CORS['allowed_origins'])) {
            app()->options($request->path(), function() use($origin) {
                return response('OK', 200)
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, DELETE')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Origin, Set-Cookie, Authorization')
                    ->header('Access-Control-Allow-Credentials', 'true');
            });
        }
    }
}
