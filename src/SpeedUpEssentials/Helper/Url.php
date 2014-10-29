<?php

namespace SpeedUpEssentials\Helper;

class Url {

    private static $staticDomain;
    private static $baseUri;

    public static function getStaticDomain() {
        return self::$staticDomain;
    }

    public static function getBaseUri() {
        return self::$baseUri;
    }

    public static function setStaticDomain($staticDomain) {
        self::$staticDomain = $staticDomain;
    }

    public static function setBaseUri($baseUri) {
        self::$baseUri = $baseUri;
    }

    public static function normalizeUrl($url) {

        if (substr($url, 0, 5) != 'data:' && substr($url, 0, 2) != '//' && !preg_match('#^https?://#', $url)) {
            if ($url['0'] == '/') {
                $url = '//' . self::$staticDomain . $url;
            } else {
                $url = '//' . self::$staticDomain . self::$baseUri . $url;
            }
        }
        return preg_replace('#^https?://#', '//', $url);
    }

}
