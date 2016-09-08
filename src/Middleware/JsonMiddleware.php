<?php
namespace F3\Middleware;

use Illuminate\Http\Request;
use F3\Components\Response;
use Closure;

class JsonMiddleware
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
            // Make sure that the request body is not empty and should be in JSON format.
            if (!$request->all()) {
                return Response::error(400);
            }

            // Call the next middleware/controller.
            return $next($request);

        } catch (\Exception $e) {
            return Response::exception($e);
        }
    }
}
