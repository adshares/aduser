<?php
declare(strict_types = 1);

namespace Adshares\Aduser\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function getenv;
use function implode;
use function is_array;
use function json_encode;
use function preg_replace;
use function str_replace;
use function strpos;
use function strtoupper;

class InfoController extends AbstractController
{
    public function index(): Response
    {
        $logo = '<img src="/logo.svg" width="100" alt="'.getenv('APP_NAME').'" />';
        $page = self::createHtmlPage($logo);

        return new Response($page);
    }

    public function info(Request $request): Response
    {
        srand(crc32($request->getClientIp() . date('-d-m-Y-h')));
        $info = [
            'module' => 'aduser',
            'name' => getenv('APP_NAME'),
            'version' => getenv('APP_VERSION'),
            'pixelUrl' => str_replace(
                ['_:', ':_', '.html', $request->getHost()],
                ['{', '}', '.{format}', self::getRandomDomain($request)],
                $this->generateUrl(
                    'pixel_register',
                    [
                        'slug' => self::generateRandomString(),
                        'adserver' => '_:adserver:_',
                        'user' => '_:user:_',
                        'nonce' => '_:nonce:_',
                        '_format' => 'html',
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ),
            'supportedFormats' => ['gif', 'html', 'htm'],
            'privacyUrl' => $this->generateUrl('privacy', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        return new Response(
            $request->getRequestFormat() === 'txt' ? self::formatTxt($info) : self::formatJson($info)
        );
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
                $value = '"'.$value.'"';
            }
            $response .= sprintf("%s=%s\n", $key, $value);
        }

        return $response;
    }

    private static function generateRandomString($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_:.-';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    private static function getRandomDomain(Request $request)
    {
        $available = array_filter(explode(',', getenv('ADUSER_DOMAINS')));
        if (empty($available)) {
            return $request->getHost();
        }

        return $available[rand(0, count($available) - 1)];
    }

    private static function formatJson(array $data): string
    {
        return json_encode($data);
    }

    public function privacy(): Response
    {
        return new Response('<h1>Privacy</h1>');
    }

    private static function createHtmlPage($content)
    {
        $page = '<!DOCTYPE html>';
        $page .= '<html lang="en">';
        $page .= '<head>';
        $page .= '<meta charset="utf-8">';
        $page .= '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">';
        $page .= '<meta http-equiv="X-UA-Compatible" content="IE=edge">';
        $page .= '<title>'.getenv('APP_NAME').'</title>';
        $page .= '<link rel="stylesheet" href="/styles.css?ver=1">';
        $page .= '<link rel="icon" type="image/png" href="/favicon.png" />';
        $page .= '</head>';
        $page .= '<body>';
        $page .= '<div>'.$content.'</div>';
        $page .= '<footer><small> v'.getenv('APP_VERSION').'</small></footer>';
        $page .= '</body>';
        $page .= '</html>';

        return $page;
    }
}
