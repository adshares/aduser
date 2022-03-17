<?php

/**
 * Copyright (c) 2018-2022 Adshares sp. z o.o.
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

use App\Utils\IdGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

final class Fingerprint
{
    private RouterInterface $router;

    private LoggerInterface $logger;

    public function __construct(RouterInterface $router, LoggerInterface $logger)
    {
        $this->router = $router;
        $this->logger = $logger;
    }

    public function getPageUrl(string $trackingId): string
    {
        return 'https:' . $this->router->generate(
            'pixel_fingerprint',
            [
                'tracking' => bin2hex($trackingId),
                'nonce' => IdGenerator::generateNonce(),
            ],
            UrlGeneratorInterface::NETWORK_PATH
        );
    }

    public function getRegisterCode(): string
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return <<<SCRIPT
<script src="/js/fgp2.min.js"></script>
<script>
    const fgp = function() { Fingerprint2.get(function (c) {
          const f = new FormData();
          for(var k in c) { f.append(c[k].key, ["canvas", "webgl", "plugins", "fonts"].indexOf(c[k].key) >= 0 ? Fingerprint2.x64hash128(Array.isArray(c[k].value) ? c[k].value.join("") : c[k].value, 31) : c[k].value) }
          const r = new XMLHttpRequest(); r.open("POST", window.location); r.send(f);
    })}
    if (window.requestIdleCallback) { requestIdleCallback(fgp); } else { setTimeout(fgp, 200); }
</script>
SCRIPT;
        // phpcs:enable
    }

    public function getHash(string $trackingId, Request $request): ?string
    {
        $country = $request->headers->get('cf-ipcountry', 'n/a');
        $ip = mb_strtolower($country) === 't1' ? $country : $request->getClientIp();

        $data = [
            'last_ip' => $ip,
            'user_agent' => self::getRequestField($request, 'user-agent', 'string', 256),
            'accept' => self::getRequestField($request, 'accept', 'string', 256),
            'accept_encoding' => self::getRequestField($request, 'accept-encoding', 'string', 256),
            'accept_language' => self::getRequestField($request, 'accept-language', 'string', 256),
            'language' => self::getRequestField($request, 'language', 'string', 8),
            'color_depth' => self::getRequestField($request, 'colorDepth', 'int'),
            'device_memory' => self::getRequestField($request, 'deviceMemory', 'int'),
            'hardware_concurrency' => self::getRequestField($request, 'hardwareConcurrency', 'int'),
            'screen_resolution' => self::getRequestField($request, 'screenResolution', 'string', 16),
            'available_screen_resolution' => self::getRequestField($request, 'availableScreenResolution', 'string', 16),
            'timezone_offset' => self::getRequestField($request, 'timezoneOffset', 'int'),
            'timezone' => self::getRequestField($request, 'timezone', 'string', 11),
            'session_storage' => self::getRequestField($request, 'sessionStorage', 'bool'),
            'local_storage' => self::getRequestField($request, 'localStorage', 'bool'),
            'indexed_db' => self::getRequestField($request, 'indexedDb', 'bool'),
            'add_behavior' => self::getRequestField($request, 'addBehavior', 'bool'),
            'open_database' => self::getRequestField($request, 'openDatabase', 'bool'),
            'cpu_class' => self::getRequestField($request, 'cpuClass', 'string', 16),
            'platform' => self::getRequestField($request, 'platform', 'string', 64),
            'plugins' => self::getRequestField($request, 'plugins', 'string', 32),
            'canvas' => self::getRequestField($request, 'canvas', 'string', 32),
            'webgl' => self::getRequestField($request, 'webgl', 'string', 32),
            'webgl_vendor_and_renderer' => self::getRequestField($request, 'webglVendorAndRenderer', 'string', 256),
            'ad_block' => self::getRequestField($request, 'adBlock', 'bool'),
            'has_lied_languages' => self::getRequestField($request, 'hasLiedLanguages', 'bool'),
            'has_lied_resolution' => self::getRequestField($request, 'hasLiedResolution', 'bool'),
            'has_lied_os' => self::getRequestField($request, 'hasLiedOs', 'bool'),
            'has_lied_browser' => self::getRequestField($request, 'hasLiedBrowser', 'bool'),
            'touch_support' => self::getRequestField($request, 'touchSupport', 'string', 16),
            'fonts' => self::getRequestField($request, 'fonts', 'string', 32),
            'audio' => self::getRequestField($request, 'audio', 'string', 32),
        ];

        if (empty(array_filter($data))) {
            $this->logger->debug(sprintf('No fingerprint data for %s', bin2hex($trackingId)));

            return null;
        }

        $hash = md5(implode('', $data), true);

        $this->logger->debug(sprintf('Fingerprint for %s: %s', bin2hex($trackingId), bin2hex($hash)), $data);

        return $hash;
    }

    private static function getRequestField(Request $request, string $key, string $type = 'string', ?int $length = null)
    {
        $value = $request->request->get($key);
        if ($value === null) {
            $value = $request->headers->get($key);
        }
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'bool':
                $value = (int)in_array($value, [true, 1, '1', 'true', 'on'], true);
                break;
            case 'int':
                $value = (int)$value;
                break;
            case 'float':
                $value = (float)$value;
                break;
            default:
                $value = mb_substr((string)$value, 0, $length);
                break;
        }
        return $value;
    }
}
