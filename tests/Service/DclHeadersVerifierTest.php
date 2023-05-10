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

require_once dirname(__DIR__) . '/../vendor/adshares/php-ecrecover/CryptoCurrencyPHP/AddressCodec.class.php';
require_once dirname(__DIR__) . '/../vendor/adshares/php-ecrecover/CryptoCurrencyPHP/PrivateKey.class.php';

use AddressCodec;
use App\Service\DclHeadersVerifier;
use DateTimeImmutable;
use kornrunner\Keccak;
use PHPUnit\Framework\TestCase;
use PrivateKey;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Signature;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

// phpcs:ignoreFile PHPCompatibility.Miscellaneous.ValidIntegers.HexNumericStringFound
final class DclHeadersVerifierTest extends TestCase
{
    public function testGetUserId(): void
    {
        $headers = new HeaderBag();
        $headers->set(
            'x-identity-auth-chain-0',
            '{"type":"SIGNER","payload":"0x05cf6d580d994d6eda7fd065b1cd239b08e2fd67","signature":""}'
        );
        $dclHeadersVerifier = new DclHeadersVerifier(
            $this->mockClient('0x05cf6d580d994d6eda7fd065b1cd239b08e2fd67'),
            new NullLogger()
        );

        $result = $dclHeadersVerifier->getUserId($headers);

        self::assertEquals('0x05cf6d580d994d6eda7fd065b1cd239b08e2fd67', $result);
    }

    public function testGetUserIdFailWhileInvalidFormat(): void
    {
        $headers = new HeaderBag();
        $headers->set(
            'x-identity-auth-chain-0',
            '{"type":"SIGNER","payload":"05cf6d580d994d6eda7fd065b1cd239b08e2fd67","signature":""}'
        );

        $result = (new DclHeadersVerifier(new MockHttpClient(), new NullLogger()))->getUserId($headers);

        self::assertNull($result);
    }

    public function testGetUserIdFailWhileMissingHeader(): void
    {
        $headers = new HeaderBag();

        $result = (new DclHeadersVerifier(new MockHttpClient(), new NullLogger()))->getUserId($headers);

        self::assertNull($result);
    }

    public function testVerify(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();

        $result = (new DclHeadersVerifier($this->mockClient($userPublicKey), new NullLogger()))->verify($headers);

        self::assertTrue($result);
    }

