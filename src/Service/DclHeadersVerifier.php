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

namespace App\Service;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class DclHeadersVerifier implements DclHeadersVerifierInterface
{
    private const COORDINATES_MARGIN = 2;
    private const HEADER_IDENTIFICATION = 'x-identity-auth-chain-0';
    private const HEADER_DELEGATION = 'x-identity-auth-chain-1';
    private const HEADER_AUTHORIZATION = 'x-identity-auth-chain-2';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getUserId(HeaderBag $headers): ?string
    {
        if (null === ($identification = $headers->get(self::HEADER_IDENTIFICATION))) {
            return null;
        }

        $identificationData = json_decode($identification, true);
        if (
            !$this->isDecodedHeaderStructureValid($identificationData) ||
            'SIGNER' !== $identificationData['type'] ||
            '' !== $identificationData['signature'] ||
            1 !== preg_match('/^0x[a-z0-9]{40}$/i', $identificationData['payload'])
        ) {
            return null;
        }

        return strtolower($identificationData['payload']);
    }

    public function verify(HeaderBag $headers): bool
    {
        if (!$headers->has(self::HEADER_AUTHORIZATION)) {
            return false;
        }

        if (null === ($userAddress = $this->getUserId($headers))) {
            return false;
        }

        if (null === ($delegateAddress = $this->getDelegateId($headers, $userAddress))) {
            return false;
        }

        $authorizationData = json_decode($headers->get(self::HEADER_AUTHORIZATION), true);
        if (
            !$this->isDecodedHeaderStructureValid($authorizationData) ||
            'ECDSA_SIGNED_ENTITY' !== $authorizationData['type']
        ) {
            return false;
        }

        $payload = $authorizationData['payload'];
        if (!$this->isSignatureValid($payload, $authorizationData['signature'], $delegateAddress)) {
            return false;
        }

        $payloadParts = explode(':', $payload, 4);
        if (4 !== count($payloadParts)) {
            return false;
        }
        $metadata = json_decode($payloadParts[3], true);
        if (!is_array($metadata)) {
            return false;
        }
        $host = $metadata['realm']['hostname'] ?? null;
        $parcel = $metadata['parcel'] ?? null;
        if (
            !is_string($host) || !is_string($parcel) ||
            (!str_ends_with($host, '.decentraland.org') && !str_ends_with($host, '.decentral.io'))
        ) {
            return false;
        }
        $coordinates = explode(',', $parcel);
        if (2 !== count($coordinates)) {
            return false;
        }
        $coordinates = array_map(fn($coordinate) => (int)$coordinate, $coordinates);

        return $this->checkIfUserCoordinatesAreReal($host, $userAddress, $coordinates);
    }

    private function isDecodedHeaderStructureValid(mixed $header): bool
    {
        return is_array($header) &&
            isset($header['type']) &&
            isset($header['signature']) &&
            isset($header['payload']) &&
            is_string($header['type']) &&
            is_string($header['signature']) &&
            is_string($header['payload']);
    }

    private function isSignatureValid(string $message, string $signature, string $publicKey): bool
    {
        try {
            $recoveredPublicKey = personal_ecRecover($message, $signature);
        } catch (Exception $exception) {
            $this->logger->debug('DCL signature failed', ['exception' => $exception]);
            return false;
        }
        return $publicKey === $recoveredPublicKey;
    }

    private function getDelegateId(HeaderBag $headers, string $userAddress): ?string
    {
        if (null === ($delegation = $headers->get(self::HEADER_DELEGATION))) {
            return null;
        }

        $delegationData = json_decode($delegation, true);
        if (
            !$this->isDecodedHeaderStructureValid($delegationData) ||
            'ECDSA_EPHEMERAL' !== $delegationData['type']
        ) {
            return null;
        }

        $payload = $delegationData['payload'];
        if (!$this->isSignatureValid($payload, $delegationData['signature'], $userAddress)) {
            return null;
        }

        $payloadParts  = explode("\n", $payload);
        if (3 !== count($payloadParts) || 'Decentraland Login' !== $payloadParts[0]) {
            return null;
        }
        $expiration = strtotime(substr($payloadParts[2], 12));
        if (false === $expiration || time() > $expiration) {
            return null;
        }
        return strtolower(substr($payloadParts[1], 19));
    }

    private function checkIfUserCoordinatesAreReal(string $host, string $userAddress, array $coordinates): bool
    {
        $url = sprintf('https://%s/comms/peers/', $host);
        try {
            $response = $this->client->request('GET', $url)->toArray();
        } catch (ExceptionInterface $exception) {
            $this->logger->error('DCL user verification failed', ['exception' => $exception]);
            return false;
        }

        if (
            !isset($response['ok']) ||
            !isset($response['peers']) ||
            true !== $response['ok'] ||
            !is_array($response['peers'])
        ) {
            $this->logger->error('DCL user verification failed: Invalid response');
            return false;
        }

        foreach ($response['peers'] as $peer) {
            if (!is_array($peer) || !isset($peer['address']) || $userAddress !== $peer['address']) {
                continue;
            }
            if (!isset($peer['parcel']) || !is_array($peer['parcel']) || 2 !== count($peer['parcel'])) {
                $this->logger->error('DCL user verification failed: Invalid response parcel format');
                return false;
            }
            if (
                abs($coordinates[0] - $peer['parcel'][0]) <= self::COORDINATES_MARGIN &&
                abs($coordinates[1] - $peer['parcel'][1]) <= self::COORDINATES_MARGIN
            ) {
                return true;
            }
            return false;
        }

        return false;
    }
}
