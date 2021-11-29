<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeInterface;

interface PageInfoProviderInterface
{
    public function getTaxonomy(): array;

    public function getInfo(string $url, array $categories = []): array;

    public function getBatchInfo(int $limit = 1000, int $offset = 0, DateTimeInterface $changedAfter = null): array;

    public function reassessment(array $data): array;
}
