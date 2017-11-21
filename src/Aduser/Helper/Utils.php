<?php
namespace Aduser\Helper;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

class Utils
{

    public static function UrlSafeBase64Encode($string)
    {
        return str_replace([
            '/',
            '+',
            '='
        ], [
            '_',
            '-',
            ''
        ], base64_encode($string));
    }

    public static function UrlSafeBase64Decode($string)
    {
        return base64_decode(str_replace([
            '_',
            '-'
        ], [
            '/',
            '+'
        ], $string));
    }
    
    public static function getRawTrackingId($encodedId)
    {
        if (! $encodedId)
            return "";
            $input = Utils::UrlSafeBase64Decode($encodedId);
            return bin2hex(substr($input, 0, 16));
    }
    
    /**
     *
     * @param string $secret
     * @return string
     */
    public static function createTrackingId($secret)
    {
        $input = [];
        $input[] = microtime();
        $input[] = $_SERVER['REMOTE_ADDR'] ?? mt_rand();
        $input[] = $_SERVER['REMOTE_PORT'] ?? mt_rand();
        $input[] = $_SERVER['REQUEST_TIME_FLOAT'] ?? mt_rand();
        $input[] = is_callable('random_bytes') ? random_bytes(22) : openssl_random_pseudo_bytes(22);
        
        $id = substr(sha1(implode(':', $input), true), 0, 16);
        $checksum = substr(sha1($id . $secret, true), 0, 6);
        
        return Utils::UrlSafeBase64Encode($id . $checksum);
    }
    
    public static function validTrackingId($input, $secret)
    {
        if (! is_string($input)) {
            return false;
        }
        $input = Utils::UrlSafeBase64Decode($input);
        $id = substr($input, 0, 16);
        $checksum = substr($input, 16);
        return substr(sha1($id . $secret, true), 0, 6) == $checksum;
    }
    
    public function attachTrackingCookie($secret, Request $request, Response $response, $contentSha1, \DateTime $contentModified)
    {
        $tid = $request->cookies->get('tid');
        if (! Utils::validTrackingId($tid, $secret)) {
            $etags = $request->getETags();
            if (isset($etags[0])) {
                $tag = str_replace('"', '', $etags[0]);
                $tid = self::decodeEtag($tag);
            }
            if (! Utils::validTrackingId($tid, $secret)) {
                $tid = Utils::createTrackingId($secret);
            }
        }
        $response->headers->setCookie(new Cookie('tid', $tid, new \DateTime('+ 1 month'), '/', $request->getHttpHost()));
        $response->headers->set('P3P', 'CP="CAO PSA OUR"'); // IE needs this, not sure about meaning of this header
        
        
        //         $response->setVary("Origin");
        $response->setCache(array(
            'etag' => self::generateEtag($tid, $contentSha1),
            'last_modified' => $contentModified,
            'max_age' => 0,
            'private' => true
        ));
        $response->headers->addCacheControlDirective("no-transform");
        return $tid;
    }
    
    private static function generateEtag($tid, $contentSha1)
    {
        $sha1 = pack('H*', $contentSha1);
        return Utils::UrlSafeBase64Encode(substr($sha1, 0, 6) . strrev(Utils::UrlSafeBase64Decode($tid)));
    }
    
    private static function decodeEtag($etag)
    {
        $etag = str_replace('"', '', $etag);
        return Utils::UrlSafeBase64Encode(strrev(substr(Utils::UrlSafeBase64Decode($etag), 6)));
    }
}