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

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TimeoutExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class Cookie3
{
    public const STATUS_PENDING = 1;
    public const STATUS_READY = 2;
    public const STATUS_UNAVAILABLE = 3;

    private const CACHE_EXPIRY_PERIOD = 5 * 60;
    private const FETCH_TIMEOUT = 5;
    private const QUICK_FETCH_TIMEOUT = 0.1;

    private string $apiUrl;
    private string $apiKey;
    private HttpClientInterface $client;
    private Connection $connection;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        string $apiUrl,
        string $apiKey,
        HttpClientInterface $client,
        Connection $connection,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
        $this->client = $client;
        $this->connection = $connection;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getTags(string $address): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            return $this->cache->get('cookie3_tags_' . $address, function (ItemInterface $item) use ($address) {
                $item->expiresAfter(self::CACHE_EXPIRY_PERIOD);
                $row = $this->connection->fetchAssociative(
                    'SELECT tags FROM cookie3_wallets WHERE address = :address',
                    ['address' => $address]
                );
                if (false !== $row) {
                    $this->connection->executeQuery(
                        'UPDATE cookie3_wallets SET visited_at = NOW() WHERE address = :address',
                        ['address' => $address]
                    );
                    return null !== $row['tags'] ? json_decode($row['tags'], true) : null;
                }
                return $this->updateTags($address);
            });
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
            return null;
        }
    }

    /**
     * @throws DBALException
     */
    public function updateTags(string $address, bool $quickFetch = true, ?int $id = null): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        try {
            $result = $this->request('/analysis/' . $address, $quickFetch);
        } catch (TimeoutExceptionInterface $exception) {
            $this->logger->warning($exception->getMessage());
            $result = ['processing_status' => ['code' => self::STATUS_PENDING]];
        } catch (ExceptionInterface $exception) {
            $this->logger->error($exception->getMessage());
        }

        $status = $result['processing_status']['code'] ?? self::STATUS_UNAVAILABLE;
        $tags = self::STATUS_READY === $status ? self::extractTags($result['result'] ?? []) : null;

        $parameters = [
            'status' => $status,
            'tags' => null !== $tags ? json_encode($tags) : null,
            'updated_at' => self::STATUS_READY === $status ? (new DateTimeImmutable()) : null
        ];
        $types = [
            'updated_at' => Types::DATETIME_IMMUTABLE
        ];

        if (null !== $id) {
            $this->connection->executeQuery(
                'UPDATE cookie3_wallets
                SET status = :status, tags = :tags, updated_at = :updated_at
                WHERE id = :id',
                array_merge($parameters, ['id' => $id]),
                $types
            );
        } else {
            $this->connection->executeQuery(
                'INSERT INTO cookie3_wallets (address, status, tags, updated_at)
                VALUES (:address, :status, :tags, :updated_at)',
                array_merge($parameters, ['address' => $address]),
                $types
            );
        }

        return $tags;
    }

    /**
     * @throws ExceptionInterface
     */
    private function request(string $path, bool $quickFetch = true): array
    {
        $response = $this->client->request(
            'GET',
            $this->apiUrl . $path,
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'X-Api-Key' => $this->apiKey,
                ],
                'timeout' => $quickFetch ? self::QUICK_FETCH_TIMEOUT : self::FETCH_TIMEOUT
            ]
        );
        return $response->toArray()['result'] ?? [];
    }

    private static function extractTags(array $result): array
    {
        return array_merge(
            array_map(fn($tag) => self::formatTag('token', $tag), $result['eth_details']['token_tags'] ?? []),
            array_map(fn($tag) => self::formatTag('nft', $tag), $result['eth_details']['nft_tags'] ?? [])
        );
    }

    private static function formatTag(string $prefix, string $tag): string
    {
        return 'cookie3-' . $prefix . '-' . strtolower(str_replace('_', '-', $tag));
    }
}
