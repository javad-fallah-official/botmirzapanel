<?php

declare(strict_types=1);

namespace App\Shared\Utils;

/**
 * Array manipulation utilities
 */
class ArrayHelper
{
    /**
     * Get a value from an array using dot notation
     * 
     * @param array $array The array to search
     * @param string $key The key in dot notation (e.g., 'user.profile.name')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public static function get(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Set a value in an array using dot notation
     * 
     * @param array &$array The array to modify
     * @param string $key The key in dot notation
     * @param mixed $value The value to set
     * @return void
     */
    public static function set(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $segment) {
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                $current[$segment] = [];
            }
            $current = &$current[$segment];
        }

        $current = $value;
    }

    /**
     * Check if a key exists in an array using dot notation
     * 
     * @param array $array The array to check
     * @param string $key The key in dot notation
     * @return bool
     */
    public static function has(array $array, string $key): bool
    {
        if (isset($array[$key])) {
            return true;
        }

        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    /**
     * Remove a key from an array using dot notation
     * 
     * @param array &$array The array to modify
     * @param string $key The key in dot notation
     * @return void
     */
    public static function forget(array &$array, string $key): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        for ($i = 0; $i < count($keys) - 1; $i++) {
            $segment = $keys[$i];
            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                return;
            }
            $current = &$current[$segment];
        }

        unset($current[end($keys)]);
    }

    /**
     * Flatten a multi-dimensional array
     * 
     * @param array $array The array to flatten
     * @param string $prefix Prefix for keys
     * @return array
     */
    public static function flatten(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? $key : $prefix . '.' . $key;

            if (is_array($value)) {
                $result = array_merge($result, self::flatten($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Filter array by keys
     * 
     * @param array $array The array to filter
     * @param array $keys Keys to keep
     * @return array
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Remove keys from array
     * 
     * @param array $array The array to filter
     * @param array $keys Keys to remove
     * @return array
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Check if array is associative
     * 
     * @param array $array
     * @return bool
     */
    public static function isAssociative(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }
}