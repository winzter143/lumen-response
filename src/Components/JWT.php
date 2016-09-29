<?php
namespace F3\Components;

use Validator;
use Cache;
use DB;
use F3\Models\Party;

class JWT 
{
    /**
     * Creates a new token.
     */
    public static function createToken($payload, $secret_key, $header = null)
    {
        // Set the default header.
        if (!$header) {
            $header = [
                'alg' => config('settings.jwt.alg'),
                'typ' => config('settings.jwt.typ')
            ];
        }

        // Check the header.
        if (!self::checkHeader($header)) {
            throw new \Exception('Invalid JWT header.', 401);
        }

        // Add iat to the payload.
        if (!isset($payload['iat'])) {
            $payload['iat'] = time();
        }

        // Check the payload.
        if (!self::checkPayload($payload)) {
            throw new \Exception('Invalid JWT payload.', 401);
        }

        // Create the signature.
        $signature = self::sign($header, $payload, $secret_key);

        // Build the token.
        return __base64_encode_safe(json_encode($header), true) . '.' . __base64_encode_safe(json_encode($payload), true) . '.' . $signature;
    }

    /**
     * Checks if the JWT is valid.
     */
    public static function checkToken($token)
    {
        try {
            // Parse the token.
            $token = explode('.', $token);

            // Decode the token.
            $token = self::decode($token);

            if (isset($token['payload']['exp'])) {
                // Check if the token has expired.
                if (time() > $token['payload']['exp']) {
                    throw new \Exception('The token has expired.', 401);
                }
            } else {
                // Check the iat value (issued at). This is the time when the token was generated.
                // Reject the token if the time difference between the current time and iat exceeds the allotted time limit.
                // Also, check if the iat value is negative. This happens if the client sends the timestamp in milliseconds.
                /*
                 * Note: disabling iat check for now.
                $diff = time() - $token['payload']['iat'];
                
                if ($diff < 0 || $diff > config('settings.jwt.max_iat')) {
                   throw new \Exception('Invalid token. Please check the iat value.', 401);
                }
                */
            }
            
            // Check if the party data is available from the cache.
            $token['party'] = Cache::get($token['payload']['sub']);

            if (!$token['party']) {
                // Get the party from the DB.
                $token['party'] = Party::getByApiKey($token['payload']['sub']);

                if (!$token['party']) {
                    throw new \Exception('Invalid key or your account may have been disabled.', 401);
                }

                // Cache it.
                Cache::put($token['payload']['sub'], $token['party'], config('settings.cache.expires_in'));
            }

            // Check the signature.
            if ($token['signature'] !== self::sign($token['header'], $token['payload'], $token['party']->secret_key, $token['header']['alg'])) {
                throw new \Exception('Invalid token. Please check the JWT signature.', 401);
            }

            return $token;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Decodes the token.
     */
    public static function decode($token)
    {
        // Check if the parts are complete.
        if (count($token) != 3) {
            throw new \Exception('Invalid token. Please check the token format.', 401);
        }

        // Decode the header.
        $header = json_decode(__base64_decode_safe($token[0], true), true);
        $header = ($header) ?: [];

        if (!self::checkHeader($header)) {
            throw new \Exception('Invalid JWT header.', 401);
        }

        // Decode the payload.
        $payload = json_decode(__base64_decode_safe($token[1], true), true);
        $payload = ($payload) ?: [];

        if (!self::checkPayload($payload)) {
            throw new \Exception('Invalid JWT payload.', 401);
        }

        return [
            'header' => $header,
            'payload' => $payload,
            'signature' => $token[2]
        ];
    }

    /**
     * Checks the token header.
     */
    public static function checkHeader($header)
    {
        // Validate the header.
        $validator = Validator::make($header, [
            'alg' => 'string|required|in:HS256',
            'typ' => 'string|required|in:JWT'
        ]);

        return $validator->passes();
    }

    /**
     * Checks the token payload.
     */
    public static function checkPayload($payload)
    {
        // Validate the payload.
        $validator = Validator::make($payload, [
            'sub' => 'string|required',
            'iat' => 'integer|required'
        ]);

        return $validator->passes();
    }

    /**
     * Generates a signature.
     */
    private static function sign($header, $payload, $secret_key, $alg = 'HS256')
    {
        // Build the data to be signed.
        $data = __base64_encode_safe(json_encode($header), true) . '.' . __base64_encode_safe(json_encode($payload), true);

        // Sign it.
        switch (strtolower($alg)) {
        case 'hs256':
            $signature = __base64_encode_safe(hash_hmac('sha256', $data, $secret_key, true), true);
            break;
        default:
            throw new \Exception('The requested hashing algorithm is not supported.', 401);
        }

        return $signature;
    }
}
