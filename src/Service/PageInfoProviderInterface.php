<?php

namespace App\Service;

interface PageInfoProviderInterface
{
    public function getTaxonomy(): array;

    public function getInfo(string $url): array;

    public function getBatchInfo(int $limit = 1000, int $offset = 0): array;

    public function reassessment(array $data): array;
}
