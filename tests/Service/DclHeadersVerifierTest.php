<?php

/*
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

use App\Service\DclHeadersVerifier;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Contracts\HttpClient\HttpClientInterface;

// phpcs:ignoreFile Generic.Files.LineLength.TooLong
final class DclHeadersVerifierTest extends TestCase
{
    public function testGetUserId(): void
    {
        $headers = new HeaderBag();
        $headers->set(
            'x-identity-auth-chain-0',
            '{"type":"SIGNER","payload":"0x05cf6d580d994d6eda7fd065b2cd239b08e2fd68","signature":""}'
        );

        $result = (new DclHeadersVerifier($this->mockClient(), new NullLogger()))->getUserId($headers);

        // phpcs:disableNextLine PHPCompatibility.Miscellaneous.ValidIntegers.HexNumericStringFound
        self::assertEquals('0x05cf6d580d994d6eda7fd065b2cd239b08e2fd68', $result);
    }

    public function testGetUserIdFailWhileInvalidFormat(): void
    {
        $headers = new HeaderBag();
        $headers->set(
            'x-identity-auth-chain-0',
            '{"type":"SIGNER","payload":"05cf6d580d994d6eda7fd065b2cd239b08e2fd68","signature":""}'
        );

        $result = (new DclHeadersVerifier($this->mockClient(), new NullLogger()))->getUserId($headers);

        self::assertNull($result);
    }

    public function testGetUserIdFailWhileMissingHeader(): void
    {
        $headers = new HeaderBag();

        $result = (new DclHeadersVerifier($this->mockClient(), new NullLogger()))->getUserId($headers);

        self::assertNull($result);
    }

    public function testVerify(): void
    {
        $headers = new HeaderBag();
        $headers->set('referer', 'https://play.decentraland.org/');
        $headers->set(
            'x-identity-auth-chain-0',
            '{"type":"SIGNER","payload":"0x05cf6d580d994d6eda7fd065b2cd239b08e2fd68","signature":""}'
        );
        $headers->set(
            'x-identity-auth-chain-1',
            '{"type":"ECDSA_EPHEMERAL","payload":"Decentraland Login\nEphemeral address: 0x6B8f0CFB4F47A9b55f741439F8FFbB02f7241140\nExpiration: 2023-05-12T12:03:16.012Z","signature":"0x36ab3c95c0d75f77cde66255feb3947f499d38ab69a7aae319020da498d5c3150cbfee806df3186584c795e1faaab3885d0830a12ea227c1906c9383695297211c"}'
        );
        $headers->set(
            'x-identity-auth-chain-2',
            '{"type":"ECDSA_SIGNED_ENTITY","payload":"get:/supply/register:1683288224211:{\"origin\":\"https://play.decentraland.org\",\"sceneid\":\"bafkreicj6onw4r34ouwafqigovb27rowajhmsddrkcuaapypydldelsawu\",\"parcel\":\"-53,20\",\"tld\":\"org\",\"network\":\"mainnet\",\"isguest\":false,\"realm\":{\"hostname\":\"peer-eu1.decentraland.org\",\"protocol\":\"v3\",\"servername\":\"baldr\"},\"signer\":\"decentraland-kernel-scene\"}","signature":"0xf56a4fcce2f9f36a8d800379997537847cd54ac81213f0201106c8e06383713014791c0285ee44d8dae2002a788f0240a3be208db4225ed18f79a15a12d576ce1b"}'
        );
        $headers->set(
            'x-identity-metadata',
            '{"origin":"https://play.decentraland.org","sceneId":"bafkreicj6onw4r34ouwafqigovb27rowajhmsddrkcuaapypydldelsawu","parcel":"-53,20","tld":"org","network":"mainnet","isGuest":false,"realm":{"hostname":"peer-eu1.decentraland.org","protocol":"v3","serverName":"baldr"},"signer":"decentraland-kernel-scene"}'
        );
        $headers->set('x-identity-timestamp', '1683288224211');

        $result = (new DclHeadersVerifier($this->mockClient(), new NullLogger()))->verify($headers);

        self::assertTrue($result);
    }

    public function testVerifyNoHeaders(): void
    {
        $headers = new HeaderBag();

        $result = (new DclHeadersVerifier($this->mockClient(), new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    private function mockClient(): HttpClientInterface
    {
        $responses = [
            new MockResponse(
                <<<JSON
{
    "ok": true,
    "peers": [
        {
            "id": "0x05cf6d580d994d6eda7fd065b2cd239b08e2fd68",
            "address": "0x05cf6d580d994d6eda7fd065b2cd239b08e2fd68",
            "lastPing": 1683624184399,
            "parcel": [
                -53,
                21
            ],
            "position": [
                -854.8482511278,
                1.679999828338621,
				338.7134579940580
            ]
        }
    ]
}
JSON
            ),
        ];
        return new MockHttpClient($responses);
    }
}
