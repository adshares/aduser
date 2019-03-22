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
        return new Response('<h1>' . getenv('ADUSER_NAME') . ' v' . getenv('ADUSER_VERSION') . '</h1>');
    }

    public function info(Request $request): Response
    {
        $info = [
            'module' => 'aduser',
            'name' => getenv('APP_NAME'),
            'version' => getenv('APP_VERSION'),
            'pixelUrl' => str_replace(
                ['_:', ':_', '.html'],
                ['{', '}', '.{format}'],
                $this->generateUrl(
                    'pixel_register',
                    [
                        'adserver' => '_:adserver:_',
                        'user' => '_:user:_',
                        'nonce' => '_:nonce:_',
                        '_format' => 'html',
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            ),
            'supportedFormats' => ['gif', 'html'],
            'privacyUrl' => $this->generateUrl('privacy', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ];

        return new Response(
            $request->getRequestFormat() === 'txt'
                ? self::formatTxt($info)
                : self::formatJson($info)
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
                $value = '"' . $value . '"';
            }
            $response .= sprintf("%s=%s\n", $key, $value);
        }

        return $response;
    }

    private static function formatJson(array $data): string
    {
        return json_encode($data);
    }

    public function privacy(): Response
    {
        return new Response('<h1>Privacy</h1>');
    }
}
