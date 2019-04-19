<?php

declare(strict_types=1);


namespace Adshares\Aduser\SiteDataProvider;

use Adshares\Aduser\Utils\UrlNormalizer;
use function implode;
use function mb_strtolower;

class Site
{
    /** @var string */
    private $url;
    /** @var string */
    private $title;
    /** @var string */
    private $description;
    /** @var array */
    private $keywords;

    public function __construct(string $url, ?string $title, ?string $description, array $keywords)
    {
        $this->url = UrlNormalizer::normalize($url);
        $this->title = $title;
        $this->description = $description;
        $this->keywords = mb_strtolower(implode(',', $keywords));
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getKeywords(): ?string
    {
        if (!$this->keywords) {
            return null;
        }

        return $this->keywords;
    }
}
