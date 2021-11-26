<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Gitoku implements PageInfoProviderInterface
{
    public const GITOKU_URL = 'https://gitoku.com';

    private HttpClientInterface $client;
    protected CacheInterface $cache;

    public function __construct(
        HttpClientInterface $client,
        CacheInterface $cache
    ) {
        $this->client = $client;
        $this->cache = $cache;
    }

    public function getTaxonomy(): array
    {
        return $this->cache->get('', function (ItemInterface $item) {
            $item->expiresAfter(60);
            return $this->request('/taxonomy');
        });
    }

    public function getInfo(string $url): array
    {
        return $this->request('/page-rank/' . urlencode($url));
    }

    public function getBatchInfo(int $limit = 1000, int $offset = 0): array
    {
        return $this->request('/page-ranks/?' . http_build_query(['limit' => $limit, 'offset' => $offset]));
    }

    public function reassessment(array $data): array
    {
        return $this->request('/reassessment', 'POST', $data);
    }

    private function request(string $path, string $method = 'GET', ?array $data = null): array
    {
        $response = $this->client->request(
            $method,
            self::GITOKU_URL . '/api/v1' . $path,
            null !== $data ? ['json' => $data] : []
        );
        return $response->toArray();
    }
}
