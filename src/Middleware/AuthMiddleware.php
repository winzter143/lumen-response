<?php
namespace F3\Middleware;

use Illuminate\Http\Request;
use F3\Components\Response;
use Closure;
use GuzzleHttp\Client;

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

            try {
                // Validate the token.
                // Create an HTTP client.
                $client = new Client;

                // Set up the parameters.
                $params = [
                    'token' => $token,
                    'role' => $role
                ];

                // Send the request.
                // The request is considered successful if we get a 200 status code.
                // Note: only network errors and 4xx-5xx errors will throw an exception so we should still check the status code of the response.
                // TODO: figure out how to require the environment variable.
                $response = $client->post(env('F3_BASE_URL') . '/auth/token', ['json' => $params]);

                if ($response->getStatusCode() == 200) {
                    // Decode the response.
                    $body = json_decode($response->getBody(), true);

                    // Instantiate the party.
                    $party = new $body['party']['class']($body['party']);
                } else {
                    throw new \Exception($response->getBody(), $response->getStatusCode());
                }
            } catch (\Exception $e) {
                if ($e->getCode() == 401) {
                    // Get the error message.
                    $body = json_decode($e->getResponse()->getBody());
                    throw new \Exception($body->description, 401);
                } else {
                    // Something else went wrong.
                    throw $e;
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
