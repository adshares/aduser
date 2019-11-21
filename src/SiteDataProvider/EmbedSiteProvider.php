<?php

declare(strict_types=1);


namespace Adshares\Aduser\SiteDataProvider;

use Embed\Embed;
use Embed\Http\CurlDispatcher;

class EmbedSiteProvider implements SiteProviderInterface
{
    public function fetchSite(string $url): Site
    {
        $dispatcher = new CurlDispatcher([
            CURLOPT_MAXREDIRS => 20,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 1,
            CURLOPT_ENCODING => '',
            CURLOPT_AUTOREFERER => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:47.0) Gecko/20100101 Firefox/47.0',
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ]);

        $info = Embed::create($url, null, $dispatcher);

        return new Site($info->url, $info->title, $info->description, $info->tags);
    }
}
