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
        return <<<SCRIPT
<script>
    import('/js/fp.min.js').then(fp => fp.load()).then(fp => fp.get()).then(d => {
        const f = new FormData(); f.append('visitorId', d.visitorId)
        const r = new XMLHttpRequest(); r.open('POST', window.location); r.send(f);
    })
</script>
SCRIPT;
    }

    public function getHash(string $trackingId, Request $request): ?string
    {
        $country = $request->headers->get('cf-ipcountry', 'n/a');
        $ip = mb_strtolower($country) === 't1' ? $country : $request->getClientIp();
        $visitorId = $request->get('visitorId');
        if (empty($visitorId)) {
            $this->logger->debug(sprintf('No fingerprint data for %s', bin2hex($trackingId)));
            return null;
        }
        $hash = md5(sprintf('%s/%s', $visitorId, $ip), true);
        $this->logger->debug(
            sprintf('Fingerprint for %s: %s', bin2hex($trackingId), bin2hex($hash)),
            $request->request->all()
        );
        return $hash;
    }
}
