<?php

declare(strict_types=1);

namespace App\Service;

use App\Utils\UrlNormalizer;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Utils\Cache\ApcuCache;

final class RequestInfo
{
    protected Browscap $browscap;

    protected ApcuCache $cache;

    protected LoggerInterface $logger;

    public function __construct(
        Browscap $browscap,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->browscap = $browscap;
        $this->cache = new ApcuCache();
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
            return $this->cache->getOrGenerate(
                $key,
                function () use ($userAgent) {
                    $this->logger->debug(sprintf('Info cache MISS for %s', $userAgent));
                    return $this->browscap->getInfo($userAgent);
                },
                300
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
