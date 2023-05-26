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
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Gitoku implements PageInfoProviderInterface
{
    public const GITOKU_URL = 'https://gitoku.com';

    private HttpClientInterface $client;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private int $apiVersion = 1;

    public function __construct(
        HttpClientInterface $client,
        CacheInterface $cache,
        LoggerInterface $logger,
    ) {
        $this->client = $client;
        $this->cache = $cache;
        $this->logger = $logger;
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
        $path = '/page-rank/' . urlencode($url) . '?' . http_build_query(['categories' => $categories]);
        try {
            return $this->request($path);
        } catch (ExceptionInterface $exception) {
            $this->logger->error('Gitoku getInfo failed', ['exception' => $exception]);
            return [];
        }
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

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
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
