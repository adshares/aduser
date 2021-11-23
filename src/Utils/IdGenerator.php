<?php declare(strict_types = 1);

namespace App\Utils;

use Exception;
use Symfony\Component\HttpFoundation\Request;

final class IdGenerator
{
    public static function generateNonce($length = 8): string
    {
        try {
            return substr(sha1(random_bytes(256)), 0, $length);
        } catch (Exception $e) {
            return '';
        }
    }

    public static function generateTrackingId(Request $request): string
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

        return $trackingId . self::trackingIdChecksum($trackingId);
    }

    public static function validTrackingId(string $trackingId): bool
    {
        $userId = substr($trackingId, 0, 14);
        $checksum = substr($trackingId, 14, 16);

        return self::trackingIdChecksum($userId) === $checksum;
    }

    private static function trackingIdChecksum(string $trackingId): string
    {
        return substr(sha1($trackingId . $_ENV['ADUSER_TRACKING_SECRET'], true), 0, 2);
    }
}
