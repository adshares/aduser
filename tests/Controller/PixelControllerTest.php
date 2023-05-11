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

namespace App\Tests\Controller;

use App\Service\DclHeadersVerifierInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// phpcs:ignoreFile PHPCompatibility.Miscellaneous.ValidIntegers.HexNumericStringFound
final class PixelControllerTest extends WebTestCase
{
    private const URI_REGISTER = '/register/adserver/tracking/nonce.html';

    public function testRegisterOptions(): void
    {
        $client = static::createClient(server: ['HTTP_ORIGIN' => 'en.example.com']);

        $client->request('OPTIONS', self::URI_REGISTER);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHasHeader('Access-Control-Max-Age');
        $this->assertResponseHasHeader('Access-Control-Allow-Origin');
        $this->assertResponseHeaderSame('Access-Control-Allow-Origin', 'en.example.com');
    }

    public function testRegister(): void
    {
        $client = static::createClient();

        $client->request('GET', self::URI_REGISTER);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHasCookie('__au');
        $connection = static::getContainer()->get(Connection::class);
        self::assertEquals(1, $connection->fetchOne('SELECT COUNT(*) FROM users'));
        self::assertEquals(1, $connection->fetchOne('SELECT COUNT(*) FROM adserver_register'));
    }

    public function testRegisterDclUser(): void
    {
        $client = static::createClient();
        $dclHeadersVerifier = self::createMock(DclHeadersVerifierInterface::class);
        $dclHeadersVerifier->expects(self::once())
            ->method('verify')
            ->willReturn(true);
        $dclHeadersVerifier->expects(self::once())
            ->method('getUserId')
            ->willReturn('0x05cf6d580d994d6eda7fd065b1cd239b08e2fd67');
        static::getContainer()->set('test.App\Service\DclHeadersVerifierInterface', $dclHeadersVerifier);

        $client->request('GET', self::URI_REGISTER);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHasCookie('__au');
        $connection = static::getContainer()->get(Connection::class);
        self::assertEquals(
            1,
            $connection->fetchOne(
                'SELECT COUNT(*) FROM users WHERE external_user_id = :external_user_id',
                [
                    'external_user_id' => '0x05cf6d580d994d6eda7fd065b1cd239b08e2fd67',
                ]
            )
        );
        self::assertEquals(1, $connection->fetchOne('SELECT COUNT(*) FROM adserver_register'));
    }
}