    public function testVerifyFailWhileIdentificationHeaderInvalid(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $headers->set(
            'x-identity-auth-chain-0',
            str_replace('SIGNER', 'SINGER', $headers->get('x-identity-auth-chain-0'))
        );

        $result = (new DclHeadersVerifier($this->mockClient($userPublicKey), new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileDelegationHeaderInvalid(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $headers->set(
            'x-identity-auth-chain-1',
            str_replace('"payload"', '"data"', $headers->get('x-identity-auth-chain-1'))
        );

        $result = (new DclHeadersVerifier($this->mockClient($userPublicKey), new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileDelegationHeaderMissing(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $headers->set('x-identity-auth-chain-1', null);

        $result = (new DclHeadersVerifier($this->mockClient($userPublicKey), new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileDelegationHeaderSignatureInvalid(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $header = json_decode($headers->get('x-identity-auth-chain-1'), true);
        $header['signature'] = '0x' . str_repeat('0', 130);
        $headers->set('x-identity-auth-chain-1', json_encode($header));

        $result = (new DclHeadersVerifier($this->mockClient($userPublicKey), new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    /**
     * @dataProvider invalidDataProvider
     */
    public function testVerifyFailWhileInvalidData(array $data): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders($data);

        $result = (new DclHeadersVerifier($this->mockClient($userPublicKey), new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    public function invalidDataProvider(): array
    {
        return [
            'Delegation header purpose invalid' => [['delegationPurpose' => 'Login']],
            'Delegation header expired' => [['delegationExpiration' => '-1 second']],
            'Parcel invalid' => [['parcel' => '0']],
            'Realm host invalid' => [['realmHost' => 'example.com']],
            'Authorization header payload malformed' => [['authorizationHeaderMalformed' => true]],
            'Authorization header payload json malformed' => [['signedFetchMethod' => 'get:example.com']],
        ];
    }

    public function testVerifyFailWhileAuthorizationHeaderInvalid(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $headers->set(
            'x-identity-auth-chain-2',
            str_replace('"signature"', '"sign"', $headers->get('x-identity-auth-chain-2'))
        );

        $result = (new DclHeadersVerifier($this->mockClient($userPublicKey), new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileAuthorizationHeaderSignatureInvalid(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $header = json_decode($headers->get('x-identity-auth-chain-2'), true);
        $header['signature'] = '0x' . str_repeat('0', 130);
        $headers->set('x-identity-auth-chain-2', json_encode($header));

        $result = (new DclHeadersVerifier($this->mockClient($userPublicKey), new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileNoHeaders(): void
    {
        $headers = new HeaderBag();

        $result = (new DclHeadersVerifier(new MockHttpClient(), new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileRemoteError(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $client = new MockHttpClient(
            new MockResponse('Service unavailable', ['http_code' => Response::HTTP_SERVICE_UNAVAILABLE])
        );
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('DCL user verification failed');

        $result = (new DclHeadersVerifier($client, $logger))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileRemoteResponseInvalid(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $client = new MockHttpClient(new MockResponse('{"ok": false, "peers": []}'));
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('DCL user verification failed: Invalid response');

        $result = (new DclHeadersVerifier($client, $logger))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileRemoteResponseForAnotherUser(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $client = $this->mockClient('0x05cf6d580d994d6eda7fd065b1cd239b08e2fd67');

        $result = (new DclHeadersVerifier($client, new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileUserOutOfAllowedMargin(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $responses = [
            new MockResponse(
                str_replace(
                    '%ADDRESS%',
                    $userPublicKey,
                    <<<TEMPLATE
{
    "ok": true,
    "peers": [
        {
            "id": "%ADDRESS%",
            "address": "%ADDRESS%",
            "lastPing": 1683624184399,
            "parcel": [
                -50,
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
TEMPLATE,
                )
            ),
        ];
        $client = new MockHttpClient($responses);

        $result = (new DclHeadersVerifier($client, new NullLogger()))->verify($headers);

        self::assertFalse($result);
    }

    public function testVerifyFailWhileInvalidParcel(): void
    {
        [$headers, $userPublicKey] = $this->setupHeaders();
        $responses = [
            new MockResponse(
                str_replace(
                    '%ADDRESS%',
                    $userPublicKey,
                    <<<TEMPLATE
{
    "ok": true,
    "peers": [
        {
            "id": "%ADDRESS%",
            "address": "%ADDRESS%",
            "lastPing": 1683624184399,
            "parcel": [
                0
            ],
            "position": [
                -854.8482511278,
                1.679999828338621,
				338.7134579940580
            ]
        }
    ]
}
TEMPLATE,
                )
            ),
        ];
        $client = new MockHttpClient($responses);
        $logger = self::createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('DCL user verification failed: Invalid response parcel format');

        $result = (new DclHeadersVerifier($client, $logger))->verify($headers);

        self::assertFalse($result);
    }

    private function mockClient(string $address): HttpClientInterface
    {
        $responses = [
            new MockResponse(
                str_replace(
                    '%ADDRESS%',
                    $address,
                    <<<TEMPLATE
{
    "ok": true,
    "peers": [
        {
            "id": "%ADDRESS%",
            "address": "%ADDRESS%",
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
TEMPLATE,
                )
            ),
        ];
        return new MockHttpClient($responses);
    }

    private function encodeMetadata(string $metadata): string
    {
        return str_replace(
            ['"', '\/'],
            ['\\"', '/'],
            json_encode(
                $this->lowerJsonKeys(
                    json_decode($metadata, true)
                )
            )
        );
    }

    private function lowerJsonKeys(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            $result[strtolower($key)] = is_array($value) ? $this->lowerJsonKeys($value) : $value;
        }
        return $result;
    }

    private function getSignatureV(array $signature, string $hash, string $publicKey): string
    {
        $encodedPublicKey = Signature::getPubKeyWithRS(27, $signature['R'], $signature['S'], $hash);
        if (
            $publicKey === $this->getPublicKey($encodedPublicKey)
        ) {
            return '1b';
        }
        return '1c';
    }

    private function getPublicKey(string $encodedPublicKey): string
    {
        return '0x' . substr(Keccak::hash(hex2bin(substr($encodedPublicKey, 2)), 256), -40);
    }

    private function sign(string $payload, string $privateKey, string $publicKey): string
    {
        $tmpPayload = str_replace(['\n', '\"'], ["\n", '"'], $payload);
        $hash = Keccak::hash("\x19Ethereum Signed Message:\n" . strlen($tmpPayload) . $tmpPayload, 256);
        $signature = Signature::getSignatureHashPoints($hash, $privateKey);
        return '0x' . $signature['R'] . $signature['S'] . $this->getSignatureV(
                $signature,
                $hash,
                $publicKey,
            );
    }

    private function setupHeaders(array $merge = []): array
    {
        $config = array_merge(
            [
                'authorizationHeaderMalformed' => false,
                'delegationPurpose' => 'Decentraland Login',
                'delegationExpiration' => '+2 days',
                'parcel' => '-53,20',
                'realmHost' => 'peer-eu1.decentraland.org',
                'signedFetchMethod' => 'get',
            ],
            $merge,
        );
        
        $userKeyStore = new PrivateKey();
        $userPrivateKey = $userKeyStore->getPrivateKey();
        $userPublicKey = $this->getPublicKey(AddressCodec::Hex($userKeyStore->getPubKeyPoints()));

        $delegateKeyStore = new PrivateKey();
        $delegatePrivateKey = $delegateKeyStore->getPrivateKey();
        $delegatePublicKey = $this->getPublicKey(AddressCodec::Hex($delegateKeyStore->getPubKeyPoints()));

        $timestamp = (string)time();
        $delegationPayload = sprintf(
            '%s\nEphemeral address: %s\nExpiration: %s',
            $config['delegationPurpose'],
            $delegatePublicKey,
            (new DateTimeImmutable($config['delegationExpiration']))->format('Y-m-d\TH:i:s.000\Z'),
        );
        $delegationSignature = $this->sign($delegationPayload, $userPrivateKey, $userPublicKey);

        $metadata = '{' .
            '"origin":"https://play.decentraland.org",' .
            '"sceneId":"bafkreicj6onw4r34ouwafqigovb27rowajhmsddrkcuaapypydldelsawu",' .
            '"parcel":"' . $config['parcel'] . '",' .
            '"tld":"org",' .
            '"network":"mainnet",' .
            '"isGuest":false,' .
            '"realm":{"hostname":"' . $config['realmHost'] . '","protocol":"v3","serverName":"baldr"},' .
            '"signer":"decentraland-kernel-scene"' .
            '}';
        $authorizationPayload = $config['authorizationHeaderMalformed'] ? '' : sprintf(
            '%s:/supply/register:%s:%s',
            $config['signedFetchMethod'],
            $timestamp,
            $this->encodeMetadata($metadata)
        );
        $authorizationSignature = $this->sign($authorizationPayload, $delegatePrivateKey, $delegatePublicKey);

        $headers = new HeaderBag();
        $headers->set('referer', 'https://play.decentraland.org/');
        $headers->set(
            'x-identity-auth-chain-0',
            sprintf('{"type":"SIGNER","payload":"%s","signature":""}', $userPublicKey),
        );
        $headers->set(
            'x-identity-auth-chain-1',
            sprintf(
                '{"type":"ECDSA_EPHEMERAL","payload":"%s","signature":"%s"}',
                $delegationPayload,
                $delegationSignature,
            ),
        );
        $headers->set(
            'x-identity-auth-chain-2',
            sprintf(
                '{"type":"ECDSA_SIGNED_ENTITY","payload":"%s","signature":"%s"}',
                $authorizationPayload,
                $authorizationSignature,
            ),
        );
        $headers->set('x-identity-metadata', $metadata);
        $headers->set('x-identity-timestamp', $timestamp);
        return [$headers, $userPublicKey];
    }
}
