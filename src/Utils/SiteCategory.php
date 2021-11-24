<?php

declare(strict_types=1);

namespace App\Utils;

use App\Service\Taxonomy;

final class SiteCategory
{
    public static function getTaxonomySiteCategories(): array
    {
        return self::getTaxonomySite('category');
    }

    public static function getTaxonomySiteQualityLevels(): array
    {
        return self::getTaxonomySite('quality');
    }

    protected static function getTaxonomySite(string $key): array
    {
        $categories = [];

        foreach (Taxonomy::getTaxonomy()['site'] as $item) {
            if ($key === $item['key']) {
                $categories = $item['data'];
            }
        }

        return $categories;
    }

    public static function getCategoryValueToLabelMap(array $categoriesTaxonomy): array
    {
        $map = [];

        foreach ($categoriesTaxonomy as $category) {
            $map[$category['value']] = $category['label'];
            if (isset($category['values'])) {
                $map = array_merge($map, self::getCategoryValueToLabelMap($category['values']));
            }
        }

        return $map;
    }

    public static function getCategoryValueToIncludedCategoriesValuesMap(array $categoriesTaxonomy): array
    {
        $map = [];

        foreach ($categoriesTaxonomy as $category) {
            $includedCategoryValues = [$category['value']];
            if (isset($category['values'])) {
                $localMap = self::getCategoryValueToIncludedCategoriesValuesMap($category['values']);
                $map = array_merge($map, $localMap);

                foreach ($localMap as $categoryValues) {
                    array_push($includedCategoryValues, ...$categoryValues);
                }
            }
            $map[$category['value']] = $includedCategoryValues;
        }

        return $map;
    }

    public static function getCategoriesChains(array $categoriesTaxonomy): array
    {
        $chains = [];

        foreach ($categoriesTaxonomy as $category) {
            $value = $category['value'];
            if (isset($category['values'])) {
                $subChains = self::getCategoriesChains($category['values']);
                foreach ($subChains as $key => $subChain) {
                    $subChain[] = $value;
                    $subChains[$key] = $subChain;
                }
                $chains = array_merge($chains, $subChains);
            }
            $chains[$value] = [];
        }

        return $chains;
    }
}
