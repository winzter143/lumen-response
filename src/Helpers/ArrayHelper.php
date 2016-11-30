<?php
namespace F3\Helpers;

/**
 * Collection of methods for manipulating arrays.
 */
class ArrayHelper
{
    /**
     * Converts array keys using the dot notation into a multi-dimensional array.
     * This is the opposite of Laravel's array_dot helper function.
     * @param array $array Associative array.
     */
    public static function dotArray($array)
    {
        // Convert the items to a multi-dimensional array.
        foreach ($array as $k => $v) {
            if (strpos($k, '.') !== false) {
                array_set($array, $k, $v);
                unset($array[$k]);
            }
        }

        return $array;
    }
}