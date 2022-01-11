<?php

declare(strict_types=1);

namespace App\Service;

use BrowscapPHP\Browscap as BrowscapPHP;
use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Exception as BrowscapException;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use stdClass;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class Browscap
{
    private string $iniFile;

    private CacheInterface $cache;

    private LoggerInterface $logger;

    public function __construct(string $iniFile, string $cacheDir, LoggerInterface $logger)
    {
        $this->iniFile = $iniFile;
        $this->cache = new Psr16Cache(new FilesystemAdapter('browscap', 0, $cacheDir));
        $this->logger = $logger;
    }

    public function update(): bool
    {
        $this->logger->info('Clearing cache');
        $this->logger->info(sprintf('Updating Browscap cache with remote file %s', $this->iniFile));
        $browscap = new BrowscapUpdater($this->cache, $this->logger);
        try {
            $browscap->update($this->iniFile);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        $this->logger->info('Updating Browscap cache finished');
        return true;
    }

    public function getInfo(string $userAgent): ?stdClass
    {
        $bc = new BrowscapPHP($this->cache, $this->logger);
        try {
            $info = $bc->getBrowser($userAgent);
        } catch (BrowscapException $e) {
            return null;
        }
        return $info;
    }
}
