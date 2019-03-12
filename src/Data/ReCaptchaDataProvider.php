<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Data;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

final class ReCaptchaDataProvider extends AbstractDataProvider
{
    /**
     * @var string
     */
    private $siteKey;

    /**
     * @var string
     */
    private $secretKey;

    /**
     * @var float
     */
    private $defaultScore;

    public function __construct(RouterInterface $router, Connection $connection, LoggerInterface $logger)
    {
        $this->siteKey = getenv('RECAPTCHA_SITE_KEY');
        $this->secretKey = getenv('RECAPTCHA_SECRET_KEY');
        $this->defaultScore = getenv('ADUSER_DEFAULT_HUMAN_SCORE');
        parent::__construct($router, $connection, $logger);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'rec';
    }

    /**
     * @param string $trackingId
     * @param Request $request
     * @return string|null
     */
    public function getPageUrl(string $trackingId, Request $request): ?string
    {
        return $this->generatePixelUrl($trackingId, 'html');
    }

    /**
     * @param string $trackingId
     * @param Request $request
     * @return Response
     */
    public function register(string $trackingId, Request $request): Response
    {
        // log request
        $this->logRequest($trackingId, $request);

        // handle data
        if ($request->getRequestFormat() === 'gif') {
            $this->saveScore($trackingId, $request);
            $response = $this->createImageResponse();
        } else {
            $response = $this->createHtmlResponse($this->getSiteScript($trackingId));
        }

        // render
        return $response;
    }

    /**
     * @param string $trackingId
     * @param Request $request
     */
    private function saveScore(string $trackingId, Request $request)
    {
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $payload = [
            'secret' => $this->secretKey,
            'response' => $request->get('token'),
        ];
        $response = self::httpPost($url, $payload);
        $this->logger->debug(sprintf('reCaptcha score response: %s', $response));
        if ($response === false) {
            return;
        }

        $data = json_decode($response, true);
        $score = $this->defaultScore;
        $success = false;
        if (isset($data['success']) && $data['success']) {
            $score = $data['score'];
            $success = true;
        }

        try {
            $this->connection->insert("{$this->getName()}_score", [
                'tracking_id' => $trackingId,
                'success' => (int)$success,
                'score' => $score,
                'data' => json_encode($data),
            ]);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * @param string $trackingId
     * @return string
     */
    private function getSiteScript(string $trackingId): string
    {
        $url = $this->generatePixelUrl($trackingId);

        return "<script src=\"https://www.google.com/recaptcha/api.js?render={$this->siteKey}\"></script>
<script>
  grecaptcha.ready(function() {
      grecaptcha.execute('{$this->siteKey}', {action: 'pixel'}).then(function(token) {
        var img = document.createElement('img');
        img.src = '{$url}?token=' + token;
        document.body.appendChild(img);
      });
  });
</script>";
    }
}
