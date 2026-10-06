<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Recursive request sanitizer for Core PHP (POST / GET / nested data).
 *
 * Use when reading user input before validation / business logic.
 * Always escape again when printing HTML (View::e).
 *
 * Supported shapes:
 * - string, int, float, bool, null
 * - list arrays, associative arrays
 * - multidimensional arrays
 * - objects / array of objects (public props + JsonSerializable / stdClass)
 */

final class Input
{
    /**
     * Sanitize any request-shaped value recursively.
     *
     * @param  mixed  $value   Raw input (string|array|object|scalar|null)
     * @param  bool   $trim    Trim whitespace on strings
     * @param  bool   $stripTags Remove HTML tags (recommended for plain-text fields)
     * @return mixed  Same shape as input, with string leaves cleaned
     */
    public static function sanitize(mixed $value, bool $trim = true, bool $stripTags = true): mixed
    {
        // null value stays null (use full for optional fields)
        if (is_null($value)) {
            return null;
        }
        
        // Booleans - do not cast to string
        if (is_bool($value)) {
            return $value;
        }

        // numbers - keep as numbers (avoids "1<script>" style confusion later)
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        // Strings - the actual cleaning happens here
        if (is_string($value)) {
            return self::sanitizeString($value, $trim, $stripTags);
        }

        // Arrays (list, assoc, multi-dimensional)
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $item) {
                // Sanitize keys to (attacker can send weird key names)
                $safeKey = is_string($key) ? self::sanitizeString($key, true, true) : $key;

                $clean[$safeKey] = self::sanitize($item, $trim, $stripTags);
            }

            return $clean;
        }

        // Objects — convert to a safe array of public data, then sanitize
        if (is_object($value)) {
            return self::sanitize(self::objectToArray($value), $trim, $stripTags);
        }

        // Resources / unknown types — reject rather than pass through
        return null;
    }

     /**
     * Clean a single string leaf.
     */
    private static function sanitizeString(string $value, bool $trim, bool $stripTags): string
    {
        // Normalize encoding — invalid UTF-8 can bypass filters
        $value = self::toUtf8($value);

        // Null bytes used in old truncation / path tricks
        $value = str_replace("\0", '', $value);

        if ($trim) {
            $value = trim($value);
        }

        if ($stripTags) {
            // Plain-text fields: drop tags. (For rich HTML later, use a real HTML purifier.)
            $value = strip_tags($value);
        }

        /*
         * Encode special characters that have meaning in HTML.
         * ENT_QUOTES: both ' and "
         * ENT_SUBSTITUTE: replace invalid code units instead of returning empty string
         * ENT_HTML5: modern entity set
         *
         * Note: If you will escape again on output with htmlspecialchars,
         * double-encoding can show &amp; — pick ONE strategy:
         *   A) sanitize without htmlspecialchars here, escape only on output (common), OR
         *   B) encode here and print without re-escaping (easy to forget later).
         *
         * Industry default for apps with a View layer: strip/normalize here,
         * htmlspecialchars on output. We use strip + control-char filter here,
         * and leave final HTML escaping to View::e().
         *
         * If you truly want encode-at-input, uncomment the next line and
         * do NOT call View::e() again on the same string.
         */

        // Strip other control characters except \r \n \t
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';

        return $value;
    }

    /**
     * Force UTF-8; drop / convert broken sequences.
     */
    private static function toUtf8(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = mb_convert_encoding($value, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');

        return is_string($converted) ? $converted : '';
    }

    /**
     * Flatten objects into arrays for recursive sanitize.
     *
     * @return array<string, mixed>
     */
    private static function objectToArray(object $object): array
    {
        if ($object instanceof \JsonSerializable) {
            $data = $object->jsonSerialize();
            return is_array($data) ? $data : ['value' => $data];
        }

        if ($object instanceof \stdClass) {
            /** @var array<string, mixed> $arr */
            $arr = (array) $object;
            return $arr;
        }

        // Generic object: public properties only (never expose private via reflection)
        $arr = get_object_vars($object);

        return $arr;
    }


    /**
     * Read and sanitize $_GET or $_POST (or a custom bag).
     *
     * Examples:
     *   Input::fromRequest('post');
     *   Input::fromRequest('get');
     *   Input::fromRequest('request'); // merged GET + POST (POST wins)
     *
     * @param  'get'|'post'|'request'  $source
     * @return array<string, mixed>
     */
    public static function fromRequest(string $source = 'post'): array
    {
        $source = strtolower($source);

        $bag = match ($source) {
            'get' => $_GET,
            'post' => $_POST,
            'request' => array_merge($_GET, $_POST),
            default => throw new \InvalidArgumentException('Source must be get, post, or request.'),
        };

        /** @var array<string, mixed> $clean */
        $clean = self::sanitize($bag);

        return is_array($clean) ? $clean : [];
    }

    /**
     * Fetch one key from sanitized POST/GET.
     *
     * @param  mixed  $default  Returned if key missing
     */
    public static function get(string $key, string $source = 'post', mixed $default = null): mixed
    {
        $data = self::fromRequest($source);

        return array_key_exists($key, $data) ? $data[$key] : $default;
    }
}