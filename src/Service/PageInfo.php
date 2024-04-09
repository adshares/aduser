<?php

/**
 * Copyright (c) 2018-2024 Adshares sp. z o.o.
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
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Throwable;

final class PageInfo
{
    public const INFO_UNKNOWN = 'unknown';
    private const JSON_PATH_MATCH_REGEXP = '(\[\?\(@\.[^=]+=[^]]+\)]|\.[^.\[]+|\[]$)';

    private PageInfoProviderInterface $pageInfoProvider;
    private string $taxonomyChangesFile;
    protected Connection $connection;
    protected CacheInterface $cache;
    protected LoggerInterface $logger;
    private int $apiVersion = 1;

    public function __construct(
        PageInfoProviderInterface $pageInfoProvider,
        string $taxonomyChangesFile,
        Connection $connection,
        CacheInterface $cache,
        LoggerInterface $logger,
    ) {
        $this->pageInfoProvider = $pageInfoProvider;
        $this->taxonomyChangesFile = $taxonomyChangesFile;
        $this->connection = $connection;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function version(int $apiVersion): PageInfo
    {
        $this->apiVersion = $apiVersion;
        $this->pageInfoProvider->version($this->apiVersion);
        return $this;
    }

    public function getTaxonomy(): array
    {
        return $this->taxonomyChanges($this->pageInfoProvider->getTaxonomy());
    }

    public function reassessment(array $data): array
    {
        return $this->pageInfoProvider->reassessment($data);
    }

    public function getPageRank(string $url, array $categories): array
    {
        $host = UrlNormalizer::normalizeHost(UrlNormalizer::normalize($url));
        $key = 'page_info_domain_' . md5($host);
        try {
            return $this->cache->get($key, function (ItemInterface $item) use ($url, $host, $categories) {
                $item->expiresAfter(300);
                if (null !== ($pageRank = $this->fetchPageRank($host))) {
                    return $pageRank;
                }
                $info = $this->pageInfoProvider->getInfo($url, $categories);
                $this->savePageRank($url, $info);
                return $info;
            });
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
            throw $exception;
        }
    }

    private function fetchPageRank(string $url): ?array
    {
        $query = '
            SELECT `rank`, info, categories, quality, updated_at
            FROM page_ranks
            WHERE url = :url
        ';
        try {
            $result = $this->connection->fetchAssociative($query, ['url' => $url]);
            if (false !== $result) {
                return [
                    'rank' => max(-1.0, min(1.0, (float)$result['rank'])),
                    'info' => (string)$result['info'],
                    'categories' => json_decode($result['categories'] ?? '[]'),
                    'quality' => (string)$result['quality'],
                    'updated_at' => (string)$result['updated_at']
                ];
            }
        } catch (DBALException $exception) {
            $this->logger->error($exception->getMessage());
        }
        return null;
    }

    public function update(DateTimeInterface $changedAfter = null): bool
    {
        $this->logger->info('Updating pages info from the provider');
        $limit = 1000;
        $offset = 0;

        do {
            $list = $this->pageInfoProvider->getBatchInfo($limit, $offset, $changedAfter)['page_ranks'];
            foreach ($list as $info) {
                $this->savePageRank($info['url'], $info);
            }
            $offset += $limit;
        } while (count($list) === $limit);

        $this->logger->info('Updating pages info finished');
        return true;
    }

    private function savePageRank(string $url, array $info): void
    {
        try {
            $domain = UrlNormalizer::normalizeHost($url);
            if (empty($domain)) {
                return;
            }
            if (!array_key_exists('rank', $info) || !array_key_exists('info', $info)) {
                return;
            }
            if (0 === $info['rank'] && self::INFO_UNKNOWN === $info['info']) {
                return;
            }
            $categories = json_encode($info['categories'] ?? []);
            $this->connection->executeStatement(
                'INSERT INTO page_ranks(url, `rank`, info, categories, quality) VALUES(?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE `rank` = ?, info = ?, categories = ?, quality = ?',
                [
                    $domain,
                    $info['rank'],
                    $info['info'],
                    $categories,
                    $info['quality'] ?? self::INFO_UNKNOWN,
                    $info['rank'],
                    $info['info'],
                    $categories,
                    $info['quality'] ?? self::INFO_UNKNOWN,
                ]
            );
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function taxonomyChanges(array $taxonomy): array
    {
        if (empty($this->taxonomyChangesFile) || 1 === $this->apiVersion) {
            return $taxonomy;
        }

        if (false === $content = @file_get_contents($this->taxonomyChangesFile)) {
            throw new RuntimeException(sprintf('Cannot read file `%s`.', $this->taxonomyChangesFile));
        }
        if (null === $changes = @json_decode($content, true)) {
            throw new RuntimeException(sprintf('Cannot parse file `%s`.', $this->taxonomyChangesFile));
        }

        foreach ($changes as $change) {
            $taxonomy = self::applyJsonChange(
                $taxonomy,
                self::parseJsonPath($change['path']),
                $change['value'],
            );
        }

        return $taxonomy;
    }

    private static function parseJsonPath(string $path): array
    {
        $matches = [];
        $result = preg_match_all('/' . self::JSON_PATH_MATCH_REGEXP . '/', $path, $matches);
        if (false === $result || $result < 1) {
            throw new \InvalidArgumentException(sprintf('Path `%s` is invalid.', $path));
        }
        $addValue = false;
        $pathFragments = $matches[1];
        if ($pathFragments[count($pathFragments) - 1] === '[]') {
            $addValue = true;
            array_pop($pathFragments);
        }
        $pathFragments = array_map(
            fn($fragment) => $fragment[0] === '.' ? substr($fragment, 1) : $fragment,
            $pathFragments
        );

        return [
            'add_value' => $addValue,
            'path_fragments' => $pathFragments,
        ];
    }

    private static function applyJsonChange(
        array $baseData,
        array $pathParsingResult,
        ?array $value
    ): array {
        $temp = &$baseData;
        $parent = null;
        $parentKey = null;
        foreach ($pathParsingResult['path_fragments'] as $pathFragment) {
            if (str_starts_with($pathFragment, '[?(@.') && str_ends_with($pathFragment, ')]')) {
                [$k, $v] = explode('=', substr($pathFragment, strlen('[?(@.'), -strlen(')]')), 2);
                for ($i = 0; $i < count($temp); $i++) {
                    if (($temp[$i][$k] ?? '') === $v) {
                        $pathFragment = $i;
                        break;
                    }
                }
            }
            if (!array_key_exists($pathFragment, $temp)) {
                throw new \InvalidArgumentException(sprintf('Path fragment `%s` is invalid.', $pathFragment));
            }
            $parentKey = $pathFragment;
            $parent = &$temp;
            $temp = &$temp[$pathFragment];
        }
        if ($pathParsingResult['add_value']) {
            $temp[] = $value;
        } elseif (null === $value && null !== $parent) {
            unset($parent[$parentKey]);
            if (is_int($parentKey)) {
                $parent = array_values($parent);
            }
        } else {
            $temp = $value;
        }
        unset($temp);

        return $baseData;
    }
}
