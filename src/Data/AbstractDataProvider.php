<?php
/**
 * Created by PhpStorm.
 * User: maciek
 * Date: 08.03.2019
 * Time: 13:07
 */

namespace Adshares\Aduser\Data;


use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractDataProvider implements DataProviderInterface
{
    public function getRedirectUrl(string $trackingId, Request $request): ?string
    {
        return null;
    }

    public function getImageUrl(string $trackingId, Request $request): ?string
    {
        return null;
    }

    public function getPageUrl(string $trackingId, Request $request): ?string
    {
        return null;
    }

    public function register(Request $request, Connection $connection): Response
    {
        throw new NotFoundHttpException(sprinf('Provider "%s" does not support registration',
            $request->get('provider')));
    }

    protected static function createImageResponse(?string $data = null)
    {
        if ($data === null) {
            $data = "R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
        }
        $response = new Response(base64_decode($data));
        $response->headers->set('Content-Type', 'image/gif');

        return $response;
    }

    protected static function createHtmlResponse(?string $head = null, ?string $body = null)
    {
        $content = '<!DOCTYPE html><html lang="en">';
        if ($head !== null) {
            $content .= '<head>' . $head . '</head>';
        }
        if ($body !== null) {
            $content .= '<body>' . $body . '</body>';
        }
        $content .= '</html>';

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/html; charset=UTF-8');

        return $response;
    }
}