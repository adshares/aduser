<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class InfoController extends AbstractController
{
    public function info(Request $request): Response
    {
        srand(crc32($request->getClientIp() . date('-d-m-Y-h')));
        $info = [
            'module' => 'aduser',
            'name' => $_ENV['APP_NAME'],
            'version' => $_ENV['APP_VERSION'],
            'pixelUrl' => str_replace(
                ['_:', ':_', $request->getHost()],
                ['{', '}', self::getRandomDomain($request)],
                $this->generateUrl(
                    'pixel_register',
                    [
                        'slug' => self::generateRandomString(),
                        'adserver' => '_:adserver:_',
                        'tracking' => '_:tracking:_',
                        'nonce' => '_:nonce:_',
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ),
            'privacyUrl' => $this->generateUrl('privacy', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        switch ($request->getRequestFormat()) {
            case 'html':
                $content = $this->renderView('app/info.html.twig', ['items' => self::formatHtml($info)]);
                break;
            case 'txt':
                $content = self::formatTxt($info);
                break;
            default:
                $content = self::formatJson($info);
        }

        return new Response($content);
    }

    private static function generateRandomString($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    private static function getRandomDomain(Request $request)
    {
        $available = array_filter(explode(',', (string)$_ENV['ADUSER_DOMAINS']));
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

    private static function formatHtml(array $data): array
    {
        $response = [];
        foreach ($data as $key => $value) {
            $key = strtoupper(preg_replace('([A-Z])', '_$0', $key));
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            $response[$key] = $value;
        }

        return $response;
    }

    public function privacy(): Response
    {
        return $this->render('app/privacy.html.twig');
    }

    private static function createHtmlPage($content)
    {
        $page = '<!DOCTYPE html>';
        $page .= '<html lang="en">';
        $page .= '<head>';
        $page .= '<meta charset="utf-8">';
        $page .= '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
        $page .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
        $page .= '<title>' . $_ENV['APP_NAME'] . '</title>';
        $page .= '<link rel="stylesheet" href="/styles.css?ver=1">';
        $page .= '<link rel="icon" type="image/png" href="/favicon.png" />';
        $page .= '</head>';
        $page .= '<body>';
        $page .= '<div>' . $content . '</div>';
        $page .= '<footer><small> v' . $_ENV['APP_VERSION'] . '</small></footer>';
        $page .= '</body>';
        $page .= '</html>';

        return $page;
    }
}
