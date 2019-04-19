<?php

declare(strict_types=1);


namespace Adshares\Aduser\Utils;

class UrlNormalizer
{
    public static function normalize(string $url): string
    {
        return rtrim($url,'/');
    }
}
