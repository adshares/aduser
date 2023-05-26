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

use App\Service\Gitoku;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;

final class GitokuTest extends TestCase
{
    public function testGetInfoWhileError422(): void
    {
        $gitoku = new Gitoku(
            new MockHttpClient([
                new MockResponse('', ['http_code' => Response::HTTP_UNPROCESSABLE_ENTITY]),
            ]),
            self::createMock(CacheInterface::class),
            new NullLogger(),
        );

        $info = $gitoku->getInfo('https://example.com');

        self::assertEquals([], $info);
    }
}
