<?php
/**
 * Collection of utility functions.
 */
use Illuminate\Support\Facades\Hash;

/**
 * Laravel config_path() function.
 */
if (!function_exists('config_path')) {
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

/**
 * Laravel public_path() function.
 */
if (!function_exists('public_path')) {
    function public_path($path = '')
    {
        return app()->basePath() . '/public' . ($path ? '/' . $path : $path);
    }
}

/**
 * Generates an API/secret key pair for the given party.
 * @param int $party_id
 */
if (!function_exists('__generate_api_key')) {
    function __generate_api_key($party_id)
    {
        // Generate an API key.
        $key['api_key'] = uniqid($party_id);

        // Generate a secret key.
        $key['secret_key'] = md5(implode('|', [$party_id, $key['api_key'], microtime()]));

        // Set the expiration date to 60 days.
        $key['expires_at'] = date('Y-m-d', strtotime('+60 days'));

        return $key;
    }
}

/**
 * Generates a random key.
 */
if (!function_exists('__generate_key')) {
    function __generate_key($prefix = null)
    {
        $key = ($prefix) ? uniqid($prefix, true) : uniqid(null, true);
        return Hash::make($key);
    }
}

/**
 * Encodes the data and makes it URL safe.
 */
if (!function_exists('__base64_encode_safe')) {
    function __base64_encode_safe($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

/**
 * Decodes the data.
 */
if (!function_exists('__base64_decode_safe')) {
    function __base64_decode_safe($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}

/**
 * Checks if $array has numeric keys.
 */
if (!function_exists('__is_array_indexed')) {
    function __is_array_indexed($array)
    {
        // Check if it's an array.
        if (!is_array($array)) {
            return false;
        }

        // Empty arrays are indexed.
        if (empty($array)) {
            return true;
        }

        // Check the keys.
        foreach ($array as $k => $v) {
            if (!is_int($k)) {
                return false;
            }
        }

        return true;
    }
}

/**
 * Checks if $array has string keys.
 */
if (!function_exists('__is_array_associative')) {
    function __is_array_associative($array)
    {
        // Check if it's an array. Empty arrays are not associative.
        if (!is_array($array) || empty($array)) {
            return false;
        }

        // Check the keys. At least one key should be string.
        foreach ($array as $k => $v) {
            if (is_string($k)) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Formats the given date.
 */
if (!function_exists('__format_date')) {
    function __format_date($date, $adjustment = null, $format = 'Y-m-d H:i:s')
    {
        if (!is_numeric($date)) {
            // Convert it to unix time.
            $date = strtotime($date);
        }

        if (!$date) {
            return false;
        }
        
        // Apply the date adjustment.
        if ($adjustment) {
            $date = strtotime($adjustment, $date);

            if (!$date) {
                return false;
            }
        }

        // Format it.
        return date($format, $date);
    }
}
