<?php

declare(strict_types=1);


namespace Adshares\Aduser\SiteDataProvider;

interface SiteProviderInterface
{
    public function fetchSite(string $url): Site;
}
