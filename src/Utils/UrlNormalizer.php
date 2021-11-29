<?php

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

            $cleanedHost = '';
            $cleanedUrl = '';
            if (isset($parts['host'])) {
                $cleanedHost = preg_replace('/^www\./i', '', mb_strtolower($parts['host']));
                $cleanedUrl = '//' . $cleanedHost;
            }
            if (isset($parts['port'])) {
                $cleanedUrl .= ':' . $parts['port'];
            }
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

            $urls = array_reverse($urls);
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
            $urls = array_merge($urls, array_reverse($hosts));

            self::$urlCache[$url] = array_values($urls);
        }

        return self::$urlCache[$url];
    }
}
