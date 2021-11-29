<?php

declare(strict_types=1);

namespace App\Service;

use App\Utils\UrlNormalizer;
use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class PageInfo
{
    public const INFO_UNKNOWN = 'unknown';

    private PageInfoProviderInterface $pageInfoProvider;

    protected Connection $connection;

    protected CacheInterface $cache;

    protected LoggerInterface $logger;

    public function __construct(
        PageInfoProviderInterface $pageInfoProvider,
        Connection $connection,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->pageInfoProvider = $pageInfoProvider;
        $this->connection = $connection;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getTaxonomy(): array
    {
        return $this->pageInfoProvider->getTaxonomy();
    }


    public function reassessment(array $data): array
    {
        return $this->pageInfoProvider->reassessment($data);
    }

    public function getPageRank(string $url, array $categories): array
    {
        $pageRank = $this->fetchPageRank($url, true);
        if (null === $pageRank) {
            $key = 'page_info_domain_' . md5($url);
            $pageRank = $this->cache->get($key, function (ItemInterface $item) use ($url, $categories) {
                $item->expiresAfter(300);
                $info = $this->pageInfoProvider->getInfo($url, $categories);
                $this->savePageRank($url, $info);
                return $info;
            });
        }
        return $pageRank;
    }

    public function fetchPageRank(string $requestUrl, bool $hostExactMatch = false): ?array
    {
        $url = UrlNormalizer::normalize($requestUrl);
        $ranks = $this->fetchPageRanks();

        if ($hostExactMatch) {
            $host = UrlNormalizer::normalizeHost($url);
            if (array_key_exists($host, $ranks)) {
                return $ranks[$host];
            }

            return null;
        }

        foreach (UrlNormalizer::explodeUrl($url) as $part) {
            $key = ltrim($part, '//');
            if (array_key_exists($key, $ranks)) {
                return $ranks[$key];
            }
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
                'INSERT INTO page_ranks(url, rank, info, categories, quality) VALUES(?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE rank = ?, info = ?, categories = ?, quality = ?',
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
            $this->cache->delete('page_info_page_ranks');
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function fetchPageRanks(): array
    {
        try {
            return $this->cache->get('page_info_page_ranks', function (ItemInterface $item) {
                $item->expiresAfter(300);
                $ranks = [];
                $query = '
                    SELECT url, rank, info, categories, quality
                    FROM page_ranks
                    WHERE rank IS NOT NULL
                    ORDER BY updated_at DESC
                ';
                foreach ($this->connection->fetchAllAssociative($query) as $row) {
                    $ranks[$row['url']] = [
                        'url' => $row['url'],
                        'rank' => max(-1.0, min(1.0, (float)$row['rank'])),
                        'info' => $row['info'],
                        'categories' => json_decode($row['categories'] ?? '[]'),
                        'quality' => $row['quality']
                    ];
                }
                return $ranks;
            });
        } catch (InvalidArgumentException | DBALException $exception) {
            $this->logger->error($exception->getMessage());
            return [];
        }
    }
}
