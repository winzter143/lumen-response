<?php
namespace F3\Middleware;

use Illuminate\Http\Request;
use F3\Components\Response;
use F3\Providers\CorsServiceProvider;
use Closure;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     * @param Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Fetch the origin.
            $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : false;
            
            if (in_array($origin, CorsServiceProvider::CORS['allowed_origins'])) {
                // Origin is allowed. Add the access control headers.
                return $next($request)
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Methods', 'OPTIONS, GET, POST, PUT, DELETE')
                    ->header('Access-Control-Allow-Credentials', 'true');
            } else {
                // Origin is not allowed.
                // Call the next middleware/controller.
                return $next($request);
            }
        } catch (\Exception $e) {
            return Response::exception($e);
        }
    }
}
