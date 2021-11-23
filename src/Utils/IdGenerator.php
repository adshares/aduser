<?php

declare(strict_types=1);

namespace App\Utils;

use Exception;
use Symfony\Component\HttpFoundation\Request;

final class IdGenerator
{
    private string $trackingSecret;

    public function __construct(string $trackingSecret)
    {
        $this->trackingSecret = $trackingSecret;
    }

    public static function generateNonce(int $length = 8): string
    {
        try {
            return substr(sha1(random_bytes(256)), 0, $length);
        } catch (Exception $e) {
            return '';
        }
    }

    public function generateTrackingId(Request $request): string
    {
        $elements = [
            microtime(true),
            $request->getClientIp(),
            $request->getPort(),
            $request->server->get('REQUEST_TIME_FLOAT'),
        ];

        try {
            $elements[] = random_bytes(22);
        } catch (Exception $e) {
            $elements[] = microtime(true);
        }

        $trackingId = substr(sha1(implode(':', $elements), true), 0, 14);
        return $trackingId . $this->trackingIdChecksum($trackingId);
    }

    public function validTrackingId(string $trackingId): bool
    {
        $userId = substr($trackingId, 0, 14);
        $checksum = substr($trackingId, 14, 16);
        return $this->trackingIdChecksum($userId) === $checksum;
    }

    private function trackingIdChecksum(string $trackingId): string
    {
        return substr(sha1($trackingId . $this->trackingSecret, true), 0, 2);
    }
}
