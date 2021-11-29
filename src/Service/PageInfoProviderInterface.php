<?php

namespace App\Service;

interface PageInfoProviderInterface
{
    public function getTaxonomy(): array;

    public function getInfo(string $url, array $categories = []): array;

    public function reassessment(array $data): array;
}
