<?php
namespace Aduser\Helper;


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

}