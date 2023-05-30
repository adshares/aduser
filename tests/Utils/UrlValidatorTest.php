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

namespace App\Tests\Utils;

use App\Service\Taxonomy;
use App\Utils\UrlValidator;
use PHPUnit\Framework\TestCase;

final class UrlValidatorTest extends TestCase
{
    /**
     * @dataProvider isValidProvider
     */
    public function testIsValid($url): void
    {
        self::assertTrue(UrlValidator::isValid($url));
    }

    public function isValidProvider(): array
    {
        return [
            ['https://example.com'],
            ['https://example.com:80'],
            ['https://example.com/'],
            ['https://user@example.com'],
            ['https://user:pass@example.com'],
            ['https://example.com/api'],
            ['https://example.com?v=1'],
            ['https://example.com#v2'],
            ['https://user:pass@example.com:8080/api/test?v=1&a[]=tmp#head'],
            ['https://example.com?redirect=https%3A%2f%2Fadshares.net'],
        ];
    }
    /**
     * @dataProvider isValidFailProvider
     */
    public function testIsValidFail($url): void
    {
        self::assertFalse(UrlValidator::isValid($url));
    }

    public function isValidFailProvider(): array
    {
        return [
            'null' => [null],
            'empty' => [''],
            'ftp protocol' => ['ftp://127.0.0.1'],
        ];
    }
}
