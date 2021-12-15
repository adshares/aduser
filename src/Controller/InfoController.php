<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Gitoku;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class InfoController extends AbstractController
{
    private string $aduserDomains = '';

    /**
     * @required
     */
    public function setAduserDomains(string $aduserDomains): self
    {
        $this->aduserDomains = $aduserDomains;
        return $this;
    }

    /**
     * @Route("/", name="index")
     */
    public function index(): Response
    {
        return $this->redirectToRoute('info');
    }

    /**
     * @Route("/info.{_format}",
     *     name="info",
     *     methods={"GET"},
     *     defaults={"_format": "json"},
     *     requirements={"_format": "json|txt"}
     * )
     */
    public function info(string $appName, string $appVersion, Request $request): Response
    {
        srand(crc32($request->getClientIp() . date('-d-m-Y-h')));
        $info = [
            'module' => 'aduser',
            'name' => $appName,
            'version' => $appVersion,
            'pixelUrl' => str_replace(
                ['_:', ':_', $request->getHost()],
                ['{', '}', $this->getRandomDomain($request)],
                'https:' . $this->generateUrl(
                    'pixel_register',
                    [
                        'slug' => self::generateRandomString(),
                        'adserver' => '_:adserver:_',
                        'tracking' => '_:tracking:_',
                        'nonce' => '_:nonce:_',
                    ],
                    UrlGeneratorInterface::NETWORK_PATH
                )
            ),
        ];

        return new Response(
            $request->getRequestFormat() === 'txt' ? self::formatTxt($info) : self::formatJson($info)
        );
    }

    /**
     * @Route("/panel.html", name="panel")
     */
    public function panel(Request $request): Response
    {
        if (null !== $qs = $request->getQueryString()) {
            $qs = '?' . $qs;
        }
        return $this->redirect(Gitoku::GITOKU_URL . $request->getPathInfo() . $qs);
    }

    private static function generateRandomString($length = 8): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function getRandomDomain(Request $request): string
    {
        $available = array_filter(explode(',', $this->aduserDomains));
        if (empty($available)) {
            return $request->getHost();
        }
        return $available[rand(0, count($available) - 1)];
    }

    private static function formatJson(array $data): string
    {
        return json_encode($data);
    }

    private static function formatTxt(array $data): string
    {
        $response = '';
        foreach ($data as $key => $value) {
            $key = strtoupper(preg_replace('([A-Z])', '_$0', $key));
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            if (strpos($value, ' ') !== false) {
                $value = '"' . $value . '"';
            }
            $response .= sprintf("%s=%s\n", $key, $value);
        }
        return $response;
    }
}
