<?php declare(strict_types = 1);

namespace Adshares\Aduser\External;

use BrowscapPHP\Browscap as BrowscapPHP;
use BrowscapPHP\BrowscapUpdater;
use BrowscapPHP\Exception;
use BrowscapPHP\Exception\ErrorCachedVersionException;
use BrowscapPHP\Exception\FetcherException;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class Browscap
{
    /** @var string */
    private $iniFile;

    /** @var CacheInterface */
    private $cache;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(string $iniFile, $cacheDir, LoggerInterface $logger)
    {
        $this->iniFile = $iniFile;
        $this->cache = new Psr16Cache(new FilesystemAdapter('browscap', 0, $cacheDir));
        $this->logger = $logger;
    }

    public function update(): bool
    {
        $this->logger->info('Clearing cache');
        $this->cache->clear();
        $this->logger->info(sprintf('Updating Browscap cache with remote file %s', $this->iniFile));
        $browscap = new BrowscapUpdater($this->cache, $this->logger);

        try {
            $browscap->update($this->iniFile);
        } catch (ErrorCachedVersionException $e) {
            $this->logger->error($e->getMessage());

            return false;
        } catch (FetcherException $e) {
            $this->logger->error($e->getMessage());

            return false;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return false;
        }

        $this->logger->info('Updating Browscap cache finished');

        return true;
    }

    public function getInfo(string $userAgent): ?\stdClass
    {
        $bc = new BrowscapPHP($this->cache, $this->logger);

        try {
            $info = $bc->getBrowser($userAgent);
        } catch (Exception $e) {
            return null;
        }

        return $info;
    }
}
