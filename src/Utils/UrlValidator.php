<?php

/**
 * Copyright (c) 2018-2022 Adshares sp. z o.o.
 *
 * This file is part of AdUser
 *
 * AdUser is free software: you can redistribute and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AdUser is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AdServer. If not, see <https://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace App\Utils;

final class UrlValidator
{
    private const URL_PATTERN = '~^
        https?://                                                                # protocol
        (((?:[\_\.\pL\pN-]|%[0-9A-F]{2})+:)?((?:[\_\.\pL\pN-]|%[0-9A-F]{2})+)@)? # basic auth (optional)
        ([\pL\pN\pS\-\_\.])+(\.?([\pL\pN]|xn\-\-[\pL\pN-]+)+\.?)                 # a domain name
        (:[0-9]+)?                                                               # a port (optional)
        (?:/ (?:[\pL\pN\-._\~!$&\'()*+,;=:@]|%[0-9A-F]{2})* )*                   # a path (optional)
        (?:\? (?:[\pL\pN\-._\~!$&\'\[\]()*+,;=:@/?]|%[0-9A-F]{2})* )?            # a query (optional)
        (?:\# (?:[\pL\pN\-._\~!$&\'()*+,;=:@/?]|%[0-9A-F]{2})* )?                # a fragment (optional)
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
