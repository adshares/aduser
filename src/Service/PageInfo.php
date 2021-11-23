<?php declare(strict_types = 1);

namespace App\Service;

use App\Utils\UrlNormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\FetchMode;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;

final class PageInfo
{
    const INFO_OK = 'ok';

    const INFO_UNKNOWN = 'unknown';

    const INFO_HIGH_IVR = 'high-ivr';

    const INFO_HIGH_CTR = 'high-ctr';

    const INFO_LOW_CTR = 'low-ctr';

    const INFO_POOR_TRAFFIC = 'poor-traffic';

    const INFO_POOR_CONTENT = 'poor-content';

    const INFO_SUSPICIOUS_DOMAIN = 'suspicious-domain';

    const STATUS_VERIFIED = 0;

    const STATUS_NEW = 1;

    const STATUS_SCANNED = 2;

    /** @var Connection */
    protected $connection;

    /** @var CacheItemPoolInterface */
    protected $cache;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        Connection $connection,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getPageRank(string $requestUrl): ?array
    {
        $url = UrlNormalizer::normalize($requestUrl);
        $ranks = $this->fetchPageRanks();

        foreach (UrlNormalizer::explodeUrl($url) as $part) {
            $key = ltrim($part, '//');
            if (array_key_exists($key, $ranks)) {
                return $ranks[$key];
            }
        }

        return null;
    }

    public function noteDomain(string $url): void
    {
        try {
            $item = $this->cache->getItem('page_info_domain_'.md5($url));
            if (!$item->isHit()) {
                $domain = UrlNormalizer::normalizeHost($url);
                if (empty($domain)) {
                    return;
                }
                try {
                    $this->connection->executeUpdate(
                        'INSERT INTO page_ranks(url, status) VALUES(?, ?)',
                        [$domain, self::STATUS_NEW]
                    );
                } catch (DBALException $exception) {
                    $this->logger->warning($exception->getMessage());
                }
                $this->cache->save($item->set(true));
            }
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    private function fetchPageRanks(): array
    {
        try {
            $item = $this->cache->getItem('page_info_page_ranks');
            if (!$item->isHit()) {
                $ranks = [];
                $st = $this->connection->executeQuery('SELECT url, rank, info FROM page_ranks WHERE rank IS NOT NULL');
                while ($row = $st->fetch(FetchMode::ASSOCIATIVE)) {
                    $ranks[$row['url']] = [max(0.0, min(1.0, (float)$row['rank'])), $row['info']];
                }
                $this->cache->save($item->set($ranks));
            }

            return $item->get();
        } catch (InvalidArgumentException|DBALException $exception) {
            $this->logger->error($exception->getMessage());

            return [];
        }
    }
}
