<?php

declare(strict_types=1);

namespace App\Service;

use App\Utils\IdGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

final class ReCaptcha
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** @var string */
    private $siteKey;

    /** @var string */
    private $secretKey;

    /** @var RouterInterface */
    private $router;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(RouterInterface $router, LoggerInterface $logger)
    {
        $this->siteKey = (string)$_ENV['RECAPTCHA_SITE_KEY'];
        $this->secretKey = (string)$_ENV['RECAPTCHA_SECRET_KEY'];
        $this->router = $router;
        $this->logger = $logger;
    }

    public function getPageUrl(string $trackingId): ?string
    {
        if (empty($this->siteKey)) {
            return null;
        }

        return $this->router->generate(
            'pixel_recaptcha',
            [
                'tracking' => bin2hex($trackingId),
                'nonce' => IdGenerator::generateNonce(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    public function getRegisterCode(): ?string
    {
        if (empty($this->siteKey)) {
            return null;
        }

        return <<<SCRIPT
<script src="https://www.recaptcha.net/recaptcha/api.js?render={$this->siteKey}"></script>
<script>
  grecaptcha.ready(function() {
      grecaptcha.execute('{$this->siteKey}', {action: 'pixel'}).then(function(token) {
          const f = new FormData(); f.append('token', token);
          const r = new XMLHttpRequest(); r.open("POST", window.location); r.send(f);
      });
  });
</script>
SCRIPT;
    }

    public function getHumanScore(string $trackingId, Request $request): ?float
    {
        $token = $request->get('token');
        if (empty($token)) {
            $this->logger->debug(sprintf('No reCaptcha token for %s', bin2hex($trackingId)));

            return null;
        }

        $payload = [
            'secret' => $this->secretKey,
            'response' => $token,
        ];

        try {
            $client = HttpClient::create();
            $response = $client->request('POST', self::VERIFY_URL, ['body' => $payload])->toArray();
        } catch (ExceptionInterface $e) {
            $this->logger->warning($e->getMessage(), $payload);

            return null;
        }

        $score = null;
        if (isset($response['success']) && $response['success']) {
            $score = (float)$response['score'];
        }

        $this->logger->debug(sprintf('reCaptcha score for %s: %f', bin2hex($trackingId), $score), $response);

        return $score;
    }
}
