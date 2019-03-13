<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Data;

use Adshares\Share\Url;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

final class ReCaptchaDataProvider extends AbstractDataProvider
{
    private const NAME = 'rec';

    private const NO_SCORE = -1.0;

    /** @var string */
    private $siteKey;

    /** @var string */
    private $secretKey;

    public function __construct(RouterInterface $router, Connection $connection, LoggerInterface $logger)
    {
        $this->siteKey = (string)getenv('RECAPTCHA_SITE_KEY');
        $this->secretKey = (string)getenv('RECAPTCHA_SECRET_KEY');
        parent::__construct($router, $connection, $logger);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getPageUrl(string $trackingId, Request $request): Url
    {
        return $this->generatePixelUrl($trackingId, 'html');
    }

    public function register(string $trackingId, Request $request): Response
    {
        $this->logRequest($trackingId, $request);

        if ($request->getRequestFormat() === 'gif') {
            $this->saveScore($trackingId, $request);
            $response = self::createImageResponse();
        } else {
            $response = self::createHtmlResponse($this->getSiteScript($trackingId));
        }

        return $response;
    }

    public function getHumanScore(string $trackingId): float
    {
        try {
            $score = $this->connection->fetchColumn(
                'SELECT score FROM rec_score WHERE tracking_id = ? AND success = 1 ORDER BY date DESC',
                [$trackingId]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
            $score = false;
        }

        $this->logger->debug(sprintf('reCaptcha score: %s -> %s', $trackingId, $score));

        return $score === false ? self::NO_SCORE : (float)$score;
    }

    private function saveScore(string $trackingId, Request $request): void
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $payload = [
            'secret' => $this->secretKey,
            'response' => $request->get('token'),
        ];

        try {
            $response = self::httpPost($url, $payload);
        } catch (RuntimeException $e) {
            $this->logger->debug($e->getMessage(), $payload);

            return;
        }

        $this->logger->debug(sprintf('reCaptcha score response: %s -> %s', $trackingId, $response));

        $data = json_decode($response, true);
        $score = self::NO_SCORE;
        $success = false;
        if (isset($data['success']) && $data['success']) {
            $score = $data['score'];
            $success = true;
        }

        try {
            $this->connection->insert(
                "{$this->getName()}_score",
                [
                    'tracking_id' => $trackingId,
                    'success' => (int)$success,
                    'score' => $score,
                    'data' => json_encode($data),
                ]
            );
        } catch (DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    private function getSiteScript(string $trackingId): string
    {
        return <<<SCRIPT
<script src="https://www.google.com/recaptcha/api.js?render={$this->siteKey}"></script>
<script>
  grecaptcha.ready(function() {
      grecaptcha.execute('{$this->siteKey}', {action: 'pixel'}).then(function(token) {
        var img = document.createElement('img');
        img.src = '{$this->generatePixelUrl($trackingId)}?token=' + token;
        document.body.appendChild(img);
      });
  });
</script>
SCRIPT;
    }
}
