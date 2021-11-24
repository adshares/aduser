<?php

declare(strict_types=1);

namespace App\Utils;

final class UrlValidator
{
    private const URL_PATTERN = '~^
        https?://                                                                        # protocol
        (((?:[\_\.\pL\pN-]|%%[0-9A-Fa-f]{2})+:)?((?:[\_\.\pL\pN-]|%%[0-9A-Fa-f]{2})+)@)? # basic auth
        ([\pL\pN\pS\-\_\.])+(\.?([\pL\pN]|xn\-\-[\pL\pN-]+)+\.?)                         # a domain name
        (:[0-9]+)?                                                                       # a port (optional)
        (?:/ (?:[\pL\pN\-._\~!$&\'()*+,;=:@]|%%[0-9A-Fa-f]{2})* )*                       # a path
        (?:\? (?:[\pL\pN\-._\~!$&\'\[\]()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?                # a query (optional)
        (?:\# (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%%[0-9A-Fa-f]{2})* )?                    # a fragment (optional)
    $~ixu';

    /**
     * Checks if provided URL is valid. Accepts HTTP and HTTPS protocol only.
     *
     * @param mixed $value URL
     *
     * @return bool true, if URL is valid
     */
    public static function isValid($value): bool
    {
        if (null === $value || '' === $value) {
            return false;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            return false;
        }

        $value = (string)$value;
        if ('' === $value) {
            return false;
        }

        return 1 === preg_match(self::URL_PATTERN, $value);
    }
}
