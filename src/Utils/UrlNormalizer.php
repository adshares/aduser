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

final class UrlNormalizer
{
    private static array $urlCache = [];

    public static function normalize(string $url): string
    {
        return rtrim($url, '/');
    }

    public static function normalizeHost(string $url): string
    {
        if (strpos($url, '//') === false) {
            $url = '//' . $url;
        }
        if (($parts = parse_url($url)) === false || !isset($parts['host'])) {
            return '';
        }
        return preg_replace('/^www\./i', '', mb_strtolower($parts['host']));
    }

    public static function explodeUrl(string $url): array
    {
        if (!array_key_exists($url, self::$urlCache)) {
            if (strpos($url, '//') === false) {
                $url = '//' . $url;
            }

            if (($parts = parse_url($url)) === false) {
                return [];
            }
            $urls = [];
            [$cleanedHost, $cleanedUrl] = self::cleanHost($parts);
            if (!empty($cleanedUrl)) {
                $urls[] = $cleanedUrl;
            }

            $path = '';
            if (isset($parts['path'])) {
                foreach (explode('/', $parts['path']) as $item) {
                    if (empty($item)) {
                        continue;
                    }
                    $path .= '/' . $item;
                    $urls[] = $cleanedUrl . $path;
                }
            }
            if (isset($parts['query'])) {
                $urls[] = $cleanedUrl . $path . '?' . $parts['query'];
            }

            $urls = array_merge(array_reverse($urls), array_reverse(self::explodeHost($cleanedHost)));
            self::$urlCache[$url] = array_values($urls);
        }

        return self::$urlCache[$url];
    }

    private static function cleanHost(array $parts): array
    {
        $cleanedHost = '';
        $cleanedUrl = '';
        if (isset($parts['host'])) {
            $cleanedHost = preg_replace('/^www\./i', '', mb_strtolower($parts['host']));
            $cleanedUrl = '//' . $cleanedHost;
        }
        if (isset($parts['port'])) {
            $cleanedUrl .= ':' . $parts['port'];
        }
        return [$cleanedHost, $cleanedUrl];
    }

    private static function explodeHost(string $cleanedHost): array
    {
        $hosts = [];
        if (!empty($cleanedHost)) {
            $host = '';
            foreach (array_reverse(explode('.', $cleanedHost)) as $item) {
                if (empty($item)) {
                    continue;
                }
                if (empty($host)) {
                    $host = $item;
                } else {
                    $host = $item . '.' . $host;
                }
                $hosts[] = $host;
            }
        }
        return $hosts;
    }
}
