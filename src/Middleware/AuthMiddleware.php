<?php
namespace F3\Middleware;

use Illuminate\Http\Request;
use F3\Components\Response;
use Closure;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     * @param Illuminate\Http\Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $roles = null, $permission = null, $party_param = false, $override_party_param = true)
    {
        // Get the user.
        $user = $request->user();

        // Check if the user is assigned the roles.
        if ($roles) {
            // Parse the roles.
            $roles = explode('|', $roles);

            if (!$user->hasRole($roles)) {
                return Response::error(401, 'You do not have the privilege to access this resource.');
            }
        }

        // Check if the user has the permission.
        if ($permission) {
            if ($party_param) {
                if (!$user->can($permission, $request->input($party_param))) {
                    if (filter_var($override_party_param, FILTER_VALIDATE_BOOLEAN)) {
                        $request->merge([$party_param => $user->party_id]);
                    } else {
                        return Response::error(401, 'You do not have the permission to access this resource for the requested parties.');
                    }
                }
            } else {
                if (!$user->can($permission)) {
                    return Response::error(401, 'You do not have the permission to access this resource.');
                }
            }
        }

        // Call the next middleware/controller.
        return $next($request);
    }
}
