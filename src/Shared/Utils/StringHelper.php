<?php

declare(strict_types=1);

namespace App\Shared\Utils;

/**
 * String manipulation utilities
 */
class StringHelper
{
    /**
     * Convert string to camelCase
     * 
     * @param string $string
     * @return string
     */
    public static function camelCase(string $string): string
    {
        $string = str_replace(['-', '_'], ' ', $string);
        $string = ucwords(strtolower($string));
        $string = str_replace(' ', '', $string);
        return lcfirst($string);
    }

    /**
     * Convert string to PascalCase
     * 
     * @param string $string
     * @return string
     */
    public static function pascalCase(string $string): string
    {
        return ucfirst(self::camelCase($string));
    }

    /**
     * Convert string to snake_case
     * 
     * @param string $string
     * @return string
     */
    public static function snakeCase(string $string): string
    {
        $string = preg_replace('/([a-z])([A-Z])/', '$1_$2', $string);
        $string = str_replace(['-', ' '], '_', $string);
        return strtolower($string);
    }

    /**
     * Convert string to kebab-case
     * 
     * @param string $string
     * @return string
     */
    public static function kebabCase(string $string): string
    {
        $string = preg_replace('/([a-z])([A-Z])/', '$1-$2', $string);
        $string = str_replace(['_', ' '], '-', $string);
        return strtolower($string);
    }

    /**
     * Generate a random string
     * 
     * @param int $length
     * @param string $characters
     * @return string
     */
    public static function random(int $length = 16, string $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'): string
    {
        $result = '';
        $charactersLength = strlen($characters);
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, $charactersLength - 1)];
        }
        
        return $result;
    }

    /**
     * Check if string starts with given substring
     * 
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }

    /**
     * Check if string ends with given substring
     * 
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /**
     * Check if string contains given substring
     * 
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }

    /**
     * Truncate string to specified length
     * 
     * @param string $string
     * @param int $length
     * @param string $suffix
     * @return string
     */
    public static function truncate(string $string, int $length, string $suffix = '...'): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - strlen($suffix)) . $suffix;
    }

    /**
     * Sanitize string for use in URLs
     * 
     * @param string $string
     * @return string
     */
    public static function slug(string $string): string
    {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        return trim($string, '-');
    }

    /**
     * Mask sensitive information in string
     * 
     * @param string $string
     * @param int $visibleStart
     * @param int $visibleEnd
     * @param string $mask
     * @return string
     */
    public static function mask(string $string, int $visibleStart = 2, int $visibleEnd = 2, string $mask = '*'): string
    {
        $length = strlen($string);
        
        if ($length <= $visibleStart + $visibleEnd) {
            return str_repeat($mask, $length);
        }
        
        $start = substr($string, 0, $visibleStart);
        $end = substr($string, -$visibleEnd);
        $middle = str_repeat($mask, $length - $visibleStart - $visibleEnd);
        
        return $start . $middle . $end;
    }

    /**
     * Convert string to title case
     * 
     * @param string $string
     * @return string
     */
    public static function title(string $string): string
    {
        return ucwords(strtolower($string));
    }

    /**
     * Remove extra whitespace from string
     * 
     * @param string $string
     * @return string
     */
    public static function squish(string $string): string
    {
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}