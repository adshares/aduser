<?php

/**
 * Copyright (c) 2018-2023 Adshares sp. z o.o.
 *
 * This file is part of AdUser
 *
 * AdUser is free software: you can redistribute and/or modify it
 * under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AdUser is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AdServer. If not, see <https://www.gnu.org/licenses/>
 */

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Taxonomy;
use PHPUnit\Framework\TestCase;

final class TaxonomyTest extends TestCase
{
    public function testGetCountries(): void
    {
        $countries = Taxonomy::getCountries();

        self::assertArrayHasKey('gs', $countries);
        self::assertEquals('South Georgia and the South Sandwich Islands', $countries['gs']);
    }

    public function testGetLanguages(): void
    {
        $languages = Taxonomy::getLanguages();

        self::assertArrayHasKey('sv', $languages);
        self::assertEquals('Swedish', $languages['sv']);
    }

    public function testGetDeviceTypes(): void
    {
        $deviceTypes = Taxonomy::getDeviceTypes();

        self::assertArrayHasKey('mobile', $deviceTypes);
        self::assertEquals('Mobile', $deviceTypes['mobile']);
    }

    public function testMapDeviceType(): void
    {
        $deviceType = Taxonomy::mapDeviceType('Mobile Device');

        self::assertEquals('mobile', $deviceType);
    }

    public function testGetOperatingSystems(): void
    {
        $operatingSystems = Taxonomy::getOperatingSystems();

        self::assertArrayHasKey('apple-os', $operatingSystems);
        self::assertEquals('Apple OS', $operatingSystems['apple-os']);
    }

    public function testMapOperatingSystem(): void
    {
        $operatingSystem = Taxonomy::mapOperatingSystem('Android for GoogleTV');

        self::assertEquals('android', $operatingSystem);
    }

    public function testGetBrowsers(): void
    {
        $operatingSystems = Taxonomy::getBrowsers();

        self::assertArrayHasKey('opera', $operatingSystems);
        self::assertEquals('Opera', $operatingSystems['opera']);
    }

    public function testMapBrowser(): void
    {
        $browser = Taxonomy::mapBrowser('Safari');

        self::assertEquals('safari', $browser);
    }

    public function testGetExtensions(): void
    {
        $extensions = Taxonomy::getExtensions();

        self::assertArrayHasKey('metamask', $extensions);
        self::assertEquals('MetaMask', $extensions['metamask']);
    }

    /**
     * @dataProvider mapExtensionsProvider
     */
    public function testMapExtensions(array $expectedExtensions, array $data): void
    {
        $extensions = Taxonomy::mapExtensions($data);

        self::assertEquals($expectedExtensions, $extensions);
    }

    public function mapExtensionsProvider(): array
    {
        return [
            'empty' => [
                [],
                [],
            ],
            'unknown extension' => [
                [],
                ['invalid' => 1],
            ],
            'metamask enabled with int' => [
                ['metamask'],
                ['metamask' => 1],
            ],
            'metamask enabled with string' => [
                ['metamask'],
                ['metamask' => 'true'],
            ],
            'metamask disabled' => [
                [],
                ['metamask' => 0],
            ],
        ];
    }
}
