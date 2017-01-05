<?php
namespace F3\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use F3\Providers\UserProvider;
use GuzzleHttp\Client;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * @return void
     */
    public function register()
    {
    }

    /**
     * Boot the authentication services for the application.
     * @return void
     */
    public function boot()
    {
        // Register the UserProvider callback.
        // It should return a UserProvider instance or null.
        $this->app['auth']->viaRequest('api', [$this, 'getUser']);
    }

    /**
     * Returns the user instance.
     * @param Request $request
     */
    public function getUser($request)
    {
        // Get the token. It can be passed via cookie or the header using the "Authorization: Bearer" schema.
        $jwt = $this->getToken($request);

        try {
            // Validate the token and get the party details.
            // Create an HTTP client.
            $client = new Client;

            // Set up the parameters.
            $params = ['token' => $jwt];

            // Send the request.
            // The request is considered successful if we get a 200 status code.
            // Note: only network errors and 4xx-5xx errors will throw an exception so we should still check the status code of the response.
            // TODO: figure out how to require the environment variable.
            $response = $client->post(env('F3_BASE_URL') . '/auth/token', ['json' => $params]);

            if ($response->getStatusCode() == 200) {
                // Decode the response.
                $body = json_decode($response->getBody(), true);

                // Create the user provider.
                return new UserProvider($body);
            } else {
                // The token is invalid.
                return null;
            }
        } catch (\Exception $e) {
            // There was an error.
            return null;
        }

    }

    /**
     * Returns the JSON web token.
     * The JWT can be passed via cookie or the header using the "Authorization: Bearer" schema.
     * @param Request $request
     */
    private function getToken($request)
    {
        if ($request->hasCookie('token')) {
            $jwt = $request->cookie('token');
        } else {
            $jwt = $request->bearerToken();
        }

        return ($jwt) ? $jwt : null;
    }
    
}
