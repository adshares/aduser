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

namespace App\Service;

use App\Utils\UrlNormalizer;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class RequestInfo
{
    private Browscap $browscap;
    private Cookie3 $cookie3;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        Browscap $browscap,
        Cookie3 $cookie3,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->browscap = $browscap;
        $this->cookie3 = $cookie3;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getDeviceKeywords(ParameterBag $params): array
    {
        $keywords = [];
        $extensions = Taxonomy::mapExtensions($params->get('extensions', []));
        if (!empty($extensions)) {
            $keywords['extensions'] = $extensions;
        }
        if (null !== ($info = $this->getInfo($params))) {
            $keywords = array_merge(
                $keywords,
                [
                    'type' => Taxonomy::mapDeviceType($info->device_type),
                    'os' => Taxonomy::mapOperatingSystem($info->platform),
                    'browser' => Taxonomy::mapBrowser($info->browser),
                    'crawler' => $info->crawler ?? false,
                ]
            );
        }
        return $keywords;
    }

    public function getSiteKeywords(ParameterBag $params): array
    {
        $requestUrl = $params->get('url');

        $domain = [];
        if ($requestUrl !== null) {
            $url = UrlNormalizer::normalize((string)$requestUrl);
            $domain = UrlNormalizer::explodeUrl($url);
        }

        $keywords = [];
        if (!empty($domain)) {
            $keywords['domain'] = $domain;
        } else {
            $this->logger->debug('Cannot find domain for', $params->all());
        }

        return $keywords;
    }

    public function getCookie3Tags(ParameterBag $params): array
    {
        $account = $params->get('account');
        if (empty($account) || !preg_match('/^0x[0-9a-f]{40}$/i', $account)) {
            $this->logger->debug('Cannot find account', $params->all());
            return [];
        }
        return $this->cookie3->getTags($account) ?? [];
    }

    public function isCrawler(ParameterBag $params): bool
    {
        $info = $this->getInfo($params);
        return (bool)($info->crawler ?? false);
    }

    private function getInfo(ParameterBag $params): ?stdClass
    {
        $userAgent = self::getHeader('User-Agent', $params);

        if ($userAgent === null) {
            $this->logger->debug('Cannot find User-Agent', $params->all());
            return null;
        }
        try {
            $key = 'browscap_info_' . sha1($userAgent);
            return $this->cache->get(
                $key,
                function (ItemInterface $item) use ($userAgent) {
                    $item->expiresAfter(300);
                    $this->logger->debug(sprintf('Info cache MISS for %s', $userAgent));
                    return $this->browscap->getInfo($userAgent);
                }
            );
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
            return null;
        }
    }

    private static function getHeader(string $name, ParameterBag $params): ?string
    {
        $value = null;
        if (null !== $headers = $params->get('headers')) {
            $value = $headers[$name] ?? $headers[strtolower($name)] ?? null;
        }

        return $value;
    }
}
