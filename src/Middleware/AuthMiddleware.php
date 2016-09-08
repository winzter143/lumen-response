<?php
namespace F3\Middleware;

use Illuminate\Http\Request;
use F3\Components\Response;
use F3\Components\JWT;
use Closure;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     * @param Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $role = null)
    {
        try {
            if ($request->hasCookie('token')) {
                // Check if the token is passed as a cookie.
                $token = $request->cookie('token');
            } else {
                // Check if the token is passed using the authorization bearer schema.
                $token = $request->bearerToken();

                if (!$token) {
                    throw new \Exception('Token not found.', 401);
                }
            }

            // Check the token.
            $token = JWT::checkToken($token);

            // Get the party.
            $party = $token['party'];

            if ($role) {
                // Check the role.
                if (!$party->hasRole(explode('|', $role))) {
                    throw new \Exception('You do not have sufficient privileges to access this resource.', 401);
                }
            }

            // Make the party data available to the other controllers.
            $request->attributes->set('party', $party);

            // Call the next middleware/controller.
            return $next($request);

        } catch (\Exception $e) {
            return Response::exception($e);
        }
    }
}
