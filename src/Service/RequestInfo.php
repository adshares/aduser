<?php declare(strict_types = 1);

namespace App\Service;

use App\Utils\UrlNormalizer;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

final class RequestInfo
{
    /** @var Browscap */
    protected $browscap;

    /** @var CacheItemPoolInterface */
    protected $cache;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        Browscap $browscap,
        CacheItemPoolInterface $cache,
        LoggerInterface $logger
    ) {
        $this->browscap = $browscap;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getDeviceKeywords(ParameterBag $params): array
    {
        $info = $this->getInfo($params);

        return $info === null
            ? []
            : [
                'type' => Taxonomy::mapDeviceType($info->device_type),
                'os' => Taxonomy::mapOperatingSystem($info->platform),
                'browser' => Taxonomy::mapBrowser($info->browser),
            ];
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

    public function isCrawler(ParameterBag $params): bool
    {
        $info = $this->getInfo($params);

        return (bool)($info->crawler ?? false);
    }

    private function getInfo(ParameterBag $params): ?\stdClass
    {
        $userAgent = self::getHeader('User-Agent', $params);

        if ($userAgent === null) {
            $this->logger->debug('Cannot find User-Agent', $params->all());

            return null;
        }
        try {
            $item = $this->cache->getItem('browscap_info_'.sha1($userAgent));
            if (!$item->isHit()) {
                $info = $this->browscap->getInfo($userAgent);
                $this->cache->save($item->set($info));
                $this->logger->debug(sprintf('Info cache MISS for %s', $userAgent));
            }

            return $item->get();
        } catch (InvalidArgumentException $exception) {
            $this->logger->error($exception->getMessage());

            return null;
        }
    }

    private static function getHeader($name, ParameterBag $params): ?string
    {
        $value = null;
        if (null !== $headers = $params->get('headers')) {
            $value = $headers[$name] ?? $headers[strtolower($name)] ?? null;
        }

        return $value;
    }
}
