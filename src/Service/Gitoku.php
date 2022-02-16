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

use DateTimeInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Gitoku implements PageInfoProviderInterface
{
    public const GITOKU_URL = 'https://gitoku.com';

    private HttpClientInterface $client;
    private CacheInterface $cache;
    private int $apiVersion = 1;

    public function __construct(
        HttpClientInterface $client,
        CacheInterface $cache
    ) {
        $this->client = $client;
        $this->cache = $cache;
    }

    public function version(int $apiVersion): PageInfoProviderInterface
    {
        $this->apiVersion = $apiVersion;
        return $this;
    }

    public function getTaxonomy(): array
    {
        return $this->cache->get('gitoku_taxonomy_' . $this->apiVersion, function (ItemInterface $item) {
            $item->expiresAfter(60);
            return $this->request('/taxonomy');
        });
    }

    public function getInfo(string $url, array $categories = []): array
    {
        return $this->request('/page-rank/' . urlencode($url) . '?' . http_build_query(['categories' => $categories]));
    }

    public function getBatchInfo(int $limit = 1000, int $offset = 0, DateTimeInterface $changedAfter = null): array
    {
        $params = [
            'limit' => $limit,
            'offset' => $offset
        ];
        if (null !== $changedAfter) {
            $params['changedAfter'] = $changedAfter->format(DateTimeInterface::W3C);
        }
        return $this->request('/page-rank?' . http_build_query($params));
    }

    public function reassessment(array $data): array
    {
        return $this->request('/reassessment', 'POST', $data);
    }

    private function request(string $path, string $method = 'GET', ?array $data = null): array
    {
        $response = $this->client->request(
            $method,
            self::GITOKU_URL . '/api/v' . $this->apiVersion . $path,
            null !== $data ? ['json' => $data] : []
        );
        return $response->toArray();
    }
}
